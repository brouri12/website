<?php

namespace App\Entity;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdresseRepository::class)]
class Adresse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La rue ne peut pas être vide")]
    #[Assert\Length(max: 255, maxMessage: "La rue ne peut pas dépasser 255 caractères")]
    private string $rue;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "La ville ne peut pas être vide")]
    #[Assert\Length(max: 100, maxMessage: "La ville ne peut pas dépasser 100 caractères")]
    private string $ville;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le code postal ne peut pas être vide")]
    #[Assert\Length(max: 20, maxMessage: "Le code postal ne peut pas dépasser 20 caractères")]
    private string $code_postal;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le pays ne peut pas être vide")]
    #[Assert\Length(max: 100, maxMessage: "Le pays ne peut pas dépasser 100 caractères")]
    private string $pays;

    /**
     * @var Client|null
     */
    #[ORM\ManyToOne(inversedBy: 'adresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRue(): string
    {
        return $this->rue;
    }

    public function setRue(string $rue): self
    {
        $this->rue = $rue;

        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->code_postal;
    }

    public function setCodePostal(string $code_postal): self
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    public function getPays(): string
    {
        return $this->pays;
    }

    public function setPays(string $pays): self
    {
        $this->pays = $pays;

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
}