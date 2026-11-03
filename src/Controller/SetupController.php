<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class SetupController extends AbstractController
{
    #[Route('/setup', name: 'symsvel_setup', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $workspace = dirname((string) $this->getParameter('kernel.project_dir'));
        $stateFile = $workspace.'/.symsvel/setup.json';
        if (is_link($stateFile) || !is_file($stateFile)) {
            throw $this->createNotFoundException();
        }
        try {
            $state = json_decode((string) file_get_contents($stateFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw $this->createNotFoundException();
        }
        if (($state['schema'] ?? null) !== 1 || !in_array($state['status'] ?? null, ['pending', 'running'], true)) {
            throw $this->createNotFoundException();
        }
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('symsvel_setup', (string) $request->request->get('_token', ''))) {
                return new Response('Requête invalide.', Response::HTTP_FORBIDDEN);
            }
            $tokenFile = $workspace.'/.symsvel/setup-token';
            if (is_link($tokenFile) || !is_file($tokenFile)) {
                return new Response('Jeton d’installation indisponible.', Response::HTTP_FORBIDDEN);
            }
            $expected = trim((string) file_get_contents($tokenFile));
            $provided = (string) $request->request->get('setup_token', '');
            if ($expected === '' || !hash_equals($expected, $provided)) {
                return new Response('Jeton d’installation incorrect.', Response::HTTP_FORBIDDEN);
            }
            $url = trim((string) $request->request->get('database_url', ''));
            $parts = parse_url($url);
            if ($parts === false || !in_array($parts['scheme'] ?? '', ['postgresql', 'postgres'], true)
                || empty($parts['host']) || empty($parts['path']) || !isset($parts['user'])) {
                $this->addFlash('setup_error', 'URL PostgreSQL invalide.');
                return $this->redirectToRoute('symsvel_setup');
            }
            $backend = (string) $this->getParameter('kernel.project_dir');
            $envFile = $backend.'/.env.local';
            if (is_link($envFile)) {
                return new Response('Configuration symbolique interdite.', Response::HTTP_FORBIDDEN);
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
                if (file_put_contents($temporary, $contents) === false || !rename($temporary, $envFile)) {
                    throw new \RuntimeException('Impossible d’enregistrer la configuration.');
                }
            } finally {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
            $process = new Process([PHP_BINARY, 'bin/console', 'symsvel:setup:finalize', '--no-interaction'], $backend);
            $process->setTimeout(null);
            $process->run();
            if (!$process->isSuccessful()) {
                $this->addFlash('setup_error', 'Finalisation impossible. Vérifiez la connexion PostgreSQL et les migrations.');
                return $this->redirectToRoute('symsvel_setup');
            }
            @unlink($tokenFile);
            return new Response('Installation SymSvel terminée.', Response::HTTP_OK);
        }
        return $this->render('setup/index.html.twig');
    }
}
