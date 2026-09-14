<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Une douleur signalée sur le schéma corporel, avec son intensité (1 à 5).
 */
#[ORM\Entity]
class DouleurZone
{
    /**
     * Zones cliquables du schéma corporel.
     * La clé correspond à l'attribut `data-zone` du SVG (enfant/journal/etape2.html.twig).
     */
    public const ZONES = [
        'yeux' => ['label' => 'Yeux', 'emoji' => '👀'],
        'cou' => ['label' => 'Cou / nuque', 'emoji' => '🦴'],
        'epaule' => ['label' => 'Épaules', 'emoji' => '💪'],
        'dos' => ['label' => 'Dos', 'emoji' => '🔙'],
        'poignet' => ['label' => 'Poignets', 'emoji' => '🤚'],
        'main' => ['label' => 'Doigts / main', 'emoji' => '✋'],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JournalEntree::class, inversedBy: 'douleurs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?JournalEntree $journalEntree = null;

    #[ORM\Column(length: 20)]
    private string $zone;

    #[ORM\Column]
    private int $intensite;

    public function __construct(string $zone, int $intensite)
    {
        $this->zone = $zone;
        $this->intensite = $intensite;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJournalEntree(): ?JournalEntree
    {
        return $this->journalEntree;
    }

    public function setJournalEntree(JournalEntree $journalEntree): static
    {
        $this->journalEntree = $journalEntree;

        return $this;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    public function getZoneLabel(): string
    {
        return self::ZONES[$this->zone]['label'] ?? $this->zone;
    }

    public function getZoneEmoji(): string
    {
        return self::ZONES[$this->zone]['emoji'] ?? '📍';
    }

    public function getIntensite(): int
    {
        return $this->intensite;
    }
}
