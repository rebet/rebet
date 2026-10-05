<?php

declare(strict_types=1);

namespace Rebet\Tests\Application\Console\Command\Project;

use Override;
use Rebet\Application\Console\Command\Project\ProjectInitCommand;
use Rebet\Tests\RebetConsoleTestCase;

/**
 * ProjectInitCommand that behaves as if every `composer require` failed.
 */
class ComposerRequireFailingProjectInitCommand extends ProjectInitCommand
{
    #[Override]
    protected function composerRequire(string $cwd, array $packages, bool $dev): bool
    {
        return empty($packages);
    }
}

class ProjectInitCommandComposerFailureTest extends RebetConsoleTestCase
{
    public const AVIRABLE_COMMANDS = [[ComposerRequireFailingProjectInitCommand::class, __DIR__ . '/../../../../../../../../skeltons']];

    public function test_execute_composerRequireFailed_showsHowToRetryAndFails(): void
    {
        $work_dir = static::makeSubWorkingDir('project-init-composer-require-failed');
        file_put_contents("{$work_dir}/composer.json", ProjectInitCommandTest::COMPOSER_JSON);
        $cwd = getcwd();
        chdir($work_dir);
        try {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--view' => 'blade'], ['interactive' => false]);
            $this->assertSame(1, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('The application files were generated, but some Composer packages could not be installed.', $display);
            $this->assertStringContainsString("composer require --ignore-platform-req='ext-*' 'illuminate/view:^13.21'", $display);
            $this->assertStringContainsString("composer require --dev --ignore-platform-req='ext-*' 'friendsofphp/php-cs-fixer:^3.95' 'phpstan/phpstan:^2.2' 'phpunit/phpunit:^11.5' 'psy/psysh:^0.12.24'", $display);
            $this->assertStringNotContainsString('initilized!', $display);

            // The application files themselves were generated.
            $this->assertFileExists("{$work_dir}/app");
        } finally {
            chdir($cwd);
        }
    }
}
