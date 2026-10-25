<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\SymSvelInstallCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

final class SymSvelInstallCommandTest extends TestCase
{
    public function testNonInteractiveInstallationIsRejectedWithoutCreatingDirectories(): void
    {
        $projectDir = sys_get_temp_dir().'/symsvel-install-test-'.bin2hex(random_bytes(8));
        mkdir($projectDir);
        try {
            $command = new SymSvelInstallCommand($projectDir);
            $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
            $tester = new CommandTester($command);
            self::assertSame(2, $tester->execute([], ['interactive' => false]));
            self::assertDirectoryDoesNotExist($projectDir.'/bo');
        } finally {
            rmdir($projectDir);
        }
    }

    public function testExistingBoDirectoryIsNotOverwritten(): void
    {
        $projectDir = sys_get_temp_dir().'/symsvel-install-test-'.bin2hex(random_bytes(8));
        mkdir($projectDir);
        mkdir($projectDir.'/bo');
        file_put_contents($projectDir.'/bo/keep.txt', 'preserved');
        try {
            $command = new SymSvelInstallCommand($projectDir);
            $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
            $tester = new CommandTester($command);
            $tester->setInputs(['1']);
            self::assertSame(1, $tester->execute([]));
            self::assertSame('preserved', file_get_contents($projectDir.'/bo/keep.txt'));
        } finally {
            unlink($projectDir.'/bo/keep.txt');
            rmdir($projectDir.'/bo');
            rmdir($projectDir);
        }
    }
}
