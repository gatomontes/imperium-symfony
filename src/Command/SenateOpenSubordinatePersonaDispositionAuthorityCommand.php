<?php declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Senate\SubordinatePersonaDispositionAuthorityOpeningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name: 'imperium:senate:open-subordinate-persona-disposition-authority')]
final class SenateOpenSubordinatePersonaDispositionAuthorityCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaDispositionAuthorityOpeningService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('reconciliation-id', InputArgument::REQUIRED); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $record = $this->service->open((string) $input->getArgument('reconciliation-id'), new \DateTimeImmutable()); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        $output->writeln('<info>PERSONA_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION</info> '.$record['opening_id']);
        return self::SUCCESS;
    }
}
