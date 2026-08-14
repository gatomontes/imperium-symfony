<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillProductionAuthorizationRequestService
{
    private string $caseDirectory;
    private string $requestDirectory;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $this->caseDirectory =
            $p .
            "/var/imperium/offices/foundry/external-reviewer-production-cases";
        $this->requestDirectory =
            $p .
            "/var/imperium/curia/blackquill-production-authorization-requests";
    }
    public function request(string $caseId): array
    {
        if (
            !preg_match(
                '/^blackquill-production-remediation-case-[a-f0-9]{20}$/',
                $caseId,
            )
        ) {
            throw new \InvalidArgumentException(
                "C117_BLACKQUILL_REMEDIATION_CASE_ID_INVALID",
            );
        }
        $c = $this->read(
            $this->caseDirectory . "/" . $caseId . ".json",
            "C118_BLACKQUILL_REMEDIATION_CASE_ABSENT",
        );
        if (
            !$this->ok($c) ||
            "imperium.foundry-blackquill-production-remediation-case/v1" !==
                ($c["schema"] ?? null) ||
            $caseId !== ($c["case_id"] ?? null) ||
            "BLOCKED_PENDING_CURIA_PRODUCTION_AUTHORIZATION" !==
                ($c["status"] ?? null) ||
            true !== ($c["remediation_open"] ?? null) ||
            false !== ($c["production_authority"] ?? null) ||
            $this->downstream($c) ||
            true !== ($c["sealed"] ?? null)
        ) {
            throw new \RuntimeException(
                "C119_BLACKQUILL_REMEDIATION_CASE_INVALID",
            );
        }
        $refs = [
            "source_disposition_id" => $c["source_disposition_id"],
            "source_disposition_digest" => $c["source_disposition_digest"],
            "source_intake_id" => $c["source_intake_id"],
            "source_intake_digest" => $c["source_intake_digest"],
            "target_review_case_id" => $c["target_review_case_id"],
            "target_review_case_digest" => $c["target_review_case_digest"],
        ];
        $id =
            "blackquill-production-authorization-request-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $caseId,
                        $c["record_digest"],
                        $refs,
                        "imperator-development-root",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.curia-blackquill-production-authorization-request/v1",
            "request_id" => $id,
            "instance_id" => $c["instance_id"],
            "requester" => ["office" => "curia", "seat" => "curia.seneschal"],
            "recipient" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "source_case_id" => $caseId,
            "source_case_digest" => $c["record_digest"],
            "lineage" => $refs,
            "persona" => $c["persona"],
            "requested_scope" => [
                "seal exact immutable Blackquill Persona candidate from fingerprinted repo source",
                "conduct production-review path required for later Foundry release consideration",
            ],
            "question" =>
                "Authorize Foundry production processing only for this exact Blackquill remediation case?",
            "requested_authority" =>
                "EXACT_BLACKQUILL_PERSONA_PRODUCTION_PROCESSING_ONLY",
            "status" => "PENDING_IMPERATOR_DECISION",
            "approval_recorded" => false,
            "production_authority" => false,
            "review_findings_authority" => false,
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
    private function downstream(array $r): bool
    {
        foreach (
            [
                "review_authority",
                "senate_confirmation_authority",
                "release_authority",
                "admission_authority",
                "spawning_authority",
                "seat_binding_authority",
                "candidate_approval_authority",
                "execution_authority",
            ]
            as $k
        ) {
            if (true === ($r[$k] ?? false)) {
                return true;
            }
        }
        return false;
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
    private function ok(array $r): bool
    {
        $d = $r["record_digest"] ?? null;
        unset($r["record_digest"]);
        return is_string($d) &&
            hash_equals($d, hash("sha256", CanonicalJson::encode($r)));
    }
    private function persist(string $id, array $r): array
    {
        if (
            !is_dir($this->requestDirectory) &&
            !mkdir($this->requestDirectory, 0770, true) &&
            !is_dir($this->requestDirectory)
        ) {
            throw new \RuntimeException(
                "Curia Blackquill production-request directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->requestDirectory . "/" . $id . ".json";
        if (is_file($p)) {
            $o = $this->read(
                $p,
                "C120_BLACKQUILL_PRODUCTION_REQUEST_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($o) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "C120_BLACKQUILL_PRODUCTION_REQUEST_REPLAY_CONFLICT",
                );
            }
            return $o;
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
                "Blackquill production authorization request cannot be committed atomically.",
            );
        }
        return $r;
    }
}
