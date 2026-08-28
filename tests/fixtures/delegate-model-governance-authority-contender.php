<?php

declare(strict_types=1);

use App\Imperium\Runtime\Curia\DelegateMissionModelCriteriaRequestService;
use App\Imperium\Runtime\Curia\DelegateMissionModelGovernanceAuthorityTransition;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $authorityId, $gate, $contender] = $argv;
$directory = $root.'/var/imperium/offices/curia/delegate-mission-model-criteria-requests';
while (!is_file($gate)) {
    usleep(1000);
}
$result = DelegateMissionModelGovernanceAuthorityTransition::run($directory, $authorityId, function () use ($directory, $authorityId, $contender): array {
    $existing = glob($directory.'/*.json') ?: [];
    if ([] !== $existing) {
        return json_decode((string) file_get_contents($existing[0]), true, 512, JSON_THROW_ON_ERROR);
    }
    usleep(20000);
    $id = 'delegate-mission-model-criteria-request-'.str_repeat($contender, 20);
    $record = [
        'schema' => 'imperium.curia-delegate-mission-model-criteria-request/v1',
        'request_id' => $id,
        'instance_id' => 'imperium-test',
        'requester' => ['seat' => 'curia.seneschal', 'binding_id' => 'curia-seneschal-binding-'.str_repeat('d', 20)],
        'source_readiness' => ['id' => 'delegate-mission-resource-invocation-readiness-assessment-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
        'criteria_proposal_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false],
        'proposed_criteria' => ['cognitive_task' => 'Contender '.$contender],
        'presented_at' => '2026-08-28T16:00:00+00:00',
        'status' => 'DELEGATE_MISSION_MODEL_CRITERIA_PRESENTED_PENDING_IMPERATOR_DECISION',
        'sealed' => true,
    ];

    return DelegateMissionModelGovernanceAuthorityTransition::put($directory, $id, $record, DelegateMissionModelCriteriaRequestService::class, 'WRITE_FAILED', 'CONFLICT');
});
echo $result['request_id'];
