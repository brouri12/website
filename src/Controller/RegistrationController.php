<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Adresse;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, LoggerInterface $logger, ClientRepository $clientRepository): Response
    {
        $client = new Client();
        // Initialize with one address
        $adresse = new Adresse();
        $client->addAdress($adresse);
        
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        // Debug: Log form submission
        $logger->info('Form submitted', [
            'isSubmitted' => $form->isSubmitted(),
            'method' => $request->getMethod()
        ]);

        if ($form->isSubmitted()) {
            $logger->info('Form is submitted, checking validity...');
            
            if ($form->isValid()) {
                $logger->info('Form is valid, processing registration...');
                
                try {
                    // Check if email already exists
                    $existingClientByEmail = $clientRepository->findOneBy(['email' => $client->getEmail()]);
                    if ($existingClientByEmail) {
                        throw new \Exception('Cet email est déjà utilisé');
                    }

                    // Check if phone number already exists
                    $existingClientByPhone = $clientRepository->findOneBy(['numero_telephone' => $client->getNumeroTelephone()]);
                    if ($existingClientByPhone) {
                        throw new \Exception('Ce numéro de téléphone est déjà utilisé');
                    }

                    // Debug: Log client data before processing
                    $logger->info('Client data before processing', [
                        'nom' => $client->getNom(),
                        'prenom' => $client->getPrenom(),
                        'email' => $client->getEmail(),
                        'hasPassword' => !empty($client->getMotDePasse()),
                        'telephone' => $client->getNumeroTelephone()
                    ]);

                    // Hash the password
                    $plainPassword = $client->getMotDePasse();
                    if (!$plainPassword) {
                        throw new \Exception('Password is missing');
                    }
                    $client->setMotDePasse($passwordHasher->hashPassword($client, $plainPassword));

                    // Set default values
                    $client->setDateInscription(new \DateTime());
                    $client->setStatutCompte('active');
                    // Rôle par défaut
                    $client->setRoles(['ROLE_USER']);

                    // Concatenate address fields into Client::adresse
                    $adresses = $client->getAdresses();
                    if ($adresses->isEmpty()) {
                        throw new \Exception('No address provided');
                    }

                    $adresse = $adresses->first();
                    
                    // Debug: Log address data
                    $logger->info('Address data', [
                        'rue' => $adresse->getRue(),
                        'ville' => $adresse->getVille(),
                        'codePostal' => $adresse->getCodePostal(),
                        'pays' => $adresse->getPays()
                    ]);
                    
                    if (!$adresse->getRue() || !$adresse->getVille() || !$adresse->getCodePostal() || !$adresse->getPays()) {
                        throw new \Exception('One or more address fields are missing');
                    }

                    $concatenatedAddress = sprintf(
                        '%s, %s, %s, %s',
                        $adresse->getRue(),
                        $adresse->getVille(),
                        $adresse->getCodePostal(),
                        $adresse->getPays()
                    );
                    $client->setAdresse($concatenatedAddress);
                    
                    // Set the client reference in the address
                    $adresse->setClient($client);

                    // Debug: Log before persist
                    $logger->info('About to persist entities...');

                    // Persist entities in correct order
                    $entityManager->persist($client);
                    $entityManager->persist($adresse);
                    
                    // Debug: Log before flush
                    $logger->info('About to flush to database...');
                    
                    $entityManager->flush();

                    $logger->info('Client registered successfully', [
                        'email' => $client->getEmail(),
                        'adresse' => $concatenatedAddress,
                        'clientId' => $client->getId()
                    ]);

                    $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_login');
                    
                } catch (\Exception $e) {
                    $logger->error('Error during registration: ' . $e->getMessage(), [
                        'email' => $client->getEmail(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription : ' . $e->getMessage());
                }
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $logger->warning('Form is invalid', ['errors' => $errors]);
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire : ' . implode(', ', $errors));
            }
        }

        return $this->render('register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}