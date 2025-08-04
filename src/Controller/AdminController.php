<?php

namespace App\Controller;

use App\Entity\FraisLivraison;
use App\Entity\Taxe;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\Client;
use App\Repository\FraisLivraisonRepository;
use App\Repository\TaxeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ProduitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Form\CategorieType;
use App\Form\ClientType;
use App\Entity\ProduitSize;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/frais-livraison', name: 'admin_frais_livraison')]
    public function fraisLivraison(FraisLivraisonRepository $fraisLivraisonRepository): Response
    {
        $fraisLivraison = $fraisLivraisonRepository->findAll();

        return $this->render('admin/frais_livraison.html.twig', [
            'frais_livraison' => $fraisLivraison,
        ]);
    }

    #[Route('/frais-livraison/ajouter', name: 'admin_frais_livraison_ajouter', methods: ['GET', 'POST'])]
    public function ajouterFraisLivraison(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $fraisLivraison = new FraisLivraison();
            $fraisLivraison->setNomZone($request->request->get('nomZone'));
            $fraisLivraison->setMontant($request->request->get('montant'));
            $fraisLivraison->setDescription($request->request->get('description'));
            $fraisLivraison->setActif($request->request->get('actif') === 'on');

            $entityManager->persist($fraisLivraison);
            $entityManager->flush();

            $this->addFlash('success', 'Frais de livraison ajouté avec succès');
            return $this->redirectToRoute('admin_frais_livraison');
        }

        return $this->render('admin/ajouter_frais_livraison.html.twig');
    }

    #[Route('/frais-livraison/toggle/{id}', name: 'admin_frais_livraison_toggle', methods: ['POST'])]
    public function toggleFraisLivraison(int $id, EntityManagerInterface $em): Response
    {
        $frais = $em->getRepository(FraisLivraison::class)->find($id);
        if (!$frais) {
            $this->addFlash('error', 'Frais de livraison introuvable.');
            return $this->redirectToRoute('admin_frais_livraison');
        }
        // Si on active ce frais, désactiver tous les autres
        if (!$frais->isActif()) {
            $allFrais = $em->getRepository(FraisLivraison::class)->findAll();
            foreach ($allFrais as $f) {
                $f->setActif(false);
            }
            $frais->setActif(true);
            $this->addFlash('success', 'Frais de livraison activé.');
        } else {
            $frais->setActif(false);
            $this->addFlash('success', 'Frais de livraison désactivé.');
        }
        $em->flush();
        return $this->redirectToRoute('admin_frais_livraison');
    }

    #[Route('/taxes', name: 'admin_taxes')]
    public function taxes(TaxeRepository $taxeRepository): Response
    {
        $taxes = $taxeRepository->findAll();

        return $this->render('admin/taxes.html.twig', [
            'taxes' => $taxes,
        ]);
    }

    #[Route('/taxes/ajouter', name: 'admin_taxes_ajouter', methods: ['GET', 'POST'])]
    public function ajouterTaxe(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $taxe = new Taxe();
            $taxe->setNom($request->request->get('nom'));
            $taxe->setTaux($request->request->get('taux'));
            $taxe->setDescription($request->request->get('description'));
            $taxe->setActif($request->request->get('actif') === 'on');

            $entityManager->persist($taxe);
            $entityManager->flush();

            $this->addFlash('success', 'Taxe ajoutée avec succès');
            return $this->redirectToRoute('admin_taxes');
        }

        return $this->render('admin/ajouter_taxe.html.twig');
    }

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $nbProduits = $em->getRepository(Produit::class)->count([]);
        $nbCategories = $em->getRepository(Categorie::class)->count([]);
        $nbCommandes = $em->getRepository(Commande::class)->count([]);
        $nbClients = $em->getRepository(Client::class)->count([]);

        // Récupérer le frais de livraison actif (le premier trouvé)
        $fraisLivraison = $em->getRepository(FraisLivraison::class)->findOneBy(['actif' => true]);
        $fraisLivraisonMontant = $fraisLivraison ? $fraisLivraison->getMontant() : null;

        return $this->render('admin/dashboard.html.twig', [
            'nbProduits' => $nbProduits,
            'nbCategories' => $nbCategories,
            'nbCommandes' => $nbCommandes,
            'nbClients' => $nbClients,
            'fraisLivraisonMontant' => $fraisLivraisonMontant,
        ]);
    }

    #[Route('/produits', name: 'admin_produits')]
    public function produits(Request $request, EntityManagerInterface $em): Response
    {
        $produits = $em->getRepository(Produit::class)->findAll();
        return $this->render('admin/produits.html.twig', [
            'produits' => $produits
        ]);
    }

    #[Route('/produits/add', name: 'admin_produits_add')]
    public function addProduit(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $produit->setDateAjout(new \DateTime());
            $imageFile = $form->get('image_produit')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^A-Za-z0-9_]/', '', strtolower($originalFilename));
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/apploads',
                        $newFilename
                    );
                } catch (\Exception $e) {
                    $this->addFlash('error', "Erreur lors de l'upload de l'image : ".$e->getMessage());
                    return $this->redirectToRoute('admin_produits_add');
                }
                $produit->setImageProduit($newFilename);
            }
            $em->persist($produit);
            $em->flush();
            $categorie = $produit->getCategorie() ? strtolower($produit->getCategorie()->getNomCategorie()) : '';
            $categorie = str_replace(['é', 'è', 'ê', 'ë'], 'e', $categorie);
            $categorie = trim($categorie);
            if (preg_match('/^vetement(s)?$/', $categorie)) {
                return $this->redirectToRoute('admin_produits_add_size_multi', ['id' => $produit->getId()]);
            } elseif (preg_match('/^chaussure(s)?$/', $categorie)) {
                return $this->redirectToRoute('admin_produits_add_size_shoes', ['id' => $produit->getId()]);
            } else {
                return $this->redirectToRoute('admin_produits_set_stock', ['id' => $produit->getId()]);
            }
        }
        return $this->render('admin/produit_form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false
        ]);
    }

    #[Route('/produits/edit/{id}', name: 'admin_produits_edit')]
    public function editProduit(Request $request, EntityManagerInterface $em, Produit $produit): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image_produit')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^A-Za-z0-9_]/', '', strtolower($originalFilename));
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/apploads',
                        $newFilename
                    );
                } catch (\Exception $e) {
                    $this->addFlash('error', "Erreur lors de l'upload de l'image : ".$e->getMessage());
                    return $this->redirectToRoute('admin_produits_edit', ['id' => $produit->getId()]);
                }
                $produit->setImageProduit($newFilename);
            }
            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès');
            return $this->redirectToRoute('admin_produits');
        }
        return $this->render('admin/produit_form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => true,
            'produit' => $produit
        ]);
    }

    #[Route('/produits/delete/{id}', name: 'admin_produits_delete')]
    public function deleteProduit(EntityManagerInterface $em, Produit $produit): RedirectResponse
    {
        $em->remove($produit);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé avec succès');
        return $this->redirectToRoute('admin_produits');
    }

    #[Route('/produits/{id}/add-size', name: 'admin_produits_add_size')]
    public function addSize(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $error = null;
        if ($request->isMethod('POST')) {
            $sizeValue = $request->request->get('size');
            $quantite = $request->request->get('quantite');
            if ($sizeValue && $quantite !== null) {
                // Chercher si la taille existe déjà pour ce produit
                $existingSize = null;
                foreach ($produit->getProduitSizes() as $ps) {
                    if ($ps->getSize() === $sizeValue) {
                        $existingSize = $ps;
                        break;
                    }
                }
                if ($existingSize) {
                    $existingSize->setQuantite($existingSize->getQuantite() + (int)$quantite);
                    $em->flush();
                } else {
                    $size = new ProduitSize();
                    $size->setProduit($produit);
                    $size->setSize($sizeValue);
                    $size->setQuantite((int)$quantite);
                    $em->persist($size);
                    $em->flush();
                    $em->refresh($produit); // Ajouté pour forcer le rechargement des tailles
                }
                $produit->setStockTotal();
                $em->flush();
                return $this->redirectToRoute('admin_produits');
            } else {
                $error = 'Veuillez remplir tous les champs.';
            }
        }
        return $this->render('admin/produit_add_size.html.twig', [
            'produit' => $produit,
            'error' => $error
        ]);
    }

    #[Route('/produits/{id}/add-size-multi', name: 'admin_produits_add_size_multi')]
    public function addSizeMulti(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $error = null;
        if ($request->isMethod('POST')) {
            $hasAtLeastOne = false;
            foreach ($sizes as $sizeValue) {
                $quantite = $request->request->get('quantite_' . $sizeValue);
                if ($quantite !== null && $quantite !== '' && (int)$quantite > 0) {
                    $hasAtLeastOne = true;
                    $produitSize = new ProduitSize();
                    $produitSize->setProduit($produit);
                    $produitSize->setSize($sizeValue);
                    $produitSize->setQuantite((int)$quantite);
                    $em->persist($produitSize);
                }
            }
            if ($hasAtLeastOne) {
                $em->flush();
                // Recalculate stock_total
                $produit->setStockTotal();
                $em->flush();
                $this->addFlash('success', 'Tailles ajoutées avec succès');
                return $this->redirectToRoute('admin_produits');
            } else {
                $error = 'Veuillez saisir au moins une quantité.';
            }
        }
        return $this->render('admin/produit_add_size_multi.html.twig', [
            'produit' => $produit,
            'sizes' => $sizes,
            'error' => $error
        ]);
    }

    #[Route('/produits/{id}/set-stock', name: 'admin_produits_set_stock')]
    public function setStock(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $stock = $request->request->get('stock_total');
            if ($stock !== null && is_numeric($stock) && (int)$stock >= 0) {
                $produit->setStockTotal((int)$stock);
                $em->flush();
                $this->addFlash('success', 'Stock total défini avec succès');
                return $this->redirectToRoute('admin_produits');
            } else {
                $error = 'Veuillez saisir un stock valide.';
            }
        } else {
            $error = null;
        }
        return $this->render('admin/produit_set_stock.html.twig', [
            'produit' => $produit,
            'error' => $error
        ]);
    }

    #[Route('/produits/{id}/add-size-shoes', name: 'admin_produits_add_size_shoes')]
    public function addSizeShoes(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $sizes = ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45'];
        $error = null;
        if ($request->isMethod('POST')) {
            $hasAtLeastOne = false;
            foreach ($sizes as $sizeValue) {
                $quantite = $request->request->get('quantite_' . $sizeValue);
                if ($quantite !== null && $quantite !== '' && (int)$quantite > 0) {
                    $hasAtLeastOne = true;
                    $produitSize = new ProduitSize();
                    $produitSize->setProduit($produit);
                    $produitSize->setSize($sizeValue);
                    $produitSize->setQuantite((int)$quantite);
                    $em->persist($produitSize);
                }
            }
            if ($hasAtLeastOne) {
                $em->flush();
                // Recalculate stock_total
                $produit->setStockTotal();
                $em->flush();
                $this->addFlash('success', 'Pointures ajoutées avec succès');
                return $this->redirectToRoute('admin_produits');
            } else {
                $error = 'Veuillez saisir au moins une quantité.';
            }
        }
        return $this->render('admin/produit_add_size_shoes.html.twig', [
            'produit' => $produit,
            'sizes' => $sizes,
            'error' => $error
        ]);
    }

    #[Route('/produits/{produit}/size/{sizeId}/edit', name: 'admin_produits_edit_size', methods: ['POST'])]
    public function editProduitSize(Request $request, Produit $produit, ProduitSize $size, EntityManagerInterface $em): Response
    {
        $quantite = $request->request->get('quantite');
        if ($quantite !== null && is_numeric($quantite) && (int)$quantite >= 0) {
            $size->setQuantite((int)$quantite);
            $em->flush();
            $produit->setStockTotal();
            $em->flush();
            $this->addFlash('success', 'Quantité modifiée');
        } else {
            $this->addFlash('error', 'Quantité invalide');
        }
        return $this->redirectToRoute('admin_produits_edit', ['id' => $produit->getId()]);
    }

    #[Route('/produits/{produit}/size/{sizeId}/delete', name: 'admin_produits_delete_size', methods: ['POST'])]
    public function deleteProduitSize(Produit $produit, ProduitSize $size, EntityManagerInterface $em): Response
    {
        $em->remove($size);
        $em->flush();
        $produit->setStockTotal();
        $em->flush();
        $this->addFlash('success', 'Taille supprimée');
        return $this->redirectToRoute('admin_produits_edit', ['id' => $produit->getId()]);
    }

    #[Route('/categories', name: 'admin_categories')]
    public function categories(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Categorie::class)->findAll();
        return $this->render('admin/categories.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/categories/add', name: 'admin_categories_add')]
    public function addCategorie(Request $request, EntityManagerInterface $em): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie ajoutée avec succès');
            return $this->redirectToRoute('admin_categories');
        }
        return $this->render('admin/categorie_form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => false
        ]);
    }

    #[Route('/categories/edit/{id}', name: 'admin_categories_edit')]
    public function editCategorie(Request $request, EntityManagerInterface $em, Categorie $categorie): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie modifiée avec succès');
            return $this->redirectToRoute('admin_categories');
        }
        return $this->render('admin/categorie_form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => true
        ]);
    }

    #[Route('/categories/delete/{id}', name: 'admin_categories_delete')]
    public function deleteCategorie(EntityManagerInterface $em, Categorie $categorie): RedirectResponse
    {
        $em->remove($categorie);
        $em->flush();
        $this->addFlash('success', 'Catégorie supprimée avec succès');
        return $this->redirectToRoute('admin_categories');
    }

    #[Route('/commandes', name: 'admin_commandes')]
    public function commandes(EntityManagerInterface $em): Response
    {
        $commandes = $em->getRepository(Commande::class)->findAll();
        return $this->render('admin/commandes.html.twig', [
            'commandes' => $commandes
        ]);
    }

    #[Route('/commandes/view/{id}', name: 'admin_commandes_view')]
    public function viewCommande(EntityManagerInterface $em, int $id): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée');
        }
        return $this->render('admin/commande_view.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/commandes/change-status', name: 'admin_commandes_change_status', methods: ['POST'])]
    public function changeCommandeStatus(Request $request, EntityManagerInterface $em): Response
    {
        $commandeId = $request->request->get('commande_id');
        $statut = $request->request->get('statut');
        $commande = $em->getRepository(Commande::class)->find($commandeId);
        if (!$commande) {
            $this->addFlash('error', 'Commande non trouvée');
            return $this->redirectToRoute('admin_commandes');
        }
        if (!in_array($statut, ['livrée', 'confirmée', 'annulée'])) {
            $this->addFlash('error', 'Statut invalide');
            return $this->redirectToRoute('admin_commandes');
        }
        $commande->setStatutCommande($statut);
        $em->flush();
        $this->addFlash('success', 'Statut de la commande mis à jour !');
        return $this->redirectToRoute('admin_commandes');
    }

    #[Route('/commandes/invoice/{id}', name: 'admin_commandes_invoice')]
    public function downloadInvoice(EntityManagerInterface $em, int $id): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée');
        }
        // Calcul du frais de livraison (reprendre la logique du PanierService)
        $fraisLivraisonMontant = 0;
        $fraisLivraison = $em->getRepository(\App\Entity\FraisLivraison::class)->findBy(['actif' => true]);
        if (!empty($fraisLivraison)) {
            $fraisLivraisonMontant = $fraisLivraison[0]->getMontant();
        }
        $html = $this->renderView('admin/commande_invoice.html.twig', [
            'commande' => $commande,
            'project_dir' => $this->getParameter('kernel.project_dir'),
            'frais_livraison_montant' => $fraisLivraisonMontant,
        ]);
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="facture-commande-' . $commande->getId() . '.pdf"'
            ]
        );
    }

    #[Route('/clients', name: 'admin_clients')]
    public function clients(EntityManagerInterface $em): Response
    {
        $clients = $em->getRepository(Client::class)->findAll();
        return $this->render('admin/clients.html.twig', [
            'clients' => $clients
        ]);
    }

    #[Route('/clients/view/{id}', name: 'admin_clients_view')]
    public function viewClient(Client $client): Response
    {
        return $this->render('admin/client_view.html.twig', [
            'client' => $client
        ]);
    }

    #[Route('/clients/edit/{id}', name: 'admin_clients_edit')]
    public function editClient(Request $request, EntityManagerInterface $em, Client $client): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Client modifié avec succès');
            return $this->redirectToRoute('admin_clients');
        }
        return $this->render('admin/client_form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => true
        ]);
    }

    #[Route('/clients/delete/{id}', name: 'admin_clients_delete')]
    public function deleteClient(EntityManagerInterface $em, Client $client): RedirectResponse
    {
        $em->remove($client);
        $em->flush();
        $this->addFlash('success', 'Client supprimé avec succès');
        return $this->redirectToRoute('admin_clients');
    }

    #[Route('/commandes/delete/{id}', name: 'admin_commandes_delete')]
    public function deleteCommande(EntityManagerInterface $em, int $id): RedirectResponse
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            $this->addFlash('error', 'Commande non trouvée');
            return $this->redirectToRoute('admin_commandes');
        }
        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Commande supprimée avec succès');
        return $this->redirectToRoute('admin_commandes');
    }

    #[Route('/commandes/edit/{id}', name: 'admin_commandes_edit', methods: ['GET', 'POST'])]
    public function editCommande(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée');
        }
        if ($request->isMethod('POST')) {
            $statut = $request->request->get('statut_commande');
            $adresse = $request->request->get('adresse_livraison');
            $methode = $request->request->get('methode_paiement');
            if ($statut) $commande->setStatutCommande($statut);
            if ($adresse) $commande->setAdresseLivraison($adresse);
            if ($methode) $commande->setMethodePaiement($methode);
            $em->flush();
            $this->addFlash('success', 'Commande modifiée avec succès');
            return $this->redirectToRoute('admin_commandes_view', ['id' => $commande->getId()]);
        }
        return $this->render('admin/commande_edit.html.twig', [
            'commande' => $commande
        ]);
    }
} 