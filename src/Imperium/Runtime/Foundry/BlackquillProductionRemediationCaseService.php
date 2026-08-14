<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillProductionRemediationCaseService
{
    private string $dispositionDirectory;
    private string $intakeDirectory;
    private string $caseDirectory;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->dispositionDirectory =
            $projectDir .
            "/var/imperium/offices/garrison/admission-dispositions";
        $this->intakeDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/external-reviewer-intakes";
        $this->caseDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/external-reviewer-production-cases";
    }
    public function open(string $dispositionId): array
    {
        if (
            !preg_match(
                '/^garrison-external-reviewer-admission-disposition-[a-f0-9]{20}$/',
                $dispositionId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F127_EXTERNAL_ADMISSION_DISPOSITION_ID_INVALID",
            );
        }
        $d = $this->read(
            $this->dispositionDirectory . "/" . $dispositionId . ".json",
            "F128_EXTERNAL_ADMISSION_DISPOSITION_ABSENT",
        );
        $intakeId = $d["source_intake_id"] ?? null;
        $i = is_string($intakeId)
            ? $this->read(
                $this->intakeDirectory . "/" . $intakeId . ".json",
                "F129_EXTERNAL_REVIEWER_REMEDIATION_CHAIN_INVALID",
            )
            : [];
        $required = [
            "production-approved immutable Persona artifact",
            "Foundry release packet for the exact Persona version",
            "passing Senate confirmation record for the exact manifested candidate",
            "authorized admission handoff correlation identity",
        ];
        if (
            !$this->digestMatches($d) ||
            !$this->digestMatches($i) ||
            "imperium.garrison-external-reviewer-admission-disposition/v1" !==
                ($d["schema"] ?? null) ||
            $dispositionId !== ($d["disposition_id"] ?? null) ||
            "REFUSED_INCOMPLETE_ADMISSION_CHAIN" !==
                ($d["disposition"] ?? null) ||
            "SEALED_RETURN_TO_FOUNDRY" !== ($d["status"] ?? null) ||
            "foundry.artificer" !== ($d["return_recipient"] ?? null) ||
            true === ($d["admitted"] ?? null) ||
            true === ($d["custody_record_created"] ?? null) ||
            true !== ($d["sealed"] ?? null) ||
            CanonicalJson::encode($required) !==
                CanonicalJson::encode(
                    $d["evaluation"]["missing_evidence"] ?? null,
                ) ||
            "imperium.foundry-external-reviewer-persona-intake/v1" !==
                ($i["schema"] ?? null) ||
            ($d["source_intake_digest"] ?? null) !==
                ($i["record_digest"] ?? null) ||
            ($d["instance_id"] ?? null) !== ($i["instance_id"] ?? null) ||
            CanonicalJson::encode($d["persona"] ?? null) !==
                CanonicalJson::encode($i["external_persona"] ?? null) ||
            true !== ($i["source_validated"] ?? null) ||
            true === ($i["eligible_for_review_occupation"] ?? null)
        ) {
            throw new \RuntimeException(
                "F129_EXTERNAL_REVIEWER_REMEDIATION_CHAIN_INVALID",
            );
        }
        foreach (
            glob(
                $this->caseDirectory .
                    "/blackquill-production-remediation-case-*.json",
            ) ?:
            []
            as $p
        ) {
            $old = $this->read(
                $p,
                "F130_BLACKQUILL_REMEDIATION_REPLAY_CONFLICT",
            );
            if (
                $dispositionId === ($old["source_disposition_id"] ?? null) &&
                $this->digestMatches($old)
            ) {
                return $old;
            }
        }
        $id =
            "blackquill-production-remediation-case-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $dispositionId,
                        $d["record_digest"],
                        $intakeId,
                        $i["record_digest"],
                        $required,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-blackquill-production-remediation-case/v1",
            "case_id" => $id,
            "instance_id" => $d["instance_id"],
            "source_disposition_id" => $dispositionId,
            "source_disposition_digest" => $d["record_digest"],
            "source_intake_id" => $intakeId,
            "source_intake_digest" => $i["record_digest"],
            "persona" => $i["external_persona"],
            "target_review_case_id" => $i["target_review_case_id"],
            "target_review_case_digest" => $i["target_review_case_digest"],
            "garrison_findings" => $d["evaluation"],
            "required_production_path" => [
                "Curia authorizes exact Blackquill Persona production review",
                "Foundry seals an immutable candidate without importing skill authority",
                "an independent reviewer returns an exact-version review",
                "Foundry separately renders production approval and release",
                "Senate confirms the exact examination manifestation",
                "Curia supplies an authorized admission handoff to Garrison",
            ],
            "status" => "BLOCKED_PENDING_CURIA_PRODUCTION_AUTHORIZATION",
            "remediation_open" => true,
            "production_authority" => false,
            "review_authority" => false,
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
            !is_dir($this->caseDirectory) &&
            !mkdir($this->caseDirectory, 0770, true) &&
            !is_dir($this->caseDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry Blackquill remediation directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->caseDirectory . "/" . $id . ".json";
        if (is_file($p)) {
            $old = $this->read(
                $p,
                "F130_BLACKQUILL_REMEDIATION_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "F130_BLACKQUILL_REMEDIATION_REPLAY_CONFLICT",
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
                "Blackquill remediation case cannot be committed atomically.",
            );
        }
        return $r;
    }
}
