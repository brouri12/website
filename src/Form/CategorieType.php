<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom_categorie', TextType::class, [
                'label' => 'Nom de la catégorie'
            ])
            ->add('description_categorie', TextType::class, [
                'label' => 'Description',
                'required' => false
            ])
            ->add('categorieParent', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nomCategorie',
                'required' => false,
                'label' => 'Catégorie parente',
                'placeholder' => 'Aucune'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
        ]);
    }
} 