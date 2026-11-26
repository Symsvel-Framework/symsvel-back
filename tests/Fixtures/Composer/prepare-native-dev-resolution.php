<?php

declare(strict_types=1);

// Isolated Composer solver regression test, without installing vendors or
// executing recipes, package scripts or any application/database bootstrap.
$back = dirname(__DIR__, 3);
$target = $argv[1] ?? '';
if ($target === '' || !is_dir($target) || realpath($target) === realpath($back)) {
    throw new RuntimeException('A separate existing temporary directory is required.');
}
$native = json_decode((string) file_get_contents($back.'/config/symsvel-native-modules.json'), true, 32, JSON_THROW_ON_ERROR);
$modules = array_values(array_unique(array_merge($native['modules'], ...array_values($native['components']))));
$packages = array_merge(['symsvel-framework/core', 'symsvel-framework/tooling'], $modules);

if (($argv[2] ?? '') === '--verify') {
    $lock = json_decode((string) file_get_contents($target.'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
    $versions = array_column(array_merge($lock['packages'], $lock['packages-dev']), 'version', 'name');
    foreach ($packages as $package) {
        if (($versions[$package] ?? null) !== 'dev-dev') {
            throw new RuntimeException($package.' must resolve to dev-dev; got '.($versions[$package] ?? 'missing'));
        }
        echo $package." dev-dev\n";
    }
    exit(0);
}
$manifest = json_decode((string) file_get_contents($back.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
// Like create-project --dev, avoid the stable Satis repository for official
// packages. Keep the rest of the Back dependency graph and stability policy.
$manifest['repositories'] = array_values(array_filter(
    $manifest['repositories'] ?? [],
    static fn (array $repo): bool => !(($repo['type'] ?? '') === 'composer'
        && in_array(rtrim($repo['url'] ?? '', '/'), ['https://packages.symsvel.com', 'https://packages.xavier-tezza.fr'], true)),
));
$manifest['require']['symsvel-framework/core'] = 'dev-dev';
$manifest['require-dev']['symsvel-framework/tooling'] = 'dev-dev';
foreach ($modules as $package) {
    $manifest['require'][$package] = 'dev-dev';
    $slug = substr($package, strlen('symsvel-framework/'));
    $manifest['repositories'][] = [
        'type' => 'vcs',
        'url' => 'https://github.com/Symsvel-Framework/symsvel-'.$slug.'.git',
    ];
}
if (file_put_contents($target.'/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n") === false) {
    throw new RuntimeException('Cannot write isolated Composer manifest.');
}
