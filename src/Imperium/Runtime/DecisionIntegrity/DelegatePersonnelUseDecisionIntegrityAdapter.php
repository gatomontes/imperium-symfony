<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegatePersonnelUseDecisionIntegrityAdapter
{
    private const string NO_PREEXISTING_EXPIRY = '9999-12-31T23:59:59+00:00';
    private const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'];
    private const array DENIED_AUTHORITIES = [
        'reservation', 'retrieval', 'custody transfer', 'profile derivation', 'profile examination', 'profile approval',
        'profile installation', 'profile qualification', 'manifestation assembly', 'seat binding', 'deployment',
        'operational use', 'cognition', 'provider invocation', 'data access', 'tool use', 'credential use',
        'perimeter crossing', 'external action', 'execution', 'continuing turn',
    ];

    private DecisionIntegrityRecordStore $store;
    private DecisionSurfaceAssembler $assembler;
    private DecisionIntegrityReconstructionService $reconstruction;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?DecisionIntegrityRecordStore $store = null)
    {
        $this->store = $store ?? new DecisionIntegrityRecordStore($root);
        $this->assembler = new DecisionSurfaceAssembler($this->store);
        $this->reconstruction = new DecisionIntegrityReconstructionService($this->store);
    }

    public function presentSurface(string $requestId, array $resolution, array $intake, array $demand, array $garrisonResponse, \DateTimeImmutable $presentedAt): array
    {
        $presentedAt = $presentedAt->setTimezone(new \DateTimeZone('UTC'));
        $evidence = [
            $this->evidence($resolution['resolution_id'], $resolution['record_digest'], 'guildhall-personnel-resolution', 'Exact profession and Persona suitability resolution.', $presentedAt),
            $this->evidence($intake['disposition_id'], $intake['record_digest'], 'guildhall-capability-demand-intake', 'Accepted intake of the exact mission capability demand.', $presentedAt),
            $this->evidence($demand['demand_id'], $demand['record_digest'], 'curia-capability-demand', 'Exact mission-bound capabilities, outcomes, and Seat.', $presentedAt),
            $this->evidence($garrisonResponse['response_id'], $garrisonResponse['record_digest'], 'garrison-inventory-facts', 'Authoritative Persona custody and availability facts.', $presentedAt),
        ];
        $evidenceIds = array_column($evidence, 'artifact_id');
        $options = [
            $this->option('authorize-exact-personnel-commitment', 'Authorize the exact Guildhall-resolved profession and Persona for this mission-bound capability demand.', 'Opens only one single-use Guildhall acceptance authority.', $evidenceIds),
            $this->option('refuse-exact-personnel-commitment', 'Refuse the exact personnel commitment.', 'Records refusal and opens no personnel-use authority.', $evidenceIds),
            $this->option('return-for-revision', 'Return the exact request for revision.', 'Requires Curia follow-up and opens no personnel-use authority.', $evidenceIds),
            $this->option('propose-alternative', 'Propose that a different exact commitment be considered.', 'Requires a new governed resolution path and opens no personnel-use authority.', $evidenceIds),
            $this->option('require-clarification', 'Require clarification before deciding.', 'Keeps the matter non-authorizing pending clarification.', $evidenceIds),
            $this->option('defer-decision', 'Defer the personnel-use decision.', 'Records deferral and opens no personnel-use authority.', $evidenceIds),
        ];
        $universe = $this->seal([
            'schema' => DecisionSurfaceOptionUniverseContract::SCHEMA,
            'universe_id' => 'personnel-use-option-universe-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $resolution['record_digest'], $demand['record_digest'], $garrisonResponse['record_digest']])), 0, 20),
            'instance_id' => $resolution['instance_id'],
            'proceeding_id' => $requestId,
            'options' => $options,
            'evidence' => $evidence,
            'sealed' => true,
        ]);
        $directive = $this->seal([
            'schema' => DecisionSurfacePresentationDirectiveContract::SCHEMA,
            'directive_id' => 'personnel-use-presentation-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $universe['record_digest'], $presentedAt->format(DATE_ATOM)])), 0, 20),
            'instance_id' => $resolution['instance_id'],
            'proceeding_id' => $requestId,
            'source_option_universe' => ['id' => $universe['universe_id'], 'digest' => $universe['record_digest']],
            'decision_owner' => ['actor_id' => 'imperator-development-root', 'office_or_seat' => 'imperator', 'authority_basis' => 'development-local-cli', 'accountability_boundary' => 'One exact Delegate personnel-use decision only.'],
            'decision_question' => 'Authorize mission-bound use of this exact Guildhall-resolved profession and Persona for the exact correlated Delegate capability demand?',
            'presented_option_ids' => array_column($options, 'option_id'),
            'unavailable_option_ids' => [],
            'prohibited_option_ids' => [],
            'rejected_option_ids' => [],
            'unexamined_option_ids' => [],
            'material_consequences' => 'Authorization opens only one exact Guildhall acceptance authority; every later reservation, Profile, deployment, resource, and execution decision remains separate.',
            'risks' => ['The exact Persona may become temporarily unavailable after a later independent Garrison reservation decision.'],
            'reversibility' => 'Non-authorizing dispositions grant nothing; an authorization is single-use and the later lifecycle retains its declared return path.',
            'recommendation' => ['author' => 'curia.seneschal', 'recommended_option_id' => 'authorize-exact-personnel-commitment', 'rationale' => 'Present the exact commitment resolved suitable by Guildhall from the cited Garrison facts: '.$resolution['rationale']],
            'evidence' => $evidence,
            'requested_authority' => 'ONE_EXACT_DELEGATE_PERSONNEL_USE_COMMITMENT_ONLY',
            'authority_not_requested' => self::DENIED_AUTHORITIES,
            'limitations' => ['No profession or Persona substitution.', 'No reservation, Profile, deployment, resource, external-action, or execution authority.', 'No continuing authority.'],
            'expires_at' => self::NO_PREEXISTING_EXPIRY,
            'allowed_dispositions' => self::DISPOSITIONS,
            'authored_at' => $presentedAt->format(DATE_ATOM),
            'sealed' => true,
        ]);

        return $this->assembler->assemble($universe, $directive, $presentedAt);
    }

    public function readSurface(array $request): array
    {
        $reference = $request['institutional_decision_surface'] ?? null;
        if (!is_array($reference) || !is_string($reference['id'] ?? null) || !is_string($reference['digest'] ?? null)) {
            throw new \RuntimeException('DI170_PERSONNEL_USE_SURFACE_REFERENCE_INVALID');
        }
        $surface = $this->store->readSurface($reference['id']);
        if ($surface['record_digest'] !== $reference['digest']
            || $surface['instance_id'] !== $request['instance_id']
            || $surface['proceeding_id'] !== $request['request_id']
            || true !== $surface['authorization_state']['decision_pending']
            || false !== $surface['authorization_state']['authority_granted']) {
            throw new \RuntimeException('DI170_PERSONNEL_USE_SURFACE_REFERENCE_INVALID');
        }

        return $surface;
    }

    public function recordDecision(array $request, array $surface, string $legacyDecisionId, string $disposition, string $response, ?string $limitations, \DateTimeImmutable $decidedAt, ?array $legacyAuthority, string $resultingState): array
    {
        $decidedAt = $decidedAt->setTimezone(new \DateTimeZone('UTC'));
        $authorized = 'AUTHORIZED' === $disposition;
        $recordId = 'decision-record-'.substr(hash('sha256', CanonicalJson::encode([$legacyDecisionId, $request['record_digest'], $surface['record_digest'], $disposition, $response, $limitations])), 0, 20);
        $granted = $authorized ? ['ACCEPT_ONE_EXACT_AUTHORIZED_DELEGATE_PERSONNEL_COMMITMENT'] : [];
        $selectedOption = $this->optionForDisposition($disposition);
        $options = array_map(static function (array $option) use ($selectedOption, $disposition): array {
            $selected = $selectedOption === $option['option_id'];

            return ['option_id' => $option['option_id'], 'examined_disposition' => $selected ? 'SELECTED' : 'REJECTED', 'reason' => $selected ? 'Selected by the exact Imperator '.$disposition.' disposition.' : 'Not selected by the exact Imperator disposition.'];
        }, $surface['options_presented']);
        $risk = $authorized ? [[
            'identified_risk' => $surface['risks'][0],
            'proposed_treatment' => 'Retain every later custody and lifecycle decision as separately governed.',
            'applied_treatment' => 'Grant only the exact single-use Guildhall acceptance authority under the stated limitations.',
            'residual_risk' => 'The Persona may become temporarily unavailable if Garrison later reserves it.',
            'residual_risk_owner' => ['actor_id' => 'imperator-development-root', 'office_or_seat' => 'imperator', 'authority_basis' => 'development-local-cli', 'competent_authority' => true],
            'acceptance_disposition' => 'ACCEPTED',
        ]] : [[
            'identified_risk' => $surface['risks'][0],
            'proposed_treatment' => 'Do not open personnel-use authority.',
            'applied_treatment' => 'The disposition remains non-authorizing.',
            'residual_risk' => 'NONE',
            'residual_risk_owner' => null,
            'acceptance_disposition' => 'REFUSED',
        ]];
        $lineage = $authorized ? [[
            'authority' => $granted[0],
            'source' => $recordId,
            'consumer' => 'guildhall.guildmaster',
            'scope' => 'Personnel commitment digest '.hash('sha256', CanonicalJson::encode($request['personnel_commitment'])),
            'limitations' => null === $limitations ? [] : [$limitations],
            'expires_at' => $surface['expires_at'],
            'continuing_authority' => false,
        ]] : [];
        $record = [
            'schema' => DefensibleDecisionRecordContract::SCHEMA,
            'decision_record_id' => $recordId,
            'instance_id' => $request['instance_id'],
            'proceeding_id' => $request['request_id'],
            'source_decision_surface' => ['id' => $surface['surface_id'], 'digest' => $surface['record_digest']],
            'source_requests' => [['id' => $request['request_id'], 'digest' => $request['record_digest']]],
            'prior_decisions' => [],
            'underlying_proceeding_evidence' => array_merge([$surface['source_option_universe'], $surface['source_presentation_directive']], array_map(static fn (array $item): array => ['id' => $item['artifact_id'], 'digest' => $item['record_digest']], $surface['evidence'])),
            'decision' => [
                'disposition' => $disposition,
                'decided_scope' => 'One exact Delegate personnel commitment: '.hash('sha256', CanonicalJson::encode($request['personnel_commitment'])),
                'granted_authority' => $granted,
                'denied_authority' => self::DENIED_AUTHORITIES,
                'resulting_state' => $resultingState,
                'everything_remaining_unauthorized' => self::DENIED_AUTHORITIES,
            ],
            'decision_owner' => ['actor_id' => 'imperator-development-root', 'office_or_seat' => 'imperator', 'authority_basis' => 'development-local-cli', 'accountability_boundary' => 'One exact Delegate personnel-use decision only.'],
            'options_considered' => $options,
            'risks' => $risk,
            'evidence_relied_on' => $surface['evidence'],
            'rationale' => 'Imperator response: '.$response.(null === $limitations ? ' No additional limitations stated.' : ' Limitations: '.$limitations),
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'limitations' => null === $limitations ? [] : [$limitations],
            'expires_at' => $surface['expires_at'],
            'authority_lineage' => $lineage,
            'supersession' => ['supersedes' => null, 'reason' => null],
            'sealed' => true,
        ];
        if ($authorized && ($legacyAuthority['purpose'] ?? null) !== $granted[0]) {
            throw new \RuntimeException('DI171_PERSONNEL_USE_DECISION_AUTHORITY_MISMATCH');
        }

        return $this->store->putDecision($record);
    }

    public function readDecision(array $legacyDecision): array
    {
        $reference = $legacyDecision['institutional_decision_record'] ?? null;
        if (!is_array($reference) || !is_string($reference['id'] ?? null) || !is_string($reference['digest'] ?? null)) {
            throw new \RuntimeException('DI172_PERSONNEL_USE_DECISION_RECORD_INVALID');
        }
        $reconstruction = $this->reconstruction->reconstruct($reference['id']);
        $record = $reconstruction['decision_record'];
        $surface = $reconstruction['decision_surface'];
        $expectedGranted = 'AUTHORIZED' === $legacyDecision['disposition'] ? ['ACCEPT_ONE_EXACT_AUTHORIZED_DELEGATE_PERSONNEL_COMMITMENT'] : [];
        $expectedLimitations = null === $legacyDecision['limitations'] ? [] : [$legacyDecision['limitations']];
        if ($record['record_digest'] !== $reference['digest']
            || $record['instance_id'] !== $legacyDecision['instance_id']
            || $record['decision']['disposition'] !== $legacyDecision['disposition']
            || $record['decision']['granted_authority'] !== $expectedGranted
            || $record['decision']['resulting_state'] !== $legacyDecision['status']
            || $record['source_requests'][0] !== $legacyDecision['source_request']
            || $record['limitations'] !== $expectedLimitations
            || ('AUTHORIZED' === $legacyDecision['disposition'] && 'guildhall.guildmaster' !== ($record['authority_lineage'][0]['consumer'] ?? null))) {
            throw new \RuntimeException('DI172_PERSONNEL_USE_DECISION_RECORD_INVALID');
        }

        return $record;
    }

    private function option(string $id, string $explanation, string $consequence, array $evidenceIds): array
    {
        return ['option_id' => $id, 'materially_relevant' => true, 'availability' => 'AVAILABLE', 'classification_reason' => 'This is an explicit Imperator disposition preserved by the existing personnel-use contract.', 'plain_language_explanation' => $explanation, 'material_consequences' => $consequence, 'risks' => ['An incorrect choice may delay or misbind mission personnel.'], 'costs' => [], 'external_effects' => [], 'reversibility' => 'No choice silently grants adjacent or continuing authority.', 'authority_effect' => $consequence, 'evidence' => $evidenceIds];
    }

    private function evidence(string $id, string $digest, string $provenance, string $relevance, \DateTimeImmutable $observedAt): array
    {
        return ['artifact_id' => $id, 'record_digest' => $digest, 'provenance' => $provenance, 'version' => '1', 'relevance' => $relevance, 'sealed' => true, 'observed_at' => $observedAt->format(DATE_ATOM), 'expires_at' => self::NO_PREEXISTING_EXPIRY];
    }

    private function optionForDisposition(string $disposition): string
    {
        return match ($disposition) {
            'AUTHORIZED' => 'authorize-exact-personnel-commitment',
            'REFUSED' => 'refuse-exact-personnel-commitment',
            'RETURNED_FOR_REVISION' => 'return-for-revision',
            'ALTERNATIVE_PROPOSED' => 'propose-alternative',
            'CLARIFICATION_REQUIRED' => 'require-clarification',
            'DEFERRED' => 'defer-decision',
            default => throw new \RuntimeException('DI173_PERSONNEL_USE_DISPOSITION_UNMAPPED'),
        };
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
