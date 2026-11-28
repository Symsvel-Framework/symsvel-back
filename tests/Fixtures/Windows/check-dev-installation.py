"""Exercise the official CLI, real repositories and loopback HTTP Setup on Windows."""
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

if os.name != "nt":
    raise RuntimeError("Native Windows required")

cli = Path(sys.argv[1]).resolve(strict=True)
workspace = Path(sys.argv[2]).resolve()
workspace.mkdir(parents=True, exist_ok=False)
env = os.environ.copy()
token = env.pop("SYMSVEL_READ_TOKEN", "")
if token:
    env["COMPOSER_AUTH"] = json.dumps({"github-oauth": {"github.com": token}})
# A new project must not rely on a machine-wide Satis repository or Git credentials.
env["COMPOSER_HOME"] = str(workspace.parent / "symsvel-ci-composer-home")
env["GIT_TERMINAL_PROMPT"] = "0"
env["COMPOSER_NO_INTERACTION"] = "1"
log = workspace.parent / "symsvel-windows-install.stdout.log"
error_log = workspace.parent / "symsvel-windows-install.stderr.log"
password = "Zeko!8" + secrets.token_hex(12)
print("::add-mask::" + password, flush=True)
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
opener.addheaders = [("Accept-Language", "fr-FR,fr;q=0.9")]
proc = None

def read_log():
    return log.read_text(encoding="utf-8", errors="replace") if log.exists() else ""

def request(url, fields=None, timeout=900):
    body = urllib.parse.urlencode(fields).encode() if fields is not None else None
    with opener.open(url, data=body, timeout=timeout) as response:
        return response.geturl(), response.read().decode("utf-8", errors="replace")

def csrf(page):
    match = re.search(r'name="_token" value="([a-f0-9]{64})"', page)
    if not match:
        raise RuntimeError("Setup CSRF field missing")
    return match.group(1)

try:
    subprocess.run(["php", str(cli), "doctor"], cwd=workspace, env=env, check=True, timeout=60)
    with log.open("wb") as output, error_log.open("wb") as errors:
        proc = subprocess.Popen(
            ["php", str(cli), "create-project", "--components=all", "--dev"],
            cwd=workspace, env=env, stdin=subprocess.DEVNULL,
            stdout=output, stderr=errors,
        )
        deadline = time.monotonic() + 1200
        url = None
        while time.monotonic() < deadline:
            if proc.poll() is not None:
                raise RuntimeError(f"CLI exited before Setup: {proc.returncode}")
            match = re.search(r"http://127\.0\.0\.1:\d+/setup\b", read_log())
            if match:
                url = match.group(0)
                try:
                    _, page = request(url, timeout=3)
                    csrf(page)
                    break
                except (OSError, urllib.error.URLError):
                    pass
            time.sleep(2)
        else:
            raise RuntimeError("CLI did not open the Setup wizard in time")
        print("Real CLI bootstrap and Setup HTTP available", flush=True)
        origin = url.removesuffix("/setup")
        admin_url, admin = request(url, {
            "_token": csrf(page),
            "project_name": "SymSvel Windows CT",
            "local_domain": "windows-ct.localhost",
            "db_driver": "sqlite",
            "db_sqlite_path": "var/app.sqlite",
            "origin_back": origin,
            "origin_bo": "http://bo.windows-ct.localhost",
            "origin_app": "http://app.windows-ct.localhost",
            "origin_front": "http://front.windows-ct.localhost",
        })
        if not admin_url.endswith("/setup/admin"):
            raise RuntimeError("SQLite migrations did not advance to administrator phase")
        print("SQLite migrations completed", flush=True)
        _, done = request(admin_url, {
            "_token": csrf(admin),
            "setup_action": "provision",
            "admin_email": "windows-ct@example.invalid",
            "admin_password": password,
            "admin_password_confirmation": password,
        })
        state = json.loads((workspace / ".symsvel/setup.json").read_text())
        if state.get("status") != "completed":
            raise RuntimeError("Setup did not complete")
        for path in ["backend/var/app.sqlite", "bo/build/index.html", "app/build/index.html",
                     "front/build/index.html", "dev-doc/site/index.html"]:
            if not (workspace / path).is_file():
                raise RuntimeError(f"Expected artifact missing: {path}")
        channel = json.loads((workspace / "backend/.symsvel/project.json").read_text())
        if channel.get("channel") != "dev":
            raise RuntimeError("Project channel was not preserved")
        _, health = request(origin + "/api/health", timeout=30)
        json.loads(health)
        print("Real native Windows installation: SQLite, administrator, BO/App/Front, docs and health OK", flush=True)
except Exception:
    # Logs never include a full environment or the submitted form. Redact
    # disposable credentials and token before printing diagnostic tails.
    for label, path in (("stdout", log), ("stderr", error_log)):
        diagnostic = path.read_text(encoding="utf-8", errors="replace")[-18000:] if path.is_file() else ""
        for value in (password, token):
            if value:
                diagnostic = diagnostic.replace(value, "[redacted]")
        print(f"--- CLI {label} ---\n{diagnostic}", flush=True)
    # Private Setup log may contain a child-command diagnostic, not form payloads.
    setup_log = workspace / ".symsvel/setup-error.log"
    if setup_log.is_file():
        diagnostic = setup_log.read_text(encoding="utf-8", errors="replace")[-8000:]
        for value in (password, token):
            if value:
                diagnostic = diagnostic.replace(value, "[redacted]")
        print(diagnostic, flush=True)
    raise
finally:
    if proc is not None and proc.poll() is None:
        subprocess.run(["taskkill", "/PID", str(proc.pid), "/T", "/F"],
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, check=False)
