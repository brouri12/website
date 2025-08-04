<?php

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: "La quantité ne peut pas être vide")]
    #[Assert\Positive(message: "La quantité doit être positive")]
    private ?int $quantite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Le prix unitaire ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "Le prix unitaire doit être positif ou zéro")]
    private ?string $prix_unitaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Le sous-total ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "Le sous-total doit être positif ou zéro")]
    private ?string $sous_total = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $taille = null;

    /**
     * @var Produit|null
     */
    #[ORM\ManyToOne(inversedBy: 'lignesCommande')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    /**
     * @var Commande|null
     */
    #[ORM\ManyToOne(inversedBy: 'lignesCommande')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

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

    public function getSousTotal(): ?string
    {
        return $this->sous_total;
    }

    public function setSousTotal(string $sous_total): self
    {
        $this->sous_total = $sous_total;

        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): self
    {
        $this->taille = $taille;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(Produit $produit): self
    {
        $this->produit = $produit;

        return $this;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(Commande $commande): self
    {
        $this->commande = $commande;

        return $this;
    }
}