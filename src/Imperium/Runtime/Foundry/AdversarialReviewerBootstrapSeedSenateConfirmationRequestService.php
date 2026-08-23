<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerBootstrapSeedSenateConfirmationRequestService
{
    private string $returns;
    private string $deliveries;
    private string $approvals;
    private string $candidates;
    private string $senateInbox;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->returns =
            $projectDir .
            "/var/imperium/offices/foundry/inbox/adversarial-reviewer-bootstrap-seed-admission-returns";
        $this->deliveries =
            $projectDir .
            "/var/imperium/offices/garrison/inbox/adversarial-reviewer-bootstrap-seed-admissions";
        $this->approvals =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-production-approvals";
        $this->candidates =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates";
        $this->senateInbox =
            $projectDir .
            "/var/imperium/offices/senate/inbox/persona-confirmation-requests";
    }

    public function request(string $returnId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-bootstrap-seed-admission-return-[a-f0-9]{20}$/',
                $returnId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F151_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_RETURN_ID_INVALID",
            );
        }
        $return = $this->read(
            $this->returns . "/" . $returnId . ".json",
            "F152_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_RETURN_ABSENT",
        );
        if (
            !$this->digestMatches($return) ||
            "imperium.garrison-adversarial-reviewer-bootstrap-seed-admission-return/v1" !==
                ($return["schema"] ?? null) ||
            $returnId !== ($return["return_id"] ?? null) ||
            "garrison.constable" !== ($return["constable"]["seat"] ?? null) ||
            "foundry" !== ($return["recipient"]["office"] ?? null) ||
            "foundry.artificer" !== ($return["recipient"]["seat"] ?? null) ||
            "REFUSED_INCOMPLETE_PERSONA_ADMISSION_PACKAGE" !==
                ($return["disposition"] ?? null) ||
            "REFUSED" !== ($return["admission_decision"] ?? null) ||
            false !== ($return["bootstrap_exception_extended"] ?? null) ||
            false !== ($return["custody_created"] ?? null) ||
            true !== ($return["sealed"] ?? null) ||
            !$this->exactMissingConfirmationDefects(
                $return["defects"] ?? null,
            ) ||
            $this->hasAuthority($return)
        ) {
            throw new \RuntimeException(
                "F153_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_RETURN_INVALID",
            );
        }

        $deliveryId = $return["source_delivery_id"] ?? null;
        $delivery = is_string($deliveryId)
            ? $this->read(
                $this->deliveries . "/" . $deliveryId . ".json",
                "F154_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_CHAIN_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($delivery) ||
            ($return["source_delivery_digest"] ?? null) !==
                ($delivery["record_digest"] ?? null) ||
            ($return["instance_id"] ?? null) !==
                ($delivery["instance_id"] ?? null) ||
            ($return["production_approval_id"] ?? null) !==
                ($delivery["production_approval_id"] ?? null) ||
            ($return["production_approval_digest"] ?? null) !==
                ($delivery["production_approval_digest"] ?? null) ||
            ($return["persona_candidate_id"] ?? null) !==
                ($delivery["persona_candidate_id"] ?? null) ||
            ($return["persona_candidate_digest"] ?? null) !==
                ($delivery["persona_candidate_digest"] ?? null) ||
            "DELIVERED_PENDING_GARRISON_ACCEPTANCE" !==
                ($delivery["status"] ?? null) ||
            true !== ($delivery["production_approval"] ?? null) ||
            false !== ($delivery["admission_authority"] ?? null) ||
            null !== ($delivery["admission_decision"] ?? null)
        ) {
            throw new \RuntimeException(
                "F154_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_CHAIN_CHANGED",
            );
        }

        $approvalId = $return["production_approval_id"] ?? null;
        $approval = is_string($approvalId)
            ? $this->read(
                $this->approvals . "/" . $approvalId . ".json",
                "F155_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_APPROVAL_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($approval) ||
            ($return["production_approval_digest"] ?? null) !==
                ($approval["record_digest"] ?? null) ||
            "imperium.foundry-adversarial-reviewer-bootstrap-seed-production-approval/v1" !==
                ($approval["schema"] ?? null) ||
            "APPROVED_PENDING_GARRISON_ADMISSION" !==
                ($approval["status"] ?? null) ||
            true !== ($approval["production_approval"] ?? null) ||
            true !== ($approval["bootstrap_seed_authority_consumed"] ?? null)
        ) {
            throw new \RuntimeException(
                "F155_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_APPROVAL_CHANGED",
            );
        }

        $candidateId = $return["persona_candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->candidates . "/" . $candidateId . ".json",
                "F156_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($candidate) ||
            ($return["persona_candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($approval["persona_candidate_id"] ?? null) !== $candidateId ||
            ($approval["persona_candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            "foundry.adversarial-reviewer" !==
                ($candidate["persona_id"] ?? null) ||
            "1.0.0" !== ($candidate["persona_version"] ?? null) ||
            true !== ($candidate["construction_complete"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            true === ($candidate["production_approval"] ?? null)
        ) {
            throw new \RuntimeException(
                "F156_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            );
        }

        $id =
            "adversarial-reviewer-bootstrap-seed-confirmation-request-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $returnId,
                        $return["record_digest"],
                        $approvalId,
                        $approval["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        "senate.qualification",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.senate-persona-confirmation-request/v1",
            "confirmation_request_id" => $id,
            "instance_id" => $candidate["instance_id"],
            "proceeding_class" => "PENDING_ADMISSION_PERSONA_QUALIFICATION",
            "requester" => $approval["actor"],
            "recipient" => [
                "office" => "senate",
                "seat" => "senate.lord-speaker",
            ],
            "source_admission_return_id" => $returnId,
            "source_admission_return_digest" => $return["record_digest"],
            "source_admission_delivery_id" => $deliveryId,
            "source_admission_delivery_digest" => $delivery["record_digest"],
            "production_approval_id" => $approvalId,
            "production_approval_digest" => $approval["record_digest"],
            "bootstrap_seed_acceptance_id" =>
                $approval["bootstrap_seed_acceptance_id"],
            "bootstrap_seed_acceptance_digest" =>
                $approval["bootstrap_seed_acceptance_digest"],
            "authorization_act_id" => $approval["authorization_act_id"],
            "authorization_act_digest" => $approval["authorization_act_digest"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "persona_id" => $candidate["persona_id"],
            "persona_version" => $candidate["persona_version"],
            "persona" => $candidate["persona"],
            "design_basis" => $candidate["sources"]["design_basis"],
            "bootstrap_seed_boundary" => $approval["bootstrap_seed_boundary"],
            "authorized_review_target" =>
                $approval["authorized_review_target"] ?? null,
            "review_target_lineage" =>
                $approval["review_target_lineage"] ?? null,
            "examination_contract" => [
                "subject_state" => "production-approved-pending-admission",
                "manifestation_required" => true,
                "profile_class" => "examination_only",
                "sterile_witness_required" => true,
                "exact_candidate_only" => true,
                "independent_senate_disposition_required" => true,
                "self_review_prohibited" => true,
                "ordinary_operational_use_prohibited" => true,
            ],
            "requested_disposition" =>
                "OPEN_EXACT_MANIFESTATION_BOUND_CONFIRMATION_CASE",
            "status" => "DELIVERED_PENDING_SENATE_ACCEPTANCE",
            "recipient_acceptance" => null,
            "senate_finding" => null,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function exactMissingConfirmationDefects(mixed $defects): bool
    {
        return [
            "MISSING_EXACT_SENATE_CONFIRMATION_ID",
            "MISSING_EXACT_SENATE_CONFIRMATION_DIGEST",
            "MISSING_EXACT_TESTED_MANIFESTATION_ID",
        ] === $defects;
    }
    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "admission_authority",
                "profile_approval_authority",
                "spawning_authority",
                "seat_binding_authority",
                "selection_authority",
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
            !is_dir($this->senateInbox) &&
            !mkdir($this->senateInbox, 0770, true) &&
            !is_dir($this->senateInbox)
        ) {
            throw new \RuntimeException(
                "Senate confirmation-request inbox cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->senateInbox . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F157_SENATE_CONFIRMATION_REQUEST_ABSENT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F158_SENATE_CONFIRMATION_REQUEST_REPLAY_CONFLICT",
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
                "Senate confirmation request cannot be committed atomically.",
            );
        }
        return $record;
    }
}
