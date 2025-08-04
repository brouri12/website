<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du produit ne peut pas être vide")]
    #[Assert\Length(max: 255, maxMessage: "Le nom du produit ne peut pas dépasser 255 caractères")]
    private ?string $nom_produit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Le prix unitaire ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "Le prix unitaire doit être positif ou zéro")]
    private ?string $prix_unitaire = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $stock_total = null; // Changed to stock_total to reflect total across all sizes

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image_produit = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $date_ajout = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le statut ne peut pas être vide")]
    #[Assert\Choice(choices: ['disponible', 'indisponible', 'en_rupture'], message: "Statut invalide")]
    private string $statut;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le genre ne peut pas être vide")]
    #[Assert\Choice(choices: ['homme', 'femme', 'enfant', 'unisexe'], message: "Genre invalide")]
    private string $genre;

    /**
     * @var Collection<int, ProduitSize>
     */
    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: ProduitSize::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $produitSizes;

    /**
     * @var Categorie|null
     */
    #[ORM\ManyToOne(inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    /**
     * @var Collection<int, LigneCommande>
     */
    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: LigneCommande::class, orphanRemoval: true)]
    private Collection $lignesCommande;

    /**
     * @var Collection<int, Panier>
     */
    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: Panier::class, orphanRemoval: true)]
    private Collection $paniers;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: Avis::class, orphanRemoval: true)]
    private Collection $avis;

    /**
     * @var Collection<int, Promotion>
     */
    #[ORM\ManyToMany(targetEntity: Promotion::class, inversedBy: 'produits')]
    private Collection $promotions;

    public function __construct()
    {
        $this->produitSizes = new ArrayCollection();
        $this->lignesCommande = new ArrayCollection();
        $this->paniers = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->promotions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomProduit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNomProduit(string $nom_produit): self
    {
        $this->nom_produit = $nom_produit;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(string $prix_unitaire): self
    {
        $this->prix_unitaire = $prix_unitaire;

        return $this;
    }

  public function getTotalStock(): int
{
    return array_sum($this->getProduitSizes()->map(fn($size) => $size->getQuantite())->toArray());
}

    public function getStockTotal(): ?int
    {
        return $this->stock_total;
    }

    public function setStockTotal(?int $stock_total = null): self
    {
        if ($stock_total !== null) {
            $this->stock_total = $stock_total;
        } else {
            $this->stock_total = $this->getTotalStock();
        }
        return $this;
    }

    

    public function getImageProduit(): ?string
    {
        return $this->image_produit;
    }

    public function setImageProduit(?string $image_produit): self
    {
        // Si un nom de fichier est donné, on stocke le chemin relatif dans 'apploads/'
        if ($image_produit !== null && $image_produit !== '') {
            $this->image_produit = 'apploads/' . ltrim($image_produit, '/\\');
        } else {
            $this->image_produit = null;
        }
        return $this;
    }

    public function getDateAjout(): ?\DateTime
    {
        return $this->date_ajout;
    }

    public function setDateAjout(?\DateTime $date_ajout = null): self
    {
        $this->date_ajout = $date_ajout ?? new \DateTime();
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getGenre(): string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): self
    {
        $this->genre = $genre;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, ProduitSize>
     */
    public function getProduitSizes(): Collection
    {
        return $this->produitSizes;
    }

    public function addProduitSize(ProduitSize $produitSize): self
    {
        if (!$this->produitSizes->contains($produitSize)) {
            $this->produitSizes->add($produitSize);
            $produitSize->setProduit($this);
        }

        return $this;
    }

    public function removeProduitSize(ProduitSize $produitSize): self
    {
        if ($this->produitSizes->removeElement($produitSize)) {
            if ($produitSize->getProduit() === $this) {
                $produitSize->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getLignesCommande(): Collection
    {
        return $this->lignesCommande;
    }

    public function addLigneCommande(LigneCommande $ligneCommande): self
    {
        if (!$this->lignesCommande->contains($ligneCommande)) {
            $this->lignesCommande->add($ligneCommande);
            $ligneCommande->setProduit($this);
        }

        return $this;
    }

    public function removeLigneCommande(LigneCommande $ligneCommande): self
    {
        if ($this->lignesCommande->removeElement($ligneCommande)) {
            if ($ligneCommande->getProduit() === $this) {
                $ligneCommande->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Panier>
     */
    public function getPaniers(): Collection
    {
        return $this->paniers;
    }

    public function addPanier(Panier $panier): self
    {
        if (!$this->paniers->contains($panier)) {
            $this->paniers->add($panier);
            $panier->setProduit($this);
        }

        return $this;
    }

    public function removePanier(Panier $panier): self
    {
        if ($this->paniers->removeElement($panier)) {
            if ($panier->getProduit() === $this) {
                $panier->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvis(Avis $avis): self
    {
        if (!$this->avis->contains($avis)) {
            $this->avis->add($avis);
            $avis->setProduit($this);
        }

        return $this;
    }

    public function removeAvis(Avis $avis): self
    {
        if ($this->avis->removeElement($avis)) {
            if ($avis->getProduit() === $this) {
                $avis->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Promotion>
     */
    public function getPromotions(): Collection
    {
        return $this->promotions;
    }

    public function addPromotion(Promotion $promotion): self
    {
        if (!$this->promotions->contains($promotion)) {
            $this->promotions->add($promotion);
        }

        return $this;
    }

    public function removePromotion(Promotion $promotion): self
    {
        $this->promotions->removeElement($promotion);

        return $this;
    }
}