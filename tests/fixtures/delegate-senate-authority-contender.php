<?php

declare(strict_types=1);

use App\Imperium\Runtime\Senate\DelegateMissionDeliberationOpeningService;
use App\Imperium\Runtime\Senate\DelegateMissionSenateAuthorityTransition;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $authorityId, $gate, $contender] = $argv;
$directory = $root.'/var/imperium/offices/senate/delegate-mission-profile-examination-deliberation-openings';
while (!is_file($gate)) {
    usleep(1000);
}
$result = DelegateMissionSenateAuthorityTransition::run($directory, $authorityId, function () use ($directory, $authorityId, $contender): array {
    $existing = glob($directory.'/*.json') ?: [];
    if ([] !== $existing) {
        return json_decode((string) file_get_contents($existing[0]), true, 512, JSON_THROW_ON_ERROR);
    }
    usleep(20000);
    $id = 'delegate-mission-profile-examination-deliberation-opening-'.str_repeat($contender, 20);
    $record = [
        'schema' => 'imperium.senate-delegate-mission-profile-examination-deliberation-opening/v1',
        'deliberation_id' => $id,
        'instance_id' => 'imperium-test',
        'source_finding_readiness' => ['id' => 'delegate-mission-profile-examination-finding-readiness-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
        'lord_speaker' => ['seat' => 'senate.lord-speaker', 'binding_id' => 'senate-lord-speaker-binding-'.str_repeat('d', 20)],
        'deliberation_opening_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false],
        'opened_at' => '2026-08-28T12:00:00+00:00',
        'status' => 'DELEGATE_MISSION_DELIBERATION_OPENED_PENDING_FINDING_RECONCILIATION',
        'sealed' => true,
    ];

    return DelegateMissionSenateAuthorityTransition::put($directory, $id, $record, DelegateMissionDeliberationOpeningService::class, 'WRITE_FAILED', 'CONFLICT');
});
echo $result['deliberation_id'];
