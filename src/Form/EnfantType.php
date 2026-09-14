<?php

namespace App\Form;

use App\Entity\Enfant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Profil d'un enfant, créé ou modifié par son parent.
 *
 * Option « creation » : à true, ajoute le mot de passe du compte de connexion.
 */
class EnfantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Liste des avatars pour le ChoiceType : ['Renard malin' => 'renard', …]
        $avatars = [];
        foreach (Enfant::AVATARS as $cle => $avatar) {
            $avatars[$avatar['nom']] = $cle;
        }

        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Léa'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Martin'],
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'help' => 'Digi-Santé Junior est conçu pour les enfants de 8 à 14 ans.',
                // Le calendrier du navigateur ne propose que les dates autorisées
                // (la validation de l'entité reste la vraie protection).
                'attr' => [
                    'min' => (new \DateTimeImmutable('today -15 years +1 day'))->format('Y-m-d'),
                    'max' => (new \DateTimeImmutable('today -8 years'))->format('Y-m-d'),
                ],
            ])
            // Galerie d'avatars : voir templates/form/avatars.html.twig
            ->add('avatar', ChoiceType::class, [
                'label' => 'Avatar',
                'choices' => $avatars,
                'expanded' => true,
                'choice_attr' => fn (string $cle) => [
                    'data-emoji' => Enfant::AVATARS[$cle]['emoji'],
                    'data-couleur' => Enfant::AVATARS[$cle]['couleur'],
                ],
            ])
            ->add('maxMinutesJour', RangeType::class, [
                'label' => 'Limite quotidienne de temps d\'écran',
                'attr' => [
                    'min' => Enfant::LIMITE_MIN,
                    'max' => Enfant::LIMITE_MAX,
                    'step' => Enfant::LIMITE_PAS,
                    'data-limite-curseur' => '',
                ],
            ]);

        // Un curseur envoie du texte (« 120 »). On le convertit en nombre ;
        // une valeur non numérique devient null et la validation l'affiche.
        $builder->get('maxMinutesJour')->addModelTransformer(new CallbackTransformer(
            fn (?int $minutes) => (string) $minutes,
            fn (?string $valeur) => is_numeric($valeur) ? (int) $valeur : null,
        ));

        if ($options['creation']) {
            $builder->add('motDePasse', TextType::class, [
                'label' => 'Mot de passe de connexion',
                'mapped' => false,
                'help' => 'Choisissez un mot de passe simple à retenir pour votre enfant. Notez-le : il ne sera plus affiché ensuite.',
                'attr' => ['autocomplete' => 'off', 'placeholder' => 'Au moins 6 caractères'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Choisissez un mot de passe pour votre enfant.'),
                    new Assert\Length(min: 6, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enfant::class,
            'creation' => false,
        ]);
    }
}
