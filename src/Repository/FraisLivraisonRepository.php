<?php

namespace App\Repository;

use App\Entity\FraisLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FraisLivraison>
 */
class FraisLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FraisLivraison::class);
    }

    /**
     * Trouve les frais de livraison actifs
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('f.montant', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les frais de livraison par zone
     */
    public function findByZone(string $zone): ?FraisLivraison
    {
        return $this->createQueryBuilder('f')
            ->where('f.nomZone = :zone')
            ->andWhere('f.actif = :actif')
            ->setParameter('zone', $zone)
            ->setParameter('actif', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 