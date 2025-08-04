<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    //    /**
    //     * @return Produit[] Returns an array of Produit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Trouve les produits avec filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->leftJoin('p.produitSizes', 'ps')
            ->addSelect('c', 'ps');

        // Filtre par catégorie (IN)
        if (!empty($filters['category'])) {
            $qb->andWhere('c.nom_categorie IN (:categories)')
               ->setParameter('categories', $filters['category']);
        }
        // Filtre par prix (IN)
        if (!empty($filters['price'])) {
            $orX = $qb->expr()->orX();
            foreach ($filters['price'] as $i => $price) {
                switch ($price) {
                    case '0-50':
                        $orX->add($qb->expr()->lte('p.prix_unitaire', 50));
                        break;
                    case '50-100':
                        $orX->add($qb->expr()->andX($qb->expr()->gt('p.prix_unitaire', 50), $qb->expr()->lte('p.prix_unitaire', 100)));
                        break;
                    case '100-200':
                        $orX->add($qb->expr()->andX($qb->expr()->gt('p.prix_unitaire', 100), $qb->expr()->lte('p.prix_unitaire', 200)));
                        break;
                    case '200-500':
                        $orX->add($qb->expr()->andX($qb->expr()->gt('p.prix_unitaire', 200), $qb->expr()->lte('p.prix_unitaire', 500)));
                        break;
                    case '500+':
                        $orX->add($qb->expr()->gt('p.prix_unitaire', 500));
                        break;
                }
            }
            $qb->andWhere($orX);
        }
        // Filtre par taille (IN)
        if (!empty($filters['size'])) {
            $qb->andWhere('ps.size IN (:sizes) AND ps.quantite > 0')
               ->setParameter('sizes', $filters['size']);
        }
        // Filtre par marque (IN)
        if (!empty($filters['brand'])) {
            $orX = $qb->expr()->orX();
            foreach ($filters['brand'] as $brand) {
                $orX->add($qb->expr()->like('p.nomProduit', $qb->expr()->literal('%' . $brand . '%')));
            }
            $qb->andWhere($orX);
        }
        // Filtre par genre (IN)
        if (!empty($filters['genre'])) {
            $qb->andWhere('p.genre IN (:genres)')
               ->setParameter('genres', $filters['genre']);
        }

        // Tri
        switch ($filters['sort'] ?? 'newest') {
            case 'price-low':
                $qb->orderBy('p.prix_unitaire', 'ASC');
                break;
            case 'price-high':
                $qb->orderBy('p.prix_unitaire', 'DESC');
                break;
            case 'popularity':
                // Vous pouvez ajouter un champ de popularité plus tard
                $qb->orderBy('p.date_ajout', 'DESC');
                break;
            case 'rating':
                // Vous pouvez ajouter un champ de note plus tard
                $qb->orderBy('p.date_ajout', 'DESC');
                break;
            default:
                $qb->orderBy('p.date_ajout', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les produits populaires (les plus récents)
     */
    public function findPopularProducts(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->orderBy('p.date_ajout', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les produits par catégorie
     */
    public function findByCategory(string $categoryName, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->where('c.nom_categorie = :category')
            ->setParameter('category', $categoryName)
            ->orderBy('p.date_ajout', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les produits en promotion
     */
    public function findPromotedProducts(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.promotions', 'prom')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c', 'prom')
            ->where('prom.id IS NOT NULL')
            ->orderBy('p.date_ajout', 'DESC');
            
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les marques disponibles (extrait des noms de produits)
     */
    public function findAvailableBrands(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.nom_produit')
            ->where('p.nom_produit LIKE :brand1 OR p.nom_produit LIKE :brand2 OR p.nom_produit LIKE :brand3 OR p.nom_produit LIKE :brand4 OR p.nom_produit LIKE :brand5')
            ->setParameter('brand1', '%Nike%')
            ->setParameter('brand2', '%Adidas%')
            ->setParameter('brand3', '%Puma%')
            ->setParameter('brand4', '%Reebok%')
            ->setParameter('brand5', '%Converse%');

        $results = $qb->getQuery()->getResult();
        
        $brands = [];
        foreach ($results as $result) {
            $productName = $result['nom_produit'];
            if (stripos($productName, 'Nike') !== false) $brands['nike'] = 'Nike';
            if (stripos($productName, 'Adidas') !== false) $brands['adidas'] = 'Adidas';
            if (stripos($productName, 'Puma') !== false) $brands['puma'] = 'Puma';
            if (stripos($productName, 'Reebok') !== false) $brands['reebok'] = 'Reebok';
            if (stripos($productName, 'Converse') !== false) $brands['converse'] = 'Converse';
        }
        
        return array_values($brands);
    }

    /**
     * Trouve les tailles disponibles
     */
    public function findAvailableSizes(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.produitSizes', 'ps')
            ->select('DISTINCT ps.size')
            ->where('ps.size IS NOT NULL')
            ->andWhere('ps.quantite > 0')
            ->orderBy('ps.size', 'ASC');

        $results = $qb->getQuery()->getResult();
        
        return array_column($results, 'size');
    }
}
