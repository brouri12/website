<?php

namespace App\Command;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\ProduitSize;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-products',
    description: 'Créer des produits de test avec leurs tailles',
)]
class CreateTestProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Créer des catégories si elles n'existent pas
        $categories = $this->createCategories();

        // Produits de test
        $products = [
            [
                'nom' => 'T-shirt Basic',
                'description' => 'T-shirt en coton 100% bio, confortable et respirant',
                'prix' => 25.99,
                'image' => 'tshirt-basic.jpg',
                'categorie' => $categories['vêtements'],
                'sizes' => [
                    ['taille' => 'S', 'quantite' => 10],
                    ['taille' => 'M', 'quantite' => 15],
                    ['taille' => 'L', 'quantite' => 12],
                    ['taille' => 'XL', 'quantite' => 8],
                ]
            ],
            [
                'nom' => 'Jeans Slim Fit',
                'description' => 'Jeans moderne avec coupe slim, parfait pour toutes les occasions',
                'prix' => 89.99,
                'image' => 'jeans-slim.jpg',
                'categorie' => $categories['vêtements'],
                'sizes' => [
                    ['taille' => '30', 'quantite' => 5],
                    ['taille' => '32', 'quantite' => 8],
                    ['taille' => '34', 'quantite' => 10],
                    ['taille' => '36', 'quantite' => 6],
                ]
            ],
            [
                'nom' => 'Sneakers Urban',
                'description' => 'Sneakers tendance avec semelle confortable, idéal pour la ville',
                'prix' => 129.99,
                'image' => 'sneakers-urban.jpg',
                'categorie' => $categories['chaussures'],
                'sizes' => [
                    ['taille' => '39', 'quantite' => 7],
                    ['taille' => '40', 'quantite' => 12],
                    ['taille' => '41', 'quantite' => 15],
                    ['taille' => '42', 'quantite' => 10],
                    ['taille' => '43', 'quantite' => 8],
                ]
            ],
            [
                'nom' => 'Sac à dos Laptop',
                'description' => 'Sac à dos avec compartiment spécialisé pour ordinateur portable',
                'prix' => 59.99,
                'image' => 'sac-laptop.jpg',
                'categorie' => $categories['accessoires'],
                'sizes' => [
                    ['taille' => '15"', 'quantite' => 20],
                    ['taille' => '17"', 'quantite' => 15],
                ]
            ],
            [
                'nom' => 'Montre Connectée',
                'description' => 'Montre connectée avec suivi d\'activité et notifications',
                'prix' => 199.99,
                'image' => 'montre-connectee.jpg',
                'categorie' => $categories['accessoires'],
                'sizes' => [
                    ['taille' => 'S', 'quantite' => 25],
                    ['taille' => 'M', 'quantite' => 30],
                    ['taille' => 'L', 'quantite' => 20],
                ]
            ],
            [
                'nom' => 'Pull Hiver',
                'description' => 'Pull chaud et confortable pour l\'hiver',
                'prix' => 45.99,
                'image' => 'pull-hiver.jpg',
                'categorie' => $categories['vêtements'],
                'sizes' => [
                    ['taille' => 'S', 'quantite' => 8],
                    ['taille' => 'M', 'quantite' => 12],
                    ['taille' => 'L', 'quantite' => 10],
                    ['taille' => 'XL', 'quantite' => 6],
                ]
            ],
            [
                'nom' => 'Bottes Cuir',
                'description' => 'Bottes en cuir véritable, élégantes et durables',
                'prix' => 159.99,
                'image' => 'bottes-cuir.jpg',
                'categorie' => $categories['chaussures'],
                'sizes' => [
                    ['taille' => '38', 'quantite' => 5],
                    ['taille' => '39', 'quantite' => 8],
                    ['taille' => '40', 'quantite' => 10],
                    ['taille' => '41', 'quantite' => 7],
                ]
            ],
            [
                'nom' => 'Casquette Baseball',
                'description' => 'Casquette de baseball classique, ajustable',
                'prix' => 19.99,
                'image' => 'casquette-baseball.jpg',
                'categorie' => $categories['accessoires'],
                'sizes' => [
                    ['taille' => 'One Size', 'quantite' => 50],
                ]
            ]
        ];

        $createdCount = 0;
        foreach ($products as $productData) {
            $product = new Produit();
            $product->setNomProduit($productData['nom']);
            $product->setDescription($productData['description']);
            $product->setPrixUnitaire($productData['prix']);
            $product->setImageProduit($productData['image']);
            $product->setCategorie($productData['categorie']);
            $product->setDateAjout(new \DateTime());
            $product->setStatut('disponible');
            $totalStock = array_sum(array_column($productData['sizes'], 'quantite'));
            $product->setStockTotal($totalStock);

            $this->entityManager->persist($product);

            // Créer les tailles pour ce produit
            foreach ($productData['sizes'] as $sizeData) {
                $produitSize = new ProduitSize();
                $produitSize->setProduit($product);
                $produitSize->setSize($sizeData['taille']);
                $produitSize->setQuantite($sizeData['quantite']);

                $this->entityManager->persist($produitSize);
            }

            $createdCount++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d produits créés avec succès avec leurs tailles !', $createdCount));

        return Command::SUCCESS;
    }

    private function createCategories(): array
    {
        $categories = [];
        $categoryNames = ['Vêtements', 'Chaussures', 'Accessoires'];

        foreach ($categoryNames as $name) {
            $existingCategory = $this->entityManager->getRepository(Categorie::class)->findOneBy(['nom_categorie' => $name]);
            
            if (!$existingCategory) {
                $category = new Categorie();
                $category->setNomCategorie($name);
                $this->entityManager->persist($category);
                $this->entityManager->flush();
                $categories[strtolower($name)] = $category;
            } else {
                $categories[strtolower($name)] = $existingCategory;
            }
        }

        return $categories;
    }
} 