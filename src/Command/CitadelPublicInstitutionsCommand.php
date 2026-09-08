<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\Formation\FormationInstitution;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:citadel:public-institutions', description: 'Read only native public institutional witnesses from the fixed installation root')]
final class CitadelPublicInstitutionsCommand extends Command
{
    public function __construct(private readonly FormationInstitution $institution) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = []; $missing = false;
        foreach (FormationInstitution::SEATS as $role => $seat) {
            try {
                $rows[$role] = ['status' => 'NATIVE_CHAIN_VERIFIED_CUSTODY_UNVERIFIED', 'witness' => $this->institution->witness($role)];
            } catch (\Throwable $error) {
                $missing = true;
                $rows[$role] = ['status' => 'UNVERIFIED', 'seat' => $seat, 'reason' => $error->getMessage()];
            }
        }
        $output->writeln(json_encode(['schema' => 'imperium.citadel-public-institutions/v1', 'institutions' => $rows,
            'live_ready' => false, 'execution_authority' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
        return $missing ? 2 : self::SUCCESS;
    }
}
