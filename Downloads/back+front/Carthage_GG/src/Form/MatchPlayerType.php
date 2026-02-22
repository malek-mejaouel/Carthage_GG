<?php
// src/Form/MatchPlayerType.php

namespace App\Form;

use App\Entity\MatchPlayer;
use App\Entity\GameMatch;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class MatchPlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('match', EntityType::class, [
                'class' => GameMatch::class,
                'choice_label' => function ($match) {
                    return $match->getTeamA()?->getTeamName() . ' vs ' . $match->getTeamB()?->getTeamName();
                },
                'label' => 'Match',
                'required' => true,
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Player',
                'required' => true,
            ])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'team_name',
                'label' => 'Team',
                'required' => true,
            ])
            ->add('role', TextType::class, [
                'label' => 'Role (e.g., Captain, Player)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MatchPlayer::class,
        ]);
    }
}