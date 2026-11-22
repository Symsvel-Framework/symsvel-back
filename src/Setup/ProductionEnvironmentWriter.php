<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * The production environment belongs to this deployment, never to Git.
 * The backend uses .env.local (Symfony override) and secrets/.env is its private copy.
 */
final class ProductionEnvironmentWriter
{
    public function write(string $backend, string $databaseUrl, string $domain, array $origins): void
    {
        $workspace = dirname($backend);
        $secrets = $workspace.'/secrets';
        if (is_link($secrets) || (file_exists($secrets) && !is_dir($secrets))) {
            throw new \RuntimeException('Répertoire secrets invalide.');
        }
        if (!is_dir($secrets) && !mkdir($secrets, 0700) && !is_dir($secrets)) {
            throw new \RuntimeException('Impossible de créer secrets/.');
        }
        foreach ([$backend.'/.env.local', $secrets.'/.env'] as $path) {
            if (is_link($path) || (file_exists($path) && !is_file($path))) {
                throw new \RuntimeException('Fichier de configuration invalide : '.$path);
            }
        }
        $backendFile = $backend.'/.env.local';
        $existing = is_file($backendFile) ? (string) file_get_contents($backendFile) : '';
        $lines = preg_split('/\r\n|\r|\n/', $existing) ?: [];
        $secret = null;
        foreach ($lines as $line) {
            if (preg_match('/^APP_SECRET="([a-f0-9]{64})"$/D', $line, $matches)) {
                $secret = $matches[1];
            }
        }
        $lines = array_values(array_filter($lines, static fn (string $line): bool =>
            preg_match('/^\s*(?:APP_ENV|APP_DEBUG|APP_SECRET|DATABASE_URL|SYMSVEL_CORS_ALLOW_ORIGIN|SYMSVEL_BO_DOMAIN|SYMSVEL_(?:BACK|BO|APP|FRONT)_ORIGIN)\s*=/', $line) !== 1
            && $line !== ''
        ));
        $values = [
            'APP_ENV' => 'prod',
            'APP_DEBUG' => '0',
            'APP_SECRET' => $secret ?? bin2hex(random_bytes(32)),
            'DATABASE_URL' => $databaseUrl,
        ] + (new ProjectUrlMapping())->environment($origins, $domain);
        foreach ($values as $key => $value) {
            $lines[] = $key.'='.json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }
        $contents = implode("\n", $lines)."\n";
        $this->publish($secrets.'/.env', $contents);
        $this->publish($backendFile, $contents);
    }

    private function publish(string $path, string $contents): void
    {
        $temporary = tempnam(dirname($path), '.env-');
        if ($temporary === false) {
            throw new \RuntimeException('Impossible de préparer '.$path);
        }
        try {
            if (!chmod($temporary, 0600) || file_put_contents($temporary, $contents) === false || !rename($temporary, $path)) {
                throw new \RuntimeException('Impossible de publier '.$path);
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
