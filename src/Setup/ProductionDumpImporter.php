<?php

declare(strict_types=1);

namespace App\Setup;

use Symfony\Component\Process\Process;

/**
 * Imports a plain SQL dump using the native client of the selected DBMS.
 * No SQL is parsed or split by PHP. Importing is not transactionally reversible.
 */
final class ProductionDumpImporter
{
    public function import(string $backend, string $databaseUrl, string $uploadedPath): void
    {
        if (!is_file($uploadedPath) || !is_readable($uploadedPath) || filesize($uploadedPath) === 0) {
            throw new \RuntimeException('Dump SQL absent, vide ou illisible.');
        }
        $parts = parse_url($databaseUrl);
        if (!is_array($parts) || !is_string($parts['scheme'] ?? null)) {
            throw new \RuntimeException('Connexion BDD invalide pour import.');
        }
        $scheme = $parts['scheme'];
        $environment = [];
        if ($scheme === 'sqlite') {
            $prefix = 'sqlite:///%kernel.project_dir%/';
            if (!str_starts_with($databaseUrl, $prefix)) {
                throw new \RuntimeException('Chemin SQLite non pris en charge.');
            }
            $relative = substr($databaseUrl, strlen($prefix));
            if ($relative === '' || str_contains($relative, '..') || preg_match('~^[a-zA-Z0-9_./-]+$~D', $relative) !== 1) {
                throw new \RuntimeException('Chemin SQLite invalide.');
            }
            $file = $backend.'/'.$relative;
            if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0775, true) && !is_dir(dirname($file))) {
                throw new \RuntimeException('Impossible de préparer le dossier SQLite.');
            }
            $command = ['sqlite3', '-bail', $file];
        } elseif (in_array($scheme, ['postgresql', 'postgres', 'mysql'], true)) {
            $host = (string) ($parts['host'] ?? '');
            $port = (int) ($parts['port'] ?? ($scheme === 'mysql' ? 3306 : 5432));
            $database = rawurldecode(ltrim((string) ($parts['path'] ?? ''), '/'));
            $user = rawurldecode((string) ($parts['user'] ?? ''));
            $password = rawurldecode((string) ($parts['pass'] ?? ''));
            if ($host === '' || $database === '' || $user === '') {
                throw new \RuntimeException('Connexion BDD incomplète pour import.');
            }
            if ($scheme === 'mysql') {
                $command = ['mysql', '--binary-mode', '--host='.$host, '--port='.$port, '--user='.$user, '--database='.$database];
                $environment['MYSQL_PWD'] = $password;
            } else {
                $command = ['psql', '-X', '-v', 'ON_ERROR_STOP=1', '--host='.$host, '--port='.$port, '--username='.$user, '--dbname='.$database];
                $environment['PGPASSWORD'] = $password;
            }
        } else {
            throw new \RuntimeException('SGBD non pris en charge pour l’import SQL.');
        }
        $stream = fopen($uploadedPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Impossible de lire le dump SQL.');
        }
        try {
            $process = new Process($command, $backend, $environment);
            $process->setInput($stream);
            $process->setTimeout(null);
            $process->run();
            if (!$process->isSuccessful()) {
                // Never echo client stderr: SQL statements and credentials may appear in it.
                throw new \RuntimeException('Import SQL échoué. Vérifiez le format du dump, les droits BDD et la présence du client SQL sur le serveur. Une reprise manuelle peut être nécessaire.');
            }
        } catch (\Symfony\Component\Process\Exception\ProcessStartFailedException $exception) {
            throw new \RuntimeException('Client SQL introuvable sur le serveur (psql, mysql ou sqlite3).', 0, $exception);
        } finally {
            fclose($stream);
        }
    }
}
