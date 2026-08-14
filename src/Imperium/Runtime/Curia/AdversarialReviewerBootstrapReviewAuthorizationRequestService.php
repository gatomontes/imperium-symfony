<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class AdversarialReviewerBootstrapReviewAuthorizationRequestService
{
    private string $responses;
    private string $reviewCases;
    private string $requests;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $this->responses =
            $p .
            "/var/imperium/curia/adversarial-reviewer-availability-responses";
        $this->reviewCases =
            $p .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-review-cases";
        $this->requests =
            $p .
            "/var/imperium/curia/adversarial-reviewer-bootstrap-review-authorization-requests";
    }
    public function request(string $responseId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-availability-response-[a-f0-9]{20}$/',
                $responseId,
            )
        ) {
            throw new \InvalidArgumentException(
                "C131_ADVERSARIAL_REVIEWER_AVAILABILITY_RESPONSE_ID_INVALID",
            );
        }
        $response = $this->read(
            $this->responses . "/" . $responseId . ".json",
            "C132_ADVERSARIAL_REVIEWER_AVAILABILITY_RESPONSE_ABSENT",
        );
        $reviewCaseId = $response["source_review_case_id"] ?? null;
        $case = is_string($reviewCaseId)
            ? $this->read(
                $this->reviewCases . "/" . $reviewCaseId . ".json",
                "C133_ADVERSARIAL_REVIEWER_BOOTSTRAP_REQUEST_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->ok($response) ||
            !$this->ok($case) ||
            "imperium.garrison-adversarial-reviewer-availability-response/v1" !==
                ($response["schema"] ?? null) ||
            $responseId !== ($response["response_id"] ?? null) ||
            "AUTHORITATIVE_INVENTORY_FACTS_DELIVERED_TO_CURIA" !==
                ($response["status"] ?? null) ||
            true !== ($response["authoritative_inventory_response"] ?? null) ||
            true !== ($response["identity_match_only"] ?? null) ||
            false !== ($response["suitability_determined"] ?? null) ||
            false !== ($response["availability_interpreted"] ?? null) ||
            "NO_DISTINCT_ADMITTED_ADVERSARIAL_REVIEWER_PERSONA_HELD" !==
                ($response["ledger_finding"] ?? null) ||
            0 !== ($response["matching_identity_record_count"] ?? null) ||
            [] !== ($response["matching_identity_records"] ?? null) ||
            "curia.seneschal" !== ($response["recipient"]["seat"] ?? null) ||
            $this->hasAuthority($response) ||
            true !== ($response["sealed"] ?? null) ||
            "imperium.foundry-adversarial-reviewer-persona-review-case/v1" !==
                ($case["schema"] ?? null) ||
            $reviewCaseId !== ($case["review_case_id"] ?? null) ||
            ($response["source_review_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            ($response["instance_id"] ?? null) !==
                ($case["instance_id"] ?? null) ||
            CanonicalJson::encode($response["blocked_candidate"] ?? null) !==
                CanonicalJson::encode($this->candidateReference($case)) ||
            "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION" !==
                ($case["status"] ?? null) ||
            true !== ($case["review_initiated"] ?? null) ||
            false !== ($case["review_complete"] ?? null) ||
            $this->hasAuthority($case) ||
            true !== ($case["sealed"] ?? null)
        ) {
            throw new \RuntimeException(
                "C133_ADVERSARIAL_REVIEWER_BOOTSTRAP_REQUEST_CHAIN_INVALID",
            );
        }
        foreach (
            glob(
                $this->requests .
                    "/adversarial-reviewer-bootstrap-review-authorization-request-*.json",
            ) ?:
            []
            as $path
        ) {
            $existing = $this->read(
                $path,
                "C134_ADVERSARIAL_REVIEWER_BOOTSTRAP_REQUEST_REPLAY_CONFLICT",
            );
            if (
                $responseId ===
                    ($existing["source_availability_response_id"] ?? null) &&
                $this->ok($existing)
            ) {
                return $existing;
            }
        }
        $id =
            "adversarial-reviewer-bootstrap-review-authorization-request-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $responseId,
                        $response["record_digest"],
                        $reviewCaseId,
                        $case["record_digest"],
                        "imperator-development-root",
                        "exact-one-time-bootstrap-review-only",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.curia-adversarial-reviewer-bootstrap-review-authorization-request/v1",
            "request_id" => $id,
            "instance_id" => $case["instance_id"],
            "requester" => [
                "office" => "curia",
                "seat" => "curia.seneschal",
            ],
            "recipient" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "source_availability_response_id" => $responseId,
            "source_availability_response_digest" => $response["record_digest"],
            "source_inquiry_id" => $response["source_inquiry_id"],
            "source_inquiry_digest" => $response["source_inquiry_digest"],
            "source_review_case_id" => $reviewCaseId,
            "source_review_case_digest" => $case["record_digest"],
            "persona_candidate" => $this->candidateReference($case),
            "construction_acceptance_id" => $case["construction_acceptance_id"],
            "construction_acceptance_digest" =>
                $case["construction_acceptance_digest"],
            "artificer" => $case["artificer"],
            "required_capability" => $case["required_capability"],
            "bootstrap_constraint" => $case["bootstrap_constraint"],
            "review_scope" => $case["review_scope"],
            "inventory_basis" => [
                "responder" => $response["responder"],
                "custody_ledger_record_count" =>
                    $response["custody_ledger_record_count"],
                "custody_ledger_digest" => $response["custody_ledger_digest"],
                "finding" => $response["ledger_finding"],
                "finding_scope" => "exact Garrison custody snapshot only",
            ],
            "curia_finding" => [
                "normal_path_state" =>
                    "NO_DISTINCT_ADMITTED_REVIEWER_FOUND_IN_EXACT_CUSTODY_SNAPSHOT",
                "exception_implied" => false,
                "imperator_decision_required" => true,
            ],
            "requested_protocol" => [
                "kind" =>
                    "EXACT_ONE_TIME_ADVERSARIAL_REVIEWER_BOOTSTRAP_REVIEW",
                "review_target" => $this->candidateReference($case),
                "review_scope" => $case["review_scope"],
                "reviewer_requirement" =>
                    "an Imperator-designated reviewer independent of the candidate and Artificer, whose identity and mandate are separately bound before review",
                "permitted_output" =>
                    "attributable adverse or clean findings for this exact immutable candidate only",
                "expires_on" =>
                    "review completion, formal abort, candidate revision, or any identity or digest change",
                "general_precedent" => false,
                "creates_persona" => false,
                "creates_institution" => false,
                "imports_reviewer_authority" => false,
                "production_approval_included" => false,
                "admission_authority_included" => false,
                "execution_authority_included" => false,
            ],
            "question" =>
                "Authorize only this exact one-time bootstrap-review protocol, subject to separate reviewer identity and mandate binding?",
            "requested_authority" =>
                "EXACT_ONE_TIME_ADVERSARIAL_REVIEWER_BOOTSTRAP_REVIEW_PROTOCOL_ONLY",
            "status" => "PENDING_IMPERATOR_DECISION",
            "approval_recorded" => false,
            "protocol_authority" => false,
            "reviewer_designation_authority" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }
    private function candidateReference(array $case): array
    {
        return [
            "persona_candidate_id" => $case["persona_candidate_id"] ?? null,
            "persona_candidate_digest" =>
                $case["persona_candidate_digest"] ?? null,
            "persona_id" => $case["persona_id"] ?? null,
            "persona_version" => $case["persona_version"] ?? null,
            "construction_acceptance_id" =>
                $case["construction_acceptance_id"] ?? null,
            "construction_acceptance_digest" =>
                $case["construction_acceptance_digest"] ?? null,
        ];
    }
    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "ranking_authority",
                "selection_authority",
                "qualification_authority",
                "review_authority",
                "review_findings_authority",
                "exception_authority",
                "protocol_authority",
                "reviewer_designation_authority",
                "production_approval",
                "profile_approval_authority",
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
            !is_dir($this->requests) &&
            !mkdir($this->requests, 0770, true) &&
            !is_dir($this->requests)
        ) {
            throw new \RuntimeException(
                "Curia bootstrap-review authorization-request directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->requests . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "C134_ADVERSARIAL_REVIEWER_BOOTSTRAP_REQUEST_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "C134_ADVERSARIAL_REVIEWER_BOOTSTRAP_REQUEST_REPLAY_CONFLICT",
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
                "Bootstrap-review authorization request cannot be committed atomically.",
            );
        }
        return $record;
    }
}
