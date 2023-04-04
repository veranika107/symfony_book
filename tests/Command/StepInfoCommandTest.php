<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class StepInfoCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        // Create test tag.
        $process = new Process(['git', 'tag', '-a', 'v1phpunittest', '-m', 'test version v1test']);
        $process->mustRun();

        $command = $application->find('app:step:info');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Delete test tag.
        $process = new Process(['git', 'tag', '-d', 'v1phpunittest']);
        $process->mustRun();

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('v1phpunittest', $output);
    }
}