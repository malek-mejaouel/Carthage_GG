<?php
// src/Form/MatchType.php

namespace App\Form;

use App\Entity\GameMatch;
use App\Entity\Tournament;
use App\Entity\Game;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class MatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tournament', EntityType::class, [
                'class' => Tournament::class,
                'choice_label' => 'tournament_name',
                'label' => 'Tournament',
                'required' => false,
            ])
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
                'label' => 'Game',
                'required' => false,
            ])
            ->add('teamA', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'team_name',
                'label' => 'Team A',
                'required' => false,
            ])
            ->add('teamB', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'team_name',
                'label' => 'Team B',
                'required' => false,
            ])
            ->add('match_date', DateTimeType::class, [
                'label' => 'Match Date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('score_team_a', IntegerType::class, [
                'label' => 'Score Team A',
                'required' => false,
            ])
            ->add('score_team_b', IntegerType::class, [
                'label' => 'Score Team B',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameMatch::class,
        ]);
    }
}