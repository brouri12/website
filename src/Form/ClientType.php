<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Adresse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom ne peut pas être vide'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Entrez votre nom',
                    'minlength' => '2',
                    'maxlength' => '50',
                    'pattern' => '[a-zA-ZÀ-ÿ\s\'-]+',
                    'title' => 'Lettres, espaces, tirets et apostrophes uniquement'
                ]
            ])
            ->add('prenom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le prénom ne peut pas être vide'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Entrez votre prénom',
                    'minlength' => '2',
                    'maxlength' => '50',
                    'pattern' => '[a-zA-ZÀ-ÿ\s\'-]+',
                    'title' => 'Lettres, espaces, tirets et apostrophes uniquement'
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'email ne peut pas être vide'
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email n\'est pas valide'
                    ]),
                    new Assert\Length([
                        'max' => 180,
                        'maxMessage' => 'L\'email ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'exemple@email.com',
                    'maxlength' => '180',
                    'type' => 'email'
                ]
            ])
            ->add('mot_de_passe', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false, // <-- Important !
                'first_options' => [
                    'label' => 'Mot de passe',
                    'constraints' => [
                        new Assert\Callback(function ($value, $context) {
                            if ($value) {
                                if (strlen($value) < 8) {
                                    $context->buildViolation('Le mot de passe doit contenir au moins 8 caractères.')->addViolation();
                                }
                                if (!preg_match('/[A-Z]/', $value)) {
                                    $context->buildViolation('Le mot de passe doit contenir une majuscule.')->addViolation();
                                }
                                if (!preg_match('/[a-z]/', $value)) {
                                    $context->buildViolation('Le mot de passe doit contenir une minuscule.')->addViolation();
                                }
                                if (!preg_match('/\d/', $value)) {
                                    $context->buildViolation('Le mot de passe doit contenir un chiffre.')->addViolation();
                                }
                                if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
                                    $context->buildViolation('Le mot de passe doit contenir un caractère spécial.')->addViolation();
                                }
                            }
                        })
                    ],
                    'attr' => [
                        'placeholder' => 'Entrez votre mot de passe',
                        'minlength' => '8',
                        'maxlength' => '255',
                        'pattern' => '(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^a-zA-Z0-9]).{8,}',
                        'title' => 'Au moins 8 caractères avec minuscule, majuscule, chiffre et caractère spécial'
                    ],
                    'empty_data' => '',
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'placeholder' => 'Confirmez votre mot de passe',
                        'minlength' => '8',
                        'maxlength' => '255'
                    ],
                    'empty_data' => '',
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
            ])
            ->add('numero_telephone', TelType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le numéro de téléphone ne peut pas être vide'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(\+216|216)?[0-9]{8}$/',
                        'message' => 'Le numéro de téléphone doit être au format tunisien valide'
                    ]),
                    new Assert\Length([
                        'min' => 8,
                        'max' => 12,
                        'minMessage' => 'Le numéro de téléphone doit contenir au moins {{ limit }} chiffres',
                        'maxMessage' => 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'placeholder' => '71234567 ou +21671234567',
                    'minlength' => '8',
                    'maxlength' => '12',
                    'pattern' => '(\+216|216)?[0-9]{8}',
                    'title' => 'Format tunisien : 71234567 ou +21671234567'
                ]
            ])
            ->add('adresses', CollectionType::class, [
                'entry_type' => AdresseType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
                'prototype' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'validation_groups' => function ($form) {
                // Si l'entité n'a pas d'id, c'est une création
                $client = $form->getData();
                return ($client && !$client->getId()) ? ['Default', 'registration'] : ['Default'];
            },
        ]);
    }
}