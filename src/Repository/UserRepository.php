<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Appelée par Symfony Security à la connexion.
     *
     * Les parents saisissent leur email, les enfants leur identifiant :
     * on cherche donc dans les deux colonnes.
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifiant OR u.username = :identifiant')
            ->setParameter('identifiant', mb_strtolower(trim($identifier)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Comptes parents, du plus récent au plus ancien.
     *
     * @return User[]
     */
    public function findParents(): array
    {
        // Les rôles sont enregistrés en JSON, par exemple ["ROLE_PARENT"].
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"'.User::ROLE_PARENT.'"%')
            ->orderBy('u.createdAt', \SortDirection::Descending)
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un identifiant libre à partir du prénom : « Léa » donne « lea »,
     * puis « lea2 », « lea3 »… si l'identifiant est déjà pris.
     */
    public function genererUsername(string $prenom): string
    {
        $base = (new AsciiSlugger())->slug($prenom, '')->lower()->toString();
        $base = substr($base, 0, 40) ?: 'enfant';

        $username = $base;
        $numero = 1;

        while (null !== $this->findOneBy(['username' => $username])) {
            ++$numero;
            $username = $base.$numero;
        }

        return $username;
    }
}
