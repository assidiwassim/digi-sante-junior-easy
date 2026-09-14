<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Étape 2 du journal : le schéma corporel.
 *
 * Le JavaScript de la page écrit les zones choisies dans ce champ caché, au
 * format JSON : {"cou": 3, "yeux": 2}. Le contrôleur revérifie chaque valeur.
 * Passer par un formulaire Symfony apporte la protection CSRF.
 */
class JournalDouleursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('douleurs', HiddenType::class, [
            'required' => false,
        ]);
    }
}
