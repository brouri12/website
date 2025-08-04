<?php

namespace App\Entity;

use App\Repository\ProduitSizeRepository;
use Doctrine\DBAL\Types\Types; // Added missing use statement
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitSizeRepository::class)]
class ProduitSize
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "La taille ne peut pas être vide")]
    #[Assert\Choice(choices: ['XS', 'S', 'M', 'L', 'XL', 'XXL'], message: "Taille invalide")]
    private string $size;

    #[ORM\Column(type: Types::INTEGER)] // Now recognized due to use statement
    #[Assert\NotNull(message: "La quantité ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "La quantité doit être positive ou zéro")]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'produitSizes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;

        return $this;
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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;

        return $this;
    }
}