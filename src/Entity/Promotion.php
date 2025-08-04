<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le code de promotion ne peut pas être vide")]
    #[Assert\Length(max: 255, maxMessage: "Le code de promotion ne peut pas dépasser 255 caractères")]
    private ?string $code_promotion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotNull(message: "Le pourcentage de réduction ne peut pas être vide")]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: "Le pourcentage de réduction doit être entre 0 et 100")]
    private ?string $pourcentage_reduction = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de début ne peut pas être vide")]
    private ?\DateTime $date_debut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de fin ne peut pas être vide")]
    #[Assert\GreaterThanOrEqual(propertyPath: "date_debut", message: "La date de fin doit être postérieure ou égale à la date de début")]
    private ?\DateTime $date_fin = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le statut ne peut pas être vide")]
    #[Assert\Choice(choices: ['actif', 'inactif', 'expiré'], message: "Statut invalide")]
    private string $statut;

    /**
     * @var Collection<int, Produit>
     */
    #[ORM\ManyToMany(targetEntity: Produit::class, mappedBy: 'promotions')]
    private Collection $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodePromotion(): ?string
    {
        return $this->code_promotion;
    }

    public function setCodePromotion(string $code_promotion): self
    {
        $this->code_promotion = $code_promotion;

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

    public function getPourcentageReduction(): ?string
    {
        return $this->pourcentage_reduction;
    }

    public function setPourcentageReduction(string $pourcentage_reduction): self
    {
        $this->pourcentage_reduction = $pourcentage_reduction;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): self
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTime $date_fin): self
    {
        $this->date_fin = $date_fin;

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

    /**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): self
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->addPromotion($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            $produit->removePromotion($this);
        }

        return $this;
    }
}