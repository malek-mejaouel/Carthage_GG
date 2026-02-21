<?php

namespace App\Command;

use App\Service\VerificationDocumentGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'app:generate-verification-docs',
    description: 'Generate verification reference and test certification documents'
)]
class GenerateVerificationDocsCommand extends Command
{
    private VerificationDocumentGenerator $generator;

    public function __construct(VerificationDocumentGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    protected function configure(): void
    {
        $this
            ->addOption('first', null, InputOption::VALUE_REQUIRED, 'First name for custom test image')
            ->addOption('last', null, InputOption::VALUE_REQUIRED, 'Last name for custom test image')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Role for custom test image');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tpl = $this->generator->generateTemplate();
        if (!($tpl['success'] ?? false)) {
            $output->writeln('<error>Template generation failed: ' . ($tpl['message'] ?? 'unknown') . '</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>Template generated:</info> ' . $tpl['path']);
        $test = $this->generator->generateTestDocument();
        if (!($test['success'] ?? false)) {
            $output->writeln('<error>Test document generation failed: ' . ($test['message'] ?? 'unknown') . '</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>Test document generated:</info> ' . $test['path']);

        $first = (string) $input->getOption('first');
        $last = (string) $input->getOption('last');
        $role = (string) $input->getOption('role');
        if ($first !== '' || $last !== '' || $role !== '') {
            if ($first === '' || $last === '' || $role === '') {
                $output->writeln('<comment>Custom test image options require --first, --last, and --role</comment>');
            } else {
                $custom = $this->generator->generateCustomDocument($first, $last, $role);
                if (!($custom['success'] ?? false)) {
                    $output->writeln('<error>Custom test document generation failed: ' . ($custom['message'] ?? 'unknown') . '</error>');
                    return Command::FAILURE;
                }
                $output->writeln('<info>Custom test document generated:</info> ' . $custom['path']);
            }
        }
        return Command::SUCCESS;
    }
}
