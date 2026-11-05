<?php

declare(strict_types=1);

namespace App\Setup;

final class ProjectMetadata
{
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

        $temporary = tempnam($directory, '.project-');
        if ($temporary === false) {
            throw new \RuntimeException('Impossible de préparer les métadonnées du projet.');
        }
        try {
            $json = json_encode(['schema' => 1, 'name' => $name, 'local_domain' => $localDomain], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
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
