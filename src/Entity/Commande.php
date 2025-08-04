<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de commande ne peut pas être vide")]
    private ?\DateTime $date_commande = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le statut de la commande ne peut pas être vide")]
    #[Assert\Choice(choices: ['en_attente', 'confirmée', 'expédiée', 'livrée', 'annulée'], message: "Statut de commande invalide")]
    private string $statut_commande;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Le montant total ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "Le montant total doit être positif ou zéro")]
    private ?string $montant_total = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse de livraison ne peut pas être vide")]
    #[Assert\Length(max: 255, maxMessage: "L'adresse de livraison ne peut pas dépasser 255 caractères")]
    private string $adresse_livraison;



    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "La méthode de paiement ne peut pas être vide")]
    #[Assert\Choice(choices: ['carte_bancaire', 'paypal', 'virement'], message: "Méthode de paiement invalide")]
    private string $methode_paiement;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(propertyPath: "date_commande", message: "La date de livraison estimée doit être postérieure ou égale à la date de commande")]
    private ?\DateTime $date_livraison_estimee = null;

    /**
     * @var Client|null
     */
    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    /**
     * @var Collection<int, LigneCommande>
     */
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, orphanRemoval: true)]
    private Collection $lignesCommande;

    /**
     * @var Paiement|null
     */
    #[ORM\OneToOne(inversedBy: 'commande', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Paiement $paiement = null;

    public function __construct()
    {
        $this->lignesCommande = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCommande(): ?\DateTime
    {
        return $this->date_commande;
    }

    public function setDateCommande(\DateTime $date_commande): self
    {
        $this->date_commande = $date_commande;

        return $this;
    }

    public function getStatutCommande(): string
    {
        return $this->statut_commande;
    }

    public function setStatutCommande(string $statut_commande): self
    {
        $this->statut_commande = $statut_commande;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montant_total;
    }

    public function setMontantTotal(string $montant_total): self
    {
        $this->montant_total = $montant_total;

        return $this;
    }

    public function getAdresseLivraison(): string
    {
        return $this->adresse_livraison;
    }

    public function setAdresseLivraison(string $adresse_livraison): self
    {
        $this->adresse_livraison = $adresse_livraison;

        return $this;
    }



    public function getMethodePaiement(): string
    {
        return $this->methode_paiement;
    }

    public function setMethodePaiement(string $methode_paiement): self
    {
        $this->methode_paiement = $methode_paiement;

        return $this;
    }

    public function getDateLivraisonEstimee(): ?\DateTime
    {
        return $this->date_livraison_estimee;
    }

    public function setDateLivraisonEstimee(?\DateTime $date_livraison_estimee): self
    {
        $this->date_livraison_estimee = $date_livraison_estimee;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

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
            $ligneCommande->setCommande($this);
        }

        return $this;
    }

    public function removeLigneCommande(LigneCommande $ligneCommande): self
    {
        if ($this->lignesCommande->removeElement($ligneCommande)) {
            if ($ligneCommande->getCommande() === $this) {
                $ligneCommande->setCommande(null);
            }
        }

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): self
    {
        $this->paiement = $paiement;

        return $this;
    }
}