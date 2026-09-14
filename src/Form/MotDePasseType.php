<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Choix d'un nouveau mot de passe (saisi deux fois).
 *
 * Utilisé par le parent (son profil, ou le compte d'un enfant) et par l'enfant.
 * Les libellés peuvent être changés dans le template avec form_row(…, {label: …}).
 */
class MotDePasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Les deux mots de passe ne sont pas identiques.',
            'first_options' => ['label' => 'Nouveau mot de passe', 'attr' => ['autocomplete' => 'new-password']],
            'second_options' => ['label' => 'Confirmez le nouveau mot de passe', 'attr' => ['autocomplete' => 'new-password']],
            'constraints' => [
                new Assert\NotBlank(message: 'Merci de saisir un mot de passe.'),
                new Assert\Length(min: 6, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
            ],
        ]);
    }
}
