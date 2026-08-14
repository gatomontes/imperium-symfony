<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillReviewResolutionRequestService
{
    private string $reviewCases;
    private string $requests;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $this->reviewCases =
            $p .
            "/var/imperium/offices/foundry/blackquill-persona-review-cases";
        $this->requests =
            $p . "/var/imperium/curia/blackquill-review-resolution-requests";
    }
    public function request(string $reviewCaseId): array
    {
        if (
            !preg_match(
                '/^blackquill-persona-review-case-[a-f0-9]{20}$/',
                $reviewCaseId,
            )
        ) {
            throw new \InvalidArgumentException(
                "C125_BLACKQUILL_REVIEW_CASE_ID_INVALID",
            );
        }
        $case = $this->read(
            $this->reviewCases . "/" . $reviewCaseId . ".json",
            "C126_BLACKQUILL_REVIEW_CASE_ABSENT",
        );
        if (
            !$this->ok($case) ||
            "imperium.foundry-blackquill-persona-review-case/v1" !==
                ($case["schema"] ?? null) ||
            $reviewCaseId !== ($case["review_case_id"] ?? null) ||
            "BLOCKED_PENDING_DISTINCT_INDEPENDENT_REVIEWER" !==
                ($case["status"] ?? null) ||
            "curia.seneschal" !== ($case["escalation_recipient"] ?? null) ||
            "foundry.artificer" !== ($case["requester"] ?? null) ||
            true !== ($case["review_initiated"] ?? null) ||
            false !== ($case["review_complete"] ?? null) ||
            false !== ($case["clean_review"] ?? null) ||
            true !== ($case["sealed"] ?? null) ||
            !$this->hasLineage($case) ||
            $this->hasAuthority($case)
        ) {
            throw new \RuntimeException("C127_BLACKQUILL_REVIEW_CASE_INVALID");
        }
        foreach (
            glob(
                $this->requests .
                    "/blackquill-review-resolution-request-*.json",
            ) ?:
            []
            as $path
        ) {
            $old = $this->read(
                $path,
                "C128_BLACKQUILL_REVIEW_RESOLUTION_REQUEST_REPLAY_CONFLICT",
            );
            if (
                $reviewCaseId === ($old["source_review_case_id"] ?? null) &&
                $this->ok($old)
            ) {
                return $old;
            }
        }
        $id =
            "blackquill-review-resolution-request-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $reviewCaseId,
                        $case["record_digest"],
                        "imperator-development-root",
                        "distinct-reviewer-or-exceptional-bootstrap-protocol",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.curia-blackquill-review-resolution-request/v1",
            "request_id" => $id,
            "instance_id" => $case["instance_id"],
            "requester" => ["office" => "curia", "seat" => "curia.seneschal"],
            "recipient" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "source_review_case_id" => $reviewCaseId,
            "source_review_case_digest" => $case["record_digest"],
            "persona_candidate_id" => $case["persona_candidate_id"],
            "persona_candidate_digest" => $case["persona_candidate_digest"],
            "persona_id" => $case["persona_id"],
            "persona_version" => $case["persona_version"],
            "production_acceptance_id" => $case["production_acceptance_id"],
            "production_acceptance_digest" =>
                $case["production_acceptance_digest"],
            "authorization_act_id" => $case["authorization_act_id"],
            "authorization_act_digest" => $case["authorization_act_digest"],
            "source_case_id" => $case["source_case_id"],
            "source_case_digest" => $case["source_case_digest"],
            "template" => $case["template"],
            "source" => $case["source"],
            "artificer" => $case["artificer"],
            "review_scope" => $case["review_scope"],
            "independence_constraint" => $case["independence_constraint"],
            "lawful_resolutions" => $case["permitted_resolutions"],
            "curia_disposition" => [
                "finding" => "INDEPENDENCE_CONSTRAINT_AUTHENTICATED",
                "normal_path" =>
                    "SUPPLY_DISTINCT_ALREADY_ADMITTED_REVIEWER_IF_AVAILABLE",
                "exception_path" =>
                    "REQUIRES_SEPARATE_EXPLICIT_IMPERATOR_AUTHORIZATION",
                "self_review" => "PROHIBITED",
                "review_waiver" => "PROHIBITED",
            ],
            "question" =>
                "Direct Curia to supply a distinct admitted Reviewer, or explicitly authorize an exact exceptional bootstrap-review protocol for this candidate?",
            "requested_decision" =>
                "BLACKQUILL_INDEPENDENT_REVIEW_RESOLUTION_ONLY",
            "status" => "PENDING_IMPERATOR_DECISION",
            "decision_recorded" => false,
            "distinct_reviewer_authority" => false,
            "exception_authority" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
            "senate_confirmation_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }
    private function hasLineage(array $case): bool
    {
        foreach (
            [
                "instance_id",
                "persona_candidate_id",
                "persona_candidate_digest",
                "persona_id",
                "persona_version",
                "production_acceptance_id",
                "production_acceptance_digest",
                "authorization_act_id",
                "authorization_act_digest",
                "source_case_id",
                "source_case_digest",
                "review_scope",
            ]
            as $key
        ) {
            if (!is_string($case[$key] ?? null) || "" === $case[$key]) {
                return false;
            }
        }
        return is_array($case["template"] ?? null) &&
            is_array($case["source"] ?? null) &&
            is_array($case["artificer"] ?? null) &&
            is_array($case["independence_constraint"] ?? null) &&
            is_array($case["permitted_resolutions"] ?? null);
    }
    private function hasAuthority(array $r): bool
    {
        foreach (
            [
                "exception_authority",
                "review_findings_authority",
                "review_authority",
                "production_approval",
                "senate_confirmation_authority",
                "release_authority",
                "admission_authority",
                "spawning_authority",
                "seat_binding_authority",
                "candidate_approval_authority",
                "execution_authority",
            ]
            as $key
        ) {
            if (true === ($r[$key] ?? false)) {
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
    private function ok(array $r): bool
    {
        $digest = $r["record_digest"] ?? null;
        unset($r["record_digest"]);
        return is_string($digest) &&
            hash_equals($digest, hash("sha256", CanonicalJson::encode($r)));
    }
    private function persist(string $id, array $r): array
    {
        if (
            !is_dir($this->requests) &&
            !mkdir($this->requests, 0770, true) &&
            !is_dir($this->requests)
        ) {
            throw new \RuntimeException(
                "Curia Blackquill review-resolution request directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $path = $this->requests . "/" . $id . ".json";
        if (is_file($path)) {
            $old = $this->read(
                $path,
                "C128_BLACKQUILL_REVIEW_RESOLUTION_REQUEST_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "C128_BLACKQUILL_REVIEW_RESOLUTION_REQUEST_REPLAY_CONFLICT",
                );
            }
            return $old;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $r,
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
                "Blackquill review-resolution request cannot be committed atomically.",
            );
        }
        return $r;
    }
}
