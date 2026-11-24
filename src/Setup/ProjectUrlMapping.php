<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * Logical component => HTTP origin mapping. No workspace folder mapping,
 * DNS lookup or deployment path assumptions.
 */
final class ProjectUrlMapping
{
    private const SCOPES = ['back', 'bo', 'app', 'front'];

    /** @param array<string, string> $submitted
     *  @return array<string, string>
     */
    public function normalize(array $submitted, bool $production): array
    {
        $mapping = [];
        foreach (self::SCOPES as $scope) {
            $value = strtolower(trim((string) ($submitted[$scope] ?? '')));
            if ($value === '') {
                if (in_array($scope, ['back', 'bo'], true)) {
                    throw new \InvalidArgumentException('URL obligatoire pour le composant '.strtoupper($scope).'.');
                }
                $mapping[$scope] = '';
                continue;
            }
            if (strlen($value) > 300 || preg_match('~^https?://[a-z0-9.-]+(?::[0-9]{1,5})?$~D', $value) !== 1) {
                throw new \InvalidArgumentException('URL invalide pour '.strtoupper($scope).' : indiquez une origine HTTP(S), sans chemin.');
            }
            $parts = parse_url($value);
            if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])
                || $parts['host'] === '' || strlen($parts['host']) > 253
                || preg_match('/^(?=.{1,253}$)[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?)*$/D', $parts['host']) !== 1
                || ($production && $parts['scheme'] !== 'https')
                || (isset($parts['port']) && ($parts['port'] < 1 || $parts['port'] > 65535))) {
                throw new \InvalidArgumentException('Origine invalide pour '.strtoupper($scope).'. HTTPS est obligatoire en production.');
            }
            $mapping[$scope] = $value;
        }
        return $mapping;
    }

    /** @param array<string, string> $mapping */
    public function corsRegex(array $mapping): string
    {
        // Back is the receiving API, not a browser origin calling it.
        $origins = array_values(array_unique(array_filter([
            $mapping['bo'] ?? '', $mapping['app'] ?? '', $mapping['front'] ?? '',
        ], static fn (string $origin): bool => $origin !== '')));
        if ($origins === []) {
            throw new \InvalidArgumentException('Au moins une interface doit être déclarée.');
        }
        return '^(?:'.implode('|', array_map(
            static fn (string $origin): string => preg_quote($origin, '~'),
            $origins,
        )).')$';
    }

    /** @param array<string, string> $mapping
     *  @return array<string, string>
     */
    public function environment(array $mapping, string $projectDomain): array
    {
        (new ProjectMetadata())->validateLocalDomain($projectDomain);
        $values = [
            'SYMSVEL_CORS_ALLOW_ORIGIN' => $this->corsRegex($mapping),
            // Keep the established domain fallback for older Auth packages.
            'SYMSVEL_BO_DOMAIN' => strtolower(trim($projectDomain)),
        ];
        foreach (self::SCOPES as $scope) {
            $values['SYMSVEL_'.strtoupper($scope).'_ORIGIN'] = $mapping[$scope] ?? '';
        }
        return $values;
    }
}
