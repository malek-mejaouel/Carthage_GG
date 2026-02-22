<?php

namespace App\Form;

use App\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class NewsType extends AbstractType
{
    // Static list of news categories
    const CATEGORIES = [
        'Tournaments' => 'tournaments',
        'Matches' => 'matches',
        'Players' => 'players',
        'Events' => 'events',
        'Updates' => 'updates',
        'Announcements' => 'announcements',
        'Results' => 'results',
        'Statistics' => 'statistics',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-[#1a1a24] border border-[#D4AF37]/20 rounded-lg text-white focus:outline-none focus:border-[#D4AF37]'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                    new Length(['min' => 3, 'max' => 255, 'minMessage' => 'Title must be at least 3 characters', 'maxMessage' => 'Title cannot exceed 255 characters'])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'rows' => 6,
                    'class' => 'w-full px-3 py-2 bg-[#1a1a24] border border-[#D4AF37]/20 rounded-lg text-white focus:outline-none focus:border-[#D4AF37]'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Content is required']),
                    new Length(['min' => 10, 'minMessage' => 'Content must be at least 10 characters'])
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Category',
                'choices' => self::CATEGORIES,
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-[#1a1a24] border border-[#D4AF37]/20 rounded-lg text-white focus:outline-none focus:border-[#D4AF37]'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Category is required'])
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Featured Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-[#1a1a24] border border-[#D4AF37]/20 rounded-lg text-white focus:outline-none focus:border-[#D4AF37]'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WebP, or GIF)',
                    ])
                ]
            ])
            ->add('date_publication', DateTimeType::class, [
                'label' => 'Publication Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-3 py-2 bg-[#1a1a24] border border-[#D4AF37]/20 rounded-lg text-white focus:outline-none focus:border-[#D4AF37]'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}

