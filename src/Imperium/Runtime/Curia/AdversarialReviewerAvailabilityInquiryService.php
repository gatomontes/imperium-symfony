<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class AdversarialReviewerAvailabilityInquiryService
{
    private string $reviewCases;
    private string $occupancies;
    private string $garrisonInbox;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $this->reviewCases =
            $p .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-review-cases";
        $this->occupancies = $p . "/var/imperium/offices/garrison/occupancy";
        $this->garrisonInbox = $p . "/var/imperium/offices/garrison/inbox";
    }
    public function inquire(string $reviewCaseId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-persona-review-case-[a-f0-9]{20}$/',
                $reviewCaseId,
            )
        ) {
            throw new \InvalidArgumentException(
                "C125_ADVERSARIAL_REVIEWER_REVIEW_CASE_ID_INVALID",
            );
        }
        $case = $this->read(
            $this->reviewCases . "/" . $reviewCaseId . ".json",
            "C126_ADVERSARIAL_REVIEWER_REVIEW_CASE_ABSENT",
        );
        if (
            !$this->ok($case) ||
            "imperium.foundry-adversarial-reviewer-persona-review-case/v1" !==
                ($case["schema"] ?? null) ||
            $reviewCaseId !== ($case["review_case_id"] ?? null) ||
            "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION" !==
                ($case["status"] ?? null) ||
            "curia.seneschal" !== ($case["escalation_recipient"] ?? null) ||
            "foundry.artificer" !== ($case["requester"] ?? null) ||
            "foundry.adversarial-reviewer" !== ($case["persona_id"] ?? null) ||
            "foundry.reviewer.adversarial" !==
                ($case["required_capability"]["seat"] ?? null) ||
            true !== ($case["review_initiated"] ?? null) ||
            false !== ($case["review_complete"] ?? null) ||
            true !== ($case["sealed"] ?? null) ||
            $this->hasAuthority($case) ||
            !$this->hasLineage($case)
        ) {
            throw new \RuntimeException(
                "C127_ADVERSARIAL_REVIEWER_REVIEW_CASE_INVALID",
            );
        }
        $constable = $this->currentConstable($case["instance_id"]);
        $id =
            "adversarial-reviewer-availability-inquiry-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $reviewCaseId,
                        $case["record_digest"],
                        $case["persona_candidate_id"],
                        $case["persona_candidate_digest"],
                        "distinct-admitted-reviewer-inventory-only",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.garrison-adversarial-reviewer-availability-inquiry/v1",
            "inquiry_id" => $id,
            "instance_id" => $case["instance_id"],
            "requester" => [
                "office" => "curia",
                "seat" => "curia.seneschal",
            ],
            "recipient" => [
                "office" => "garrison",
                "seat" => "garrison.constable",
            ],
            "source_review_case_id" => $reviewCaseId,
            "source_review_case_digest" => $case["record_digest"],
            "blocked_candidate" => [
                "persona_candidate_id" => $case["persona_candidate_id"],
                "persona_candidate_digest" => $case["persona_candidate_digest"],
                "persona_id" => $case["persona_id"],
                "persona_version" => $case["persona_version"],
                "construction_acceptance_id" =>
                    $case["construction_acceptance_id"],
                "construction_acceptance_digest" =>
                    $case["construction_acceptance_digest"],
            ],
            "required_capability" => $case["required_capability"],
            "review_scope" => $case["review_scope"],
            "inventory_question" =>
                "Does Garrison hold a distinct admitted Persona eligible for later qualification and independent occupation of foundry.reviewer.adversarial?",
            "requested_facts" => [
                "exact admitted Persona identity and version",
                "custody state",
                "availability facts",
                "admission evidence held in custody",
                "conflicts and current commitments",
            ],
            "exclusions" => [
                "the blocked candidate is still under construction and cannot satisfy this inquiry",
                "Garrison must not infer missing admission, qualification, suitability, or review authority",
                "absence of an admitted Persona does not authorize construction or an exceptional protocol",
            ],
            "constable_occupancy" => $constable,
            "status" =>
                null === $constable
                    ? "CONSTABLE_ACTIVATION_REQUIRED"
                    : "DELIVERED_PENDING_CONSTABLE_RESPONSE",
            "authoritative_inventory_response" => false,
            "ranking_authority" => false,
            "selection_authority" => false,
            "qualification_authority" => false,
            "review_authority" => false,
            "exception_authority" => false,
            "reservation_authority" => false,
            "retrieval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }
    private function currentConstable(string $instanceId): ?array
    {
        $paths =
            glob($this->occupancies . "/garrison-constable-binding-*.json") ?:
            [];
        if ([] === $paths) {
            return null;
        }
        if (1 !== count($paths)) {
            throw new \RuntimeException("C128_CONSTABLE_OCCUPANCY_AMBIGUOUS");
        }
        $record = $this->read($paths[0], "C129_CONSTABLE_OCCUPANCY_INVALID");
        if (
            !$this->ok($record) ||
            "imperium.garrison-constable-occupancy/v1" !==
                ($record["schema"] ?? null) ||
            "garrison.constable" !== ($record["seat"] ?? null) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            !is_string($record["manifestation_id"] ?? null) ||
            !is_int($record["occupancy_generation"] ?? null) ||
            true !== ($record["inventory_response_authority"] ?? null) ||
            true === ($record["selection_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("C129_CONSTABLE_OCCUPANCY_INVALID");
        }
        return [
            "seat" => $record["seat"],
            "manifestation_id" => $record["manifestation_id"],
            "occupancy_generation" => $record["occupancy_generation"],
            "record_digest" => $record["record_digest"],
        ];
    }
    private function hasLineage(array $case): bool
    {
        foreach (
            [
                "instance_id",
                "persona_candidate_id",
                "persona_candidate_digest",
                "persona_version",
                "construction_acceptance_id",
                "construction_acceptance_digest",
                "review_scope",
            ]
            as $key
        ) {
            if (!is_string($case[$key] ?? null) || "" === $case[$key]) {
                return false;
            }
        }
        return true;
    }
    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "exception_authority",
                "review_authority",
                "production_approval",
                "profile_approval_authority",
                "spawning_authority",
                "seat_binding_authority",
                "admission_authority",
                "candidate_approval_authority",
                "execution_authority",
            ]
            as $key
        ) {
            if (true === ($record[$key] ?? false)) {
                return true;
            }
        }
        return false;
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
    private function ok(array $record): bool
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
            !is_dir($this->garrisonInbox) &&
            !mkdir($this->garrisonInbox, 0770, true) &&
            !is_dir($this->garrisonInbox)
        ) {
            throw new \RuntimeException("Garrison inbox cannot be created.");
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->garrisonInbox . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "C130_ADVERSARIAL_REVIEWER_INQUIRY_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "C130_ADVERSARIAL_REVIEWER_INQUIRY_REPLAY_CONFLICT",
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
                "Adversarial Reviewer availability inquiry cannot be committed atomically.",
            );
        }
        return $record;
    }
}
