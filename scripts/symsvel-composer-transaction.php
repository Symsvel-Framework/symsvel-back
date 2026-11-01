<?php

declare(strict_types=1);

$backend = dirname(__DIR__);
$launcher = $backend.'/vendor/symsvel-framework/tooling/bin/symsvel-composer-transaction.php';
if (!is_file($launcher)) {
    fwrite(STDERR, "[symsvel] Tooling transaction launcher missing: $launcher\n");
    exit(1);
}
$command = array_merge([PHP_BINARY, $launcher], array_slice($argv, 1));
$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $backend);
if (!is_resource($process)) {
    fwrite(STDERR, "[symsvel] Cannot launch Tooling.\n");
    exit(1);
}
exit(proc_close($process));
