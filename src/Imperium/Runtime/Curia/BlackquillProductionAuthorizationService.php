<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillProductionAuthorizationService
{
    private string $requestDirectory;
    private string $caseDirectory;
    private string $inboxDirectory;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $this->requestDirectory =
            $p .
            "/var/imperium/curia/blackquill-production-authorization-requests";
        $this->caseDirectory =
            $p .
            "/var/imperium/offices/foundry/external-reviewer-production-cases";
        $this->inboxDirectory =
            $p .
            "/var/imperium/offices/foundry/inbox/blackquill-production-authorizations";
    }
    public function authorize(string $requestId): array
    {
        if (
            !preg_match(
                '/^blackquill-production-authorization-request-[a-f0-9]{20}$/',
                $requestId,
            )
        ) {
            throw new \InvalidArgumentException(
                "C121_BLACKQUILL_PRODUCTION_REQUEST_ID_INVALID",
            );
        }
        $r = $this->read(
            $this->requestDirectory . "/" . $requestId . ".json",
            "C122_BLACKQUILL_PRODUCTION_REQUEST_ABSENT",
        );
        $caseId = $r["source_case_id"] ?? null;
        $c = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "C123_BLACKQUILL_PRODUCTION_AUTHORIZATION_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->ok($r) ||
            !$this->ok($c) ||
            "imperium.curia-blackquill-production-authorization-request/v1" !==
                ($r["schema"] ?? null) ||
            $requestId !== ($r["request_id"] ?? null) ||
            "PENDING_IMPERATOR_DECISION" !== ($r["status"] ?? null) ||
            "imperator" !== ($r["recipient"]["kind"] ?? null) ||
            "imperator-development-root" !== ($r["recipient"]["id"] ?? null) ||
            "EXACT_BLACKQUILL_PERSONA_PRODUCTION_PROCESSING_ONLY" !==
                ($r["requested_authority"] ?? null) ||
            true === ($r["approval_recorded"] ?? null) ||
            true === ($r["production_authority"] ?? null) ||
            $this->downstream($r) ||
            "imperium.foundry-blackquill-production-remediation-case/v1" !==
                ($c["schema"] ?? null) ||
            ($r["source_case_digest"] ?? null) !==
                ($c["record_digest"] ?? null) ||
            ($r["instance_id"] ?? null) !== ($c["instance_id"] ?? null) ||
            CanonicalJson::encode($r["persona"] ?? null) !==
                CanonicalJson::encode($c["persona"] ?? null) ||
            "BLOCKED_PENDING_CURIA_PRODUCTION_AUTHORIZATION" !==
                ($c["status"] ?? null) ||
            true === ($c["production_authority"] ?? null) ||
            $this->downstream($c) ||
            true !== ($c["sealed"] ?? null)
        ) {
            throw new \RuntimeException(
                "C123_BLACKQUILL_PRODUCTION_AUTHORIZATION_CHAIN_INVALID",
            );
        }
        $actId =
            "blackquill-production-authorization-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $requestId,
                        $r["record_digest"],
                        $caseId,
                        $c["record_digest"],
                        "imperator-development-root",
                    ]),
                ),
                0,
                20,
            );
        $act = [
            "schema" =>
                "imperium.imperator-blackquill-production-authorization/v1",
            "kind" =>
                "EXACT_BLACKQUILL_PERSONA_PRODUCTION_PROCESSING_AUTHORIZATION",
            "act_id" => $actId,
            "instance_id" => $r["instance_id"],
            "actor" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "authority_basis" => "explicit-imperator-directive",
            "source_request_id" => $requestId,
            "source_request_digest" => $r["record_digest"],
            "source_case_id" => $caseId,
            "source_case_digest" => $c["record_digest"],
            "lineage" => $r["lineage"],
            "persona" => $r["persona"],
            "authorized_scope" => $r["requested_scope"],
            "disposition" =>
                "AUTHORIZED_FOR_EXACT_BLACKQUILL_PRODUCTION_PROCESSING",
            "production_authority" => true,
            "production_authority_exercisable" => false,
            "review_findings_authority" => false,
            "senate_confirmation_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ];
        $act["record_digest"] = hash("sha256", CanonicalJson::encode($act));
        $id =
            "blackquill-production-authorization-delivery-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $actId,
                        $act["record_digest"],
                        "foundry.artificer",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-blackquill-production-authorization-delivery/v1",
            "delivery_id" => $id,
            "office" => "foundry",
            "target" => "foundry.artificer",
            "instance_id" => $r["instance_id"],
            "source_case_id" => $caseId,
            "source_case_digest" => $c["record_digest"],
            "authorization_act_id" => $actId,
            "authorization_act_digest" => $act["record_digest"],
            "status" => "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
            "recipient_acceptance" => null,
            "production_authority" => true,
            "production_authority_exercisable" => false,
            "review_findings_authority" => false,
            "senate_confirmation_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "authorization_act" => $act,
        ]);
    }
    private function downstream(array $r): bool
    {
        foreach (
            [
                "review_findings_authority",
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
            !is_dir($this->inboxDirectory) &&
            !mkdir($this->inboxDirectory, 0770, true) &&
            !is_dir($this->inboxDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry Blackquill production-authorization inbox cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->inboxDirectory . "/" . $id . ".json";
        if (is_file($p)) {
            $o = $this->read(
                $p,
                "C124_BLACKQUILL_PRODUCTION_DELIVERY_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($o) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "C124_BLACKQUILL_PRODUCTION_DELIVERY_REPLAY_CONFLICT",
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
                "Blackquill production authorization cannot be delivered atomically.",
            );
        }
        return $r;
    }
}
