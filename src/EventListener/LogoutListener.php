<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        try {
            $token = $event->getToken();
            
            if ($token) {
                $user = $token->getUser();

                if ($user instanceof User) {
                    // Refresh the user entity to ensure it's managed
                    $user = $this->entityManager->getRepository(User::class)->find($user->getId());
                    
                    if ($user) {
                        $user->setIsActive(false);
                        $user->setUpdatedAt(new \DateTime());

                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail to prevent logout from breaking
            // Log error if needed: error_log('Logout listener error: ' . $e->getMessage());
        }
    }
}
