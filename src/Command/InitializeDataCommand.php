<?php

namespace App\Command;

use App\Entity\FraisLivraison;
use App\Entity\Taxe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-data',
    description: 'Initialise les données de base (frais de livraison et taxes)',
)]
class InitializeDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des données de base');

        // Initialiser les frais de livraison
        $this->initializeFraisLivraison($io);

        // Initialiser les taxes
        $this->initializeTaxes($io);

        $io->success('Données initialisées avec succès !');

        return Command::SUCCESS;
    }

    private function initializeFraisLivraison(SymfonyStyle $io): void
    {
        $io->section('Initialisation des frais de livraison');

        $fraisLivraisonData = [
            [
                'nomZone' => 'Tunis',
                'montant' => '5.00',
                'description' => 'Livraison standard à Tunis'
            ],
            [
                'nomZone' => 'Autres villes',
                'montant' => '9.99',
                'description' => 'Livraison standard vers les autres villes'
            ],
            [
                'nomZone' => 'Livraison express',
                'montant' => '15.00',
                'description' => 'Livraison express (24h)'
            ]
        ];

        foreach ($fraisLivraisonData as $data) {
            $fraisLivraison = new FraisLivraison();
            $fraisLivraison->setNomZone($data['nomZone']);
            $fraisLivraison->setMontant($data['montant']);
            $fraisLivraison->setDescription($data['description']);
            $fraisLivraison->setActif(true);

            $this->entityManager->persist($fraisLivraison);
            $io->text("✓ Frais de livraison créé : {$data['nomZone']} - {$data['montant']} TND");
        }

        $this->entityManager->flush();
    }

    private function initializeTaxes(SymfonyStyle $io): void
    {
        $io->section('Initialisation des taxes');

        $taxesData = [
            [
                'nom' => 'TVA',
                'taux' => '19.00',
                'description' => 'Taxe sur la valeur ajoutée'
            ],
            [
                'nom' => 'Taxe de luxe',
                'taux' => '5.00',
                'description' => 'Taxe sur les produits de luxe'
            ]
        ];

        foreach ($taxesData as $data) {
            $taxe = new Taxe();
            $taxe->setNom($data['nom']);
            $taxe->setTaux($data['taux']);
            $taxe->setDescription($data['description']);
            $taxe->setActif(true);

            $this->entityManager->persist($taxe);
            $io->text("✓ Taxe créée : {$data['nom']} - {$data['taux']}%");
        }

        $this->entityManager->flush();
    }
} 