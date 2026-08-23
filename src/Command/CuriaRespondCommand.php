<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\CurianDeliberation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:respond', description: 'Append an Imperator response and advance a Curian proceeding')]
final class CuriaRespondCommand extends Command
{
    public function __construct(private readonly CurianDeliberation $deliberation)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('proceeding-id', InputArgument::REQUIRED, 'Existing Curian proceeding identity')
            ->addArgument('response', InputArgument::REQUIRED, 'Exact Imperator response')
            ->addOption('response-id', null, InputOption::VALUE_REQUIRED, 'Stable idempotency identity for this response')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete immutable turn record');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $turn = $this->deliberation->respond(
                (string) $input->getArgument('proceeding-id'),
                (string) $input->getArgument('response'),
                is_string($input->getOption('response-id')) ? $input->getOption('response-id') : null,
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($turn, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $output->writeln('<info>'.$turn['seneschal']['disposition'].'</info> turn '.$turn['sequence'].' '.$turn['response_id']);
        $output->writeln('Chamberlain: '.$turn['chamberlain']['disposition']);
        $output->writeln('Isolde: '.$turn['secretary']['disposition']);
        $output->writeln('Seneschal: '.$turn['seneschal']['decision']);
        if (null !== $turn['seneschal']['question']) {
            $output->writeln('Question: '.$turn['seneschal']['question']);
        }
        $output->writeln('Resources: '.([] === $turn['resource_demands'] ? 'none declared' : implode(', ', $turn['resource_demands'])));
        $output->writeln('Authorization: '.($turn['authorization_required'] ? 'required' : 'not currently required'));

        return self::SUCCESS;
    }
}
