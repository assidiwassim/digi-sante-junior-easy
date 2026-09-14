<?php

namespace App\Form;

use App\Entity\ContenuBienEtre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contenu de la bibliothèque, géré par l'administrateur.
 */
class ContenuBienEtreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ['📄 Fiche' => 'fiche', …]
        $types = [];
        foreach (ContenuBienEtre::TYPES as $cle => $type) {
            $types[$type['emoji'].' '.$type['label']] = $cle;
        }

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => $types,
            ])
            // array_flip : ['Règle du 20-20-20' => '20-20-20', …]
            ->add('declencheur', ChoiceType::class, [
                'label' => 'Règle du moteur de conseils',
                'choices' => array_flip(ContenuBienEtre::DECLENCHEURS),
                'required' => false,
                'placeholder' => 'Aucune — visible seulement dans la bibliothèque',
                'help' => 'Le premier contenu d\'une règle est proposé à l\'enfant quand celle-ci se déclenche.',
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['rows' => 8],
            ])
            ->add('url', UrlType::class, [
                'label' => 'Lien (vidéo, article…)',
                'required' => false,
                'default_protocol' => 'https',
                'help' => 'Facultatif. Affiché sous forme de bouton dans la bibliothèque et les conseils.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContenuBienEtre::class]);
    }
}
