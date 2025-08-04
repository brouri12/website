<?php

namespace App\Form;

use App\Entity\Adresse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AdresseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rue', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La rue ne peut pas être vide'
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'L\'adresse doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9À-ÿ\s\',.-]+$/',
                        'message' => 'L\'adresse ne peut contenir que des lettres, chiffres, espaces et caractères de ponctuation'
                    ])
                ],
                'attr' => [
                    'placeholder' => '123 Rue de la Paix',
                    'minlength' => '5',
                    'maxlength' => '255',
                    'pattern' => '[a-zA-Z0-9À-ÿ\s\',.-]+',
                    'title' => 'Lettres, chiffres, espaces et ponctuation uniquement'
                ]
            ])
            ->add('ville', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La ville ne peut pas être vide'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'La ville doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                        'message' => 'La ville ne peut contenir que des lettres, espaces, tirets et apostrophes'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Paris',
                    'minlength' => '2',
                    'maxlength' => '100',
                    'pattern' => '[a-zA-ZÀ-ÿ\s\'-]+',
                    'title' => 'Lettres, espaces, tirets et apostrophes uniquement'
                ]
            ])
            ->add('code_postal', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le code postal ne peut pas être vide'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{4}$/',
                        'message' => 'Le code postal doit contenir exactement 4 chiffres'
                    ])
                ],
                'attr' => [
                    'placeholder' => '1000',
                    'minlength' => '4',
                    'maxlength' => '4',
                    'pattern' => '[0-9]{4}',
                    'title' => 'Exactement 4 chiffres (format tunisien)'
                ]
            ])
            ->add('pays', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le pays ne peut pas être vide'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le pays doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le pays ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                        'message' => 'Le pays ne peut contenir que des lettres, espaces, tirets et apostrophes'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'France',
                    'minlength' => '2',
                    'maxlength' => '100',
                    'pattern' => '[a-zA-ZÀ-ÿ\s\'-]+',
                    'title' => 'Lettres, espaces, tirets et apostrophes uniquement'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adresse::class,
        ]);
    }
}