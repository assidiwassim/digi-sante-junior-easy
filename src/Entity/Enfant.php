<?php

namespace App\Entity;

use App\Repository\EnfantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Profil d'un enfant, créé par son parent.
 */
#[ORM\Entity(repositoryClass: EnfantRepository::class)]
class Enfant
{
    /**
     * Avatars proposés au parent. Ce sont des emojis : aucune image à gérer.
     * La clé (ex. « renard ») est la valeur enregistrée en base.
     */
    public const AVATARS = [
        'renard' => ['emoji' => '🦊', 'nom' => 'Renard malin', 'couleur' => '#F59E0B'],
        'panda' => ['emoji' => '🐼', 'nom' => 'Panda calme', 'couleur' => '#64748B'],
        'chat' => ['emoji' => '🐱', 'nom' => 'Chat curieux', 'couleur' => '#F472B6'],
        'chien' => ['emoji' => '🐶', 'nom' => 'Chien fidèle', 'couleur' => '#C99A2E'],
        'lapin' => ['emoji' => '🐰', 'nom' => 'Lapin rapide', 'couleur' => '#A78BFA'],
        'lion' => ['emoji' => '🦁', 'nom' => 'Lion courageux', 'couleur' => '#EA580C'],
        'grenouille' => ['emoji' => '🐸', 'nom' => 'Grenouille sportive', 'couleur' => '#16A34A'],
        'poulpe' => ['emoji' => '🐙', 'nom' => 'Poulpe créatif', 'couleur' => '#0E7C7B'],
        'licorne' => ['emoji' => '🦄', 'nom' => 'Licorne magique', 'couleur' => '#DB2777'],
        'dragon' => ['emoji' => '🐲', 'nom' => 'Dragon rigolo', 'couleur' => '#059669'],
        'pingouin' => ['emoji' => '🐧', 'nom' => 'Pingouin cool', 'couleur' => '#1F3864'],
        'astronaute' => ['emoji' => '🧑‍🚀', 'nom' => 'Astronaute', 'couleur' => '#3B82F6'],
    ];

    /** Limite quotidienne d'écran : de 15 min à 8 h, par tranches de 15 min. */
    public const LIMITE_MIN = 15;
    public const LIMITE_MAX = 480;
    public const LIMITE_PAS = 15;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Le parent qui a créé ce profil. */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $parent = null;

    /**
     * Le compte de connexion de l'enfant.
     * Supprimer le profil supprime aussi ce compte (cascade « remove »).
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'profilEnfant', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $compte = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 80)]
    private ?string $prenom = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 80)]
    private ?string $nom = null;

    /*
     * L'application est destinée aux 8-14 ans.
     * « today -15 years +1 day » = dernier jour où l'on a encore 14 ans.
     */
    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de naissance est obligatoire.')]
    #[Assert\Range(
        notInRangeMessage: 'L\'application est réservée aux enfants de 8 à 14 ans.',
        min: 'today -15 years +1 day',
        max: 'today -8 years',
    )]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Choisissez un avatar.')]
    private ?string $avatar = 'renard';

    /** Limite quotidienne de temps d'écran, en minutes, fixée par le parent. */
    #[ORM\Column]
    #[Assert\NotNull(message: 'Choisissez une limite.')]
    #[Assert\Range(
        notInRangeMessage: 'La limite doit être comprise entre {{ min }} et {{ max }} minutes.',
        min: self::LIMITE_MIN,
        max: self::LIMITE_MAX,
    )]
    #[Assert\DivisibleBy(value: self::LIMITE_PAS, message: 'La limite se règle par tranches de 15 minutes.')]
    private ?int $maxMinutesJour = 120;

    /** @var Collection<int, JournalEntree> */
    #[ORM\OneToMany(targetEntity: JournalEntree::class, mappedBy: 'enfant', cascade: ['remove'])]
    private Collection $journalEntrees;

    public function __construct()
    {
        $this->journalEntrees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?User
    {
        return $this->parent;
    }

    public function setParent(?User $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getCompte(): ?User
    {
        return $this->compte;
    }

    public function setCompte(User $compte): static
    {
        $this->compte = $compte;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    public function getDateNaissance(): ?\DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    /** Âge en années révolues. */
    public function getAge(): ?int
    {
        return $this->dateNaissance?->diff(new \DateTimeImmutable('today'))->y;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatarEmoji(): string
    {
        return self::AVATARS[$this->avatar]['emoji'] ?? '🙂';
    }

    public function getAvatarNom(): string
    {
        return self::AVATARS[$this->avatar]['nom'] ?? 'Avatar';
    }

    public function getMaxMinutesJour(): ?int
    {
        return $this->maxMinutesJour;
    }

    public function setMaxMinutesJour(?int $maxMinutesJour): static
    {
        $this->maxMinutesJour = $maxMinutesJour;

        return $this;
    }

    /** @return Collection<int, JournalEntree> */
    public function getJournalEntrees(): Collection
    {
        return $this->journalEntrees;
    }
}
