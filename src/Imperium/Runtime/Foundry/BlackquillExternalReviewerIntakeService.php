<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillExternalReviewerIntakeService
{
    private string $reviewCaseDirectory;
    private string $intakeDirectory;
    private string $personaPath;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->reviewCaseDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-review-cases";
        $this->intakeDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/external-reviewer-intakes";
        $this->personaPath =
            $projectDir .
            "/offices/foundry/personas/blackquill-adversarial-reviewer.md";
    }
    public function intake(string $reviewCaseId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-persona-review-case-[a-f0-9]{20}$/',
                $reviewCaseId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F121_REVIEWER_BOOTSTRAP_CASE_ID_INVALID",
            );
        }
        $case = $this->read(
            $this->reviewCaseDirectory . "/" . $reviewCaseId . ".json",
            "F122_REVIEWER_BOOTSTRAP_CASE_ABSENT",
        );
        if (
            !$this->digestMatches($case) ||
            "imperium.foundry-adversarial-reviewer-persona-review-case/v1" !==
                ($case["schema"] ?? null) ||
            $reviewCaseId !== ($case["review_case_id"] ?? null) ||
            "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION" !==
                ($case["status"] ?? null) ||
            true !== ($case["review_initiated"] ?? null) ||
            false !== ($case["review_complete"] ?? null) ||
            false !== ($case["review_authority"] ?? null) ||
            true !== ($case["sealed"] ?? null)
        ) {
            throw new \RuntimeException("F123_REVIEWER_BOOTSTRAP_CASE_INVALID");
        }
        if (!is_file($this->personaPath)) {
            throw new \RuntimeException(
                "F124_BLACKQUILL_PERSONA_SOURCE_ABSENT",
            );
        }
        $content = (string) file_get_contents($this->personaPath);
        if ("" === trim($content)) {
            throw new \RuntimeException(
                "F125_BLACKQUILL_PERSONA_SOURCE_INVALID",
            );
        }
        $source = [
            "path" =>
                "offices/foundry/personas/blackquill-adversarial-reviewer.md",
            "content_digest" => "sha256:" . hash("sha256", $content),
            "derivation_basis" =>
                "user-designated Blackquill critical-analysis contract",
            "authority_imported" => false,
        ];
        foreach (
            glob(
                $this->intakeDirectory .
                    "/blackquill-external-reviewer-intake-*.json",
            ) ?:
            []
            as $path
        ) {
            $existing = $this->read(
                $path,
                "F126_BLACKQUILL_INTAKE_REPLAY_CONFLICT",
            );
            if (
                $reviewCaseId ===
                    ($existing["target_review_case_id"] ?? null) &&
                $this->digestMatches($existing)
            ) {
                return $existing;
            }
        }
        $id =
            "blackquill-external-reviewer-intake-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $reviewCaseId,
                        $case["record_digest"],
                        $source,
                        "foundry.external.blackquill-adversarial-reviewer",
                        "1.0.0",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-external-reviewer-persona-intake/v1",
            "intake_id" => $id,
            "instance_id" => $case["instance_id"],
            "target_review_case_id" => $reviewCaseId,
            "target_review_case_digest" => $case["record_digest"],
            "target_persona_candidate_id" => $case["persona_candidate_id"],
            "target_persona_candidate_digest" =>
                $case["persona_candidate_digest"],
            "external_persona" => [
                "persona_id" =>
                    "foundry.external.blackquill-adversarial-reviewer",
                "persona_version" => "1.0.0",
                "source" => $source,
            ],
            "independence_attestation" => [
                "not_the_candidate_under_review" => true,
                "did_not_author_target_candidate" => true,
                "did_not_repair_target_candidate" => true,
            ],
            "status" => "SEALED_PENDING_GARRISON_ADMISSION_EVIDENCE",
            "source_validated" => true,
            "admission_claim_state" => "UNVERIFIED",
            "eligible_for_review_occupation" => false,
            "review_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }
    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
    private function digestMatches(array $record): bool
    {
        $digest = $record["record_digest"] ?? null;
        unset($record["record_digest"]);
        return is_string($digest) &&
            hash_equals(
                $digest,
                hash("sha256", CanonicalJson::encode($record)),
            );
    }
    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->intakeDirectory) &&
            !mkdir($this->intakeDirectory, 0770, true) &&
            !is_dir($this->intakeDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry external-reviewer intake directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->intakeDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F126_BLACKQUILL_INTAKE_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F126_BLACKQUILL_INTAKE_REPLAY_CONFLICT",
                );
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $record,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($temporary, $path)
        ) {
            @unlink($temporary);
            throw new \RuntimeException(
                "Blackquill external-reviewer intake cannot be committed atomically.",
            );
        }
        return $record;
    }
}
