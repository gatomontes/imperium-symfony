<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaFirstTestimonyService
{
    private string $depositionDirectory;
    private string $witnessDirectory;
    private string $occupancyDirectory;
    private string $turnDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private PersonaWitnessTestimonyCognitionGateway $cognition,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->depositionDirectory = $senate . "/depositions";
        $this->witnessDirectory = $senate . "/persona-witnesses";
        $this->occupancyDirectory = $senate . "/occupancy";
        $this->turnDirectory = $senate . "/testimony-turns";
    }

    public function conduct(string $depositionId): array
    {
        if (!preg_match('/^senate-persona-deposition-[a-f0-9]{20}$/', $depositionId)) {
            throw new \InvalidArgumentException("S129_PERSONA_DEPOSITION_ID_INVALID");
        }
        foreach (glob($this->turnDirectory . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "S138_TESTIMONY_TURN_CONFLICT");
            if ($depositionId === ($existing["deposition_id"] ?? null) && "practice" === ($existing["jurisdiction"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $deposition = $this->read(
            $this->depositionDirectory . "/" . $depositionId . ".json",
            "S130_PERSONA_DEPOSITION_ABSENT",
        );
        $manifestationId = $deposition["manifestation_id"] ?? null;
        $witness = is_string($manifestationId)
            ? $this->read($this->witnessDirectory . "/" . $manifestationId . ".json", "S131_PERSONA_TESTIMONY_CHAIN_INVALID")
            : [];
        $senator = $this->senator($deposition["instance_id"] ?? null);
        if (
            !$this->digestMatches($deposition) ||
            !$this->digestMatches($witness) ||
            "imperium.senate-persona-deposition/v1" !== ($deposition["schema"] ?? null) ||
            "OPEN_PENDING_FIRST_QUESTION" !== ($deposition["status"] ?? null) ||
            [] !== ($deposition["questions"] ?? null) ||
            [] !== ($deposition["testimony"] ?? null) ||
            [] !== ($deposition["senator_findings"] ?? null) ||
            null !== ($deposition["senate_disposition"] ?? null) ||
            !in_array("practice", $deposition["confirmation_plan"]["jurisdictions"] ?? [], true) ||
            ($deposition["manifestation_digest"] ?? null) !== ($witness["record_digest"] ?? null) ||
            "INSTANTIATED_ON_STAND_PENDING_DEPOSITION" !== ($witness["status"] ?? null) ||
            true === ($deposition["admission_authority"] ?? null) ||
            true === ($deposition["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S131_PERSONA_TESTIMONY_CHAIN_INVALID");
        }

        $assignment = [
            "jurisdiction" => "practice",
            "senator" => $this->actor($senator),
            "scope" => "professional decisions, methods, competence, and competence boundaries",
            "confirmation_plan_digest" => $deposition["confirmation_plan_digest"],
            "question_authority" => true,
            "finding_authority_exercisable" => false,
        ];
        $question = $this->cognition->authorQuestion($assignment, $deposition, $witness);
        $this->validateQuestion($question);
        $answer = $this->cognition->answer($question, $deposition, $witness);
        $this->validateAnswer($answer);
        $id = "senate-persona-testimony-turn-" . substr(hash("sha256", CanonicalJson::encode([
            $depositionId,
            $deposition["record_digest"],
            $senator["binding_id"],
            $senator["record_digest"],
            $question,
            $answer,
        ])), 0, 20);

        return $this->persist($id, [
            "schema" => "imperium.senate-persona-testimony-turn/v1",
            "turn_id" => $id,
            "instance_id" => $deposition["instance_id"],
            "deposition_id" => $depositionId,
            "deposition_digest" => $deposition["record_digest"],
            "manifestation_id" => $manifestationId,
            "manifestation_digest" => $witness["record_digest"],
            "candidate_id" => $deposition["candidate_id"],
            "candidate_digest" => $deposition["candidate_digest"],
            "review_target_lineage" => $deposition["review_target_lineage"],
            "jurisdiction" => "practice",
            "assignment" => $assignment,
            "question" => $question,
            "testimony" => $answer,
            "question_dispatched_unchanged" => true,
            "question_authority_consumed" => true,
            "testimony_sealed" => true,
            "status" => "FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS",
            "senator_finding" => null,
            "senate_disposition" => null,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function senator(mixed $instanceId): array
    {
        $matches = [];
        foreach (glob($this->occupancyDirectory . "/*.json") ?: [] as $path) {
            $record = $this->read($path, "S132_PRACTICE_SENATOR_INVALID");
            if ("senate.committee.practice" === ($record["seat"] ?? null)) {
                $matches[] = $record;
            }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException("S132_PRACTICE_SENATOR_INVALID");
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
            throw new \RuntimeException("S132_PRACTICE_SENATOR_INVALID");
        }
        return $record;
    }

    private function validateQuestion(array $question): void
    {
        $keys = array_keys($question);
        sort($keys, SORT_STRING);
        if (["purpose", "question", "question_set_id", "trial_id"] !== $keys) {
            throw new \RuntimeException("S133_SENATOR_QUESTION_INVALID");
        }
        foreach ($question as $value) {
            if (!is_string($value) || "" === trim($value)) {
                throw new \RuntimeException("S133_SENATOR_QUESTION_INVALID");
            }
        }
    }

    private function validateAnswer(array $answer): void
    {
        $keys = array_keys($answer);
        sort($keys, SORT_STRING);
        if (["answer", "evidence_claims", "refusals", "uncertainties"] !== $keys || !is_string($answer["answer"] ?? null) || "" === trim($answer["answer"])) {
            throw new \RuntimeException("S134_PERSONA_TESTIMONY_INVALID");
        }
        foreach (["evidence_claims", "refusals", "uncertainties"] as $field) {
            if (!is_array($answer[$field] ?? null)) {
                throw new \RuntimeException("S134_PERSONA_TESTIMONY_INVALID");
            }
            foreach ($answer[$field] as $value) {
                if (!is_string($value) || "" === trim($value)) {
                    throw new \RuntimeException("S134_PERSONA_TESTIMONY_INVALID");
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
        if (!is_dir($this->turnDirectory) && !mkdir($this->turnDirectory, 0770, true) && !is_dir($this->turnDirectory)) {
            throw new \RuntimeException("S137_TESTIMONY_TURN_FAILED");
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->turnDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S138_TESTIMONY_TURN_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException("S138_TESTIMONY_TURN_CONFLICT");
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("S137_TESTIMONY_TURN_FAILED");
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
