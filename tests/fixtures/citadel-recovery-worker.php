<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Command\CitadelFormationCommand;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationInstitution, FormationSignatures, FormationPersonnel, FormationCognition, CuriaFormationService, BoundedFormationTransport, UnavailableFormationTransport};
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Clavium\{FormationSessionLeaseService, ProviderResponseEnvelopeService};
use App\Imperium\Runtime\Curia\ReceivingFormationHandoffService;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\{ContainerBuilder, Reference};

// Test worker only: read existing synthetic public state; no signer or enrollment.
$root = realpath($argv[1] ?? '');
$prefix = str_replace('\\', '/', (string) realpath(sys_get_temp_dir())).'/imperium-citadel-proof-';
if ($root === false || !str_starts_with(str_replace('\\', '/', $root), $prefix)
    || !ctype_digit($argv[2] ?? '')) { exit(2); }
$clock = new class((int) $argv[2]) implements Clock {
    public function __construct(private int $at) {}
    public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.$this->at); }
};
$c = new ContainerBuilder();
$c->register(Clock::class)->setSynthetic(true)->setPublic(true);
$c->register(BoundedFormationTransport::class, UnavailableFormationTransport::class);
foreach ([FormationJournal::class, FormationInstitution::class, ProviderResponseEnvelopeService::class] as $class) { $c->register($class)->setArguments([$root]); }
$c->register(ReceivingFormationHandoffService::class)->setArguments([$root, new Reference(FormationJournal::class)]);
foreach ([FormationSignatures::class, FormationPersonnel::class, FormationCognition::class, FormationSessionLeaseService::class, CitadelFormationCommand::class] as $class) {
    $c->register($class)->setAutowired(true)->setPublic(true);
}
$c->register(CuriaFormationService::class)->setArguments([$root, new Reference(FormationJournal::class), new Reference(FormationSignatures::class), new Reference(FormationPersonnel::class), new Reference(Clock::class)]);
$c->compile(); $c->set(Clock::class, $clock);
$command = new CommandTester($c->get(CitadelFormationCommand::class));
$status = $command->execute(['request-file' => $root.'/recovery-request.json']);
$output = $command->getDisplay();
if ($status === 0) {
    $handoff = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['result'];
    echo json_encode(['handoff_id' => $handoff['handoff_id'], 'digest' => FormationJournal::digest($handoff)], JSON_THROW_ON_ERROR);
} else { echo $output; }
exit($status);
