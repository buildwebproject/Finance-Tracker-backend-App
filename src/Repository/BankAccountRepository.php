<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BankAccount;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BankAccount>
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    /**
     * @return list<BankAccount>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('bank')
            ->andWhere('bank.user = :user')
            ->setParameter('user', $user)
            ->orderBy('bank.isDefault', 'DESC')
            ->addOrderBy('bank.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndUser(int $id, User $user): ?BankAccount
    {
        return $this->createQueryBuilder('bank')
            ->andWhere('bank.id = :id')
            ->andWhere('bank.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultByUser(User $user): ?BankAccount
    {
        return $this->createQueryBuilder('bank')
            ->andWhere('bank.user = :user')
            ->andWhere('bank.isDefault = :isDefault')
            ->setParameter('user', $user)
            ->setParameter('isDefault', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('bank')
            ->select('COUNT(bank.id)')
            ->andWhere('bank.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

