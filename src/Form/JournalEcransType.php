<?php

namespace App\Form;

use App\Entity\JournalEntree;
use App\Twig\DureeExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Étape 1 du journal : un curseur par écran (0 à 6 h, par 15 min).
 *
 * Le formulaire n'est lié à aucune entité : il renvoie un simple tableau
 * ['ecranTv' => '30', …] que le contrôleur garde en session jusqu'à l'étape 2.
 */
class JournalEcransType extends AbstractType
{
    /**
     * Total maximal d'une journée, tous écrans confondus : 16 h.
     * Chaque curseur va jusqu'à 6 h, donc sans ce plafond un enfant pourrait
     * déclarer 36 h d'écran en une journée.
     */
    public const TOTAL_MAX = 960;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (JournalEntree::ECRANS as $champ => $libelle) {
            $builder->add($champ, RangeType::class, [
                'label' => $libelle,
                'attr' => ['min' => 0, 'max' => 360, 'step' => 15],
                'constraints' => [
                    new Assert\Range(
                        notInRangeMessage: 'Indique une durée entre {{ min }} et {{ max }} minutes.',
                        invalidMessage: 'Indique une durée avec le curseur.',
                        min: 0,
                        max: 360,
                    ),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Valeurs de départ : sans valeur, un curseur se place au milieu
            // (3 h). Ce défaut ne s'applique qu'à la première visite : quand le
            // contrôleur passe les valeurs de la session, ce sont elles qui
            // s'affichent.
            'data' => array_fill_keys(array_keys(JournalEntree::ECRANS), 0),
            // Contrainte posée sur le formulaire entier : elle porte sur le
            // total, donc sur plusieurs champs à la fois.
            'constraints' => [new Assert\Callback([self::class, 'verifierTotal'])],
        ]);
    }

    /**
     * Le total de tous les curseurs ne peut pas dépasser TOTAL_MAX.
     *
     * @param array<string, mixed>|null $minutes
     */
    public static function verifierTotal(?array $minutes, ExecutionContextInterface $context): void
    {
        if (null === $minutes) {
            return;
        }

        $total = 0;
        foreach ($minutes as $valeur) {
            $total += (int) $valeur;
        }

        if ($total > self::TOTAL_MAX) {
            $context->buildViolation('En tout, cela fait {{ total }} d\'écran : c\'est impossible en une journée. Reprends tes curseurs (maximum {{ maximum }}).')
                ->setParameter('{{ total }}', DureeExtension::formater($total))
                ->setParameter('{{ maximum }}', DureeExtension::formater(self::TOTAL_MAX))
                ->addViolation();
        }
    }
}
