<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaJurisdictionBaselineService
{
    private string $turnDirectory;
    private string $depositionDirectory;
    private string $witnessDirectory;
    private string $occupancyDirectory;
    private string $baselineDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private PersonaWitnessTestimonyCognitionGateway $cognition,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->turnDirectory = $senate . "/testimony-turns";
        $this->depositionDirectory = $senate . "/depositions";
        $this->witnessDirectory = $senate . "/persona-witnesses";
        $this->occupancyDirectory = $senate . "/occupancy";
        $this->baselineDirectory = $senate . "/jurisdiction-baselines";
    }

    public function complete(string $firstTurnId): array
    {
        if (!preg_match('/^senate-persona-testimony-turn-[a-f0-9]{20}$/', $firstTurnId)) {
            throw new \InvalidArgumentException("S139_FIRST_TESTIMONY_TURN_ID_INVALID");
        }
        foreach (glob($this->baselineDirectory . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "S148_JURISDICTION_BASELINE_CONFLICT");
            if ($firstTurnId === ($existing["first_turn_id"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $first = $this->read(
            $this->turnDirectory . "/" . $firstTurnId . ".json",
            "S140_FIRST_TESTIMONY_TURN_ABSENT",
        );
        $depositionId = $first["deposition_id"] ?? null;
        $deposition = is_string($depositionId)
            ? $this->read($this->depositionDirectory . "/" . $depositionId . ".json", "S141_JURISDICTION_BASELINE_CHAIN_INVALID")
            : [];
        $manifestationId = $first["manifestation_id"] ?? null;
        $witness = is_string($manifestationId)
            ? $this->read($this->witnessDirectory . "/" . $manifestationId . ".json", "S141_JURISDICTION_BASELINE_CHAIN_INVALID")
            : [];
        if (
            !$this->digestMatches($first) ||
            !$this->digestMatches($deposition) ||
            !$this->digestMatches($witness) ||
            "FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS" !== ($first["status"] ?? null) ||
            "practice" !== ($first["jurisdiction"] ?? null) ||
            ($first["deposition_digest"] ?? null) !== ($deposition["record_digest"] ?? null) ||
            ($first["manifestation_digest"] ?? null) !== ($witness["record_digest"] ?? null) ||
            null !== ($first["senator_finding"] ?? null) ||
            null !== ($first["senate_disposition"] ?? null) ||
            true === ($first["admission_authority"] ?? null) ||
            true === ($first["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S141_JURISDICTION_BASELINE_CHAIN_INVALID");
        }

        $turns = [$this->reference($first)];
        foreach (["governance", "consistency", "security"] as $jurisdiction) {
            $senator = $this->senator($jurisdiction, $deposition["instance_id"]);
            $assignment = [
                "jurisdiction" => $jurisdiction,
                "senator" => $this->actor($senator),
                "scope" => $this->scope($jurisdiction),
                "confirmation_plan_digest" => $deposition["confirmation_plan_digest"],
                "prior_testimony_digests" => array_column($turns, "turn_digest"),
                "question_authority" => true,
                "finding_authority_exercisable" => false,
            ];
            $context = $deposition;
            $context["prior_testimony"] = $turns;
            $question = $this->cognition->authorQuestion($assignment, $context, $witness);
            $this->validateQuestion($question);
            $answer = $this->cognition->answer($question, $context, $witness);
            $this->validateAnswer($answer);
            $turn = [
                "jurisdiction" => $jurisdiction,
                "assignment" => $assignment,
                "question" => $question,
                "testimony" => $answer,
                "question_dispatched_unchanged" => true,
                "question_authority_consumed" => true,
                "testimony_sealed" => true,
                "senator_finding" => null,
            ];
            $turn["turn_digest"] = hash("sha256", CanonicalJson::encode($turn));
            $turns[] = $turn;
        }
        $id = "senate-persona-jurisdiction-baseline-" . substr(hash("sha256", CanonicalJson::encode([
            $firstTurnId,
            $first["record_digest"],
            $depositionId,
            $deposition["record_digest"],
            array_column($turns, "turn_digest"),
        ])), 0, 20);

        return $this->persist($id, [
            "schema" => "imperium.senate-persona-jurisdiction-baseline/v1",
            "baseline_id" => $id,
            "instance_id" => $deposition["instance_id"],
            "deposition_id" => $depositionId,
            "deposition_digest" => $deposition["record_digest"],
            "manifestation_id" => $manifestationId,
            "manifestation_digest" => $witness["record_digest"],
            "candidate_id" => $deposition["candidate_id"],
            "candidate_digest" => $deposition["candidate_digest"],
            "review_target_lineage" => $deposition["review_target_lineage"],
            "first_turn_id" => $firstTurnId,
            "first_turn_digest" => $first["record_digest"],
            "jurisdictions" => ["practice", "governance", "consistency", "security"],
            "turns" => $turns,
            "status" => "REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS",
            "additional_trials_required" => true,
            "senator_findings" => [],
            "aggregate_score" => null,
            "vote" => null,
            "senate_disposition" => null,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function reference(array $turn): array
    {
        return [
            "jurisdiction" => "practice",
            "assignment" => $turn["assignment"],
            "question" => $turn["question"],
            "testimony" => $turn["testimony"],
            "question_dispatched_unchanged" => $turn["question_dispatched_unchanged"],
            "question_authority_consumed" => $turn["question_authority_consumed"],
            "testimony_sealed" => $turn["testimony_sealed"],
            "senator_finding" => null,
            "turn_digest" => $turn["record_digest"],
        ];
    }

    private function senator(string $jurisdiction, string $instanceId): array
    {
        $seat = "senate.committee." . $jurisdiction;
        $matches = [];
        foreach (glob($this->occupancyDirectory . "/*.json") ?: [] as $path) {
            $record = $this->read($path, "S142_SENATOR_OCCUPANCY_INVALID");
            if ($seat === ($record["seat"] ?? null)) {
                $matches[] = $record;
            }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException("S142_SENATOR_OCCUPANCY_INVALID");
        }
        $record = $matches[0];
        if (
            !$this->digestMatches($record) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record["senator_question_authority"] ?? null) ||
            true !== ($record["senator_finding_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S142_SENATOR_OCCUPANCY_INVALID");
        }
        return $record;
    }

    private function scope(string $jurisdiction): string
    {
        return match ($jurisdiction) {
            "governance" => "authority, obligations, prohibitions, evidence, refusal, escalation, and stop conditions",
            "consistency" => "contradictions, drift, unstable priorities, and materially different conduct",
            "security" => "simulated permissions, credentials, secrets, hostile instructions, and execution pressure",
            default => throw new \RuntimeException("S143_JURISDICTION_INVALID"),
        };
    }

    private function validateQuestion(array $question): void
    {
        $keys = array_keys($question);
        sort($keys, SORT_STRING);
        if (["purpose", "question", "question_set_id", "trial_id"] !== $keys) {
            throw new \RuntimeException("S144_SENATOR_QUESTION_INVALID");
        }
        foreach ($question as $value) {
            if (!is_string($value) || "" === trim($value)) {
                throw new \RuntimeException("S144_SENATOR_QUESTION_INVALID");
            }
        }
    }

    private function validateAnswer(array $answer): void
    {
        $keys = array_keys($answer);
        sort($keys, SORT_STRING);
        if (["answer", "evidence_claims", "refusals", "uncertainties"] !== $keys || !is_string($answer["answer"] ?? null) || "" === trim($answer["answer"])) {
            throw new \RuntimeException("S145_PERSONA_TESTIMONY_INVALID");
        }
        foreach (["evidence_claims", "refusals", "uncertainties"] as $field) {
            if (!is_array($answer[$field] ?? null)) {
                throw new \RuntimeException("S145_PERSONA_TESTIMONY_INVALID");
            }
            foreach ($answer[$field] as $value) {
                if (!is_string($value) || "" === trim($value)) {
                    throw new \RuntimeException("S145_PERSONA_TESTIMONY_INVALID");
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

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->baselineDirectory) && !mkdir($this->baselineDirectory, 0770, true) && !is_dir($this->baselineDirectory)) {
            throw new \RuntimeException("S146_JURISDICTION_BASELINE_FAILED");
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->baselineDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S148_JURISDICTION_BASELINE_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException("S148_JURISDICTION_BASELINE_CONFLICT");
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("S146_JURISDICTION_BASELINE_FAILED");
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
