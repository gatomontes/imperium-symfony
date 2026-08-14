<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Garrison;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class ExternalReviewerAdmissionDispositionService
{
    private string $intakeDirectory;
    private string $occupancyDirectory;
    private string $dispositionDirectory;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->intakeDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/external-reviewer-intakes";
        $this->occupancyDirectory =
            $projectDir . "/var/imperium/offices/garrison/occupancy";
        $this->dispositionDirectory =
            $projectDir .
            "/var/imperium/offices/garrison/admission-dispositions";
    }
    public function evaluate(string $intakeId): array
    {
        if (
            !preg_match(
                '/^blackquill-external-reviewer-intake-[a-f0-9]{20}$/',
                $intakeId,
            )
        ) {
            throw new \InvalidArgumentException(
                "GA70_EXTERNAL_REVIEWER_INTAKE_ID_INVALID",
            );
        }
        $intake = $this->read(
            $this->intakeDirectory . "/" . $intakeId . ".json",
            "GA71_EXTERNAL_REVIEWER_INTAKE_ABSENT",
        );
        if (
            !$this->digestMatches($intake) ||
            "imperium.foundry-external-reviewer-persona-intake/v1" !==
                ($intake["schema"] ?? null) ||
            $intakeId !== ($intake["intake_id"] ?? null) ||
            "SEALED_PENDING_GARRISON_ADMISSION_EVIDENCE" !==
                ($intake["status"] ?? null) ||
            true !== ($intake["source_validated"] ?? null) ||
            "UNVERIFIED" !== ($intake["admission_claim_state"] ?? null) ||
            true === ($intake["eligible_for_review_occupation"] ?? null) ||
            true === ($intake["admission_authority"] ?? null) ||
            true !== ($intake["sealed"] ?? null)
        ) {
            throw new \RuntimeException(
                "GA72_EXTERNAL_REVIEWER_INTAKE_INVALID",
            );
        }
        $occupancy = $this->currentConstable($intake["instance_id"] ?? null);
        $missing = [
            "production-approved immutable Persona artifact",
            "Foundry release packet for the exact Persona version",
            "passing Senate confirmation record for the exact manifested candidate",
            "authorized admission handoff correlation identity",
        ];
        $id =
            "garrison-external-reviewer-admission-disposition-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $intakeId,
                        $intake["record_digest"],
                        $occupancy["record_digest"],
                        $missing,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.garrison-external-reviewer-admission-disposition/v1",
            "disposition_id" => $id,
            "instance_id" => $intake["instance_id"],
            "source_intake_id" => $intakeId,
            "source_intake_digest" => $intake["record_digest"],
            "persona" => $intake["external_persona"],
            "target_review_case_id" => $intake["target_review_case_id"],
            "constable" => [
                "seat" => "garrison.constable",
                "manifestation_id" => $occupancy["manifestation_id"],
                "occupancy_generation" => $occupancy["occupancy_generation"],
                "occupancy_digest" => $occupancy["record_digest"],
            ],
            "evaluation" => [
                "identity_and_source_integrity" => "VALID",
                "admission_chain" => "INCOMPLETE",
                "missing_evidence" => $missing,
            ],
            "disposition" => "REFUSED_INCOMPLETE_ADMISSION_CHAIN",
            "status" => "SEALED_RETURN_TO_FOUNDRY",
            "return_recipient" => "foundry.artificer",
            "admitted" => false,
            "custody_record_created" => false,
            "eligible_for_review_occupation" => false,
            "review_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority_consumed" => true,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }
    private function currentConstable(mixed $instanceId): array
    {
        $paths =
            glob(
                $this->occupancyDirectory .
                    "/garrison-constable-binding-*.json",
            ) ?:
            [];
        if (1 !== count($paths)) {
            throw new \RuntimeException("GA73_CONSTABLE_OCCUPANCY_REQUIRED");
        }
        $r = $this->read($paths[0], "GA73_CONSTABLE_OCCUPANCY_REQUIRED");
        if (
            !$this->digestMatches($r) ||
            "imperium.garrison-constable-occupancy/v1" !==
                ($r["schema"] ?? null) ||
            $instanceId !== ($r["instance_id"] ?? null) ||
            "garrison.constable" !== ($r["seat"] ?? null) ||
            "ACTIVE" !== ($r["status"] ?? null) ||
            true !== ($r["persona_admission_disposition_authority"] ?? null) ||
            true === ($r["selection_authority"] ?? null) ||
            true === ($r["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "GA74_CONSTABLE_ADMISSION_AUTHORITY_INVALID",
            );
        }
        return $r;
    }
    private function read(string $p, string $e): array
    {
        if (!is_file($p)) {
            throw new \RuntimeException($e);
        }
        return json_decode(
            (string) file_get_contents($p),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
    private function digestMatches(array $r): bool
    {
        $d = $r["record_digest"] ?? null;
        unset($r["record_digest"]);
        return is_string($d) &&
            hash_equals($d, hash("sha256", CanonicalJson::encode($r)));
    }
    private function persist(string $id, array $r): array
    {
        if (
            !is_dir($this->dispositionDirectory) &&
            !mkdir($this->dispositionDirectory, 0770, true) &&
            !is_dir($this->dispositionDirectory)
        ) {
            throw new \RuntimeException(
                "Garrison admission-disposition directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->dispositionDirectory . "/" . $id . ".json";
        if (is_file($p)) {
            $old = $this->read(
                $p,
                "GA75_ADMISSION_DISPOSITION_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "GA75_ADMISSION_DISPOSITION_REPLAY_CONFLICT",
                );
            }
            return $old;
        }
        $t = $p . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $t,
                    json_encode(
                        $r,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($t, $p)
        ) {
            @unlink($t);
            throw new \RuntimeException(
                "Garrison admission disposition cannot be committed atomically.",
            );
        }
        return $r;
    }
}
