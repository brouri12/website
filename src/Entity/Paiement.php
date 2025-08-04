<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Le montant ne peut pas être vide")]
    #[Assert\Positive(message: "Le montant doit être positif")]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de paiement ne peut pas être vide")]
    private ?\DateTime $date_paiement = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le statut du paiement ne peut pas être vide")]
    #[Assert\Choice(choices: ['en_attente', 'approuvé', 'refusé', 'annulé'], message: "Statut de paiement invalide")]
    private string $statut_paiement;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'ID de transaction ne peut pas être vide")]
    #[Assert\Length(max: 255, maxMessage: "L'ID de transaction ne peut pas dépasser 255 caractères")]
    private string $id_transaction;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "La méthode de paiement ne peut pas être vide")]
    #[Assert\Choice(choices: ['carte_bancaire', 'paypal', 'virement'], message: "Méthode de paiement invalide")]
    private string $methode_paiement;

    /**
     * @var Commande|null
     */
    #[ORM\OneToOne(mappedBy: 'paiement', cascade: ['persist', 'remove'])]
    private ?Commande $commande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDatePaiement(): ?\DateTime
    {
        return $this->date_paiement;
    }

    public function setDatePaiement(\DateTime $date_paiement): self
    {
        $this->date_paiement = $date_paiement;

        return $this;
    }

    public function getStatutPaiement(): string
    {
        return $this->statut_paiement;
    }

    public function setStatutPaiement(string $statut_paiement): self
    {
        $this->statut_paiement = $statut_paiement;

        return $this;
    }

    public function getIdTransaction(): string
    {
        return $this->id_transaction;
    }

    public function setIdTransaction(string $id_transaction): self
    {
        $this->id_transaction = $id_transaction;

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

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;

        return $this;
    }
}