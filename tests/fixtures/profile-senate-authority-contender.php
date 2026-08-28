<?php

declare(strict_types=1);

use App\Imperium\Runtime\Senate\ModelBoundProfileExaminationTestimonyOpeningService;
use App\Imperium\Runtime\Senate\ProfileSenateAuthorityTransition;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $authorityId, $gate, $contender] = $argv;
$directory = $root.'/var/imperium/offices/senate/model-bound-profile-examination-testimony-openings';
while (!is_file($gate)) {
    usleep(1000);
}
$result = ProfileSenateAuthorityTransition::run($directory, $authorityId, function () use ($directory, $authorityId, $contender): array {
    $existing = glob($directory.'/*.json') ?: [];
    if ([] !== $existing) {
        return json_decode((string) file_get_contents($existing[0]), true, 512, JSON_THROW_ON_ERROR);
    }
    usleep(20000);
    $id = 'model-bound-profile-examination-testimony-opening-'.str_repeat($contender, 20);
    $record = [
        'schema' => 'imperium.senate-model-bound-profile-examination-testimony-opening/v1',
        'opening_id' => $id,
        'instance_id' => 'imperium-test',
        'case_id' => 'profile-examination-case-'.str_repeat('1', 20),
        'case_digest' => str_repeat('2', 64),
        'source_panel_readiness' => ['id' => 'model-bound-profile-examination-panel-readiness-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
        'testimony_opening_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false],
        'lord_speaker' => ['seat' => 'senate.lord-speaker', 'binding_id' => 'senate-lord-speaker-binding-'.str_repeat('d', 20)],
        'subject_profile' => ['profile_id' => 'model-bound-profile'],
        'evidence_chain' => ['model_binding' => str_repeat('3', 64)],
        'question_authorities' => [],
        'opened_at' => '2026-08-28T12:30:00+00:00',
        'status' => 'PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING',
        'sealed' => true,
    ];

    return ProfileSenateAuthorityTransition::put($directory, $id, $record, ModelBoundProfileExaminationTestimonyOpeningService::class, 'WRITE_FAILED', 'CONFLICT');
});
echo $result['opening_id'];
