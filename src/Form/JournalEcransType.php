<?php

namespace App\Form;

use App\Entity\JournalEntree;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Étape 1 du journal : un curseur par écran (0 à 6 h, par 15 min).
 *
 * Le formulaire n'est lié à aucune entité : il renvoie un simple tableau
 * ['ecranTv' => '30', …] que le contrôleur garde en session jusqu'à l'étape 2.
 */
class JournalEcransType extends AbstractType
{
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
}
