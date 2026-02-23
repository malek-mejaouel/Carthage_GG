<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'maxlength' => 150,
                    'placeholder' => 'Product name',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 5000,
                    'placeholder' => 'Describe the product',
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('discount', NumberType::class, [
                'label' => 'Discount (%)',
                'scale' => 2,
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                    'placeholder' => '0 – 100',
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'attr' => [
                    'min' => 0,
                    'step' => '1',
                    'inputmode' => 'numeric',
                    'placeholder' => '0',
                ],
            ])
            ->add('sku', TextType::class, [
                'label' => 'SKU',
                'required' => false,
                'attr' => [
                    'maxlength' => 50,
                    'pattern' => '^[A-Za-z0-9_-]{3,50}$',
                    'placeholder' => 'e.g. HOODIE-XL-001',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Product Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new FileConstraint([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WEBP).',
                    ])
                ],
                'attr' => [
                    'accept' => 'image/png,image/jpeg,image/webp',
                ],
            ])
            ->add('averageRating', NumberType::class, [
                'label' => 'Average Rating',
                'scale' => 1,
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 5,
                    'step' => '0.1',
                    'inputmode' => 'decimal',
                    'placeholder' => '0 – 5',
                ],
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Featured',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                ],
                'placeholder' => 'Choose status',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'required' => false,
                'placeholder' => 'None',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
