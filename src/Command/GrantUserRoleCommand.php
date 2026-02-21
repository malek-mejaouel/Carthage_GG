<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:grant-role',
    description: 'Grant a Symfony role to a user by id or email'
)]
class GrantUserRoleCommand extends Command
{
    private UserRepository $users;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $users, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->users = $users;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'User id')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Role name (e.g., ROLE_VERIFIER)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $role = (string) $input->getOption('role');
        if ($role === '' || strncmp($role, 'ROLE_', 5) !== 0) {
            $output->writeln('<error>Provide a valid role starting with ROLE_</error>');
            return Command::FAILURE;
        }
        $id = $input->getOption('id');
        $email = $input->getOption('email');
        $user = null;
        if ($id !== null) {
            $user = $this->users->find((int) $id);
        } elseif ($email !== null) {
            $user = $this->users->findOneBy(['email' => (string) $email]);
        } else {
            $output->writeln('<error>Specify either --id or --email</error>');
            return Command::FAILURE;
        }
        if (!$user) {
            $output->writeln('<error>User not found</error>');
            return Command::FAILURE;
        }
        $roles = $user->getRoles();
        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
            $user->setRoles(array_values(array_unique($roles)));
            $this->em->persist($user);
            $this->em->flush();
            $output->writeln('<info>Granted ' . $role . ' to user #' . $user->getId() . '</info>');
        } else {
            $output->writeln('<comment>User already has role ' . $role . '</comment>');
        }
        return Command::SUCCESS;
    }
}
