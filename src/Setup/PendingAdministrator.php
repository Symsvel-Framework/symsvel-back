<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * One-time administrator payload for the setup process.
 * The account is not created here: the Admins module consumes it after migrations.
 */
final class PendingAdministrator
{
    public function stage(string $workspace, string $email, string $plainPassword): void
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
            throw new \InvalidArgumentException('Adresse e-mail administrateur invalide.');
        }
        if (strlen($plainPassword) < 12 || strlen($plainPassword) > 4096) {
            throw new \InvalidArgumentException('Le mot de passe administrateur doit contenir au moins 12 caractères.');
        }

        $directory = $workspace.'/.symsvel';
        $path = $directory.'/pending-admin.json';
        if (is_link($directory) || !is_dir($directory)
            || is_link($path) || (file_exists($path) && !is_file($path))) {
            throw new \RuntimeException('Stockage temporaire administrateur invalide.');
        }

        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID);
        $payload = json_encode([
            'schema' => 1,
            'email' => $email,
            'password_hash' => $hash,
            'initial_role' => 'developer',
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n";
        $temporary = tempnam($directory, '.pending-admin-');
        if ($temporary === false) {
            throw new \RuntimeException('Impossible de préparer les données administrateur.');
        }
        try {
            if (!chmod($temporary, 0600)
                || file_put_contents($temporary, $payload) === false
                || !rename($temporary, $path)) {
                throw new \RuntimeException('Impossible de stocker les données administrateur.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /** @return array{schema: int, email: string, password_hash: string, initial_role: string} */
    public function read(string $workspace): array
    {
        $path = $workspace.'/.symsvel/pending-admin.json';
        if (is_link($workspace.'/.symsvel') || is_link($path) || !is_file($path)) {
            throw new \RuntimeException('Données administrateur temporaires introuvables.');
        }
        $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload) || ($payload['schema'] ?? null) !== 1
            || !is_string($payload['email'] ?? null)
            || filter_var($payload['email'], FILTER_VALIDATE_EMAIL) === false
            || !is_string($payload['password_hash'] ?? null)
            || !password_get_info($payload['password_hash'])['algo']
            || ($payload['initial_role'] ?? null) !== 'developer') {
            throw new \RuntimeException('Données administrateur temporaires invalides.');
        }

        return $payload;
    }

    public function discard(string $workspace): void
    {
        $path = $workspace.'/.symsvel/pending-admin.json';
        if (is_link($path)) {
            throw new \RuntimeException('Lien symbolique interdit pour les données administrateur.');
        }
        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException('Impossible de supprimer les données administrateur temporaires.');
        }
    }
}
