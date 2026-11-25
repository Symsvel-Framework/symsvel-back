<?php
declare(strict_types=1);

// Documentation fixture: render actual Setup forms, never submit or initialize a database.
if (PHP_SAPI !== 'cli' || $argc !== 3) {
    exit(1);
}
require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Setup\StandaloneSetup;
use SymSvel\Tooling\Installation\SetupState;

$mode = $argv[1];
if (!in_array($mode, ['local-config', 'local-admin', 'prod-config', 'prod-admin'], true)) {
    exit(2);
}
$backend = $argv[2];
if (!is_dir($backend)) {
    exit(3);
}
$_SESSION['symsvel_setup_csrf'] = str_repeat('0', 64);
$production = str_starts_with($mode, 'prod-');
if (str_ends_with($mode, '-config')) {
    (new ReflectionMethod(StandaloneSetup::class, 'configurationPage'))->invoke(null, $backend, $production);
} else {
    (new ReflectionMethod(StandaloneSetup::class, 'administratorPage'))->invoke(
        null, $production, $production ? SetupState::RUNNING : SetupState::DATABASE_READY,
    );
}
