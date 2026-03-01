<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserBanService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function applyBan(User $admin, User $target, int $durationValue, string $durationUnit, ?string $reason = null): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only admins can apply bans');
        }
        if ($admin->getId() === $target->getId()) {
            throw new AccessDeniedException('Admins cannot ban themselves');
        }
        if (in_array('ROLE_ADMIN', $target->getRoles(), true)) {
            throw new AccessDeniedException('Banning other admins is not allowed');
        }
        if ($durationValue <= 0) {
            throw new \InvalidArgumentException('Invalid ban duration');
        }

        $now = new \DateTimeImmutable();
        $intervalSpec = match (strtolower($durationUnit)) {
            'minutes', 'minute', 'min', 'm' => 'PT' . $durationValue . 'M',
            'hours', 'hour', 'h' => 'PT' . $durationValue . 'H',
            'days', 'day', 'd' => 'P' . $durationValue . 'D',
            default => throw new \InvalidArgumentException('Unsupported duration unit'),
        };
        $interval = new \DateInterval($intervalSpec);
        $until = (new \DateTime())->add($interval);

        $target->setBannedUntil($until);
        $target->setBanReason($reason);
        $this->entityManager->persist($target);
        $this->entityManager->flush();
    }

    public function removeBan(User $admin, User $target): void
    {
        if ($admin->getId() === $target->getId()) {
            throw new AccessDeniedException('Admins cannot unban themselves');
        }

        $target->setBannedUntil(null);
        $target->setBanReason(null);
        $this->entityManager->persist($target);
        $this->entityManager->flush();
    }

    public function computeRemaining(User $user): string
    {
        return $user->getRemainingBanTime();
    }
}
