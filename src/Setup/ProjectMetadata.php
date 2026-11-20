<?php

declare(strict_types=1);

namespace App\Setup;

final class ProjectMetadata
{
    /**
     * Public project title only. Never return the local domain, channel or Setup state.
     * A missing/unreadable private file does not break the public interfaces.
     */
    public function publicName(string $workspace): string
    {
        $directory = $workspace.'/.symsvel';
        $path = $directory.'/project.json';
        if (is_link($directory) || is_link($path) || !is_file($path) || !is_readable($path)) {
            return 'SymSvel';
        }
        try {
            $metadata = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($metadata) || ($metadata['schema'] ?? null) !== 1
                || !is_string($metadata['name'] ?? null)) {
                return 'SymSvel';
            }
            $name = trim($metadata['name']);
            $this->validate($name);
            return $name;
        } catch (\Throwable) {
            return 'SymSvel';
        }
    }

    public function validate(string $name): void
    {
        $name = trim($name);
        $characters = preg_match_all('/./us', $name);
        if ($name === '' || $characters === false || $characters > 120
            || preg_match('/[\x00-\x1F\x7F]/u', $name) !== 0) {
            throw new \InvalidArgumentException('Le nom du projet doit contenir entre 1 et 120 caractères valides.');
        }
    }

    public function validateLocalDomain(string $domain): void
    {
        $domain = strtolower(trim($domain));
        if (strlen($domain) > 253 || !preg_match('/^(?=.{1,253}$)[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?(?:\\.[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?)*$/D', $domain)) {
            throw new \InvalidArgumentException('Domaine local invalide (exemple : xt.localhost).');
        }
    }

    public function save(string $workspace, string $name, string $localDomain): void
    {
        $this->validate($name);
        $this->validateLocalDomain($localDomain);
        $name = trim($name);
        $localDomain = strtolower(trim($localDomain));

        $directory = $workspace.'/.symsvel';
        $path = $directory.'/project.json';
        if (is_link($directory) || !is_dir($directory)
            || is_link($path) || (file_exists($path) && !is_file($path))) {
            throw new \RuntimeException('Métadonnées du projet invalides.');
        }

        // The CLI owns the project release channel. Setup must not erase it.
        $existing = [];
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($existing) || ($existing['schema'] ?? null) !== 1) {
                throw new \RuntimeException('Métadonnées du projet existantes invalides.');
            }
        }

        $temporary = tempnam($directory, '.project-');
        if ($temporary === false) {
            throw new \RuntimeException('Impossible de préparer les métadonnées du projet.');
        }
        try {
            $json = json_encode(array_merge($existing, ['schema' => 1, 'name' => $name, 'local_domain' => $localDomain]), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
            if (!chmod($temporary, 0600)
                || file_put_contents($temporary, $json) === false
                || !rename($temporary, $path)) {
                throw new \RuntimeException('Impossible de publier les métadonnées du projet.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
