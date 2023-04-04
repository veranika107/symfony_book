<?php

namespace App\Tests\Command;

use App\Command\CommentCleanupCommand;
use App\Repository\CommentRepository;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CommentCleanupCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    private CommentRepository $commentRepository;

    private CommentCleanupCommand $command;

    public function setUp(): void
    {
        $this->commentRepository = $this->createMock(CommentRepository::class);
        $this->command = new CommentCleanupCommand($this->commentRepository);

        (new Application())->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute()
    {
        $this->commentRepository->expects($this->once())
            ->method('deleteOldRejected')
            ->willReturn(2);

        $this->commandTester->execute([
            'command' => $this->command->getName()
            ]
        );

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Deleted "2" old rejected/spam comments.', $output);
    }

    public function testExecuteWithDryRun()
    {
        $this->commentRepository->expects($this->once())
            ->method('countOldRejected')
            ->willReturn(3);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--dry-run' => true,
            ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Deleted "3" old rejected/spam comments.', $output);
    }
}