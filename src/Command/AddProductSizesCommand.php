<?php

namespace App\Command;

use App\Entity\Produit;
use App\Entity\ProduitSize;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-product-sizes',
    description: 'Ajoute des tailles et quantités à un produit',
)]
class AddProductSizesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('product-id', InputArgument::REQUIRED, 'ID du produit')
            ->addArgument('sizes', InputArgument::REQUIRED, 'Tailles séparées par des virgules (ex: S,M,L,XL)')
            ->addArgument('quantities', InputArgument::REQUIRED, 'Quantités séparées par des virgules (ex: 10,15,20,5)')
            ->setHelp('Cette commande permet d\'ajouter des tailles et quantités à un produit existant.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $productId = $input->getArgument('product-id');
        $sizes = explode(',', $input->getArgument('sizes'));
        $quantities = explode(',', $input->getArgument('quantities'));

        // Vérifier que le produit existe
        $product = $this->entityManager->getRepository(Produit::class)->find($productId);
        if (!$product) {
            $io->error('Produit non trouvé avec l\'ID: ' . $productId);
            return Command::FAILURE;
        }

        // Vérifier que les tailles et quantités ont le même nombre
        if (count($sizes) !== count($quantities)) {
            $io->error('Le nombre de tailles doit correspondre au nombre de quantités');
            return Command::FAILURE;
        }

        $io->title('Ajout des tailles au produit: ' . $product->getNomProduit());

        // Supprimer les anciennes tailles
        $existingSizes = $this->entityManager->getRepository(ProduitSize::class)->findBy(['produit' => $product]);
        foreach ($existingSizes as $existingSize) {
            $this->entityManager->remove($existingSize);
        }

        // Ajouter les nouvelles tailles
        for ($i = 0; $i < count($sizes); $i++) {
            $size = trim($sizes[$i]);
            $quantity = (int) trim($quantities[$i]);

            $produitSize = new ProduitSize();
            $produitSize->setSize($size);
            $produitSize->setQuantite($quantity);
            $produitSize->setProduit($product);

            $this->entityManager->persist($produitSize);
            $io->text("Ajouté: Taille {$size} - Quantité {$quantity}");
        }

        $this->entityManager->flush();
        $io->success('Tailles ajoutées avec succès!');

        return Command::SUCCESS;
    }
}
