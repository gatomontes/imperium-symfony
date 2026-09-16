<?php
declare(strict_types=1);
require dirname(__DIR__, 4).'/vendor/autoload.php';

use App\Imperium\Runtime\Citadel\Formation\{FormationInstitution, FormationSignatures, FormationPersonnel, FormationModelPreparation, FormationProfileDesignation, FormationOwnerFrame};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
use App\Imperium\Runtime\Onboarding\Assignment\{NativeAssignmentEvidence, PersistentSettings, AssignmentRule};
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentClock, NativeAssignmentProof};

$input = json_decode((string) file_get_contents($argv[2]), true, 32, JSON_THROW_ON_ERROR);
$clock = new FreshEstablishmentClock(); $clock->at = $input['at'];
$store = new AuthorityStore($argv[1], $clock, $input['instance'], $input['citadel'], $input['operator'], $input['source_commit']);
$signatures = new FormationSignatures($store->journal, $clock); $institution = new FormationInstitution($argv[1]);
$personnel = new FormationPersonnel($store->journal, $signatures, $clock, $institution);
$models = new FormationModelPreparation($store->journal, $signatures, $clock, $institution);
$designations = new FormationProfileDesignation($store->journal, $signatures, $clock, $institution, $personnel);
$native = new NativeAssignmentEvidence($store, $designations, $personnel, $models);
$before = $store->journal->read(); $s = $store->state($before['state']);
$policy = array_values($s['policies'])[0]['record'];
$originals = array_map(static fn(array $v): array => $v['record'], $s['evidence']);
$code = $store->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): string => NativeAssignmentProof::refusal(fn() =>
    $native->verifyInOwner($store, $owner, $policy, $input['rows'], $originals, [])));
$settings = new PersistentSettings($store, evidence: $native); $roles = [];
foreach (AssignmentRule::ROLES as $role) { $roles[$role] = NativeAssignmentProof::refusal(fn() => $settings->resolve($role)); }
$after = $store->journal->read();
echo json_encode(['direct_native_reached' => $code, 'settings_roles' => $roles,
    'before' => ['generation' => $before['generation'], 'digest' => $before['record_digest']],
    'after' => ['generation' => $after['generation'], 'digest' => $after['record_digest']],
    'native_assignment_positive' => false], JSON_THROW_ON_ERROR);
