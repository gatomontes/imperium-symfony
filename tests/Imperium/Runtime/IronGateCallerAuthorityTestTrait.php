<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Imperator\OutboundEmailDecisionService;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityIssuanceService;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract;

trait IronGateCallerAuthorityTestTrait
{
    private string $imperatorPrincipalId = 'imperator-test-principal-version-1';

    private function writeImperatorPrincipal(): void
    {
        $record = ['schema' => ImperatorRuntimePrincipalVersionContract::SCHEMA, 'principal_version_id' => $this->imperatorPrincipalId, 'principal_id' => 'imperator-test-principal', 'instance_id' => 'imperium-test', 'binding_id' => 'imperator-test-binding', 'principal_generation' => 1, 'constitution_route' => 'FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'source_constitution_authority' => ['id' => 'authority-test', 'digest' => str_repeat('1', 64), 'schema' => 'authority/v1'], 'source_operator_root' => ['id' => 'operator-test', 'digest' => str_repeat('2', 64), 'schema' => 'operator/v1'], 'identity' => ['operator_id' => 'operator-test', 'operator_identity_digest' => str_repeat('3', 64), 'imperator_subject_id' => 'imperator-test', 'imperator_subject_digest' => str_repeat('4', 64)], 'authority_scope' => ['provider_binding_activation_authority' => true, 'outbound_email_authority' => true, 'credential_authority' => false, 'provider_execution_authority' => false, 'corridor_disposition_authority' => false], 'lifecycle' => ['constituted_at' => '2026-08-29T20:00:00+00:00', 'effective_at' => '2026-08-29T20:00:00+00:00', 'expires_at' => '2099-08-29T20:00:00+00:00', 'prior_version' => null, 'superseding_version' => null, 'current_disposition' => null], 'status' => 'ACTIVE', 'credential_reference_persisted' => false, 'credential_secret_persisted' => false, 'serialized_capability_persisted' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $directory = $this->root.'/'.DeterministicTransitionCallerAuthorityIssuanceService::IMPERATOR_PRINCIPALS;
        if (!is_dir($directory)) mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$this->imperatorPrincipalId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function authorizedRequest(string $bindingId, array $holder, string $purpose, array $scope, array $providerSafety, \DateTimeImmutable $expiresAt, \DateTimeImmutable $requestedAt): array
    {
        $target = OutboundEmailAuthorizationRequestService::callerAuthorityTarget($bindingId, $holder, $purpose, $scope, $providerSafety, $expiresAt, $requestedAt);
        $authority = (new DeterministicTransitionCallerAuthorityIssuanceService($this->root))->issueSeneschal($bindingId, $target, $requestedAt, $requestedAt->modify('+5 minutes'));

        return (new OutboundEmailAuthorizationRequestService($this->root))->request($authority['authority_id'], $bindingId, $holder, $purpose, $scope, $providerSafety, $expiresAt, $requestedAt);
    }

    private function authorizedDecision(string $requestId, string $disposition, string $rationale, string $limitations, \DateTimeImmutable $expiresAt, \DateTimeImmutable $decidedAt): array
    {
        $request = json_decode((string) file_get_contents($this->root.'/'.OutboundEmailAuthorizationRequestService::REQUESTS.'/'.$requestId.'.json'), true, 512, JSON_THROW_ON_ERROR);
        $authority = (new DeterministicTransitionCallerAuthorityIssuanceService($this->root))->issueImperator($this->imperatorPrincipalId, 'DECIDE_EXACT_OUTBOUND_EMAIL_REQUEST', ['id' => $requestId, 'digest' => $request['record_digest']], $decidedAt, $decidedAt->modify('+5 minutes'));

        return (new OutboundEmailDecisionService($this->root))->decide($authority['authority_id'], $requestId, $disposition, $rationale, $limitations, $expiresAt, $decidedAt);
    }

    private function authorizedIssuance(string $decisionId, \DateTimeImmutable $issuedAt): array
    {
        $decision = json_decode((string) file_get_contents($this->root.'/'.OutboundEmailDecisionService::DECISIONS.'/'.$decisionId.'.json'), true, 512, JSON_THROW_ON_ERROR);
        $authority = (new DeterministicTransitionCallerAuthorityIssuanceService($this->root))->issueImperator($this->imperatorPrincipalId, 'ISSUE_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', ['id' => $decisionId, 'digest' => $decision['record_digest']], $issuedAt, $issuedAt->modify('+5 minutes'));

        return (new OutboundEmailAuthorizationIssuanceService($this->root))->issue($authority['authority_id'], $decisionId, $issuedAt);
    }
}
