<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\PendingAdministrator;
use PHPUnit\Framework\TestCase;

final class PendingAdministratorTest extends TestCase
{
    public function testStagesHashedCredentialsPrivatelyAndSupportsRetry(): void
    {
        $root = sys_get_temp_dir().'/symsvel-admin-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        $service = new PendingAdministrator();
        try {
            $service->stage($root, ' developer@example.test ', 'correct-horse-battery-123');
            $path = $root.'/.symsvel/pending-admin.json';
            $payload = $service->read($root);
            self::assertSame('developer@example.test', $payload['email']);
            self::assertSame('developer', $payload['initial_role']);
            self::assertTrue(password_verify('correct-horse-battery-123', $payload['password_hash']));
            self::assertStringNotContainsString('correct-horse-battery-123', (string) file_get_contents($path));
            clearstatcache(true, $path);
            self::assertSame(0600, fileperms($path) & 0777);

            $service->stage($root, 'retry@example.test', 'another-secure-password');
            self::assertSame('retry@example.test', $service->read($root)['email']);
            $service->discard($root);
            self::assertFileDoesNotExist($path);
            $service->discard($root);
        } finally {
            @unlink($root.'/.symsvel/pending-admin.json');
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }

    public function testRejectsInvalidEmailAndPassword(): void
    {
        $service = new PendingAdministrator();
        foreach ([['invalid', 'correct-horse-battery-123'], ['a@example.test', 'short']] as [$email, $password]) {
            try {
                $service->stage(sys_get_temp_dir(), $email, $password);
                self::fail('Invalid administrator data accepted.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRefusesSymlinkDestination(): void
    {
        $root = sys_get_temp_dir().'/symsvel-admin-link-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        file_put_contents($root.'/target', 'untouched');
        symlink($root.'/target', $root.'/.symsvel/pending-admin.json');
        try {
            $this->expectException(\RuntimeException::class);
            (new PendingAdministrator())->stage($root, 'developer@example.test', 'correct-horse-battery-123');
        } finally {
            @unlink($root.'/.symsvel/pending-admin.json');
            @unlink($root.'/target');
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }
}
