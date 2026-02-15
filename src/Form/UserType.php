<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter username',
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter email address',
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter password (leave blank to keep current)',
                ],
                'empty_data' => '',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'First Name',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter first name',
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter last name',
                ]
            ])
            ->add('avatar', FileType::class, [
                'label' => 'Avatar',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, WebP)',
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*',
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Roles',
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                    'Moderator' => 'ROLE_MODERATOR',
                    'Player' => 'ROLE_PLAYER',
                    'Team Captain' => 'ROLE_TEAM_CAPTAIN',
                ],
                'multiple' => true,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('is_active', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
