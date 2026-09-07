<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** One Citadel registry, reserved child identity, immutable cross-root receipt.
 * Child roots are generated beneath the deployment root; callers supply no paths.
 */
final readonly class CuriaFormationService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        private FormationJournal $journal,
        private FormationSignatures $signatures,
        private FormationPersonnel $personnel,
        private Clock $clock,
    ) {}

    public function presentation(string $intakeId, int $version, array $appointments, int $expiresAt): array
    {
        $state = $this->journal->read()['state'];
        $dossier = $this->currentDossier($state, $intakeId, $version);
        if (($state['intakes'][$intakeId]['understanding']['registry_generation'] ?? null) !== $state['registry_generation']) {
            throw new \RuntimeException('CMF087_REGISTRY_CHANGED_REASSESS_OUTSIDE_LOCK');
        }
        $missionId = 'mission-'.substr(FormationJournal::digest([$state['citadel_id'], $intakeId]), 0, 32);
        if (!FormationJournal::keys($appointments, ['curia.seneschal', 'curia.chamberlain', 'curia.secretary'])) {
            throw new \RuntimeException('CMF080_EXACT_THREE_APPOINTMENTS_REQUIRED');
        }
        $manifestations = [];
        foreach ($appointments as $seat => $candidate) {
            $binding = $this->personnel->candidate($state, $candidate, $missionId, $seat);
            $manifestations[] = $binding['manifestation_id'];
        }
        if (count(array_unique($manifestations)) !== 3) { throw new \RuntimeException('CMF081_DISTINCT_MANIFESTATIONS_REQUIRED'); }
        if ($expiresAt <= $this->clock->now()->getTimestamp()) { throw new \RuntimeException('CMF082_FORMATION_EXPIRED'); }
        return ['intake_id' => $intakeId, 'mission_id' => $missionId,
            'curia_id' => 'curia-'.substr(FormationJournal::digest([$missionId, $dossier['dossier_id']]), 0, 32),
            'citadel_id' => $state['citadel_id'], 'parent_instance_id' => $state['parent_instance_id'], 'dossier' => $dossier,
            'registry_generation' => $state['registry_generation'],
            'overlap' => $state['intakes'][$intakeId]['understanding']['response']['overlap'],
            'appointments' => $appointments, 'expires_at' => $expiresAt,
            'typed_effects' => ['APPROVE_EXACT_MISSION', 'CONSTITUTE_CHILD_CURIA', 'APPOINT_EXACT_OFFICERS'],
            'execution_authority' => false];
    }

    public function review(array $terms, string $disposition, array $lineDigests, string $rationale, array $decision): array
    {
        return $this->journal->change(function (array &$state) use ($terms, $disposition, $lineDigests, $rationale, $decision): array {
            if (!in_array($disposition, ['APPROVE', 'OBJECT', 'REFUSE', 'DEFER'], true) || '' === trim($rationale)) {
                throw new \RuntimeException('CMF083_REVIEW_INVALID');
            }
            $current = $this->currentDossier($state, $terms['intake_id'], $terms['dossier']['version']);
            if (FormationJournal::digest($current) !== FormationJournal::digest($terms['dossier'])) { throw new \RuntimeException('CMF088_DOSSIER_SUBSTITUTION'); }
            $this->validateTerms($state, $terms);
            $effect = $disposition === 'APPROVE' ? 'APPROVE_MISSION_AND_CONSTITUTION' : 'REVIEW_MISSION_'.$disposition;
            $object = ['terms' => $terms, 'line_digests' => $lineDigests, 'rationale' => $rationale];
            $this->signatures->verify($state, $decision, $effect, $object);
            $expectedLines = array_column($terms['dossier']['lines'], 'line_digest');
            if (($disposition === 'APPROVE' && $lineDigests !== $expectedLines)
                || array_diff($lineDigests, $expectedLines) !== []
                || ($disposition === 'OBJECT' && [] === $lineDigests)) { throw new \RuntimeException('CMF084_EXACT_NUMBERED_LINES_REQUIRED'); }
            $record = ['terms' => $terms, 'disposition' => $disposition, 'line_digests' => $lineDigests, 'rationale' => $rationale, 'decision' => $decision];
            $id = 'review-'.FormationJournal::digest($record);
            $state['reviews'][$id] = $record;
            return ['review_id' => $id, ...$record];
        });
    }

    public function reserve(string $reviewId): array
    {
        return $this->journal->change(function (array &$state) use ($reviewId): array {
            $review = $state['reviews'][$reviewId] ?? throw new \RuntimeException('CMF085_REVIEW_ABSENT');
            $terms = $review['terms'];
            $id = $terms['intake_id'];
            if (isset($state['reservations'][$id])) {
                $prior = $state['reservations'][$id];
                if ($prior['status'] !== 'EXPIRED_UNUSED_TOMBSTONE') {
                    if ($prior['review_id'] !== $reviewId) { throw new \RuntimeException('CMF086_RESERVATION_CONFLICT'); }
                    return $prior;
                }
            }
            $this->validateApproval($state, $review);
            if ($terms['registry_generation'] !== $state['registry_generation']) {
                $reassessment = $state['intakes'][$id]['understanding'] ?? [];
                if (($reassessment['registry_generation'] ?? null) !== $state['registry_generation']
                    || ($reassessment['response']['overlap'] ?? null) !== $terms['overlap']) {
                    throw new \RuntimeException('CMF087_REGISTRY_CHANGED_REASSESS_OUTSIDE_LOCK');
                }
            }
            $dossier = $this->currentDossier($state, $id, $terms['dossier']['version']);
            if (FormationJournal::digest($dossier) !== FormationJournal::digest($terms['dossier'])) { throw new \RuntimeException('CMF088_DOSSIER_SUBSTITUTION'); }
            foreach ($terms['appointments'] as $seat => $candidate) {
                $binding = $this->personnel->candidate($state, $candidate, $terms['mission_id'], $seat);
                if (isset($state['occupied_manifestations'][$binding['manifestation_id']]) || isset($state['reserved_manifestations'][$binding['manifestation_id']])) { throw new \RuntimeException('CMF044_MANIFESTATION_ALREADY_OCCUPIED'); }
                $state['reserved_manifestations'][$binding['manifestation_id']] = $id;
            }
            $reservation = ['review_id' => $reviewId, 'mission_id' => $terms['mission_id'], 'curia_id' => $terms['curia_id'],
                'source_citadel_id' => $state['citadel_id'], 'approval_digest' => FormationJournal::digest($review),
                'status' => 'RESERVED_NO_EFFECT', 'expires_at' => $terms['expires_at'], 'attempt_token' => bin2hex(random_bytes(24))];
            $state['reservations'][$id] = $reservation;
            ++$state['registry_generation'];
            return $reservation;
        });
    }

    public function deliver(string $intakeId): array
    {
        $publisher = new \App\Imperium\Runtime\MasterMason\ChildCuriaFormationService($this->root, $this->journal, $this->signatures, $this->personnel, $this->clock);
        $snapshot = $this->journal->read()['state'];
        if (isset($snapshot['handoffs'][$intakeId])) { return $snapshot['handoffs'][$intakeId]; }
        // Recognize a completed effect before consulting current authority for a NEW one.
        $receipt = $publisher->reconcile($intakeId);
        $recovered = $receipt !== null;
        $recognizedContent = $receipt;
        if ($recognizedContent !== null) { unset($recognizedContent['record_digest'], $recognizedContent['publication']); }
        $prepared = $recognizedContent ?? $this->journal->change(function (array &$state) use ($intakeId): array {
            if (isset($state['handoffs'][$intakeId])) { return ['complete' => $state['handoffs'][$intakeId]]; }
            $reservation = &$state['reservations'][$intakeId];
            if (!is_array($reservation)) { throw new \RuntimeException('CMF089_RESERVATION_REQUIRED'); }
            $review = $state['reviews'][$reservation['review_id']];
            if ($reservation['status'] === 'EXPIRED_UNUSED_TOMBSTONE') { throw new \RuntimeException('CMF082_FORMATION_EXPIRED'); }
            $this->validateApproval($state, $review);
            if (isset($reservation['prepared'])) { return $reservation['prepared']; }
            $terms = $review['terms'];
            $occupants = [];
            foreach ($terms['appointments'] as $seat => $candidate) {
                $occupants[$seat] = $this->personnel->candidate($state, $candidate, $terms['mission_id'], $seat)
                    + ['generation' => 1, 'curia_id' => $terms['curia_id']];
            }
            $candidateSources = $terms['appointments'];
            $cognitionLineage = [];
            $include = function (array $record) use ($state, &$candidateSources, &$cognitionLineage): void {
                $claim = $record['claim'] ?? null;
                if (!is_array($claim)) { return; }
                $session = $state['sessions'][$claim['session_id']];
                $attempt = $session['attempts'][$claim['attempt_id']];
                unset($session['attempts']);
                $cognitionLineage[$claim['claim_id']] = ['session' => $session, 'attempt' => $attempt];
                foreach ([$claim['holder'], $claim['derivation']['lease']['issuer']] as $holder) {
                    $candidateSources[] = $holder['candidate'];
                }
            };
            foreach ($state['intakes'][$intakeId]['exchange'] as $exchange) { $include($exchange['attribution'] ?? []); }
            $include($terms['dossier']);
            $packet = ['parent_instance_id' => $state['parent_instance_id'], 'citadel_id' => $state['citadel_id'], 'intake_id' => $intakeId,
                'mission_id' => $terms['mission_id'], 'curia_id' => $terms['curia_id'],
                'intake' => $state['intakes'][$intakeId], 'dossier' => $terms['dossier'], 'review' => $review,
                'personnel_evidence' => $this->evidenceFor($state, $candidateSources),
                'cognition_lineage' => $cognitionLineage,
                'mandate' => $terms['dossier']['response']['mission_plan'], 'execution_authority' => false];
            $constitution = ['terms' => $terms, 'decision' => $review['decision'], 'signed_review' => ['terms' => $terms, 'line_digests' => $review['line_digests'], 'rationale' => $review['rationale']], 'occupants' => $occupants,
                'root_principal_created' => false, 'bootstrap_activated' => false, 'execution_authority' => false];
            $handoff = ['handoff_id' => 'handoff-'.FormationJournal::digest([$terms['curia_id'], $packet, $constitution]),
                'mission_id' => $terms['mission_id'], 'curia_id' => $terms['curia_id'],
                'packet' => $packet, 'constitution' => $constitution, 'acceptances' => [], 'execution_authority' => false];
            if (isset($reservation['prepared']) && FormationJournal::digest($reservation['prepared']) !== FormationJournal::digest($handoff)) {
                throw new \RuntimeException('CMF090_PARTIAL_CONSTITUTION_RECONCILIATION_REQUIRED');
            }
            $reservation['prepared'] = $handoff;
            $reservation['status'] = 'EFFECT_UNCERTAIN_IDENTITY_FENCED';
            return $handoff;
        });
        if (isset($prepared['complete'])) { return $prepared['complete']; }
        // Child creation is downstream of exact approval and the durable identity fence.
        if ($receipt === null) {
            try { $receipt = $publisher->publish($intakeId); }
            catch (\RuntimeException $e) {
                if ($e->getMessage() !== 'CMF131_EXISTING_CHILD_REQUIRES_RECONCILIATION') { throw $e; }
                $receipt = $publisher->reconcile($intakeId) ?? throw $e;
                $recovered = true;
            }
        }
        return $this->journal->change(function (array &$state) use ($intakeId, $prepared, $receipt, $recovered): array {
            if (isset($state['handoffs'][$intakeId])) { return $state['handoffs'][$intakeId]; }
            $reservation = &$state['reservations'][$intakeId];
            if (FormationJournal::digest($reservation['prepared']) !== FormationJournal::digest($prepared)) { throw new \RuntimeException('CMF090_PARTIAL_CONSTITUTION_RECONCILIATION_REQUIRED'); }
            $handoff = [...$prepared, 'delivery_receipt' => ['id' => $prepared['handoff_id'], 'digest' => $receipt['record_digest']]];
            $state['handoffs'][$intakeId] = $handoff;
            $state['child_roots'][$prepared['mission_id']] = ['curia_id' => $prepared['curia_id'], 'handoff_id' => $prepared['handoff_id'], 'intake_id' => $intakeId];
            foreach ($prepared['constitution']['occupants'] as $seat => $holder) { $state['occupied_manifestations'][$holder['manifestation_id']] = $prepared['curia_id'].'.'.$seat; }
            $state['intakes'][$intakeId]['mission_id'] = $prepared['mission_id'];
            $state['intakes'][$intakeId]['curia_id'] = $prepared['curia_id'];
            $state['intakes'][$intakeId]['status'] = 'DELIVERED_PENDING_SENESCHAL_ACCEPTANCE';
            unset($state['intakes'][$intakeId]['record_digest']);
            $state['intakes'][$intakeId]['record_digest'] = FormationJournal::digest($state['intakes'][$intakeId]);
            $reservation['status'] = 'DELIVERED';
            $reservation['reconciliation'] = ['kind' => $recovered ? 'RECOGNIZED_COMPLETED_EFFECT' : 'ORDINARY_DELIVERY',
                'receipt_digest' => $receipt['record_digest'], 'authorized_at' => $receipt['publication']['authorized_at'],
                'authority_frame_digest' => $receipt['publication']['authority_frame_digest'],
                'recognized_at' => $this->clock->now()->getTimestamp(), 'new_authority' => false];
            ++$state['registry_generation'];
            return $handoff;
        });
    }

    public function route(string $missionId, array $decision): array
    {
        $state = $this->journal->read()['state'];
        $this->signatures->verify($state, $decision, 'READ_EXISTING_MISSION', ['mission_id' => $missionId]);
        $entry = $state['child_roots'][$missionId] ?? throw new \RuntimeException('CMF091_MISSION_UNREGISTERED_OR_INCOMPLETE');
        $records = new ImmutableRecordStore($this->childRoot($entry['curia_id']), new AtomicTransition($this->childRoot($entry['curia_id'])));
        $receipt = $records->read('var/imperium/curia/handoffs', $entry['handoff_id']);
        if ($receipt['record_digest'] !== $state['handoffs'][$entry['intake_id']]['delivery_receipt']['digest']) { throw new \RuntimeException('CMF092_CHILD_RECEIPT_SUBSTITUTION'); }
        return ['mission_id' => $missionId, 'curia_id' => $entry['curia_id'], 'handoff_id' => $entry['handoff_id'], 'execution_authority' => false];
    }

    private function currentDossier(array $state, string $intakeId, int $version): array
    {
        $versions = $state['dossiers'][$intakeId] ?? [];
        if ($version !== count($versions) || $version < 1) { throw new \RuntimeException('CMF093_CURRENT_DOSSIER_REQUIRED'); }
        $dossier = $versions[$version - 1];
        FormationPlan::validate($dossier['response']);
        if ($dossier['intent_version'] !== $state['intakes'][$intakeId]['intent_version']) { throw new \RuntimeException('CMF065_DRAFTING_LINEAGE_CHANGED'); }
        if ($dossier['holder_digest'] !== FormationJournal::digest($this->personnel->currentCastellan($state))) { throw new \RuntimeException('CMF065_DRAFTING_LINEAGE_CHANGED'); }
        return $dossier;
    }

    private function validateApproval(array $state, array $review): void
    {
        if ($review['disposition'] !== 'APPROVE' || $review['terms']['expires_at'] <= $this->clock->now()->getTimestamp()) { throw new \RuntimeException('CMF094_EXACT_MISSION_APPROVAL_REQUIRED'); }
        $this->signatures->verify($state, $review['decision'], 'APPROVE_MISSION_AND_CONSTITUTION', ['terms' => $review['terms'], 'line_digests' => $review['line_digests'], 'rationale' => $review['rationale']]);
        $current = $this->currentDossier($state, $review['terms']['intake_id'], $review['terms']['dossier']['version']);
        if (FormationJournal::digest($current) !== FormationJournal::digest($review['terms']['dossier'])) { throw new \RuntimeException('CMF088_DOSSIER_SUBSTITUTION'); }
        $this->validateTerms($state, $review['terms']);
    }

    private function validateTerms(array $state, array $terms): void
    {
        $missionId = 'mission-'.substr(FormationJournal::digest([$state['citadel_id'], $terms['intake_id']]), 0, 32);
        if (!FormationJournal::keys($terms, ['intake_id', 'mission_id', 'curia_id', 'citadel_id', 'parent_instance_id', 'dossier', 'registry_generation', 'overlap', 'appointments', 'expires_at', 'typed_effects', 'execution_authority'])
            || $terms['parent_instance_id'] !== $state['parent_instance_id']
            || $terms['mission_id'] !== $missionId || $terms['citadel_id'] !== $state['citadel_id']
            || $terms['curia_id'] !== 'curia-'.substr(FormationJournal::digest([$missionId, $terms['dossier']['dossier_id']]), 0, 32)
            || $terms['typed_effects'] !== ['APPROVE_EXACT_MISSION', 'CONSTITUTE_CHILD_CURIA', 'APPOINT_EXACT_OFFICERS']
            || $terms['execution_authority'] !== false
            || !FormationJournal::keys($terms['appointments'], ['curia.seneschal', 'curia.chamberlain', 'curia.secretary'])) {
            throw new \RuntimeException('CMF097_FORMATION_TERMS_INVALID');
        }
        $manifestations = [];
        foreach ($terms['appointments'] as $seat => $candidate) { $manifestations[] = $this->personnel->candidate($state, $candidate, $missionId, $seat)['manifestation_id']; }
        if (count(array_unique($manifestations)) !== 3) { throw new \RuntimeException('CMF081_DISTINCT_MANIFESTATIONS_REQUIRED'); }
    }

    public function expireUnused(string $intakeId): array
    {
        return $this->journal->change(function (array &$state) use ($intakeId): array {
            $reservation = &$state['reservations'][$intakeId];
            if (!is_array($reservation) || $reservation['expires_at'] > $this->clock->now()->getTimestamp()) { throw new \RuntimeException('CMF098_RESERVATION_NOT_EXPIRED'); }
            if ($reservation['status'] !== 'RESERVED_NO_EFFECT') { throw new \RuntimeException('CMF090_PARTIAL_CONSTITUTION_RECONCILIATION_REQUIRED'); }
            foreach ($state['reserved_manifestations'] ?? [] as $manifestation => $owner) {
                if ($owner === $intakeId) { unset($state['reserved_manifestations'][$manifestation]); }
            }
            $reservation['status'] = 'EXPIRED_UNUSED_TOMBSTONE';
            ++$state['registry_generation'];
            return $reservation;
        });
    }

    public function validateStepOne(string $intakeId): array
    {
        $state = $this->journal->read()['state'];
        $handoff = $state['handoffs'][$intakeId] ?? throw new \RuntimeException('CMF063_HANDOFF_REQUIRED');
        $accepted = $handoff['acceptances'][count($handoff['acceptances']) - 1] ?? [];
        if (($accepted['response']['disposition'] ?? null) !== 'ACCEPTED') { throw new \RuntimeException('CMF099_RECEIVING_ACCEPTANCE_REQUIRED'); }
        $this->validateApproval($state, $handoff['packet']['review']);
        FormationPlan::validate($handoff['packet']['dossier']['response']);
        $child = $this->childRoot($handoff['curia_id']);
        $receipt = (new ImmutableRecordStore($child, new AtomicTransition($child)))->read('var/imperium/curia/handoffs', $handoff['handoff_id']);
        $assessment = (new ImmutableRecordStore($child, new AtomicTransition($child)))->read('var/imperium/curia/handoff-assessments', $accepted['claim']['claim_id']);
        unset($assessment['record_digest']);
        if (FormationJournal::digest($assessment) !== FormationJournal::digest($accepted)) { throw new \RuntimeException('CMF129_ATTRIBUTABLE_RECEIVING_RECORD_REQUIRED'); }
        if ($receipt['record_digest'] !== $handoff['delivery_receipt']['digest']
            || FormationJournal::digest($receipt['packet']) !== FormationJournal::digest($handoff['packet'])
            || $accepted['claim']['holder'] !== $handoff['constitution']['occupants']['curia.seneschal']) { throw new \RuntimeException('CMF092_CHILD_RECEIPT_SUBSTITUTION'); }
        return ['status' => 'STEP_1_SCHEMA_AND_FOREIGN_REFERENCES_VALIDATED_NO_EXECUTION',
            'source_citadel_id' => $handoff['packet']['citadel_id'], 'target_curia_id' => $handoff['curia_id'],
            'mission_id' => $handoff['mission_id'], 'handoff_id' => $handoff['handoff_id'],
            'dossier_id' => $handoff['packet']['dossier']['dossier_id'],
            'plan_digest' => FormationJournal::digest($handoff['packet']['dossier']['response']['mission_plan']),
            'acceptance_claim_id' => $accepted['claim']['claim_id'], 'execution_authority' => false];
    }

    private function childRoot(string $id): string
    {
        return \App\Imperium\Runtime\MasterMason\ChildCuriaFormationService::childRoot($this->root, $id);
    }

    private function evidenceFor(array $state, array $appointments): array
    {
        $evidence = [];
        $delegations = [];
        $visit = function (string $id) use (&$visit, &$evidence, &$delegations, $state): void {
            if (isset($evidence[$id])) { return; }
            $envelope = $state['personnel_evidence'][$id];
            $evidence[$id] = $envelope;
            $key = $envelope['payload']['delegation'];
            $delegations[$key] = $state['personnel_delegations'][$key];
            foreach ($envelope['payload']['sources'] as $source) { $visit($source); }
        };
        foreach ($appointments as $candidate) {
            foreach (['persona', 'suitability', 'profile', 'examination', 'qualification'] as $field) { $visit($candidate[$field]); }
        }
        return ['evidence' => $evidence, 'delegations' => $delegations, 'public_trust' => $state['trust'],
            'parent_instance_id' => $state['parent_instance_id']];
    }
}
