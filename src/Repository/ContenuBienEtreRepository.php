<?php

namespace App\Repository;

use App\Entity\ContenuBienEtre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContenuBienEtre>
 */
class ContenuBienEtreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContenuBienEtre::class);
    }

    /** Premier contenu rattaché à une règle du moteur de conseils. */
    public function findPremierPourDeclencheur(string $declencheur): ?ContenuBienEtre
    {
        return $this->createQueryBuilder('c')
            ->where('c.declencheur = :declencheur')
            ->setParameter('declencheur', $declencheur)
            ->orderBy('c.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Tous les contenus, triés par type puis par titre.
     *
     * @return ContenuBienEtre[]
     */
    public function findTousTries(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.type')
            ->addOrderBy('c.titre')
            ->getQuery()
            ->getResult();
    }

    /**
     * Contenus regroupés par type, dans l'ordre de ContenuBienEtre::TYPES.
     * Exemple : ['fiche' => [contenu, contenu], 'video' => [contenu]].
     *
     * @return array<string, ContenuBienEtre[]>
     */
    public function findGroupesParType(): array
    {
        $groupes = [];

        foreach (array_keys(ContenuBienEtre::TYPES) as $type) {
            $contenus = $this->findBy(['type' => $type], ['titre' => \SortDirection::Ascending]);

            if ([] !== $contenus) {
                $groupes[$type] = $contenus;
            }
        }

        return $groupes;
    }
}
