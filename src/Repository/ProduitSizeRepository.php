<?php

namespace App\Repository;

use App\Entity\ProduitSize;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitSizeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitSize::class);
    }
    // Ajoute ici des méthodes personnalisées si besoin
} 