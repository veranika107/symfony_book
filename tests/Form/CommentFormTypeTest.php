<?php

namespace App\Tests\Form;

use App\Entity\Comment;
use App\Form\CommentFormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Test\TypeTestCase;

class CommentFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(type: CommentFormType::class);
        $this->assertArrayHasKey('author', $form->all());
        $this->assertArrayHasKey('text', $form->all());
        $this->assertArrayHasKey('email', $form->all());
        $this->assertArrayHasKey('photo', $form->all());
    }

    public function testSubmitValidData()
    {
        $formData = ['author' => 'David', 'text' => 'Text', 'email' => 'david@example.com'];
        $form = $this->factory->create(type: CommentFormType::class);
        $form->submit($formData);
        $comment = $form->getData();

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertInstanceOf(Comment::class, $comment);
    }
}
