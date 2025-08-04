<?php

namespace App\Service;

use App\Entity\Panier;
use App\Repository\FraisLivraisonRepository;
use App\Repository\TaxeRepository;
use Doctrine\ORM\EntityManagerInterface;

class PanierService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FraisLivraisonRepository $fraisLivraisonRepository,
        private TaxeRepository $taxeRepository
    ) {
    }

    /**
     * Calcule le total du panier avec frais de livraison et taxes
     */
    public function calculerTotalPanier(array $panierItems): array
    {
        $sousTotal = 0;
        
        // Calculer le sous-total
        foreach ($panierItems as $item) {
            $sousTotal += $item->getQuantite() * $item->getProduit()->getPrixUnitaire();
        }

        // Récupérer les frais de livraison
        $fraisLivraison = $this->fraisLivraisonRepository->findActifs();
        $fraisLivraisonMontant = 0;
        if (!empty($fraisLivraison)) {
            $fraisLivraisonMontant = $fraisLivraison[0]->getMontant();
        }

        // Récupérer et calculer les taxes
        $taxes = $this->taxeRepository->findActives();
        $totalTaxes = 0;
        $detailsTaxes = [];

        foreach ($taxes as $taxe) {
            $montantTaxe = $sousTotal * $taxe->getTaux() / 100;
            $totalTaxes += $montantTaxe;
            $detailsTaxes[] = [
                'nom' => $taxe->getNom(),
                'taux' => $taxe->getTaux(),
                'montant' => $montantTaxe
            ];
        }

        // Total final sans taxes (taxes supprimées du résumé)
        $totalFinal = $sousTotal + $fraisLivraisonMontant;

        return [
            'sous_total' => $sousTotal,
            'frais_livraison' => $fraisLivraison,
            'frais_livraison_montant' => $fraisLivraisonMontant,
            'taxes' => $taxes,
            'details_taxes' => $detailsTaxes,
            'total_taxes' => $totalTaxes,
            'total_final' => $totalFinal
        ];
    }

    /**
     * Vérifie la disponibilité des produits dans le panier
     */
    public function verifierDisponibilite(array $panierItems): array
    {
        $erreurs = [];
        
        foreach ($panierItems as $item) {
            $produit = $item->getProduit();
            $quantiteDemandee = $item->getQuantite();
            $taille = $item->getTaille();

            if ($taille) {
                // Vérifier la disponibilité pour la taille spécifique
                $produitSize = null;
                foreach ($produit->getProduitSizes() as $size) {
                    if ($size->getSize() === $taille) {
                        $produitSize = $size;
                        break;
                    }
                }

                if (!$produitSize || $produitSize->getQuantite() < $quantiteDemandee) {
                    $erreurs[] = "Le produit {$produit->getNomProduit()} n'est pas disponible en quantité suffisante pour la taille $taille";
                }
            } else {
                // Vérifier la disponibilité globale
                $stockTotal = $produit->getTotalStock();
                if ($stockTotal < $quantiteDemandee) {
                    $erreurs[] = "Le produit {$produit->getNomProduit()} n'est pas disponible en quantité suffisante";
                }
            }
        }

        return $erreurs;
    }

    /**
     * Met à jour les stocks après une commande
     */
    public function mettreAJourStocks(array $panierItems): void
    {
        foreach ($panierItems as $item) {
            $produit = $item->getProduit();
            $quantite = $item->getQuantite();
            $taille = $item->getTaille();

            if ($taille) {
                // Mettre à jour le stock pour la taille spécifique
                foreach ($produit->getProduitSizes() as $size) {
                    if ($size->getSize() === $taille) {
                        $nouvelleQuantite = $size->getQuantite() - $quantite;
                        $size->setQuantite(max(0, $nouvelleQuantite));
                        break;
                    }
                }
            } else {
                // Mettre à jour le stock total
                $stockTotal = $produit->getStockTotal();
                $nouveauStock = $stockTotal - $quantite;
                $produit->setStockTotal(max(0, $nouveauStock));
            }
        }

        $this->entityManager->flush();
    }
} 