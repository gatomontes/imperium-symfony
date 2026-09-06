<?php
declare(strict_types=1);
namespace App\Command;

use App\SourceReview\{Proposal, SnapshotStore, Workflow};
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:source-review', description: 'Prepare, inspect, authorize or dispatch one exact bounded source review.')]
final class SourceReviewCommand extends Command
{
    public function __construct(private readonly SnapshotStore $snapshots, private readonly Workflow $workflow) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'prepare | inspect | authorize | lease | execute | status');
        $this->addArgument('values', InputArgument::IS_ARRAY, 'Arguments documented in docs/source-review.md');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $v = $input->getArgument('values'); $action = $input->getArgument('action');
            $counts = ['prepare' => 1, 'inspect' => 1, 'authorize' => 4, 'lease' => 2, 'execute' => 2, 'status' => 1];
            if (!isset($counts[$action]) || count($v) !== $counts[$action]) { throw new \RuntimeException('SR_USAGE_INVALID'); }
            $r = match ($action) {
                'prepare' => $this->snapshots->prepare($v[0]),
                'inspect' => $this->snapshots->get($v[0]),
                'authorize' => $this->workflow->authorize(...$v),
                'lease' => $this->workflow->lease(...$v),
                'execute' => $this->workflow->execute(...$v),
                'status' => $this->workflow->status(...$v),
            };
            if (in_array($action, ['prepare', 'inspect'], true)) {
                $p = Proposal::validate($r);
                $r = ['proposal_digest_for_approval' => Proposal::digest($p), 'proposal' => $p];
            }
            $output->writeln(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $message = preg_match('/^[A-Z][A-Z0-9_]+$/D', $e->getMessage()) ? $e->getMessage() : 'SR_INPUT_OR_OPERATION_FAILED';
            $output->writeln('REFUSED '.$message); return self::FAILURE;
        }
    }
}
