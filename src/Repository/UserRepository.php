<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function findAllUsers(): array
    {
        return $this->findAll();
    }

    public function findUsersPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->createQueryBuilder('u')
            ->orderBy('u.user_id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.user_id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.is_active = :active')
            ->setParameter('active', true)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInactiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.is_active = :active')
            ->setParameter('active', false)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByUsername(string $keyword): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByEmail(string $keyword): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCreatedDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.created_at >= :startDate AND u.created_at <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function updateUsername(User $user, string $newUsername, bool $flush = true): void
    {
        $user->setUsername($newUsername);
        $this->save($user, $flush);
    }

    public function updateEmail(User $user, string $newEmail, bool $flush = true): void
    {
        $user->setEmail($newEmail);
        $this->save($user, $flush);
    }

    public function updatePassword(User $user, string $hashedPassword, bool $flush = true): void
    {
        $user->setPassword($hashedPassword);
        $this->save($user, $flush);
    }

    public function updateProfile(User $user, ?string $firstName, ?string $lastName, ?string $avatar, bool $flush = true): void
    {
        $user->setFirstname($firstName);
        $user->setLastname($lastName);
        $user->setAvatar($avatar);
        $this->save($user, $flush);
    }

    public function activateUser(User $user, bool $flush = true): void
    {
        $user->setIsActive(true);
        $user->setUpdatedAt(new \DateTime());
        $this->save($user, $flush);
    }

    public function deactivateUser(User $user, bool $flush = true): void
    {
        $user->setIsActive(false);
        $user->setUpdatedAt(new \DateTime());
        $this->save($user, $flush);
    }

    public function updateLastLogin(User $user, bool $flush = true): void
    {
        $user->setLastLoginAt(new \DateTime());
        $this->save($user, $flush);
    }

    public function deleteById(int $id, bool $flush = true): bool
    {
        $user = $this->find($id);
        if ($user) {
            $this->remove($user, $flush);
            return true;
        }
        return false;
    }

    public function deleteMultiple(array $userIds, bool $flush = true): int
    {
        $count = 0;
        foreach ($userIds as $id) {
            if ($this->deleteById($id, false)) {
                $count++;
            }
        }
        if ($flush && $count > 0) {
            $this->getEntityManager()->flush();
        }
        return $count;
    }

    public function userExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function getRecentlyCreatedUsers(int $days = 7, int $limit = 10): array
    {
        $date = new \DateTime("-{$days} days");
        return $this->createQueryBuilder('u')
            ->andWhere('u.created_at >= :date')
            ->setParameter('date', $date)
            ->orderBy('u.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getRecentlyActiveUsers(int $days = 7, int $limit = 10): array
    {
        $date = new \DateTime("-{$days} days");
        return $this->createQueryBuilder('u')
            ->andWhere('u.last_login_at >= :date')
            ->setParameter('date', $date)
            ->orderBy('u.last_login_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
