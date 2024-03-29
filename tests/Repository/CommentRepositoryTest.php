<?php

namespace App\Tests\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommentRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    private CommentRepository $commentRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->commentRepository = $this->entityManager
            ->getRepository(Comment::class);
    }

    public function testCountOldRejected(): void
    {
        $oldRejected = $this->commentRepository->countOldRejected();

        $this->assertSame(2, $oldRejected);
    }

    public function testDeleteOldRejected(): void
    {
        $this->commentRepository->deleteOldRejected();

        $oldRejected = $this->commentRepository->countOldRejected();

        $this->assertSame(0, $oldRejected);
    }

    public function testGetCommentPaginator(): void
    {
        $conference = $this->entityManager
            ->getRepository(Conference::class)
            ->findOneBy(['slug' => 'berlin-2021']);

        $paginator = $this->commentRepository
            ->getCommentPaginator($conference, 2);


        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertCount(1, $paginator->getQuery()->getResult());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Doing this is recommended to avoid memory leaks.
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
