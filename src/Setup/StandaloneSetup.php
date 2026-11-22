<?php

declare(strict_types=1);

namespace App\Setup;

use SymSvel\Tooling\Installation\SetupState;

/**
 * Bootstrap HTTP independent from Symfony's Kernel and services.
 * Composer autoload and CLI tooling are available after composer install.
 * This file is deleted on successful production setup; setup.json remains the lock.
 */
final class StandaloneSetup
{
    private const PATHS = ['/setup', '/setup/admin', '/prod_settings', '/prod_settings/admin'];

    public static function isSetupPath(string $path): bool
    {
        return in_array($path, self::PATHS, true);
    }

    public static function handle(string $backend, string $path): void
    {
        $production = str_starts_with($path, '/prod_settings');
        $workspace = dirname($backend);
        if (!self::isSetupPath($path)) {
            self::abort(404, 'Page introuvable.');
        }
        if (!$production && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            self::abort(403, 'Setup local : accès loopback uniquement.');
        }
        // Installation/migrations can exceed PHP-FPM's request execution limit.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['GET', 'POST'], true)) {
            header('Allow: GET, POST');
            self::abort(405, 'Méthode non autorisée.');
        }
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        try {
            $state = new SetupState();
            if ($production && $state->read($workspace) === null) {
                $state->initialize($workspace);
            }
            $status = $state->read($workspace);
            if (!in_array($status, [SetupState::PENDING, SetupState::RUNNING, SetupState::DATABASE_READY], true)) {
                self::abort(404, 'Setup indisponible ou déjà terminé.');
            }
            self::session($production);
            if ($method === 'POST' && !hash_equals((string) $_SESSION['symsvel_setup_csrf'], (string) ($_POST['_token'] ?? ''))) {
                self::abort(403, 'Requête de Setup invalide.');
            }
            $adminPage = str_ends_with($path, '/admin');
            if (!$adminPage) {
                if ($production && in_array($status, [SetupState::RUNNING, SetupState::DATABASE_READY], true)
                    && (new ProductionSetupConfig())->read($workspace) !== null) {
                    self::redirect('/prod_settings/admin');
                }
                if (!$production && $status === SetupState::DATABASE_READY) {
                    self::redirect('/setup/admin');
                }
                if ($method === 'POST') {
                    self::configure($backend, $workspace, $production, $status);
                }
                self::configurationPage($backend, $production);
                return;
            }
            if ($production && (new ProductionSetupConfig())->read($workspace) === null) {
                self::redirect('/prod_settings');
            }
            if (!$production && $status !== SetupState::DATABASE_READY) {
                self::redirect('/setup');
            }
            if ($method === 'POST') {
                self::finish($backend, $workspace, $production, $status);
            }
            self::administratorPage($production, $status);
        } catch (\Throwable $exception) {
            // Public setup must never disclose DATABASE_URL, passwords or command stderr.
            error_log('SymSvel native setup failed: '.get_class($exception));
            self::page('Installation interrompue', '<p role="alert">'.self::esc(self::safeMessage($exception)).'</p><p>Les données déjà créées sont conservées. Corrigez la cause puis reprenez le Setup.</p>', 503);
        }
    }

    private static function session(bool $production): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('SYMSVEL_SETUP');
        session_set_cookie_params([
            'lifetime' => 0, 'path' => $production ? '/prod_settings' : '/setup',
            'secure' => $production, 'httponly' => true, 'samesite' => 'Strict',
        ]);
        if (!session_start()) {
            throw new \RuntimeException('Session du Setup indisponible.');
        }
        if (!isset($_SESSION['symsvel_setup_csrf'])) {
            $_SESSION['symsvel_setup_csrf'] = bin2hex(random_bytes(32));
        }
    }

    private static function configure(string $backend, string $workspace, bool $production, string $status): never
    {
        if ($status !== SetupState::PENDING) {
            throw new \RuntimeException('Configuration déjà commencée. Reprenez la phase 2.');
        }
        $name = trim((string) ($_POST['project_name'] ?? ''));
        $domain = trim((string) ($_POST['local_domain'] ?? ''));
        $metadata = new ProjectMetadata();
        $metadata->validate($name);
        $metadata->validateLocalDomain($domain);
        $databaseUrl = (new DatabaseConnectionConfig())->buildSelectedUrl(
            (string) ($_POST['db_driver'] ?? 'postgresql'),
            (string) ($_POST['db_host'] ?? '127.0.0.1'),
            (string) ($_POST['db_port'] ?? '5432'),
            (string) ($_POST['db_name'] ?? ''),
            (string) ($_POST['db_user'] ?? ''),
            (string) ($_POST['db_password'] ?? ''),
            (string) ($_POST['db_sqlite_path'] ?? 'var/app.sqlite'),
        );
        $submitted = [];
        foreach (['back', 'bo', 'app', 'front'] as $scope) {
            $submitted[$scope] = (string) ($_POST['origin_'.$scope] ?? '');
        }
        $origins = (new ProjectUrlMapping())->normalize($submitted, $production);
        if ($production) {
            $repos = [];
            $setupConfig = new ProductionSetupConfig();
            foreach (['bo', 'app', 'front'] as $scope) {
                $repos[$scope] = $setupConfig->normalizeRepository((string) ($_POST['repo_'.$scope] ?? ''));
            }
            if ($repos['bo'] === '') {
                throw new \InvalidArgumentException('Le dépôt GitHub du BO est obligatoire en production.');
            }
            (new ProductionEnvironmentWriter())->write($backend, $databaseUrl, $domain, $origins);
            $metadata->save($workspace, $name, $domain);
            $setupConfig->save($workspace, $repos);
            (new SetupState())->transition($workspace, SetupState::PENDING, SetupState::RUNNING);
            self::redirect('/prod_settings/admin');
        }
        self::writeLocalEnvironment($backend, $databaseUrl, $domain, $origins);
        $metadata->save($workspace, $name, $domain);
        // The established Tooling command owns database creation and migrations.
        self::command($backend, ['symsvel:setup:database', '--no-interaction'], null, 'Préparation de la base');
        self::redirect('/setup/admin');
    }

    private static function writeLocalEnvironment(string $backend, string $databaseUrl, string $domain, array $origins): void
    {
        $path = $backend.'/.env.local';
        if (is_link($path) || (file_exists($path) && !is_file($path))) {
            throw new \RuntimeException('Configuration locale invalide.');
        }
        $existing = is_file($path) ? (string) file_get_contents($path) : '';
        $lines = preg_split('/\r\n|\n|\r/', $existing) ?: [];
        $secret = null;
        foreach ($lines as $line) {
            if (preg_match('/^APP_SECRET="([a-f0-9]{64})"$/D', $line, $m)) {
                $secret = $m[1];
            }
        }
        $keys = ['APP_ENV', 'APP_DEBUG', 'APP_SECRET', 'DATABASE_URL', 'SYMSVEL_CORS_ALLOW_ORIGIN',
            'SYMSVEL_BO_DOMAIN', 'SYMSVEL_BACK_ORIGIN', 'SYMSVEL_BO_ORIGIN', 'SYMSVEL_APP_ORIGIN', 'SYMSVEL_FRONT_ORIGIN'];
        $lines = array_values(array_filter($lines, static function (string $line) use ($keys): bool {
            foreach ($keys as $key) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $line) === 1) {
                    return false;
                }
            }
            return trim($line) !== '';
        }));
        $values = ['APP_ENV' => 'dev', 'APP_DEBUG' => '1',
            'APP_SECRET' => $secret ?? bin2hex(random_bytes(32)), 'DATABASE_URL' => $databaseUrl]
            + (new ProjectUrlMapping())->environment($origins, $domain);
        foreach ($values as $key => $value) {
            $lines[] = $key.'='.json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }
        $temp = tempnam($backend, '.env-setup-');
        if ($temp === false) {
            throw new \RuntimeException('Impossible de préparer la configuration locale.');
        }
        try {
            if (!chmod($temp, 0600) || file_put_contents($temp, implode("\n", $lines)."\n") === false || !rename($temp, $path)) {
                throw new \RuntimeException('Impossible de publier la configuration locale.');
            }
        } finally {
            if (is_file($temp)) {
                unlink($temp);
            }
        }
    }

    private static function finish(string $backend, string $workspace, bool $production, string $status): never
    {
        $action = (string) ($_POST['setup_action'] ?? '');
        if ($production) {
            if (!in_array($action, ['doctrine', 'dump', 'complete'], true)) {
                self::abort(400, 'Action invalide.');
            }
            if ($status === SetupState::RUNNING && $action === 'complete') {
                throw new \RuntimeException('Initialisation de la base encore nécessaire.');
            }
            if ($status === SetupState::RUNNING) {
                if ($action === 'doctrine') {
                    self::command($backend, ['symsvel:setup:database', '--no-interaction'], null, 'Migrations');
                } else {
                    $upload = $_FILES['dump_sql'] ?? null;
                    if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                        || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))
                        || strtolower((string) pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION)) !== 'sql') {
                        throw new \RuntimeException('Dump SQL texte invalide ou absent.');
                    }
                    $databaseUrl = self::configuredDatabase($backend);
                    (new ProductionDumpImporter())->import($backend, $databaseUrl, (string) $upload['tmp_name']);
                    (new SetupState())->transition($workspace, SetupState::RUNNING, SetupState::DATABASE_READY);
                }
            }
            if ($action === 'doctrine') {
                self::provision($backend);
            }
            self::command($backend, ['symsvel:admins:bootstrap:verify', '--no-interaction'], null, 'Vérification administrateur');
            $repositories = (new ProductionSetupConfig())->read($workspace) ?? [];
            $configured = array_filter($repositories, static fn (string $url): bool => $url !== '');
            $token = trim((string) ($_POST['github_token'] ?? ''));
            if ($configured !== [] && $token === '') {
                throw new \RuntimeException('Jeton GitHub Actions:write requis pour déclencher les CD des interfaces.');
            }
            $messages = (new ProductionCdDispatcher())->dispatch($repositories, $token);
            // A rejected dispatch is retryable, without recreating the database.
            // GitHub's 204 only confirms queuing, not a successful publication.
            if ($configured !== [] && (count($messages) !== count($configured)
                || array_any($messages, static fn (string $message): bool => !str_contains($message, ' : CD demandée à GitHub')))) {
                throw new \RuntimeException('Une CD GitHub a refusé le déclenchement. Le Setup reste ouvert pour une reprise.');
            }
            (new SetupState())->transition($workspace, SetupState::DATABASE_READY, SetupState::COMPLETED);
            // Remove the native setup code only from this deployed production checkout.
            // The persistent completed state also prevents reopening after a future git pull.
            if (is_file(__FILE__) && !@unlink(__FILE__)) {
                $messages[] = 'Suppression du bootstrap impossible : accès fermé par setup.json, vérifiez les permissions.';
            }
            $body = '<p>Backend initialisé. Le Setup est fermé sur ce serveur.</p><h2>Déploiement des interfaces</h2>';
            foreach ($messages as $message) {
                $body .= '<p>'.self::esc($message).'</p>';
            }
            $body .= '<p>Le déclenchement GitHub ne prouve pas encore la réussite de la publication.</p>';
            self::page('Installation production terminée', $body);
        }
        if (!in_array($action, ['provision', 'complete'], true)) {
            self::abort(400, 'Action invalide.');
        }
        if ($action === 'provision') {
            self::provision($backend);
        }
        self::command($backend, ['symsvel:setup:complete', '--no-interaction'], null, 'Finalisation du Setup');
        self::page('Installation terminée', '<p>SymSvel est installé. Vous pouvez ouvrir le Back-office.</p>');
    }

    private static function configuredDatabase(string $backend): string
    {
        $env = (string) file_get_contents($backend.'/.env.local');
        if (preg_match('/^DATABASE_URL=(.+)$/m', $env, $match) !== 1) {
            throw new \RuntimeException('DATABASE_URL absent.');
        }
        $url = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        if (!is_string($url)) {
            throw new \RuntimeException('DATABASE_URL invalide.');
        }
        return $url;
    }

    private static function provision(string $backend): void
    {
        $email = trim((string) ($_POST['admin_email'] ?? ''));
        $password = (string) ($_POST['admin_password'] ?? '');
        if (!hash_equals($password, (string) ($_POST['admin_password_confirmation'] ?? ''))) {
            throw new \InvalidArgumentException('Les mots de passe ne correspondent pas.');
        }
        (new PendingAdministrator())->validate($email, $password);
        $accepted = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr');
        $language = strtolower(explode('-', explode(',', str_replace('_', '-', $accepted))[0])[0]);
        if (preg_match('/^[a-z]{2,4}$/D', $language) !== 1) {
            $language = 'fr';
        }
        self::command($backend, ['symsvel:languages:bootstrap:stdin', '--no-interaction'],
            json_encode(['language' => $language], JSON_THROW_ON_ERROR), 'Initialisation langue');
        self::command($backend, ['symsvel:admins:bootstrap:stdin', '--no-interaction'],
            json_encode(['email' => $email, 'password' => $password, 'language' => $language], JSON_THROW_ON_ERROR),
            'Création administrateur');
    }

    private static function command(string $backend, array $args, ?string $stdin, string $label): void
    {
        $php = PHP_SAPI === 'cli-server' ? PHP_BINARY : ((string) getenv('SYMSVEL_PHP_CLI') ?: 'php');
        // Child commands can print more than a pipe buffer. A private temporary
        // output file avoids blocking FPM while we wait for their completion.
        $log = tempnam(sys_get_temp_dir(), 'symsvel-setup-command-');
        if ($log === false) {
            throw new \RuntimeException($label.' : sortie de commande indisponible.');
        }
        try {
            $process = proc_open(array_merge([$php, 'bin/console'], $args),
                [0 => ['pipe', 'r'], 1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes, $backend);
            if (!is_resource($process)) {
                throw new \RuntimeException($label.' : commande indisponible.');
            }
            if ($stdin !== null) {
                fwrite($pipes[0], $stdin);
            }
            fclose($pipes[0]);
            if (proc_close($process) !== 0) {
                error_log('SymSvel native setup command failed: '.$label.' (no sensitive output logged)');
                throw new \RuntimeException($label.' échouée. Consultez les journaux du serveur, puis reprenez le Setup.');
            }
        } finally {
            @unlink($log);
        }
    }

    private static function configurationPage(string $backend, bool $production): void
    {
        $csrf = self::esc((string) $_SESSION['symsvel_setup_csrf']);
        $scheme = $production ? 'https' : 'http';
        $domainPlaceholder = $production ? 'exemple.fr' : 'xt.localhost';
        $body = '<p>Phase 1/2 : configurez votre projet et sa base de données.</p>'
            .'<form method="post"><input type="hidden" name="_token" value="'.$csrf.'">'
            .self::input('Nom du projet', 'project_name', '', true)
            .self::input('Domaine du projet (sans protocole)', 'local_domain', $domainPlaceholder, true);
        $body .= '<fieldset><legend>Origines HTTP réelles des composants</legend><p>Les URL définissent les CORS, pas les dossiers.</p>';
        foreach (['back' => 'Back (API)', 'bo' => 'Back-office', 'app' => 'Application', 'front' => 'Front public'] as $scope => $label) {
            $body .= self::input($label, 'origin_'.$scope, '', in_array($scope, ['back', 'bo'], true), 'url', ' data-origin-scope="'.$scope.'"');
        }
        $body .= '</fieldset><fieldset><legend>Base de données</legend>'
            .'<label for="db_driver">SGBD</label><select id="db_driver" name="db_driver">'
            .'<option value="postgresql">PostgreSQL</option><option value="mysql">MySQL</option>'
            .'<option value="mariadb">MariaDB</option><option value="sqlite">SQLite</option></select>'
            .'<div id="server_fields">'
            .self::input('Hôte', 'db_host', '127.0.0.1', true)
            .self::input('Port', 'db_port', '5432', true)
            .self::input('Base', 'db_name', '', true)
            .self::input('Utilisateur', 'db_user', '', true)
            .self::input('Mot de passe BDD (facultatif)', 'db_password', '', false, 'password')
            .'</div><div id="sqlite_fields" hidden>'
            .self::input('Fichier SQLite relatif au backend', 'db_sqlite_path', 'var/app.sqlite', false)
            .'</div></fieldset>';
        if ($production) {
            $body .= '<fieldset><legend>Dépôts GitHub du projet</legend>';
            $suggested = (new ProductionSetupConfig())->suggestions($backend);
            foreach (['bo' => 'Back-office', 'app' => 'Application', 'front' => 'Front public'] as $scope => $label) {
                $body .= self::input($label, 'repo_'.$scope, (string) $suggested[$scope], $scope === 'bo', 'url');
            }
            $body .= '</fieldset>';
        }
        $body .= '<button type="submit">Continuer</button></form>';
        $body .= '<script>(()=>{const d=document.getElementById("local_domain"),driver=document.getElementById("db_driver"),ports=document.getElementById("db_port");'
            .'const origins=document.querySelectorAll("[data-origin-scope]");'
            .'origins.forEach(i=>i.addEventListener("input",()=>i.dataset.manual="1"));'
            .'function suggest(){const base=d.value.trim().toLowerCase();origins.forEach(i=>{if(i.dataset.manual)return;'
            .'const scope=i.dataset.originScope;i.value=base?"'.$scheme.'://"+(scope==="front"?"www":scope)+"."+base:""})}'
            .'d.addEventListener("input",suggest);suggest();'
            .'function db(){const sqlite=driver.value==="sqlite";document.getElementById("server_fields").hidden=sqlite;'
            .'document.getElementById("sqlite_fields").hidden=!sqlite;'
            .'document.querySelectorAll("#server_fields input").forEach(i=>i.disabled=sqlite);'
            .'ports.value=driver.value==="postgresql"?"5432":"3306"}'
            .'driver.addEventListener("change",db);db()})();</script>';
        self::page('Installation SymSvel', $body);
    }

    private static function administratorPage(bool $production, string $status): void
    {
        $csrf = self::esc((string) $_SESSION['symsvel_setup_csrf']);
        $body = '<p>Phase 2/2 : finalisation de l’installation.</p>'
            .'<form method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="'.$csrf.'">';
        if ($production) {
            $body .= '<fieldset><legend>Initialisation BDD</legend>'
                .'<label><input type="radio" name="setup_action" value="doctrine" checked>Migrations Doctrine et premier administrateur</label>'
                .'<label><input type="radio" name="setup_action" value="dump">Importer un dump SQL avec administrateur existant</label>'
                .'</fieldset>';
        } else {
            $body .= '<input type="hidden" name="setup_action" value="provision">';
        }
        $body .= '<div id="admin_fields">'
            .self::input('E-mail administrateur', 'admin_email', '', false, 'email')
            .self::input('Mot de passe administrateur', 'admin_password', '', false, 'password')
            .self::input('Confirmer le mot de passe', 'admin_password_confirmation', '', false, 'password')
            .'</div>';
        if ($production) {
            $body .= '<div id="dump_fields" hidden><label>Dump SQL texte <input type="file" name="dump_sql" accept=".sql" disabled></label></div>'
                .'<fieldset><legend>CD GitHub</legend>'
                .self::input('PAT temporaire Actions:write (non conservé)', 'github_token', '', false, 'password')
                .'<p>Les CD deploy.yml des dépôts BO, App et Front seront déclenchées sur main.</p></fieldset>';
        }
        $body .= '<button type="submit">Finaliser</button></form>';
        if ($status === SetupState::DATABASE_READY) {
            $body .= '<form method="post"><input type="hidden" name="_token" value="'.$csrf.'">'
                .'<input type="hidden" name="setup_action" value="complete">';
            if ($production) {
                $body .= self::input('PAT Actions:write pour relancer les CD', 'github_token', '', false, 'password');
            }
            $body .= '<button type="submit">Reprendre la finalisation sans réinitialiser la base</button></form>';
        }
        if ($production) {
            $body .= '<script>(()=>{const radios=document.querySelectorAll("input[name=setup_action]");'
                .'function show(){const dump=document.querySelector("input[name=setup_action]:checked").value==="dump";'
                .'document.getElementById("admin_fields").hidden=dump;document.getElementById("dump_fields").hidden=!dump;'
                .'document.querySelectorAll("#dump_fields input").forEach(i=>i.disabled=!dump)}'
                .'radios.forEach(i=>i.addEventListener("change",show));show()})();</script>';
        }
        self::page('Initialisation du projet', $body);
    }

    private static function input(string $label, string $key, string $value, bool $required,
        string $type = 'text', string $extra = ''): string
    {
        return '<label for="'.self::esc($key).'">'.self::esc($label).'</label>'
            .'<input id="'.self::esc($key).'" name="'.self::esc($key).'" type="'.self::esc($type).'"'
            .' value="'.self::esc($value).'"'.($required ? ' required' : '').$extra.'>';
    }

    private static function page(string $title, string $body, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<title>'.self::esc($title).'</title>'
            .'<style>body{font:16px system-ui;background:#f7f8fa;color:#1e2831;margin:0;padding:2rem}'
            .'main{max-width:46rem;margin:0 auto;background:#fff;border-radius:14px;padding:2rem;box-shadow:0 1px 10px #0001}'
            .'h1{margin-top:0}fieldset{border:1px solid #ccd5dc;border-radius:8px;margin:1.5rem 0;padding:1rem}'
            .'label{display:block;margin-top:1rem}input:not([type=radio]):not([type=file]),select{display:block;box-sizing:border-box;width:100%;padding:.65rem;margin-top:.3rem}'
            .'button{background:#225c99;color:white;border:0;border-radius:6px;padding:.8rem 1.3rem;cursor:pointer}'
            .'[hidden]{display:none!important}</style></head><body><main><h1>'.self::esc($title).'</h1>'.$body.'</main></body></html>';
        exit;
    }

    private static function safeMessage(\Throwable $exception): string
    {
        if ($exception instanceof \InvalidArgumentException) {
            return $exception->getMessage();
        }
        if ($exception instanceof \RuntimeException) {
            $message = $exception->getMessage();
            // Existing setup helpers use human-readable errors; avoid echoing DSNs or passwords.
            if (!preg_match('~(://|password|token|secret|postgresql://|mysql://)~i', $message)) {
                return $message;
            }
        }
        return 'Erreur de configuration ou de permissions. Consultez les journaux du serveur.';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function redirect(string $url): never
    {
        header('Location: '.$url, true, 303);
        exit;
    }

    private static function abort(int $code, string $message): never
    {
        self::page('Setup SymSvel', '<p>'.self::esc($message).'</p>', $code);
    }
}
