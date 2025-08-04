<?php

namespace App\Command;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Paiement;
use App\Entity\Panier;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-order',
    description: 'Create a test order to verify the order creation process',
)]
class CreateTestOrderCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PanierService $panierService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer le premier client
        $client = $this->entityManager->getRepository(\App\Entity\Client::class)->findOneBy([]);
        if (!$client) {
            $io->error('Aucun client trouvé dans la base de données.');
            return Command::FAILURE;
        }

        // Récupérer les articles du panier du client
        $panierItems = $this->entityManager->getRepository(Panier::class)->findBy(['client' => $client]);
        
        if (empty($panierItems)) {
            $io->error('Le panier du client est vide.');
            return Command::FAILURE;
        }

        try {
            // Calculer les totaux
            $calculs = $this->panierService->calculerTotalPanier($panierItems);

            // Créer la commande
            $commande = new Commande();
            $commande->setClient($client);
            $commande->setDateCommande(new \DateTime());
            $commande->setStatutCommande('en_attente');
            $commande->setMontantTotal($calculs['total_final']);
            $commande->setAdresseLivraison('123 Rue Test, 1000 Tunis');
            $commande->setAdresseFacturation('123 Rue Test, 1000 Tunis');
            $commande->setMethodePaiement('carte_bancaire');
            $commande->setDateLivraisonEstimee((new \DateTime())->modify('+3 days'));

            $this->entityManager->persist($commande);

            // Créer les lignes de commande
            foreach ($panierItems as $panierItem) {
                $ligneCommande = new LigneCommande();
                $ligneCommande->setCommande($commande);
                $ligneCommande->setProduit($panierItem->getProduit());
                $ligneCommande->setQuantite($panierItem->getQuantite());
                $ligneCommande->setPrixUnitaire($panierItem->getProduit()->getPrixUnitaire());
                $ligneCommande->setSousTotal($panierItem->getQuantite() * $panierItem->getProduit()->getPrixUnitaire());
                $ligneCommande->setTaille($panierItem->getTaille());

                $this->entityManager->persist($ligneCommande);
            }

            // Créer le paiement
            $paiement = new Paiement();
            $paiement->setMontant($calculs['total_final']);
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setStatutPaiement('en_attente');
            $paiement->setIdTransaction('TXN_' . uniqid());
            $paiement->setMethodePaiement('carte_bancaire');
            $paiement->setCommande($commande);

            $this->entityManager->persist($paiement);

            // Mettre à jour les stocks
            $this->panierService->mettreAJourStocks($panierItems);

            // Vider le panier
            foreach ($panierItems as $panierItem) {
                $this->entityManager->remove($panierItem);
            }

            $this->entityManager->flush();

            $io->success(sprintf(
                'Commande créée avec succès ! ID: %d, Montant: %s TND',
                $commande->getId(),
                $commande->getMontantTotal()
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de la commande: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 