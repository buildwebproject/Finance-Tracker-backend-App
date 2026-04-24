<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSecuritySettingsLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSecuritySettingsLog>
 */
class UserSecuritySettingsLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSecuritySettingsLog::class);
    }

    public function countRecentByUserAndAction(User $user, string $action, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->andWhere('l.action = :action')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('action', $action)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
