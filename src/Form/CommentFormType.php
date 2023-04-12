<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Constraints\Image;

class CommentFormType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text')
            ->add('photo', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Image(['maxSize' => '1024k'])
                ]
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'empty_data' => function (FormInterface $form) {
                $user = $this->security->getUser();
                if (!$user) {
                    throw new UserNotFoundException();
                }
                return new Comment(
                    author: $user->getUserFirstName(),
                    text: $form['text']->getData(),
                    email: $user->getEmail(),
                );
            }
        ]);
    }
}
