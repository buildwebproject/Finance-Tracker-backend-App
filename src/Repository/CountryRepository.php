<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Country>
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function findOneByIso2Code(string $iso2Code): ?Country
    {
        return $this->findOneBy(['iso2Code' => strtoupper(trim($iso2Code))]);
    }

    /**
     * @return list<Country>
     */
    public function findForApi(?string $search = null, bool $activeOnly = true, int $limit = 300): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        if ($activeOnly) {
            $queryBuilder->andWhere('c.isActive = :isActive')
                ->setParameter('isActive', true);
        }

        $search = null === $search ? null : trim($search);
        if (null !== $search && '' !== $search) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'LOWER(c.name) LIKE :search',
                    'LOWER(c.iso2Code) LIKE :search',
                    'LOWER(c.iso3Code) LIKE :search',
                    'LOWER(c.dialCode) LIKE :search',
                    'LOWER(c.currencyCode) LIKE :search',
                    'LOWER(c.currencyIcon) LIKE :search'
                )
            )->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $queryBuilder->setMaxResults(max(1, min($limit, 500)));

        /** @var list<Country> $result */
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }
}
