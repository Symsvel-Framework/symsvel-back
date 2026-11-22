<?php

declare(strict_types=1);

// Install without booting Symfony: APP_SECRET and DATABASE_URL are not ready yet.
$backend = dirname(__DIR__);
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (in_array($path, ['/setup', '/setup/admin', '/prod_settings', '/prod_settings/admin'], true)) {
    $installer = $backend.'/src/Setup/StandaloneSetup.php';
    if (!is_file($installer) || is_link($installer)) {
        http_response_code(404);
        exit;
    }
    require_once $backend.'/vendor/autoload.php';
    require_once $installer;
    \App\Setup\StandaloneSetup::handle($backend, $path);
    exit;
}

// Never expose the default dev environment before the first setup.
$stateFile = dirname($backend).'/.symsvel/setup.json';
$state = is_file($stateFile) && !is_link($stateFile) ? json_decode((string) file_get_contents($stateFile), true) : null;
if (!is_file($backend.'/.env.local') || is_link($backend.'/.env.local')
    || !is_array($state) || ($state['status'] ?? null) !== 'completed') {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    echo 'Installation SymSvel non initialisée. Ouvrez /prod_settings.';
    exit;
}

require_once $backend.'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new \App\Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
