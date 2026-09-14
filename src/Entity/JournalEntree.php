<?php

namespace App\Entity;

use App\Repository\JournalEntreeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal quotidien d'un enfant : temps d'écran et douleurs.
 *
 * Un enfant ne peut avoir qu'UN journal par jour : c'est garanti en base par
 * l'index unique sur (enfant_id, date).
 */
#[ORM\Entity(repositoryClass: JournalEntreeRepository::class)]
#[ORM\UniqueConstraint(name: 'un_journal_par_jour', columns: ['enfant_id', 'date'])]
class JournalEntree
{
    /**
     * Les écrans suivis : nom de la propriété => libellé affiché.
     * Utilisé par le formulaire de l'étape 1.
     */
    public const ECRANS = [
        'ecranTv' => '📺 Télévision',
        'ecranOrdinateur' => '💻 Ordinateur',
        'ecranSmartphone' => '📱 Téléphone',
        'ecranTablette' => '📲 Tablette',
        'ecranConsole' => '🎮 Console de jeux',
        'ecranAutre' => '🖥️ Autre écran',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Enfant::class, inversedBy: 'journalEntrees')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Enfant $enfant = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    // Temps passé devant chaque écran, en minutes.

    #[ORM\Column]
    private int $ecranTv = 0;

    #[ORM\Column]
    private int $ecranOrdinateur = 0;

    #[ORM\Column]
    private int $ecranSmartphone = 0;

    #[ORM\Column]
    private int $ecranTablette = 0;

    #[ORM\Column]
    private int $ecranConsole = 0;

    #[ORM\Column]
    private int $ecranAutre = 0;

    /** @var Collection<int, DouleurZone> */
    #[ORM\OneToMany(targetEntity: DouleurZone::class, mappedBy: 'journalEntree', cascade: ['persist', 'remove'])]
    private Collection $douleurs;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable('today');
        $this->douleurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnfant(): ?Enfant
    {
        return $this->enfant;
    }

    public function setEnfant(Enfant $enfant): static
    {
        $this->enfant = $enfant;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        // On garde uniquement le jour (minuit) : l'index unique porte sur la date.
        $this->date = $date->setTime(0, 0);

        return $this;
    }

    public function getEcranTv(): int
    {
        return $this->ecranTv;
    }

    public function setEcranTv(int $minutes): static
    {
        $this->ecranTv = $minutes;

        return $this;
    }

    public function getEcranOrdinateur(): int
    {
        return $this->ecranOrdinateur;
    }

    public function setEcranOrdinateur(int $minutes): static
    {
        $this->ecranOrdinateur = $minutes;

        return $this;
    }

    public function getEcranSmartphone(): int
    {
        return $this->ecranSmartphone;
    }

    public function setEcranSmartphone(int $minutes): static
    {
        $this->ecranSmartphone = $minutes;

        return $this;
    }

    public function getEcranTablette(): int
    {
        return $this->ecranTablette;
    }

    public function setEcranTablette(int $minutes): static
    {
        $this->ecranTablette = $minutes;

        return $this;
    }

    public function getEcranConsole(): int
    {
        return $this->ecranConsole;
    }

    public function setEcranConsole(int $minutes): static
    {
        $this->ecranConsole = $minutes;

        return $this;
    }

    public function getEcranAutre(): int
    {
        return $this->ecranAutre;
    }

    public function setEcranAutre(int $minutes): static
    {
        $this->ecranAutre = $minutes;

        return $this;
    }

    /** @return Collection<int, DouleurZone> */
    public function getDouleurs(): Collection
    {
        return $this->douleurs;
    }

    public function addDouleur(DouleurZone $douleur): static
    {
        $this->douleurs->add($douleur);
        $douleur->setJournalEntree($this);

        return $this;
    }

    /** Temps d'écran total de la journée, en minutes. */
    public function getTotalEcran(): int
    {
        return $this->ecranTv + $this->ecranOrdinateur + $this->ecranSmartphone
            + $this->ecranTablette + $this->ecranConsole + $this->ecranAutre;
    }

    /** Couleur de la jauge : vert (moins de 2 h), orange (2 à 4 h), rouge (plus de 4 h). */
    public function getNiveauEcran(): string
    {
        return self::niveauPourMinutes($this->getTotalEcran());
    }

    public static function niveauPourMinutes(int $minutes): string
    {
        if ($minutes < 120) {
            return 'vert';
        }

        if ($minutes <= 240) {
            return 'orange';
        }

        return 'rouge';
    }
}
