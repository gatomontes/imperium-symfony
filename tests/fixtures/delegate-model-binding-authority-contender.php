<?php

declare(strict_types=1);

use App\Imperium\Runtime\Conscription\DelegateMissionModelBindingAuthorityTransition;
use App\Imperium\Runtime\Conscription\DelegateMissionModelBindingSealingService;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $authorityId, $gate, $contender] = $argv;
$directory = $root.'/var/imperium/offices/conscription/delegate-mission-model-bindings';
while (!is_file($gate)) {
    usleep(1000);
}
$result = DelegateMissionModelBindingAuthorityTransition::run($directory, $authorityId, function () use ($directory, $authorityId, $contender): array {
    $existing = glob($directory.'/*.json') ?: [];
    if ([] !== $existing) {
        return json_decode((string) file_get_contents($existing[0]), true, 512, JSON_THROW_ON_ERROR);
    }
    usleep(20000);
    $id = 'delegate-mission-model-binding-'.str_repeat($contender, 20);
    $record = [
        'schema' => 'imperium.conscription-delegate-mission-model-binding/v1',
        'binding_id' => $id,
        'instance_id' => 'imperium-test',
        'binder' => ['seat' => 'conscription.recruiter', 'manifestation_id' => 'manifestation-test', 'occupancy_generation' => 1],
        'source_selection_decision' => ['id' => 'delegate-mission-model-selection-decision-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
        'binding_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false],
        'sealed_at' => '2026-08-28T17:00:00+00:00',
        'status' => 'DELEGATE_MISSION_MODEL_BINDING_SEALED_PENDING_ACCESS_ATTESTATION',
        'sealed' => true,
    ];

    return DelegateMissionModelBindingAuthorityTransition::put($directory, $id, $record, DelegateMissionModelBindingSealingService::class, 'WRITE_FAILED', 'CONFLICT');
});
echo $result['binding_id'];
