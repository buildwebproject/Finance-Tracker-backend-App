<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSecuritySettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSecuritySettings>
 */
class UserSecuritySettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSecuritySettings::class);
    }

    public function findOneByUser(User $user): ?UserSecuritySettings
    {
        return $this->findOneBy(['user' => $user]);
    }
}
