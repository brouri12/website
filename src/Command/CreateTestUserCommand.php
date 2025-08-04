<?php

namespace App\Command;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Creates a test user for login testing',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if test user already exists
        $existingUser = $this->entityManager->getRepository(Client::class)->findOneBy(['email' => 'test@example.com']);
        
        if ($existingUser) {
            $io->warning('Test user already exists with email: test@example.com');
            return Command::SUCCESS;
        }

        // Create admin user
        $client = new Client();
        $client->setNom('Admin');
        $client->setPrenom('Super');
        $client->setEmail('admin@moodeek.com');
        $client->setNumeroTelephone('+21699999999');
        $client->setDateInscription(new \DateTime());
        $client->setStatutCompte('active');
        $client->setAdresse('1 Rue de l\'Admin, Tunis, 1000, Tunisie');
        $client->setRoles(['ROLE_ADMIN']);

        // Hash the password
        $plainPassword = 'admin1234';
        $hashedPassword = $this->passwordHasher->hashPassword($client, $plainPassword);
        $client->setMotDePasse($hashedPassword);

        // Persist the user
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');
        $io->info('Email: admin@moodeek.com');
        $io->info('Password: admin1234');

        return Command::SUCCESS;
    }
} 