<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Paiement;
use App\Entity\Panier;
use App\Repository\CommandeRepository;
use App\Repository\PanierRepository;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
#[IsGranted('ROLE_USER')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        /** @var \App\Entity\Client $user */
        $user = $this->getUser();
        
        $commandes = $commandeRepository->findBy(
            ['client' => $user],
            ['date_commande' => 'DESC']
        );

        return $this->render('commande/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/creer', name: 'app_commande_creer', methods: ['GET', 'POST'])]
    public function creer(Request $request, EntityManagerInterface $entityManager, PanierService $panierService): Response
    {
        /** @var \App\Entity\Client $user */
        $user = $this->getUser();
        
        // Récupérer les articles du panier
        $panierItems = $entityManager->getRepository(Panier::class)->findBy(['client' => $user]);
        
        if (empty($panierItems)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        // Calculer les totaux
        $calculs = $panierService->calculerTotalPanier($panierItems);

        if ($request->isMethod('POST')) {
            $rue = $request->request->get('rue');
            $ville = $request->request->get('ville');
            $codePostal = $request->request->get('code_postal');
            $pays = $request->request->get('pays');
            $methodePaiement = $request->request->get('methode_paiement');

            if (!$rue || !$ville || !$codePostal || !$pays || !$methodePaiement) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->render('commande/creer.html.twig', [
                    'panier_items' => $panierItems,
                    'calculs' => $calculs,
                ]);
            }

            $adresseLivraison = sprintf('%s, %s, %s, %s', $rue, $ville, $codePostal, $pays);

            try {
                // Créer la commande
                $commande = new Commande();
                $commande->setClient($user);
                $commande->setDateCommande(new \DateTime());
                $commande->setStatutCommande('en_attente');
                $commande->setMontantTotal($calculs['total_final']);
                $commande->setAdresseLivraison($adresseLivraison);
                $commande->setMethodePaiement($methodePaiement);
                $commande->setDateLivraisonEstimee((new \DateTime())->modify('+3 days'));

                $entityManager->persist($commande);

                // Créer les lignes de commande
                foreach ($panierItems as $panierItem) {
                    $ligneCommande = new LigneCommande();
                    $ligneCommande->setCommande($commande);
                    $ligneCommande->setProduit($panierItem->getProduit());
                    $ligneCommande->setQuantite($panierItem->getQuantite());
                    $ligneCommande->setPrixUnitaire($panierItem->getProduit()->getPrixUnitaire());
                    $ligneCommande->setSousTotal($panierItem->getQuantite() * $panierItem->getProduit()->getPrixUnitaire());
                    $ligneCommande->setTaille($panierItem->getTaille());

                    $entityManager->persist($ligneCommande);
                }

                // Créer le paiement
                $paiement = new Paiement();
                $paiement->setMontant($calculs['total_final']);
                $paiement->setDatePaiement(new \DateTime());
                $paiement->setStatutPaiement('en_attente');
                $paiement->setIdTransaction('TXN_' . uniqid());
                $paiement->setMethodePaiement($methodePaiement);
                $paiement->setCommande($commande);

                $entityManager->persist($paiement);

                // Mettre à jour les stocks
                $panierService->mettreAJourStocks($panierItems);

                // Vider le panier
                foreach ($panierItems as $panierItem) {
                    $entityManager->remove($panierItem);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Votre commande a été créée avec succès !');
                return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);

            } catch (\Exception $e) {
                // Log l'erreur pour le débogage
                error_log('Erreur lors de la création de la commande: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la commande: ' . $e->getMessage());
                return $this->render('commande/creer.html.twig', [
                    'panier_items' => $panierItems,
                    'calculs' => $calculs,
                ]);
            }
        }

        return $this->render('commande/creer.html.twig', [
            'panier_items' => $panierItems,
            'calculs' => $calculs,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(string $id, CommandeRepository $commandeRepository): Response
    {
        /** @var \App\Entity\Client $user */
        $user = $this->getUser();
        if (!is_numeric($id)) {
            throw $this->createNotFoundException('ID de commande invalide.');
        }
        $commande = $commandeRepository->find((int) $id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }
        // Vérifier que l'utilisateur peut voir cette commande
        if ($commande->getClient() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette commande.');
        }
        // Calcul du frais de livraison (reprendre la logique du PanierService)
        $fraisLivraisonMontant = 0;
        $fraisLivraison = $commandeRepository->getEntityManager()->getRepository(\App\Entity\FraisLivraison::class)->findBy(['actif' => true]);
        if (!empty($fraisLivraison)) {
            $fraisLivraisonMontant = $fraisLivraison[0]->getMontant();
        }
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
            'frais_livraison_montant' => $fraisLivraisonMontant,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_commande_annuler', methods: ['POST'])]
    public function annuler(string $id, CommandeRepository $commandeRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Client $user */
        $user = $this->getUser();
        
        if (!is_numeric($id)) {
            return new JsonResponse(['success' => false, 'message' => 'ID de commande invalide'], 400);
        }
        
        $commande = $commandeRepository->find((int) $id);
        
        if (!$commande) {
            return new JsonResponse(['success' => false, 'message' => 'Commande non trouvée'], 404);
        }
        
        if ($commande->getClient() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        // Seules les commandes en_attente peuvent être annulées
        if ($commande->getStatutCommande() !== 'en_attente') {
            return new JsonResponse(['success' => false, 'message' => 'Cette commande ne peut plus être annulée'], 400);
        }

        $commande->setStatutCommande('annulée');
        
        // Annuler le paiement si il existe
        if ($commande->getPaiement()) {
            $commande->getPaiement()->setStatutPaiement('annulé');
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Commande annulée avec succès']);
    }


} 