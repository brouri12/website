<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\FraisLivraisonRepository;
use App\Repository\TaxeRepository;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class PanierController extends AbstractController
{
    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(PanierRepository $panierRepository, PanierService $panierService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $panierItems = $panierRepository->findBy(['client' => $user]);
        
        // Utiliser le service pour calculer les totaux
        $calculs = $panierService->calculerTotalPanier($panierItems);

        return $this->render('panier/index.html.twig', [
            'panier_items' => $panierItems,
            'total' => $calculs['sous_total'],
            'frais_livraison' => $calculs['frais_livraison'],
            'taxes' => $calculs['taxes'],
            'frais_livraison_montant' => $calculs['frais_livraison_montant'],
            'total_taxes' => $calculs['total_taxes'],
            'total_final' => $calculs['total_final'],
        ]);
    }

    #[Route('/ajouter', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, EntityManagerInterface $entityManager, ProduitRepository $produitRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['produit_id']) || !isset($data['quantite'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        $produit = $produitRepository->find($data['produit_id']);
        if (!$produit) {
            return new JsonResponse(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }

        // Vérification du stock global ou par taille
        $quantiteDemandee = $data['quantite'];
        $taille = $data['taille'] ?? null;
        if ($taille) {
            $produitSize = null;
            foreach ($produit->getProduitSizes() as $size) {
                if ($size->getSize() === $taille) {
                    $produitSize = $size;
                    break;
                }
            }
            if (!$produitSize || $produitSize->getQuantite() < $quantiteDemandee) {
                return new JsonResponse(['success' => false, 'message' => 'Stock insuffisant pour cette taille'], 400);
            }
        } else {
            if ($produit->getTotalStock() < $quantiteDemandee) {
                return new JsonResponse(['success' => false, 'message' => 'Produit en rupture de stock'], 400);
            }
        }

        // Vérifier si le produit avec la même taille est déjà dans le panier
        $existingPanier = $entityManager->getRepository(Panier::class)->findOneBy([
            'client' => $user,
            'produit' => $produit,
            'taille' => $taille
        ]);

        if ($existingPanier) {
            // Si le produit avec la même taille existe, augmenter la quantité
            $existingPanier->setQuantite($existingPanier->getQuantite() + $data['quantite']);
        } else {
            // Vérifier si le produit existe déjà dans le panier (sans taille ou avec une taille différente)
            $existingProductInCart = $entityManager->getRepository(Panier::class)->findOneBy([
                'client' => $user,
                'produit' => $produit
            ]);

            if ($existingProductInCart && $taille === null) {
                // Le produit existe déjà sans taille, demander une taille
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Ce produit est déjà dans le panier. Veuillez sélectionner une taille différente.'
                ], 400);
            }

            // Sinon, créer un nouvel article dans le panier
            $panier = new Panier();
            $panier->setClient($user);
            $panier->setProduit($produit);
            $panier->setQuantite($data['quantite']);
            $panier->setTaille($taille); // Utiliser la taille fournie
            $panier->setDateAjout(new \DateTime());
            
            $entityManager->persist($panier);
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'message' => 'Produit ajouté au panier',
            'panier_count' => $this->getPanierCount($user, $entityManager)
        ]);
    }

    #[Route('/modifier-quantite', name: 'app_panier_modifier_quantite', methods: ['POST'])]
    public function modifierQuantite(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['panier_id']) || !isset($data['quantite'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        $panier = $entityManager->getRepository(Panier::class)->find($data['panier_id']);
        if (!$panier || $panier->getClient() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Article non trouvé'], 404);
        }

        if ($data['quantite'] <= 0) {
            $entityManager->remove($panier);
        } else {
            $panier->setQuantite($data['quantite']);
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'message' => 'Quantité modifiée',
            'panier_count' => $this->getPanierCount($user, $entityManager)
        ]);
    }

    #[Route('/supprimer/{id}', name: 'app_panier_supprimer', methods: ['POST'])]
    public function supprimer(Panier $panier, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $panier->getClient() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $entityManager->remove($panier);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'message' => 'Article supprimé du panier',
            'panier_count' => $this->getPanierCount($user, $entityManager)
        ]);
    }

    #[Route('/vider', name: 'app_panier_vider', methods: ['POST'])]
    public function vider(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $panierItems = $entityManager->getRepository(Panier::class)->findBy(['client' => $user]);
        
        foreach ($panierItems as $item) {
            $entityManager->remove($item);
        }
        
        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'message' => 'Panier vidé',
            'panier_count' => 0
        ]);
    }

    #[Route('/modifier-taille', name: 'app_panier_modifier_taille', methods: ['POST'])]
    public function modifierTaille(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['panier_id']) || !isset($data['taille'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        $panier = $entityManager->getRepository(Panier::class)->find($data['panier_id']);
        if (!$panier || $panier->getClient() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Article non trouvé'], 404);
        }

        // Vérifier si la taille est disponible pour ce produit
        $produit = $panier->getProduit();
        $tailleDisponible = false;
        foreach ($produit->getProduitSizes() as $produitSize) {
            if ($produitSize->getSize() === $data['taille'] && $produitSize->getQuantite() > 0) {
                $tailleDisponible = true;
                break;
            }
        }

        if (!$tailleDisponible) {
            return new JsonResponse(['success' => false, 'message' => 'Taille non disponible'], 400);
        }

        $panier->setTaille($data['taille']);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'message' => 'Taille modifiée avec succès'
        ]);
    }

    #[Route('/count', name: 'app_panier_count', methods: ['GET'])]
    public function getCount(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['count' => 0]);
        }

        return new JsonResponse(['count' => $this->getPanierCount($user, $this->getDoctrine()->getManager())]);
    }

    private function getPanierCount($user, EntityManagerInterface $entityManager): int
    {
        $panierItems = $entityManager->getRepository(Panier::class)->findBy(['client' => $user]);
        return array_sum(array_map(fn($item) => $item->getQuantite(), $panierItems));
    }
}
