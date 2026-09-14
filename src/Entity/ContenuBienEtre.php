<?php

namespace App\Entity;

use App\Repository\ContenuBienEtreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contenu de la bibliothèque (fiche, vidéo, quiz…), géré par l'administrateur.
 *
 * Le champ `declencheur` relie un contenu à une règle du moteur de conseils :
 * quand la règle se déclenche, ce contenu est proposé à l'enfant.
 */
#[ORM\Entity(repositoryClass: ContenuBienEtreRepository::class)]
class ContenuBienEtre
{
    public const TYPES = [
        'fiche' => ['label' => 'Fiche', 'emoji' => '📄'],
        'video' => ['label' => 'Vidéo', 'emoji' => '🎬'],
        'quiz' => ['label' => 'Quiz', 'emoji' => '❓'],
        'glossaire' => ['label' => 'Glossaire', 'emoji' => '📚'],
        'exercice' => ['label' => 'Exercice', 'emoji' => '🤸'],
    ];

    /** Règles du moteur de conseils auxquelles un contenu peut être rattaché. */
    public const DECLENCHEUR_20_20_20 = '20-20-20';
    public const DECLENCHEUR_ETIREMENT = 'etirement_cervical';
    public const DECLENCHEUR_YOGA_YEUX = 'yoga_yeux';

    public const DECLENCHEURS = [
        self::DECLENCHEUR_20_20_20 => 'Règle du 20-20-20',
        self::DECLENCHEUR_ETIREMENT => 'Étirements du cou',
        self::DECLENCHEUR_YOGA_YEUX => 'Yoga des yeux',
        'defi_sport' => 'Défi sport',
        'sommeil' => 'Sommeil',
        'posture' => 'Posture',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Choisissez un type de contenu.')]
    private ?string $type = 'fiche';

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 160)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    private ?string $contenu = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: 'Merci de saisir une URL valide.', requireTld: true)]
    #[Assert\Length(max: 500)]
    private ?string $url = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $declencheur = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type]['label'] ?? (string) $this->type;
    }

    public function getTypeEmoji(): string
    {
        return self::TYPES[$this->type]['emoji'] ?? '📄';
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDeclencheur(): ?string
    {
        return $this->declencheur;
    }

    public function setDeclencheur(?string $declencheur): static
    {
        $this->declencheur = $declencheur;

        return $this;
    }

    public function getDeclencheurLabel(): ?string
    {
        return self::DECLENCHEURS[$this->declencheur] ?? null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
