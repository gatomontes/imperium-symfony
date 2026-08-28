<?php

declare(strict_types=1);

use App\Imperium\Runtime\Curia\OperationalAdoptionAuthorityTransition;
use App\Imperium\Runtime\Curia\OperationalAdoptionReconciliationService;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $authorityId, $gate, $contender] = $argv;
$directory = $root.'/var/imperium/operational/legate-result-adoption-reconciliations';
while (!is_file($gate)) {
    usleep(1000);
}
$result = OperationalAdoptionAuthorityTransition::run($directory, $authorityId, function () use ($directory, $authorityId, $contender): array {
    $existing = glob($directory.'/*.json') ?: [];
    if ([] !== $existing) {
        return json_decode((string) file_get_contents($existing[0]), true, 512, JSON_THROW_ON_ERROR);
    }
    usleep(20000);
    $id = 'legate-result-adoption-reconciliation-'.str_repeat($contender, 20);
    $record = [
        'schema' => 'imperium.legate-result-adoption-reconciliation/v1',
        'reconciliation_id' => $id,
        'instance_id' => 'imperium-test',
        'source_reconciliation_opening' => ['id' => 'legate-result-adoption-reconciliation-opening-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
        'reconciler' => ['seat' => 'curia.seneschal', 'binding_id' => 'curia-seneschal-binding-'.str_repeat('d', 20)],
        'reconciliation_authority' => ['id' => $authorityId, 'single_use' => true, 'consumed' => true, 'continuing_authority' => false],
        'reconciliation' => ['summary' => 'Contender '.$contender],
        'reconciled_at' => '2026-08-28T15:00:00+00:00',
        'status' => 'ADOPTION_ASSESSMENTS_RECONCILED_NO_DISPOSITION_PENDING_ADOPTION_DECISION_OPENING',
        'sealed' => true,
    ];

    return OperationalAdoptionAuthorityTransition::put($directory, $id, $record, OperationalAdoptionReconciliationService::class, 'WRITE_FAILED', 'CONFLICT');
});
echo $result['reconciliation_id'];
