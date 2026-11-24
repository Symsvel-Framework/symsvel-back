<?php

declare(strict_types=1);

namespace App\Setup;

use Symfony\Component\Process\Process;

/**
 * Per-installation first-deploy metadata. No GitHub token is persisted.
 * Production files are not committed to a project's Git repository.
 */
final class ProductionSetupConfig
{
    public function suggestions(string $backend): array
    {
        $defaults = ['bo' => '', 'app' => '', 'front' => ''];
        try {
            $process = new Process(['git', '-C', $backend, 'remote', 'get-url', 'origin']);
            $process->setTimeout(5);
            $process->run();
            if (!$process->isSuccessful()) {
                return $defaults;
            }
            $origin = trim($process->getOutput());
        } catch (\Throwable) {
            // Git may be unavailable in PHP-FPM's PATH even when SSH can use it.
            return $defaults;
        }
        if (!preg_match('~^(?:https://github\.com/|git@github\.com:)([A-Za-z0-9-]+)/[A-Za-z0-9_.-]+(?:\.git)?/?$~D', $origin, $match)) {
            return $defaults;
        }
        $prefix = 'https://github.com/'.$match[1].'/';
        return ['bo' => $prefix.'bo', 'app' => $prefix.'app', 'front' => $prefix.'front'];
    }

    public function normalizeRepository(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('~^https://github\.com/([A-Za-z0-9-]+)/([A-Za-z0-9_.-]+)/?$~D', $url, $matches)) {
            throw new \InvalidArgumentException('Dépôt GitHub invalide : utilisez https://github.com/compte/depot.');
        }
        $name = preg_replace('/\.git$/', '', $matches[2]);
        if ($name === '' || $name === '.' || $name === '..') {
            throw new \InvalidArgumentException('Nom de dépôt invalide.');
        }
        return 'https://github.com/'.$matches[1].'/'.$name;
    }

    /** @param array<string, string> $repositories */
    public function save(string $workspace, array $repositories): void
    {
        $normalized = [];
        foreach (['bo', 'app', 'front'] as $name) {
            $normalized[$name] = $this->normalizeRepository((string) ($repositories[$name] ?? ''));
        }
        $directory = $workspace.'/.symsvel';
        $file = $directory.'/production.json';
        if (is_link($directory) || !is_dir($directory) || is_link($file) || (file_exists($file) && !is_file($file))) {
            throw new \RuntimeException('Répertoire de configuration production invalide.');
        }
        $temporary = tempnam($directory, '.production-');
        if ($temporary === false) {
            throw new \RuntimeException('Impossible de préparer la configuration de production.');
        }
        try {
            $json = json_encode(['schema' => 1, 'repositories' => $normalized], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
            if (!chmod($temporary, 0600) || file_put_contents($temporary, $json) === false || !rename($temporary, $file)) {
                throw new \RuntimeException('Enregistrement de la configuration de production impossible.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /** @return array<string, string>|null */
    public function read(string $workspace): ?array
    {
        $directory = $workspace.'/.symsvel';
        $file = $directory.'/production.json';
        if (is_link($directory) || is_link($file)) {
            throw new \RuntimeException('Configuration production symbolique interdite.');
        }
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || ($data['schema'] ?? null) !== 1 || !is_array($data['repositories'] ?? null)) {
            throw new \RuntimeException('Configuration production invalide.');
        }
        return $data['repositories'];
    }
}
