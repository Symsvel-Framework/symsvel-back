<?php

declare(strict_types=1);

// Composer has already changed vendor/ when post-update-cmd runs.
// Refresh ownership and the bundle map before Symfony boots for cache:clear.
$backend = dirname(__DIR__);
require $backend.'/vendor/autoload.php';

if (!class_exists(\SymSvel\Tooling\Module\ComposerModuleInventoryWriter::class)) {
    // Tooling is optional in production installations.
    exit(0);
}

try {
    $inventory = new \SymSvel\Tooling\Module\ComposerModuleSynchronizer();
    $inventory->prepare($backend);
    $modules = (new \SymSvel\Tooling\Module\ComposerModuleRegistry())->discover($backend);
    (new \SymSvel\Tooling\Module\ModuleBundleResolver())->resolve($modules);
    (new \SymSvel\Tooling\Module\ComposerModuleBundleMapWriter())->write($backend);
    (new \SymSvel\Tooling\Module\ComposerModuleInventoryWriter())->write($backend);
    echo "SymSvel: Composer ownership refreshed before cache compilation.\n";
} catch (\Throwable $exception) {
    fwrite(STDERR, 'SymSvel precache synchronization failed: '.$exception->getMessage()."\n");
    exit(1);
}
