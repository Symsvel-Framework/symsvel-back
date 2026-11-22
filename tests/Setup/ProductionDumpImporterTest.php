<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\ProductionDumpImporter;
use PHPUnit\Framework\TestCase;

final class ProductionDumpImporterTest extends TestCase
{
    public function testRejectsAbsentOrEmptyDumpBeforeLaunchingAnyDatabaseClient(): void
    {
        $importer = new ProductionDumpImporter();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dump SQL absent, vide ou illisible.');
        $importer->import(sys_get_temp_dir(), 'postgresql://u:p@localhost:5432/db', '/no-such-symsvel-dump.sql');
    }

    public function testRejectsUnsupportedDatabaseScheme(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'symsvel-sql-');
        try {
            file_put_contents($file, 'SELECT 1;');
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('SGBD non pris en charge');
            (new ProductionDumpImporter())->import(sys_get_temp_dir(), 'sqlserver://example/db', $file);
        } finally {
            @unlink($file);
        }
    }
}
