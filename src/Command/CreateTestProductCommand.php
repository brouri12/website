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
    name: 'app:create-test-product',
    description: 'Crée un produit de test avec des tailles',
)]
class CreateTestProductCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Créer une catégorie si elle n'existe pas
        $categorie = $this->entityManager->getRepository(Categorie::class)->findOneBy(['nom_categorie' => 'Vêtements']);
        if (!$categorie) {
            $categorie = new Categorie();
            $categorie->setNomCategorie('Vêtements');
            $categorie->setDescriptionCategorie('Vêtements de tous types');
            $this->entityManager->persist($categorie);
        }

        // Créer le produit
        $produit = new Produit();
        $produit->setNomProduit('T-shirt Premium');
        $produit->setDescription('T-shirt en coton bio de haute qualité, confortable et durable. Parfait pour tous les jours.');
        $produit->setPrixUnitaire('29.99');
        $produit->setDateAjout(new \DateTime());
        $produit->setStatut('disponible');
        $produit->setCategorie($categorie);

        $this->entityManager->persist($produit);

        // Ajouter les tailles
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $quantities = [5, 15, 25, 20, 15, 10];

        for ($i = 0; $i < count($sizes); $i++) {
            $produitSize = new ProduitSize();
            $produitSize->setSize($sizes[$i]);
            $produitSize->setQuantite($quantities[$i]);
            $produitSize->setProduit($produit);

            $this->entityManager->persist($produitSize);
        }

        // Calculer le stock total
        $totalStock = array_sum($quantities);
        $produit->setStockTotal($totalStock);

        $this->entityManager->flush();

        $io->success('Produit de test créé avec succès!');
        $io->text('Nom: T-shirt Premium');
        $io->text('Prix: 29.99 TND');
        $io->text('Tailles disponibles: XS(5), S(15), M(25), L(20), XL(15), XXL(10)');

        return Command::SUCCESS;
    }
}
