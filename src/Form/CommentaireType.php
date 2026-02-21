<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints as Assert;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Your Comment',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Add your comment here... (supports emojis)',
                    'class' => 'w-full px-3 py-2 bg-[#1a1a24] border border-[#D4AF37]/20 rounded-lg text-white focus:outline-none focus:border-[#D4AF37]'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Comment cannot be empty']),
                    new Assert\Length([
                        'min' => 1,
                        'max' => 5000,
                        'minMessage' => 'Comment must be at least 1 character',
                        'maxMessage' => 'Comment must not exceed 5000 characters'
                    ])
                ]
            ])
            ->add('gif_url', UrlType::class, [
                'label' => 'GIF URL (Optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Paste GIF URL (e.g., from Giphy)',
                    'class' => 'w-full px-3 py-2 bg-[#1a1a24] border border-[#D4AF37]/20 rounded-lg text-white focus:outline-none focus:border-[#D4AF37]'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}
