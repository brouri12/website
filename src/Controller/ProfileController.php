<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var Client $user */
        $user = $this->getUser();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, TokenStorageInterface $tokenStorage): Response
    {
        /** @var Client $user */
        $user = $this->getUser();
        
        $form = $this->createForm(ClientType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password if it was changed
            $plainPassword = $form->get('mot_de_passe')->getData();
            if ($plainPassword) {
                $user->setMotDePasse($passwordHasher->hashPassword($user, $plainPassword));
            }

            // Mettre à jour l'adresse principale du client avec la première adresse de la collection
            $adresses = $user->getAdresses();
            if (count($adresses) > 0) {
                $adresse = $adresses->first();
                $user->setAdresse(sprintf(
                    '%s, %s, %s, %s',
                    $adresse->getRue(),
                    $adresse->getVille(),
                    $adresse->getCodePostal(),
                    $adresse->getPays()
                ));
            }

            $entityManager->flush();

            // Rafraîchir la session utilisateur pour éviter la déconnexion
            $tokenStorage->setToken(
                new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                    $user,
                    'main',
                    $user->getRoles()
                )
            );

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
} 