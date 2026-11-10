<?php

declare(strict_types=1);

namespace App\Controller;

use App\Setup\DatabaseConnectionConfig;
use App\Setup\PendingAdministrator;
use App\Setup\ProjectMetadata;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Process;

final class SetupController extends AbstractController
{
    #[Route('/setup', name: 'symsvel_setup', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $workspace = dirname((string) $this->getParameter('kernel.project_dir'));
        $stateFile = $workspace.'/.symsvel/setup.json';
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
        // Setup is a local-only operation, not a publicly accessible installer.
        if (!in_array($request->getClientIp(), ['127.0.0.1', '::1'], true)) {
            return new Response('Installation accessible uniquement en local.', Response::HTTP_FORBIDDEN);
        }
        if (($state['status'] ?? null) === 'database_ready') {
            return $this->redirectToRoute('symsvel_setup_admin');
        }
        if ($request->isMethod('POST')) {
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
                    (string) $request->request->get('db_sqlite_path', 'var/data/app.sqlite'),
                );
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                $this->addFlash('setup_error', $exception->getMessage());
                return $this->redirectToRoute('symsvel_setup');
            }
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
            $lines = array_values(array_filter($lines, static fn (string $line): bool => preg_match('/^\s*DATABASE_URL\s*=/', $line) !== 1));
            $lines[] = 'DATABASE_URL='.json_encode($url, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
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
            (new ProjectMetadata())->save($workspace, $projectName, $localDomain);
            // Phase 1 performs only database preparation and migrations.
            $process = new Process([PHP_BINARY, 'bin/console', 'symsvel:setup:database', '--no-interaction'], $backend);
            $process->setTimeout(null);
            $process->run();
            if (!$process->isSuccessful()) {
                $this->addFlash('setup_error', 'Préparation de la base impossible. Vérifiez la connexion et les migrations, puis réessayez.');
                return $this->redirectToRoute('symsvel_setup');
            }
            return $this->redirectToRoute('symsvel_setup_admin');
        }
        return $this->render('setup/index.html.twig');
    }
    #[Route('/setup/admin', name: 'symsvel_setup_admin', methods: ['GET', 'POST'])]
    public function administrator(Request $request): Response
    {
        if (!in_array($request->getClientIp(), ['127.0.0.1', '::1'], true)) {
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
        if (($state['status'] ?? null) !== 'database_ready') {
            return $this->redirectToRoute('symsvel_setup');
        }
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('symsvel_setup_admin', (string) $request->request->get('_token', ''))) {
                return new Response('Requête invalide.', Response::HTTP_FORBIDDEN);
            }
            $action = (string) $request->request->get('setup_action', 'provision');
            if ($action === 'provision') {
                $email = (string) $request->request->get('admin_email', '');
                $password = (string) $request->request->get('admin_password', '');
                $confirmation = (string) $request->request->get('admin_password_confirmation', '');
                if (!hash_equals($password, $confirmation)) {
                    $this->addFlash('setup_error', 'Les mots de passe administrateur ne correspondent pas.');
                    return $this->redirectToRoute('symsvel_setup_admin');
                }
                try {
                    (new PendingAdministrator())->validate($email, $password);
                } catch (\InvalidArgumentException $exception) {
                    $this->addFlash('setup_error', $exception->getMessage());
                    return $this->redirectToRoute('symsvel_setup_admin');
                }
                $process = new Process([PHP_BINARY, 'bin/console', 'symsvel:admins:bootstrap:stdin', '--no-interaction'], $backend);
                $process->setInput(json_encode(['email' => trim($email), 'password' => $password], JSON_THROW_ON_ERROR));
                $process->setTimeout(60);
                $process->run();
                if (!$process->isSuccessful()) {
                    $this->addFlash('setup_error', 'Création du premier administrateur impossible. Vérifiez le module Admins et réessayez.');
                    return $this->redirectToRoute('symsvel_setup_admin');
                }
            } elseif ($action !== 'complete') {
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
        return $this->render('setup/admin.html.twig');
    }

}
