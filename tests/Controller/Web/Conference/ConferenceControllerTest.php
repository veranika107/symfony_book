<?php

namespace App\Tests\Controller\Web\Conference;

use App\Entity\Comment;
use App\Notification\CommentReviewNotification;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Notifier\NotifierInterface;

class ConferenceControllerTest extends WebTestCase
{
    public function testRedirectToEnIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertInstanceOf(RedirectResponse::class, $client->getResponse());
        $this->assertTrue($client->getResponse()->isRedirect('/en/'));
    }

    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testConferencePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');

        $this->assertCount(3, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There is one comment")');
    }

    public function testCommentSubmission(): void
    {
        $client = static::createClient();
        $userRepository = self::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'user@example.com']);
        $client->loginUser($testUser);

        $client->request('GET', '/en/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment_form[text]' => 'Some feedback from an automated functional test',
            'comment_form[photo]' => dirname(__DIR__, 4).'/public/images/under-construction.gif',
        ]);

        $email = $testUser->getEmail();

        // Simulate comment validation.
        /** @var Comment $comment */
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $photo = $comment->getPhotoFilename();
        $photoNameWithoutExtension = basename($photo, '.gif');
        $this->assertTrue(file_exists(dirname(__DIR__, 4) . '/public/uploads/photos/' . $photo));
        $this->assertTrue(ctype_xdigit($photoNameWithoutExtension));

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

    public function testSendingNotification(): void
    {
        $notifier = self::getContainer()->get(NotifierInterface::class);

        $comment = new Comment(author: 'Chad', text: 'This was awesome', email: 'chad@example.com');
        $notification = new CommentReviewNotification($comment, '/some-url');

        $notifier->send($notification, ...$notifier->getAdminRecipients());
        $this->assertNotificationCount(1);
        $notification1 = $this->getNotifierMessage(0);
        $this->assertNotificationTransportIsEqual($notification1, 'slack');
        $this->assertEmailCount(1);
    }
}