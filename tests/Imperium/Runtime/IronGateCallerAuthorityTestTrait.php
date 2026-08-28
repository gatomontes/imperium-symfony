<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Imperator\OutboundEmailDecisionService;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityIssuanceService;

trait IronGateCallerAuthorityTestTrait
{
    private string $imperatorPrincipalId = 'imperator-test-principal';

    private function writeImperatorPrincipal(): void
    {
        $record = ['schema' => 'imperium.imperator-runtime-principal/v1', 'principal_id' => $this->imperatorPrincipalId, 'instance_id' => 'imperium-test', 'binding_id' => 'imperator-test-binding', 'principal_generation' => 1, 'status' => 'ACTIVE', 'outbound_email_authority' => true, 'sealed' => true];
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
