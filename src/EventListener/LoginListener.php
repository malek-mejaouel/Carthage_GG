<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListener implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        try {
            $user = $event->getAuthenticationToken()->getUser();

            if ($user instanceof User) {
                // Refresh the user entity to ensure it's managed
                $user = $this->entityManager->getRepository(User::class)->find($user->getId());
                
                if ($user) {
                    $user->setIsActive(true);
                    $user->setLastLoginAt(new \DateTime());
                    $user->setUpdatedAt(new \DateTime());

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }
            }
        } catch (\Exception $e) {
            // Silently fail to prevent login from breaking
            // Log error if needed: error_log('Login listener error: ' . $e->getMessage());
        }
    }
}
