<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function home(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        // Récupérer seulement 4 produits populaires comme exemples
        $popularProducts = $produitRepository->findPopularProducts(4);
        
        // Récupérer seulement 3 produits en promotion comme exemples
        $promotedProducts = $produitRepository->findPromotedProducts(3);
        
        // Récupérer seulement 4 catégories comme exemples
        $categories = $categorieRepository->findAll();
        $categories = array_slice($categories, 0, 4);

        return $this->render('frontweb/home.html.twig', [
            'popularProducts' => $popularProducts,
            'promotedProducts' => $promotedProducts,
            'categories' => $categories,
        ]);
    }

    #[Route('/shop', name: 'shop')]
    public function shop(Request $request, ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        // Récupérer les paramètres de filtrage
        $category = $request->query->all('category');
        $priceRange = $request->query->all('price');
        $brand = $request->query->all('brand');
        $size = $request->query->all('size');
        $genre = $request->query->all('genre');
        $sort = $request->query->get('sort', 'newest');

        // Utiliser la méthode optimisée du repository
        $filters = [
            'category' => $category,
            'price' => $priceRange,
            'brand' => $brand,
            'size' => $size,
            'sort' => $sort,
            'genre' => $genre
        ];
        $products = $produitRepository->findWithFilters($filters);

        // Récupérer toutes les catégories pour les filtres
        $categories = $categorieRepository->findAll();

        // Récupérer les marques disponibles (depuis les noms de produits)
        $brands = $produitRepository->findAvailableBrands();

        // Récupérer les tailles disponibles
        $sizes = $produitRepository->findAvailableSizes();

        return $this->render('frontweb/shop.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'sizes' => $sizes,
            'currentFilters' => [
                'category' => $category,
                'price' => $priceRange,
                'brand' => $brand,
                'size' => $size,
                'sort' => $sort,
                'genre' => $genre
            ]
        ]);
    }

    #[Route('/product/{id}', name: 'product')]
    public function product(int $id, ProduitRepository $produitRepository, \App\Repository\PanierRepository $panierRepository = null): Response
    {
        $product = $produitRepository->find($id);
        
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        $panierItems = null;
        if ($this->getUser() && $panierRepository) {
            $panierItems = $panierRepository->findBy(['client' => $this->getUser()]);
        }

        return $this->render('frontweb/product_detail.html.twig', [
            'product' => $product,
            'panier_items' => $panierItems,
        ]);
    }

    #[Route('/fiche-produit/{id}', name: 'fiche_produit')]
    public function ficheProduit(int $id, ProduitRepository $produitRepository): Response
    {
        $product = $produitRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        return $this->render('frontweb/fiche_produit.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('frontweb/contact.html.twig');
    }
}