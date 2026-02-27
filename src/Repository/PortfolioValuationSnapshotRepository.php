<?php

namespace App\Repository;

use App\Entity\PortfolioValuationSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PortfolioValuationSnapshot>
 */
class PortfolioValuationSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioValuationSnapshot::class);
    }

    public function save(PortfolioValuationSnapshot $snapshot, bool $flush = false): void
    {
        $this->getEntityManager()->persist($snapshot);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return list<PortfolioValuationSnapshot>
     */
    public function findHistory(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.calculatedAt', 'ASC');

        if ($from !== null) {
            $qb->andWhere('p.calculatedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb->andWhere('p.calculatedAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }
}
