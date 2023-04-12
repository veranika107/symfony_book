<?php

namespace App\Tests\Form;

use App\Entity\Comment;
use App\Entity\User;
use App\Form\CommentFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Test\TypeTestCase;

class CommentFormTypeTest extends TypeTestCase
{
    private Security $security;

    public function setUp(): void
    {
        $this->security = $this->createMock(Security::class);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
            new PreloadedExtension(
                [
                new CommentFormType($this->security),
                ],[]
            ),
        ];
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(type: CommentFormType::class);
        $this->assertArrayHasKey('text', $form->all());
        $this->assertArrayHasKey('photo', $form->all());
    }

    public function testSubmitValidData()
    {
        $user = new User(email: 'user@example.com', userFirstName: 'User');
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $formData = ['text' => 'Text'];
        $form = $this->factory->create(type: CommentFormType::class);
        $form->submit($formData);
        $comment = $form->getData();

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertInstanceOf(Comment::class, $comment);
    }
}
