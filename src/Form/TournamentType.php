<?php
// src/Form/TournamentType.php

namespace App\Form;

use App\Entity\Tournament;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\TournamentLocation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tournament_name', TextType::class, [
                'label' => 'Tournament Name',
                'required' => true,
            ])
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
                'label' => 'Game',
                'required' => true,
            ])
            ->add('start_date', DateType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('end_date', DateType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Organizer',
                'required' => true,
            ])
            ->add('prize_pool', MoneyType::class, [
                'label' => 'Prize Pool',
                'currency' => 'USD',
                'required' => false,
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Location Type',
                'choices' => [
                    'Online' => TournamentLocation::ONLINE->value,
                    'Offline' => TournamentLocation::OFFLINE->value,
                ],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}