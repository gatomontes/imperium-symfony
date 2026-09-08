<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use App\Imperium\Runtime\Citadel\Formation\FormationPersonnel;

/** Session-specific extension: the legacy per-request development decision is not
 * authority for this route. Called within the aggregate reservation transaction.
 */
final readonly class FormationSessionLeaseService
{
    public function __construct(private FormationPersonnel $personnel) {}

    public function derive(array $state, array $session, array $holder, array $request, array $maximum, int $expiresAt, string $attemptId): array
    {
        $issuer = $this->personnel->currentLocksmith($state);
        $decision = $session['provider_resource_decision'];
        $scope = ['provider_resource_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']], 'session_id' => $session['session_id'], 'attempt_id' => $attemptId, 'source_decision_digest' => FormationJournal::digest($session['decision']),
            'holder_digest' => FormationJournal::digest($holder), 'input_digest' => FormationJournal::digest($request),
            'phase' => $session['phase'], 'provider' => $session['terms']['provider'], 'model' => $session['terms']['model'],
            'destination' => $session['terms']['destination'], 'maximum' => $maximum, 'expires_at' => $expiresAt];
        $authority = ['authority_id' => 'citadel-call-authority-'.FormationJournal::digest($scope), 'scope' => $scope,
            'holder' => $holder, 'planning_only' => $session['phase'] === 'drafting',
            'planning_authorization' => $decision['planning_authorization']['authorization_id'] ?? null, 'single_use' => true, 'consumed' => true, 'execution_authority' => false];
        $authority['record_digest'] = FormationJournal::digest($authority);
        $lease = ['lease_id' => 'citadel-session-lease-'.FormationJournal::digest([$authority, $issuer]),
            'authority' => ['id' => $authority['authority_id'], 'digest' => $authority['record_digest']],
            'issuer' => $issuer, 'scope' => $scope, 'single_use' => true, 'consumed' => true,
            'credential_material_present' => false, 'execution_authority' => false];
        $lease['record_digest'] = FormationJournal::digest($lease);
        return ['authority' => $authority, 'lease' => $lease];
    }
}
