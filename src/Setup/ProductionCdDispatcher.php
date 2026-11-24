<?php

declare(strict_types=1);

namespace App\Setup;

use Symfony\Component\HttpClient\HttpClient;

/** Trigger a project's already-configured GitHub Actions CD, never a framework CD. */
final class ProductionCdDispatcher
{
    /** @param array<string, string> $repositories
     *  @return list<string>
     */
    public function dispatch(array $repositories, string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['CD non déclenchées : configurez et lancez les workflows de votre projet.'];
        }
        $messages = [];
        $client = HttpClient::create(['timeout' => 15]);
        foreach (['bo', 'app', 'front'] as $component) {
            $url = (string) ($repositories[$component] ?? '');
            if ($url === '') {
                continue;
            }
            if (!preg_match('~^https://github\.com/([A-Za-z0-9-]+)/([A-Za-z0-9_.-]+)$~D', $url, $match)) {
                $messages[] = strtoupper($component).' : URL de dépôt invalide, CD non déclenchée.';
                continue;
            }
            try {
                $response = $client->request('POST', sprintf(
                    'https://api.github.com/repos/%s/%s/actions/workflows/deploy.yml/dispatches',
                    $match[1], $match[2],
                ), [
                    'headers' => [
                        'Authorization' => 'Bearer '.$token,
                        'Accept' => 'application/vnd.github+json',
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ],
                    'json' => ['ref' => 'main'],
                ]);
                if ($response->getStatusCode() === 204) {
                    $messages[] = strtoupper($component).' : CD demandée à GitHub (publication non encore confirmée).';
                } else {
                    $messages[] = strtoupper($component).' : GitHub a refusé le déclenchement de la CD (HTTP '.$response->getStatusCode().').';
                }
            } catch (\Throwable) {
                $messages[] = strtoupper($component).' : CD inaccessible. Vérifiez GitHub et le workflow deploy.yml.';
            }
        }
        return $messages === [] ? ['Aucun dépôt d’interface configuré : CD non déclenchées.'] : $messages;
    }
}
