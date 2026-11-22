<?php

declare(strict_types=1);

namespace App\Controller;

use App\Setup\DatabaseConnectionConfig;
use App\Setup\PendingAdministrator;
use App\Setup\ProjectMetadata;
use App\Setup\LocalCorsOrigins;
use App\Setup\ProductionSetupConfig;
use App\Setup\ProductionEnvironmentWriter;
use App\Setup\ProductionDumpImporter;
use App\Setup\ProductionCdDispatcher;
use SymSvel\Tooling\Installation\SetupState;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Process;

final class SetupController extends AbstractController
{
    #[Route('/setup', name: 'symsvel_setup', methods: ['GET', 'POST'])]
    #[Route('/prod_settings', name: 'symsvel_prod_settings', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $workspace = dirname((string) $this->getParameter('kernel.project_dir'));
        $stateFile = $workspace.'/.symsvel/setup.json';
        $productionUrl = $request->attributes->get('_route') === 'symsvel_prod_settings';
        // The CLI creates local state. A fresh Git clone on the VPS does not
        // carry that unversioned state and initializes its own installation.
        if ($productionUrl && !file_exists($stateFile) && !is_link($stateFile)) {
            try {
                (new SetupState())->initialize($workspace);
            } catch (\Throwable $exception) {
                return new Response('Initialisation du Setup impossible : vérifiez les permissions du workspace.', Response::HTTP_SERVICE_UNAVAILABLE);
            }
        }
        if (is_link($workspace.'/.symsvel') || is_link($stateFile) || !is_file($stateFile)) {
            throw $this->createNotFoundException();
        }
        if (!is_readable($stateFile)) {
            return new Response('État d’installation illisible par le processus PHP. Vérifiez les permissions de .symsvel.', Response::HTTP_SERVICE_UNAVAILABLE);
        }
        try {
            $state = json_decode((string) file_get_contents($stateFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw $this->createNotFoundException();
        }
        if (($state['schema'] ?? null) !== 1 || !in_array($state['status'] ?? null, ['pending', 'running', 'database_ready'], true)) {
            throw $this->createNotFoundException();
        }
        // The local endpoint remains loopback-only. Only the first-production
        // endpoint is remotely accessible while this installation is unfinished.
        if (!$productionUrl && !in_array($request->getClientIp(), ['127.0.0.1', '::1'], true)) {
            return new Response('Installation accessible uniquement en local.', Response::HTTP_FORBIDDEN);
        }
        if (($state['status'] ?? null) === 'database_ready'
            || ($productionUrl && ($state['status'] ?? null) === 'running'
                && (new ProductionSetupConfig())->read($workspace) !== null)) {
            return $this->redirectToRoute($productionUrl ? 'symsvel_prod_settings_admin' : 'symsvel_setup_admin');
        }
        if ($request->isMethod('POST')) {
            $selectedMode = (string) $request->request->get('installation_mode', $productionUrl ? 'production' : 'local');
            if (!in_array($selectedMode, ['local', 'production'], true)) {
                return new Response('Mode d’installation invalide.', Response::HTTP_BAD_REQUEST);
            }
            if ($selectedMode !== ($productionUrl ? 'production' : 'local')) {
                return $this->redirectToRoute($selectedMode === 'production' ? 'symsvel_prod_settings' : 'symsvel_setup');
            }
            $stateDirectory = $workspace.'/.symsvel';
            $backendDirectory = (string) $this->getParameter('kernel.project_dir');
            if (!is_writable($stateDirectory) || !is_executable($stateDirectory)
                || !is_writable($backendDirectory) || !is_executable($backendDirectory)) {
                return new Response('Installation bloquée : le processus PHP ne peut pas écrire les fichiers nécessaires au setup.', Response::HTTP_SERVICE_UNAVAILABLE);
            }
            if (!$this->isCsrfTokenValid('symsvel_setup', (string) $request->request->get('_token', ''))) {
                return new Response('Requête invalide.', Response::HTTP_FORBIDDEN);
            }
            $projectName = (string) $request->request->get('project_name', '');
            $localDomain = (string) $request->request->get('local_domain', '');
            try {
                (new ProjectMetadata())->validate($projectName);
                (new ProjectMetadata())->validateLocalDomain($localDomain);
            } catch (\InvalidArgumentException $exception) {
                $this->addFlash('setup_error', $exception->getMessage());
                return $this->redirectToRoute('symsvel_setup');
            }
            try {
                $url = (new DatabaseConnectionConfig())->buildSelectedUrl(
                    (string) $request->request->get('db_driver', 'postgresql'),
                    (string) $request->request->get('db_host', ''),
                    (string) $request->request->get('db_port', ''),
                    (string) $request->request->get('db_name', ''),
                    (string) $request->request->get('db_user', ''),
                    (string) $request->request->get('db_password', ''),
                    (string) $request->request->get('db_sqlite_path', 'var/app.sqlite'),
                );
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                $this->addFlash('setup_error', $exception->getMessage());
                return $this->redirectToRoute('symsvel_setup');
            }
            $backend = (string) $this->getParameter('kernel.project_dir');
            if ($productionUrl) {
                try {
                    $repositories = [];
                    foreach (['bo', 'app', 'front'] as $component) {
                        $repositories[$component] = (string) $request->request->get('repo_'.$component, '');
                    }
                    // Validation happens before publishing the production environment.
                    foreach ($repositories as $urlToCheck) {
                        (new ProductionSetupConfig())->normalizeRepository($urlToCheck);
                    }
                    (new ProductionEnvironmentWriter())->write($backend, $url, $localDomain);
                    (new ProductionSetupConfig())->save($workspace, $repositories);
                } catch (\Throwable $exception) {
                    $this->addFlash('setup_error', 'Configuration production impossible : '.$exception->getMessage());
                    return $this->redirectToRoute('symsvel_prod_settings');
                }
            } else {
                $backend = (string) $this->getParameter('kernel.project_dir');
                $envFile = $backend.'/.env.local';
                if (is_link($envFile)) {
                    return new Response('Configuration symbolique interdite.', Response::HTTP_FORBIDDEN);
                }
                if (file_exists($envFile) && (!is_file($envFile) || !is_readable($envFile))) {
                    return new Response('Configuration existante invalide.', Response::HTTP_FORBIDDEN);
                }
                $existing = is_file($envFile) ? (string) file_get_contents($envFile) : '';
                $lines = preg_split('/\r\n|\n|\r/', $existing) ?: [];
                $lines = array_values(array_filter($lines, static fn (string $line): bool => preg_match('/^\s*(?:DATABASE_URL|SYMSVEL_CORS_ALLOW_ORIGIN|SYMSVEL_BO_DOMAIN)\s*=/', $line) !== 1));
                $lines[] = 'DATABASE_URL='.json_encode($url, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                $lines[] = 'SYMSVEL_CORS_ALLOW_ORIGIN='.json_encode((new LocalCorsOrigins())->fromDomain($localDomain), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                // This public hostname must be readable by PHP-FPM, unlike private CLI Setup metadata.
                $lines[] = 'SYMSVEL_BO_DOMAIN='.json_encode(strtolower(trim($localDomain)), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                $contents = implode("\n", $lines)."\n";
                $temporary = tempnam($backend, '.env-local-');
                if ($temporary === false) {
                    throw new \RuntimeException('Impossible de préparer la configuration.');
                }
                try {
                    // Local Setup: PHP-FPM may run under a different user than the CLI.
                    // Production .env is supplied separately by CD from secrets/.
                    if (!chmod($temporary, 0644)) {
                        throw new \RuntimeException('Impossible de rendre la configuration locale lisible par PHP-FPM.');
                    }
                    if (file_put_contents($temporary, $contents) === false || !rename($temporary, $envFile)) {
                        throw new \RuntimeException('Impossible d’enregistrer la configuration.');
                    }
                } finally {
                    if (is_file($temporary)) {
                        unlink($temporary);
                    }
                }
            }
            (new ProjectMetadata())->save($workspace, $projectName, $localDomain);
            if ($productionUrl) {
                // The production second screen decides between migrations and dump.
                if (($state['status'] ?? null) === SetupState::PENDING) {
                    (new SetupState())->transition($workspace, SetupState::PENDING, SetupState::RUNNING);
                }
                return $this->redirectToRoute('symsvel_prod_settings_admin');
            }
            // Phase 1 performs only database preparation and migrations.
            $process = new Process([PHP_BINARY, 'bin/console', 'symsvel:setup:database', '--no-interaction'], $backend);
            $process->setTimeout(null);
            $process->run();
            if (!$process->isSuccessful()) {
                // Preserve the failing command's diagnostic instead of collapsing
                // connection, database creation and migration failures into one message.
                $diagnostic = trim($process->getErrorOutput()."\\n".$process->getOutput());
                $diagnostic = $this->sanitizeSetupDiagnostic($diagnostic, $url);
                $this->addFlash('setup_error', 'Préparation de la base échouée (code '.($process->getExitCode() ?? 'inconnu').'). '.($diagnostic !== '' ? $diagnostic : 'La commande n’a fourni aucun diagnostic.'));

                return $this->redirectToRoute('symsvel_setup');
            }
            return $this->redirectToRoute('symsvel_setup_admin');
        }
        return $this->render('setup/index.html.twig', [
            'production' => $productionUrl,
            'repo_suggestions' => (new ProductionSetupConfig())->suggestions((string) $this->getParameter('kernel.project_dir')),
        ]);
    }
    /** Do not expose database credentials in local setup error messages. */
    private function sanitizeSetupDiagnostic(string $diagnostic, string $databaseUrl): string
    {
        if ($diagnostic === '') {
            return '';
        }
        // Remove the configured connection URL, including its encoded password.
        $diagnostic = str_replace($databaseUrl, '[DATABASE_URL masquée]', $diagnostic);
        // Also mask connection URLs which may have been normalized by Doctrine.
        $diagnostic = preg_replace('~(?:postgres(?:ql)?|mysql|mariadb|sqlite)://[^\\s\\x22\\x27<>]+~i', '[URL BDD masquée]', $diagnostic) ?? '';
        // Passwords may appear in PDO exception diagnostics in plain form.
        $password = parse_url($databaseUrl, PHP_URL_PASS);
        if (is_string($password) && $password !== '') {
            $decoded = rawurldecode($password);
            $diagnostic = str_replace(array_unique([$password, $decoded]), '[mot de passe masqué]', $diagnostic);
        }
        return substr($diagnostic, 0, 4000);
    }

    #[Route('/setup/admin', name: 'symsvel_setup_admin', methods: ['GET', 'POST'])]
    #[Route('/prod_settings/admin', name: 'symsvel_prod_settings_admin', methods: ['GET', 'POST'])]
    public function administrator(Request $request): Response
    {
        $productionUrl = $request->attributes->get('_route') === 'symsvel_prod_settings_admin';
        if (!$productionUrl && !in_array($request->getClientIp(), ['127.0.0.1', '::1'], true)) {
            return new Response('Installation accessible uniquement en local.', Response::HTTP_FORBIDDEN);
        }
        $backend = (string) $this->getParameter('kernel.project_dir');
        $workspace = dirname($backend);
        $stateFile = $workspace.'/.symsvel/setup.json';
        if (is_link($workspace.'/.symsvel') || is_link($stateFile) || !is_file($stateFile) || !is_readable($stateFile)) {
            throw $this->createNotFoundException();
        }
        try {
            $state = json_decode((string) file_get_contents($stateFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw $this->createNotFoundException();
        }
        if (($state['schema'] ?? null) !== 1) {
            throw $this->createNotFoundException();
        }
        if ($productionUrl) {
            if ((new ProductionSetupConfig())->read($workspace) === null) {
                return $this->redirectToRoute('symsvel_prod_settings');
            }
            if (!in_array($state['status'] ?? null, [SetupState::RUNNING, SetupState::DATABASE_READY], true)) {
                throw $this->createNotFoundException();
            }
            if ($request->isMethod('POST')) {
                return $this->finishProduction($request, $backend, $workspace, $state['status']);
            }
            return $this->render('setup/admin.html.twig', ['production' => true, 'database_ready' => $state['status'] === SetupState::DATABASE_READY]);
        }
        if (($state['status'] ?? null) !== 'database_ready') {
            return $this->redirectToRoute('symsvel_setup');
        }
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('symsvel_setup_admin', (string) $request->request->get('_token', ''))) {
                return new Response('Requête invalide.', Response::HTTP_FORBIDDEN);
            }
            $action = (string) $request->request->get('setup_action', 'provision');
            if ($action === 'provision') {
                if (!$this->provisionAdministrator($request, $backend)) {
                    return $this->redirectToRoute('symsvel_setup_admin');
                }            } elseif ($action !== 'complete') {
                return new Response('Action de setup invalide.', Response::HTTP_BAD_REQUEST);
            }
            // Completion independently verifies the administrator and is safe to retry.
            // Temporary setup diagnostic; only npm discovery and process metadata.
            @file_put_contents($workspace.'/.symsvel/npm-trace.log',
                gmdate('c').' [controller pid='.getmypid().'] npm='.var_export(getenv('SYMSVEL_SETUP_NPM'), true)
                .' PATH='.var_export(getenv('PATH'), true)
                .' php='.PHP_BINARY."\n", FILE_APPEND | LOCK_EX);
            // Explicitly forward npm discovery to the console child: the web process
            // can see these values even when its child does not inherit them.
            $environment = [];
            foreach (['SYMSVEL_SETUP_NPM', 'PATH'] as $name) {
                $value = getenv($name);
                if (is_string($value) && $value !== '') {
                    $environment[$name] = $value;
                }
            }
            $complete = new Process([PHP_BINARY, 'bin/console', 'symsvel:setup:complete', '--no-interaction'], $backend, $environment);
            $complete->setTimeout(null);
            $complete->run();
            @file_put_contents($workspace.'/.symsvel/npm-trace.log', gmdate('c').' [controller pid='.getmypid().'] complete_exit='.var_export($complete->getExitCode(), true)."\n", FILE_APPEND | LOCK_EX);
            if (!$complete->isSuccessful()) {
                // The wizard is loopback-only: expose the actual command failure so
                // an installation can be diagnosed without a manual console retry.
                $details = trim($complete->getErrorOutput()."\n".$complete->getOutput());
                $this->addFlash('setup_error', 'Finalisation impossible. Les données sont conservées. '.($details !== '' ? $details : 'Commande interrompue sans diagnostic.'));
                return $this->redirectToRoute('symsvel_setup_admin');
            }
            return new Response('Installation SymSvel terminée.', Response::HTTP_OK);
        }
        return $this->render('setup/admin.html.twig', ['production' => false]);
    }

    private function provisionAdministrator(Request $request, string $backend): bool
    {
                $email = (string) $request->request->get('admin_email', '');
                $password = (string) $request->request->get('admin_password', '');
                $confirmation = (string) $request->request->get('admin_password_confirmation', '');
                if (!hash_equals($password, $confirmation)) {
                    $this->addFlash('setup_error', 'Les mots de passe administrateur ne correspondent pas.');
                    return false;
                }
                try {
                    (new PendingAdministrator())->validate($email, $password);
                } catch (\InvalidArgumentException $exception) {
                    $this->addFlash('setup_error', $exception->getMessage());
                    return false;
                }
                // Migrations and Languages catalog are ready at this phase.
                // Browser language initializes the site's one immutable primary ISO code.
                $browserLanguage = strtolower(explode('-', str_replace('_', '-',
                    (string) ($request->getPreferredLanguage() ?? 'fr')))[0]);
                if (preg_match('/^[a-z]{2,4}$/D', $browserLanguage) !== 1) {
                    $browserLanguage = 'fr';
                }
                $siteLanguage = new Process([PHP_BINARY, 'bin/console', 'symsvel:languages:bootstrap:stdin', '--no-interaction'], $backend);
                $siteLanguage->setInput(json_encode(['language' => $browserLanguage], JSON_THROW_ON_ERROR));
                $siteLanguage->setTimeout(60);
                $siteLanguage->run();
                if (!$siteLanguage->isSuccessful()) {
                    $this->addFlash('setup_error', 'Initialisation de la langue du site impossible. Vérifiez le référentiel Languages et reprenez le Setup.');
                    return false;
                }
                $process = new Process([PHP_BINARY, 'bin/console', 'symsvel:admins:bootstrap:stdin', '--no-interaction'], $backend);
                $process->setInput(json_encode(['email' => trim($email), 'password' => $password,
                    'language' => $browserLanguage], JSON_THROW_ON_ERROR));
                $process->setTimeout(60);
                $process->run();
                if (!$process->isSuccessful()) {
                    $this->addFlash('setup_error', 'Création du premier administrateur impossible. Vérifiez le module Admins et réessayez.');
                    return false;
                }

        return true;
    }


    private function finishProduction(Request $request, string $backend, string $workspace, string $status): Response
    {
        if (!$this->isCsrfTokenValid('symsvel_setup_admin', (string) $request->request->get('_token', ''))) {
            return new Response('Requête invalide.', Response::HTTP_FORBIDDEN);
        }
        $action = (string) $request->request->get('setup_action', '');
        if (!in_array($action, ['doctrine', 'dump', 'complete'], true)) {
            return new Response('Choix d’initialisation invalide.', Response::HTTP_BAD_REQUEST);
        }
        if ($status === SetupState::RUNNING && $action === 'complete') {
            return new Response('Initialisation de la base nécessaire.', Response::HTTP_BAD_REQUEST);
        }
        try {
            if ($status === SetupState::RUNNING) {
                if ($action === 'doctrine') {
                    // Reuse exactly the local database/migrations orchestrator.
                    $process = new Process([PHP_BINARY, 'bin/console', 'symsvel:setup:database', '--no-interaction'], $backend);
                    $process->setTimeout(null);
                    $process->run();
                    if (!$process->isSuccessful()) {
                        throw new \RuntimeException('Migrations Doctrine échouées. Consultez le journal du serveur et réessayez.');
                    }
                } else {
                    $uploaded = $request->files->get('dump_sql');
                    if (!$uploaded instanceof UploadedFile || !$uploaded->isValid()) {
                        throw new \RuntimeException('Dump SQL manquant ou non transféré. Vérifiez les limites d’upload PHP.');
                    }
                    if (strtolower((string) $uploaded->getClientOriginalExtension()) !== 'sql') {
                        throw new \RuntimeException('Importez un dump au format SQL texte (.sql).');
                    }
                    $contents = (string) file_get_contents($backend.'/.env.local');
                    if (!preg_match('/^DATABASE_URL=(.+)$/m', $contents, $match)) {
                        throw new \RuntimeException('Configuration DATABASE_URL absente.');
                    }
                    $databaseUrl = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
                    if (!is_string($databaseUrl)) {
                        throw new \RuntimeException('Configuration DATABASE_URL invalide.');
                    }
                    (new ProductionDumpImporter())->import($backend, $databaseUrl, $uploaded->getRealPath());
                    (new SetupState())->transition($workspace, SetupState::RUNNING, SetupState::DATABASE_READY);
                }
            }
            if ($action === 'doctrine' && !$this->provisionAdministrator($request, $backend)) {
                return $this->redirectToRoute('symsvel_prod_settings_admin');
            }
            $verify = new Process([PHP_BINARY, 'bin/console', 'symsvel:admins:bootstrap:verify', '--no-interaction'], $backend);
            $verify->setTimeout(60);
            $verify->run();
            if (!$verify->isSuccessful()) {
                throw new \RuntimeException('Aucun administrateur valide détecté après initialisation. Le Setup reste ouvert.');
            }
            (new SetupState())->transition($workspace, SetupState::DATABASE_READY, SetupState::COMPLETED);
        } catch (\Throwable $exception) {
            $this->addFlash('setup_error', $exception->getMessage());
            return $this->redirectToRoute('symsvel_prod_settings_admin');
        }
        $repositories = (new ProductionSetupConfig())->read($workspace) ?? [];
        $messages = (new ProductionCdDispatcher())->dispatch($repositories, (string) $request->request->get('github_token', ''));
        return $this->render('setup/finished.html.twig', ['messages' => $messages]);
    }

}
