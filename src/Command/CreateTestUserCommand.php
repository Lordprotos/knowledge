<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Crée un client de démonstration activé sans passer par l’envoi d’un e-mail. */
#[AsCommand(name: 'app:create-test-user', description: 'Crée un client de test vérifié.')]
final class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED)->addArgument(
            'password',
            InputArgument::REQUIRED,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            $output->writeln('Cet utilisateur existe déjà.');
            return Command::FAILURE;
        }
        $user = new User($email, '');
        $user->setPassword(
            $this->hasher->hashPassword($user, (string) $input->getArgument('password')),
        );
        $user->verify();
        $user->setAuditUser($email);
        $this->em->persist($user);
        $this->em->flush();
        $output->writeln('Utilisateur test créé.');
        return Command::SUCCESS;
    }
}
