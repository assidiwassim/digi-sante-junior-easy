<?php

namespace App\Repository;

use App\Entity\Enfant;
use App\Entity\JournalEntree;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalEntree>
 */
class JournalEntreeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalEntree::class);
    }

    /** Journal du jour d'un enfant, ou null s'il ne l'a pas encore rempli. */
    public function findAujourdhui(Enfant $enfant): ?JournalEntree
    {
        return $this->findOneBy([
            'enfant' => $enfant,
            'date' => new \DateTimeImmutable('today'),
        ]);
    }

    /**
     * Derniers journaux d'un enfant, du plus récent au plus ancien.
     *
     * @return JournalEntree[]
     */
    public function findDerniers(Enfant $enfant, int $nombre): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.enfant = :enfant')
            ->setParameter('enfant', $enfant)
            ->orderBy('j.date', \SortDirection::Descending)
            ->setMaxResults($nombre)
            ->getQuery()
            ->getResult();
    }

    /**
     * Données du graphique « temps d'écran » : un point par jour sur la période.
     * Un jour sans journal vaut 0 minute.
     *
     * @return array{labels: list<string>, minutes: list<int>}
     */
    public function getGraphiqueEcran(Enfant $enfant, int $nombreJours): array
    {
        $debut = new \DateTimeImmutable('today -'.($nombreJours - 1).' days');

        /** @var JournalEntree[] $journaux */
        $journaux = $this->createQueryBuilder('j')
            ->where('j.enfant = :enfant')
            ->andWhere('j.date >= :debut')
            ->setParameter('enfant', $enfant)
            ->setParameter('debut', $debut, 'date_immutable')
            ->getQuery()
            ->getResult();

        // On range les minutes par jour : ['2026-09-14' => 135, …]
        $minutesParJour = [];
        foreach ($journaux as $journal) {
            $minutesParJour[$journal->getDate()->format('Y-m-d')] = $journal->getTotalEcran();
        }

        $labels = [];
        $minutes = [];
        for ($i = 0; $i < $nombreJours; ++$i) {
            $jour = $debut->modify('+'.$i.' days');
            $labels[] = $jour->format('d/m');
            $minutes[] = $minutesParJour[$jour->format('Y-m-d')] ?? 0;
        }

        return ['labels' => $labels, 'minutes' => $minutes];
    }
}
