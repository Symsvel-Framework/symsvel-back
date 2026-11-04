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
            self::assertSame('argon2id', password_get_info($payload['password_hash'])['algoName']);
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
        foreach ([['invalid', 'correct-horse-battery-123'], ['a@example.test', 'short'], ['a@example.test', "valid-password\0suffix"]] as [$email, $password]) {
            try {
                $service->stage(sys_get_temp_dir(), $email, $password);
                self::fail('Invalid administrator data accepted.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testValidationDoesNotWriteAdministratorData(): void
    {
        $service = new PendingAdministrator();
        $service->validate(' developer@example.test ', 'correct-horse-battery-123');
        $this->expectException(\InvalidArgumentException::class);
        $service->validate('developer@example.test', 'short');
    }

    public function testRejectsStagedHashThatIsNotArgon2id(): void
    {
        $root = sys_get_temp_dir().'/symsvel-admin-hash-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        $path = $root.'/.symsvel/pending-admin.json';
        try {
            file_put_contents($path, json_encode([
                'schema' => 1,
                'email' => 'developer@example.test',
                'password_hash' => password_hash('correct-horse-battery-123', PASSWORD_BCRYPT),
                'initial_role' => 'developer',
            ], JSON_THROW_ON_ERROR));
            $this->expectException(\RuntimeException::class);
            (new PendingAdministrator())->read($root);
        } finally {
            @unlink($path);
            @rmdir($root.'/.symsvel');
            @rmdir($root);
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
