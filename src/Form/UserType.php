<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                ],
                'attr' => [
                    'placeholder' => 'John',
                    'class' => 'w-full pl-10 pr-4 py-2 rounded-md bg-[#1e1e2e] border border-[#D4AF37]/20 focus:outline-none focus:border-[#D4AF37]/50',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                ],
                'attr' => [
                    'placeholder' => 'Doe',
                    'class' => 'w-full pl-10 pr-4 py-2 rounded-md bg-[#1e1e2e] border border-[#D4AF37]/20 focus:outline-none focus:border-[#D4AF37]/50',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
                'attr' => [
                    'placeholder' => 'your@email.com',
                    'class' => 'w-full pl-10 pr-4 py-2 rounded-md bg-[#1e1e2e] border border-[#D4AF37]/20 focus:outline-none focus:border-[#D4AF37]/50',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'class' => 'w-full pl-10 pr-4 py-2 rounded-md bg-[#1e1e2e] border border-[#D4AF37]/20 focus:outline-none focus:border-[#D4AF37]/50',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'class' => 'w-full pl-10 pr-4 py-2 rounded-md bg-[#1e1e2e] border border-[#D4AF37]/20 focus:outline-none focus:border-[#D4AF37]/50',
                    ],
                ],
                'invalid_message' => 'The passwords must match.',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 6, 'max' => 255]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Viewer' => 'ROLE_VIEWER',
                    'Player' => 'ROLE_PLAYER',
                    'Coach' => 'ROLE_COACH',
                    'Organiser' => 'ROLE_ORGANISER',
                ],
                'multiple' => false,
                'expanded' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'class' => 'w-full pl-10 pr-4 py-2 rounded-md bg-[#1e1e2e] border border-[#D4AF37]/20 focus:outline-none focus:border-[#D4AF37]/50 appearance-none',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
