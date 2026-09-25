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

/** Crée en ligne de commande un administrateur déjà activé, avec un mot de passe haché. */
#[AsCommand(name: 'app:create-admin', description: 'Crée un administrateur vérifié.')]
final class CreateAdminCommand extends Command
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
        $email = mb_strtolower((string) $input->getArgument('email'));
        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            $output->writeln('<error>Adresse déjà utilisée.</error>');
            return Command::FAILURE;
        }
        $user = new User($email, '');
        $user->setPassword(
            $this->hasher->hashPassword($user, (string) $input->getArgument('password')),
        );
        $user->setRoles(['ROLE_ADMIN', 'ROLE_CLIENT']);
        $user->setVerificationToken(bin2hex(random_bytes(32)));
        $user->verify();
        $user->setAuditUser($email);
        $this->em->persist($user);
        $this->em->flush();
        $output->writeln('Administrateur créé.');
        return Command::SUCCESS;
    }
}
