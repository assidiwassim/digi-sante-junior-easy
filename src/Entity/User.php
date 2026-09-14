<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Compte de connexion, commun aux trois rôles :
 *  - ROLE_ADMIN et ROLE_PARENT se connectent avec leur email ;
 *  - ROLE_CHILD se connecte avec son identifiant (username).
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
#[UniqueEntity(fields: ['username'], message: 'Cet identifiant est déjà pris.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_PARENT = 'ROLE_PARENT';
    public const ROLE_CHILD = 'ROLE_CHILD';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Email de connexion (parents et administrateurs uniquement). */
    #[ORM\Column(length: 180, unique: true, nullable: true)]
    #[Assert\Email(message: 'Cette adresse email n\'est pas valide.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    /** Identifiant de connexion (enfants uniquement). */
    #[ORM\Column(length: 60, unique: true, nullable: true)]
    private ?string $username = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    /** Mot de passe haché (jamais en clair). */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 80, nullable: true)]
    #[Assert\Length(max: 80)]
    private ?string $pays = null;

    #[ORM\Column(length: 80, nullable: true)]
    #[Assert\Length(max: 80)]
    private ?string $ville = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * Enfants de ce parent. Supprimer le parent supprime ses enfants.
     *
     * @var Collection<int, Enfant>
     */
    #[ORM\OneToMany(targetEntity: Enfant::class, mappedBy: 'parent', cascade: ['remove'])]
    private Collection $enfants;

    /** Profil rattaché à ce compte, si c'est un compte enfant. */
    #[ORM\OneToOne(targetEntity: Enfant::class, mappedBy: 'compte')]
    private ?Enfant $profilEnfant = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->enfants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        // On enregistre toujours l'email en minuscules, sans espaces autour.
        $this->email = $email ? mb_strtolower(trim($email)) : null;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username ? mb_strtolower(trim($username)) : null;

        return $this;
    }

    /** Identifiant utilisé par Symfony Security : l'email, sinon le username. */
    public function getUserIdentifier(): string
    {
        return (string) ($this->email ?? $this->username);
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Enfant> */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function getProfilEnfant(): ?Enfant
    {
        return $this->profilEnfant;
    }

    public function isParent(): bool
    {
        return \in_array(self::ROLE_PARENT, $this->getRoles(), true);
    }

    /** Méthode imposée par UserInterface : rien à effacer ici. */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
