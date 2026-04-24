<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Wallet>
 */
class WalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wallet::class);
    }

    /**
     * @return list<Wallet>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('wallet')
            ->andWhere('wallet.user = :user')
            ->setParameter('user', $user)
            ->orderBy('wallet.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndUser(int $id, User $user): ?Wallet
    {
        return $this->createQueryBuilder('wallet')
            ->andWhere('wallet.id = :id')
            ->andWhere('wallet.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

