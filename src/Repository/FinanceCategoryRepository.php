<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FinanceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FinanceCategory>
 */
class FinanceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceCategory::class);
    }

    public function findOneActiveById(int $id): ?FinanceCategory
    {
        return $this->createQueryBuilder('fc')
            ->andWhere('fc.id = :id')
            ->andWhere('fc.isActive = :isActive')
            ->setParameter('id', $id)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<FinanceCategory>
     */
    public function findForApi(?string $search = null, bool $activeOnly = true, int $limit = 300): array
    {
        $qb = $this->createQueryBuilder('fc')
            ->orderBy('fc.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('fc.isActive = :isActive')->setParameter('isActive', true);
        }

        $search = null === $search ? null : trim($search);
        if (null !== $search && '' !== $search) {
            $qb
                ->andWhere('LOWER(fc.name) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $qb->setMaxResults(max(1, min($limit, 500)));

        /** @var list<FinanceCategory> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @return list<FinanceCategory>
     */
    public function findActiveOrdered(): array
    {
        /** @var list<FinanceCategory> $result */
        $result = $this->createQueryBuilder('fc')
            ->andWhere('fc.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('fc.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
