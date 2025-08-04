<?php

namespace App\Repository;

use App\Entity\Taxe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Taxe>
 */
class TaxeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Taxe::class);
    }

    /**
     * Trouve les taxes actives
     */
    public function findActives(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('t.taux', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une taxe par nom
     */
    public function findByNom(string $nom): ?Taxe
    {
        return $this->createQueryBuilder('t')
            ->where('t.nom = :nom')
            ->andWhere('t.actif = :actif')
            ->setParameter('nom', $nom)
            ->setParameter('actif', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 