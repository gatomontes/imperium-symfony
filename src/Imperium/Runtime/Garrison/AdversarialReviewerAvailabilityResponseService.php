<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Garrison;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class AdversarialReviewerAvailabilityResponseService
{
    private string $inbox;
    private string $occupancies;
    private string $custody;
    private string $responses;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $root = $p . "/var/imperium/offices/garrison";
        $this->inbox = $root . "/inbox";
        $this->occupancies = $root . "/occupancy";
        $this->custody = $root . "/custody";
        $this->responses =
            $p .
            "/var/imperium/curia/adversarial-reviewer-availability-responses";
    }
    public function respond(string $inquiryId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-availability-inquiry-[a-f0-9]{20}$/',
                $inquiryId,
            )
        ) {
            throw new \InvalidArgumentException(
                "GA68_ADVERSARIAL_REVIEWER_INQUIRY_ID_INVALID",
            );
        }
        $inquiry = $this->read(
            $this->inbox . "/" . $inquiryId . ".json",
            "GA69_ADVERSARIAL_REVIEWER_INQUIRY_ABSENT",
        );
        if (
            !$this->ok($inquiry) ||
            "imperium.garrison-adversarial-reviewer-availability-inquiry/v1" !==
                ($inquiry["schema"] ?? null) ||
            $inquiryId !== ($inquiry["inquiry_id"] ?? null) ||
            "curia" !== ($inquiry["requester"]["office"] ?? null) ||
            "curia.seneschal" !== ($inquiry["requester"]["seat"] ?? null) ||
            "garrison.constable" !== ($inquiry["recipient"]["seat"] ?? null) ||
            "foundry.adversarial-reviewer" !==
                ($inquiry["blocked_candidate"]["persona_id"] ?? null) ||
            !in_array(
                $inquiry["status"] ?? null,
                [
                    "CONSTABLE_ACTIVATION_REQUIRED",
                    "DELIVERED_PENDING_CONSTABLE_RESPONSE",
                ],
                true,
            ) ||
            true === ($inquiry["authoritative_inventory_response"] ?? null) ||
            $this->hasAuthority($inquiry) ||
            true !== ($inquiry["sealed"] ?? null)
        ) {
            throw new \RuntimeException(
                "GA70_ADVERSARIAL_REVIEWER_INQUIRY_INVALID",
            );
        }
        $constable = $this->currentConstable($inquiry["instance_id"]);
        $embedded = $inquiry["constable_occupancy"] ?? null;
        if (
            null !== $embedded &&
            CanonicalJson::encode($embedded) !==
                CanonicalJson::encode([
                    "seat" => $constable["seat"],
                    "manifestation_id" => $constable["manifestation_id"],
                    "occupancy_generation" =>
                        $constable["occupancy_generation"],
                    "record_digest" => $constable["record_digest"],
                ])
        ) {
            throw new \RuntimeException("GA72_CONSTABLE_OCCUPANCY_INVALID");
        }
        $records = $this->custodyRecords(
            $inquiry["blocked_candidate"]["persona_candidate_id"],
        );
        $matches = array_values(
            array_filter(
                $records,
                static fn(
                    array $record,
                ): bool => "foundry.adversarial-reviewer" ===
                    $record["persona_id"],
            ),
        );
        $id =
            "adversarial-reviewer-availability-response-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $inquiryId,
                        $inquiry["record_digest"],
                        $constable["record_digest"],
                        $records,
                        $matches,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.garrison-adversarial-reviewer-availability-response/v1",
            "response_id" => $id,
            "instance_id" => $inquiry["instance_id"],
            "source_inquiry_id" => $inquiryId,
            "source_inquiry_digest" => $inquiry["record_digest"],
            "source_review_case_id" => $inquiry["source_review_case_id"],
            "source_review_case_digest" =>
                $inquiry["source_review_case_digest"],
            "blocked_candidate" => $inquiry["blocked_candidate"],
            "responder" => [
                "office" => "garrison",
                "seat" => "garrison.constable",
                "manifestation_id" => $constable["manifestation_id"],
                "occupancy_generation" => $constable["occupancy_generation"],
                "occupancy_digest" => $constable["record_digest"],
            ],
            "recipient" => $inquiry["requester"],
            "inventory_question" => $inquiry["inventory_question"],
            "requested_facts" => $inquiry["requested_facts"],
            "custody_ledger_record_count" => count($records),
            "custody_ledger_digest" =>
                "sha256:" . hash("sha256", CanonicalJson::encode($records)),
            "matching_identity_record_count" => count($matches),
            "matching_identity_records" => $matches,
            "ledger_finding" =>
                [] === $matches
                    ? "NO_DISTINCT_ADMITTED_ADVERSARIAL_REVIEWER_PERSONA_HELD"
                    : "EXACT_ADMITTED_ADVERSARIAL_REVIEWER_IDENTITY_RECORDS_ATTACHED",
            "status" => "AUTHORITATIVE_INVENTORY_FACTS_DELIVERED_TO_CURIA",
            "authoritative_inventory_response" => true,
            "identity_match_only" => true,
            "suitability_determined" => false,
            "availability_interpreted" => false,
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
            "interpretation_boundary" =>
                "Garrison reports exact admitted custody identity facts only; Curia may not convert an identity match or absence into suitability, qualification, selection, review, or exceptional authority.",
            "sealed" => true,
        ]);
    }
    private function currentConstable(string $instanceId): array
    {
        $paths =
            glob($this->occupancies . "/garrison-constable-binding-*.json") ?:
            [];
        if (1 !== count($paths)) {
            throw new \RuntimeException("GA71_CONSTABLE_OCCUPANCY_REQUIRED");
        }
        $record = $this->read($paths[0], "GA71_CONSTABLE_OCCUPANCY_REQUIRED");
        if (
            !$this->ok($record) ||
            "imperium.garrison-constable-occupancy/v1" !==
                ($record["schema"] ?? null) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "garrison.constable" !== ($record["seat"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            !is_string($record["manifestation_id"] ?? null) ||
            !is_int($record["occupancy_generation"] ?? null) ||
            true !== ($record["inventory_response_authority"] ?? null) ||
            true === ($record["selection_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("GA72_CONSTABLE_OCCUPANCY_INVALID");
        }
        return $record;
    }
    private function custodyRecords(string $blockedCandidateId): array
    {
        $records = [];
        foreach (glob($this->custody . "/*.json") ?: [] as $path) {
            $record = $this->read($path, "GA73_CUSTODY_RECORD_INVALID");
            if (
                !$this->ok($record) ||
                "imperium.garrison-persona-custody/v1" !==
                    ($record["schema"] ?? null) ||
                "ADMITTED_HELD" !== ($record["custody_state"] ?? null) ||
                !is_string($record["persona_id"] ?? null) ||
                !is_string($record["persona_version"] ?? null) ||
                $blockedCandidateId ===
                    ($record["source_persona_candidate_id"] ?? null)
            ) {
                throw new \RuntimeException("GA73_CUSTODY_RECORD_INVALID");
            }
            $records[] = $record;
        }
        usort(
            $records,
            static fn(array $a, array $b): int => [
                $a["persona_id"],
                $a["persona_version"],
                $a["record_digest"],
            ] <=> [
                $b["persona_id"],
                $b["persona_version"],
                $b["record_digest"],
            ],
        );
        return $records;
    }
    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "ranking_authority",
                "selection_authority",
                "qualification_authority",
                "review_authority",
                "exception_authority",
                "reservation_authority",
                "retrieval_authority",
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
            !is_dir($this->responses) &&
            !mkdir($this->responses, 0770, true) &&
            !is_dir($this->responses)
        ) {
            throw new \RuntimeException(
                "Curia Reviewer-availability response directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->responses . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "GA74_ADVERSARIAL_REVIEWER_RESPONSE_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "GA74_ADVERSARIAL_REVIEWER_RESPONSE_REPLAY_CONFLICT",
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
                "Adversarial Reviewer availability response cannot be committed atomically.",
            );
        }
        return $record;
    }
}
