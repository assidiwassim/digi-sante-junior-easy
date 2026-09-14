<?php

namespace App\Security;

use App\Entity\Enfant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Un parent ne peut consulter, modifier ou supprimer QUE ses propres enfants.
 *
 * Utilisation dans un contrôleur :
 *   $this->denyAccessUnlessGranted(EnfantVoter::GERER, $enfant);
 *
 * @extends Voter<string, Enfant>
 */
class EnfantVoter extends Voter
{
    public const GERER = 'ENFANT_GERER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::GERER === $attribute && $subject instanceof Enfant;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Enfant $subject */
        return $subject->getParent()?->getId() === $user->getId();
    }
}
