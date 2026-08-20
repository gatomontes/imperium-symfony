<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaFreshConsistencyTrialService
{
    private string $baselineDirectory;
    private string $witnessDirectory;
    private string $occupancyDirectory;
    private string $trialDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private PersonaWitnessTestimonyCognitionGateway $cognition,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->baselineDirectory = $senate . "/jurisdiction-baselines";
        $this->witnessDirectory = $senate . "/persona-witnesses";
        $this->occupancyDirectory = $senate . "/occupancy";
        $this->trialDirectory = $senate . "/fresh-consistency-trials";
    }

    public function conduct(string $baselineId): array
    {
        if (!preg_match('/^senate-persona-jurisdiction-baseline-[a-f0-9]{20}$/', $baselineId)) {
            throw new \InvalidArgumentException("S149_JURISDICTION_BASELINE_ID_INVALID");
        }
        foreach (glob($this->trialDirectory . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "S159_FRESH_CONSISTENCY_TRIAL_CONFLICT");
            if ($baselineId === ($existing["baseline_id"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $baseline = $this->read(
            $this->baselineDirectory . "/" . $baselineId . ".json",
            "S150_JURISDICTION_BASELINE_ABSENT",
        );
        $sourceManifestationId = $baseline["manifestation_id"] ?? null;
        $sourceWitness = is_string($sourceManifestationId)
            ? $this->read($this->witnessDirectory . "/" . $sourceManifestationId . ".json", "S151_FRESH_CONSISTENCY_CHAIN_INVALID")
            : [];
        $baselineConsistency = $this->turn($baseline, "consistency");
        $bailiff = $this->occupant(
            $sourceWitness["bailiff"] ?? null,
            "senate.bailiff",
            "proceeding_security_authority",
            $baseline["instance_id"] ?? null,
        );
        $senator = $this->occupant(
            null,
            "senate.committee.consistency",
            "senator_question_authority",
            $baseline["instance_id"] ?? null,
        );
        if (
            !$this->digestMatches($baseline) ||
            !$this->digestMatches($sourceWitness) ||
            "imperium.senate-persona-jurisdiction-baseline/v1" !== ($baseline["schema"] ?? null) ||
            "REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS" !== ($baseline["status"] ?? null) ||
            true !== ($baseline["additional_trials_required"] ?? null) ||
            ($baseline["manifestation_digest"] ?? null) !== ($sourceWitness["record_digest"] ?? null) ||
            "STERILE_PERSONA_ONLY_STAND_INSTANCE" !== ($sourceWitness["manifestation_class"] ?? null) ||
            true === ($baseline["admission_authority"] ?? null) ||
            true === ($baseline["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S151_FRESH_CONSISTENCY_CHAIN_INVALID");
        }

        $freshId = "senate-persona-witness-" . substr(hash("sha256", CanonicalJson::encode([
            $baselineId,
            $baseline["record_digest"],
            $sourceManifestationId,
            $sourceWitness["record_digest"],
            "FRESH_INSTANCE_CONSISTENCY_TRIAL",
        ])), 0, 20);
        if ($freshId === $sourceManifestationId) {
            throw new \RuntimeException("S152_FRESH_WITNESS_ID_COLLISION");
        }
        $freshWitness = $this->persistWitness($freshId, [
            "schema" => "imperium.senate-persona-witness-manifestation/v1",
            "manifestation_id" => $freshId,
            "instance_id" => $sourceWitness["instance_id"],
            "confirmation_acceptance_id" => $sourceWitness["confirmation_acceptance_id"],
            "confirmation_acceptance_digest" => $sourceWitness["confirmation_acceptance_digest"],
            "confirmation_case_id" => $sourceWitness["confirmation_case_id"],
            "confirmation_case_digest" => $sourceWitness["confirmation_case_digest"],
            "candidate_id" => $sourceWitness["candidate_id"],
            "candidate_digest" => $sourceWitness["candidate_digest"],
            "persona_name" => $sourceWitness["persona_name"],
            "persona_specification_version" => $sourceWitness["persona_specification_version"],
            "persona" => $sourceWitness["persona"],
            "review_target_lineage" => $sourceWitness["review_target_lineage"],
            "lord_speaker" => $sourceWitness["lord_speaker"],
            "bailiff" => $this->actor($bailiff),
            "source_manifestation_id" => $sourceManifestationId,
            "source_manifestation_digest" => $sourceWitness["record_digest"],
            "trial_class" => "FRESH_INSTANCE_CONSISTENCY_TRIAL",
            "manifestation_class" => "STERILE_PERSONA_ONLY_STAND_INSTANCE",
            "location" => "senate.stand",
            "profile" => null,
            "officer_substrate" => null,
            "seat" => null,
            "operational_authority" => false,
            "ordinary_use_prohibited" => true,
            "retirement_required_after_disposition" => true,
            "status" => "INSTANTIATED_ON_STAND_PENDING_CONSISTENCY_TRIAL",
            "senate_finding_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);

        $assignment = [
            "jurisdiction" => "consistency",
            "senator" => $this->actor($senator),
            "scope" => "fresh-instance contradiction, drift, priority, and materially different conduct",
            "baseline_id" => $baselineId,
            "baseline_digest" => $baseline["record_digest"],
            "equivalence_target_turn_digest" => $baselineConsistency["turn_digest"],
            "equivalent_question_required" => true,
            "fresh_manifestation_required" => true,
            "question_authority" => true,
            "finding_authority_exercisable" => false,
        ];
        $context = [
            "baseline" => $baseline,
            "comparison_target" => $baselineConsistency,
            "fresh_manifestation_id" => $freshId,
        ];
        $question = $this->cognition->authorQuestion($assignment, $context, $freshWitness);
        $this->validateQuestion($question);
        if (
            ($baselineConsistency["question"]["trial_id"] ?? null) ===
                ($question["trial_id"] ?? null)
        ) {
            throw new \RuntimeException("S154_CONSISTENCY_QUESTION_INVALID");
        }
        $answer = $this->cognition->answer($question, $context, $freshWitness);
        $this->validateAnswer($answer);
        $comparison = [
            "baseline_manifestation_id" => $sourceManifestationId,
            "baseline_manifestation_digest" => $sourceWitness["record_digest"],
            "baseline_turn" => $baselineConsistency,
            "fresh_manifestation_id" => $freshId,
            "fresh_manifestation_digest" => $freshWitness["record_digest"],
            "fresh_question" => $question,
            "fresh_testimony" => $answer,
            "candidate_id_equal" => $sourceWitness["candidate_id"] === $freshWitness["candidate_id"],
            "candidate_digest_equal" => $sourceWitness["candidate_digest"] === $freshWitness["candidate_digest"],
            "persona_equal" => CanonicalJson::encode($sourceWitness["persona"]) === CanonicalJson::encode($freshWitness["persona"]),
            "variance_assessment" => null,
            "consistency_finding" => null,
        ];
        $id = "senate-persona-fresh-consistency-trial-" . substr(hash("sha256", CanonicalJson::encode([
            $baselineId,
            $baseline["record_digest"],
            $freshId,
            $freshWitness["record_digest"],
            $question,
            $answer,
        ])), 0, 20);
        return $this->persistTrial($id, [
            "schema" => "imperium.senate-persona-fresh-consistency-trial/v1",
            "trial_record_id" => $id,
            "instance_id" => $baseline["instance_id"],
            "baseline_id" => $baselineId,
            "baseline_digest" => $baseline["record_digest"],
            "candidate_id" => $baseline["candidate_id"],
            "candidate_digest" => $baseline["candidate_digest"],
            "review_target_lineage" => $baseline["review_target_lineage"],
            "assignment" => $assignment,
            "fresh_witness" => [
                "manifestation_id" => $freshId,
                "manifestation_digest" => $freshWitness["record_digest"],
                "source_manifestation_id" => $sourceManifestationId,
                "bailiff" => $this->actor($bailiff),
            ],
            "question" => $question,
            "testimony" => $answer,
            "comparison_record" => $comparison,
            "question_dispatched_unchanged" => true,
            "testimony_sealed" => true,
            "status" => "FRESH_INSTANCE_CONSISTENCY_TRIAL_SEALED_PENDING_PRESSURE_TRIALS",
            "pressure_trials_required" => true,
            "senator_finding" => null,
            "drift_conclusion" => null,
            "aggregate_score" => null,
            "vote" => null,
            "senate_disposition" => null,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function turn(array $baseline, string $jurisdiction): array
    {
        $matches = array_values(array_filter(
            $baseline["turns"] ?? [],
            static fn (mixed $turn): bool =>
                is_array($turn) && $jurisdiction === ($turn["jurisdiction"] ?? null),
        ));
        if (1 !== count($matches) || !is_string($matches[0]["turn_digest"] ?? null)) {
            throw new \RuntimeException("S151_FRESH_CONSISTENCY_CHAIN_INVALID");
        }
        return $matches[0];
    }

    private function occupant(
        mixed $reference,
        string $seat,
        string $authority,
        mixed $instanceId,
    ): array {
        $matches = [];
        foreach (glob($this->occupancyDirectory . "/*.json") ?: [] as $path) {
            $record = $this->read($path, "S153_PROCEEDING_OCCUPANCY_INVALID");
            if ($seat === ($record["seat"] ?? null)) {
                $matches[] = $record;
            }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException("S153_PROCEEDING_OCCUPANCY_INVALID");
        }
        $record = $matches[0];
        if (
            !$this->digestMatches($record) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record[$authority] ?? null) ||
            true === ($record["execution_authority"] ?? null) ||
            (is_array($reference) && (
                ($reference["binding_id"] ?? null) !== ($record["binding_id"] ?? null) ||
                ($reference["binding_digest"] ?? null) !== ($record["record_digest"] ?? null)
            ))
        ) {
            throw new \RuntimeException("S153_PROCEEDING_OCCUPANCY_INVALID");
        }
        return $record;
    }

    private function validateQuestion(array $question): void
    {
        $keys = array_keys($question);
        sort($keys, SORT_STRING);
        if (["purpose", "question", "question_set_id", "trial_id"] !== $keys) {
            throw new \RuntimeException("S154_CONSISTENCY_QUESTION_INVALID");
        }
        foreach ($question as $value) {
            if (!is_string($value) || "" === trim($value)) {
                throw new \RuntimeException("S154_CONSISTENCY_QUESTION_INVALID");
            }
        }
    }

    private function validateAnswer(array $answer): void
    {
        $keys = array_keys($answer);
        sort($keys, SORT_STRING);
        if (["answer", "evidence_claims", "refusals", "uncertainties"] !== $keys || !is_string($answer["answer"] ?? null) || "" === trim($answer["answer"])) {
            throw new \RuntimeException("S155_FRESH_TESTIMONY_INVALID");
        }
        foreach (["evidence_claims", "refusals", "uncertainties"] as $field) {
            if (!is_array($answer[$field] ?? null)) {
                throw new \RuntimeException("S155_FRESH_TESTIMONY_INVALID");
            }
            foreach ($answer[$field] as $value) {
                if (!is_string($value) || "" === trim($value)) {
                    throw new \RuntimeException(
                        "S155_FRESH_TESTIMONY_INVALID",
                    );
                }
            }
        }
    }

    private function actor(array $binding): array
    {
        return [
            "seat" => $binding["seat"],
            "binding_id" => $binding["binding_id"],
            "binding_digest" => $binding["record_digest"],
            "manifestation_id" => $binding["manifestation_id"],
            "occupancy_generation" => $binding["occupancy_generation"],
            "founding_class" => $binding["founding_class"] ?? "ARTIFACT_BACKED",
            "placeholder_version" => $binding["placeholder_version"] ?? null,
        ];
    }

    private function persistWitness(string $id, array $record): array
    {
        return $this->persist($this->witnessDirectory, $id, $record, "S156_FRESH_WITNESS_INSTANTIATION_FAILED", "S157_FRESH_WITNESS_CONFLICT");
    }

    private function persistTrial(string $id, array $record): array
    {
        return $this->persist($this->trialDirectory, $id, $record, "S158_FRESH_CONSISTENCY_TRIAL_FAILED", "S159_FRESH_CONSISTENCY_TRIAL_CONFLICT");
    }

    private function persist(
        string $directory,
        string $id,
        array $record,
        string $failure,
        string $conflict,
    ): array {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException($failure);
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $directory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, $conflict);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException($conflict);
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException($failure);
        }
        return $record;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record["record_digest"] ?? null;
        unset($record["record_digest"]);
        return is_string($digest) && hash_equals($digest, hash("sha256", CanonicalJson::encode($record)));
    }
}
