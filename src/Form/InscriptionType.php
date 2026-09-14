<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Création d'un compte parent.
 */
class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['placeholder' => 'parent@exemple.fr', 'autocomplete' => 'email'],
                // L'email est optionnel dans l'entité (les enfants n'en ont pas),
                // mais obligatoire pour un parent.
                'constraints' => [new Assert\NotBlank(message: 'Merci de saisir votre email.')],
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => ['placeholder' => 'France'],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['placeholder' => 'Lyon'],
            ])
            // « mapped: false » : le mot de passe en clair n'est pas une propriété
            // de User. Le contrôleur le hache puis appelle setPassword().
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'first_options' => ['label' => 'Mot de passe', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Confirmez le mot de passe', 'attr' => ['autocomplete' => 'new-password']],
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de choisir un mot de passe.'),
                    new Assert\Length(min: 6, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ])
            ->add('conditions', CheckboxType::class, [
                'label' => 'J\'accepte que les données de suivi de mes enfants soient enregistrées.',
                'mapped' => false,
                'constraints' => [new Assert\IsTrue(message: 'Vous devez accepter les conditions pour créer un compte.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
