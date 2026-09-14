<?php

namespace App\Repository;

use App\Entity\Enfant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enfant>
 */
class EnfantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enfant::class);
    }

    /**
     * Enfants d'un parent, par ordre alphabétique de prénom.
     *
     * @return Enfant[]
     */
    public function findByParent(User $parent): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('e.prenom')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les enfants, avec leur parent et leur compte (administration).
     *
     * @return Enfant[]
     */
    public function findAllAvecParent(): array
    {
        return $this->createQueryBuilder('e')
            ->addSelect('p', 'c')
            ->join('e.parent', 'p')
            ->join('e.compte', 'c')
            ->orderBy('e.prenom')
            ->addOrderBy('e.nom')
            ->getQuery()
            ->getResult();
    }
}
