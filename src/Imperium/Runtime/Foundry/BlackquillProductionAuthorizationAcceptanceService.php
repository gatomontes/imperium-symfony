<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillProductionAuthorizationAcceptanceService
{
    private string $inbox;
    private string $cases;
    private string $occupancy;
    private string $acceptances;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $b = $p . "/var/imperium/offices/foundry";
        $this->inbox = $b . "/inbox/blackquill-production-authorizations";
        $this->cases = $b . "/external-reviewer-production-cases";
        $this->occupancy = $b . "/occupancy";
        $this->acceptances = $b . "/blackquill-production-acceptances";
    }
    public function accept(string $deliveryId, string $bindingId): array
    {
        if (
            !preg_match(
                '/^blackquill-production-authorization-delivery-[a-f0-9]{20}$/',
                $deliveryId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F131_BLACKQUILL_PRODUCTION_DELIVERY_ID_INVALID",
            );
        }
        if (
            !preg_match(
                '/^foundry-artificer-binding-[a-f0-9]{20}$/',
                $bindingId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F132_ARTIFICER_BINDING_ID_INVALID",
            );
        }
        $d = $this->read(
            $this->inbox . "/" . $deliveryId . ".json",
            "F133_BLACKQUILL_PRODUCTION_DELIVERY_ABSENT",
        );
        $a = $d["authorization_act"] ?? null;
        $caseId = $d["source_case_id"] ?? null;
        $c = is_string($caseId)
            ? $this->read(
                $this->cases . "/" . $caseId . ".json",
                "F134_BLACKQUILL_PRODUCTION_ACCEPTANCE_CHAIN_INVALID",
            )
            : [];
        if (
            !is_array($a) ||
            !$this->ok($d) ||
            !$this->ok($a) ||
            !$this->ok($c) ||
            "imperium.foundry-blackquill-production-authorization-delivery/v1" !==
                ($d["schema"] ?? null) ||
            $deliveryId !== ($d["delivery_id"] ?? null) ||
            "foundry.artificer" !== ($d["target"] ?? null) ||
            "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE" !== ($d["status"] ?? null) ||
            null !== ($d["recipient_acceptance"] ?? null) ||
            true !== ($d["production_authority"] ?? null) ||
            false !== ($d["production_authority_exercisable"] ?? null) ||
            $this->downstream($d) ||
            ($d["authorization_act_id"] ?? null) !== ($a["act_id"] ?? null) ||
            ($d["authorization_act_digest"] ?? null) !==
                ($a["record_digest"] ?? null) ||
            "imperium.imperator-blackquill-production-authorization/v1" !==
                ($a["schema"] ?? null) ||
            "AUTHORIZED_FOR_EXACT_BLACKQUILL_PRODUCTION_PROCESSING" !==
                ($a["disposition"] ?? null) ||
            true !== ($a["production_authority"] ?? null) ||
            false !== ($a["production_authority_exercisable"] ?? null) ||
            $this->downstream($a) ||
            "imperium.foundry-blackquill-production-remediation-case/v1" !==
                ($c["schema"] ?? null) ||
            ($d["source_case_digest"] ?? null) !==
                ($c["record_digest"] ?? null) ||
            ($a["source_case_id"] ?? null) !== $caseId ||
            ($a["source_case_digest"] ?? null) !==
                ($c["record_digest"] ?? null) ||
            ($d["instance_id"] ?? null) !== ($c["instance_id"] ?? null) ||
            CanonicalJson::encode($a["persona"] ?? null) !==
                CanonicalJson::encode($c["persona"] ?? null) ||
            "BLOCKED_PENDING_CURIA_PRODUCTION_AUTHORIZATION" !==
                ($c["status"] ?? null) ||
            true === ($c["production_authority"] ?? null) ||
            $this->downstream($c) ||
            true !== ($c["sealed"] ?? null)
        ) {
            throw new \RuntimeException(
                "F134_BLACKQUILL_PRODUCTION_ACCEPTANCE_CHAIN_INVALID",
            );
        }
        $b = $this->read(
            $this->occupancy . "/" . $bindingId . ".json",
            "F135_ARTIFICER_BINDING_ABSENT",
        );
        if (
            !$this->ok($b) ||
            "imperium.foundry-artificer-occupancy/v1" !==
                ($b["schema"] ?? null) ||
            "foundry.artificer" !== ($b["seat"] ?? null) ||
            "ACTIVE" !== ($b["status"] ?? null) ||
            true !== ($b["binding_atomic"] ?? null) ||
            ($d["instance_id"] ?? null) !== ($b["instance_id"] ?? null) ||
            true === ($b["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("F136_ARTIFICER_BINDING_INVALID");
        }
        $id =
            "foundry-blackquill-production-acceptance-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $deliveryId,
                        $d["record_digest"],
                        $bindingId,
                        $b["record_digest"],
                        $caseId,
                        $c["record_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-blackquill-production-authorization-acceptance/v1",
            "acceptance_id" => $id,
            "instance_id" => $d["instance_id"],
            "delivery_id" => $deliveryId,
            "delivery_digest" => $d["record_digest"],
            "authorization_act_id" => $a["act_id"],
            "authorization_act_digest" => $a["record_digest"],
            "source_case_id" => $caseId,
            "source_case_digest" => $c["record_digest"],
            "persona" => $c["persona"],
            "binding_id" => $bindingId,
            "binding_digest" => $b["record_digest"],
            "actor" => [
                "seat" => "foundry.artificer",
                "manifestation_id" => $b["manifestation_id"],
                "occupancy_generation" => $b["occupancy_generation"],
            ],
            "disposition" =>
                "ACCEPTED_FOR_EXACT_BLACKQUILL_PRODUCTION_PROCESSING",
            "recipient_acceptance" => true,
            "production_authority" => true,
            "production_authority_exercisable" => true,
            "review_findings_authority" => false,
            "senate_confirmation_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
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
            !is_dir($this->acceptances) &&
            !mkdir($this->acceptances, 0770, true) &&
            !is_dir($this->acceptances)
        ) {
            throw new \RuntimeException(
                "Foundry Blackquill production-acceptance directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->acceptances . "/" . $id . ".json";
        if (is_file($p)) {
            $o = $this->read(
                $p,
                "F137_BLACKQUILL_PRODUCTION_ACCEPTANCE_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($o) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "F137_BLACKQUILL_PRODUCTION_ACCEPTANCE_REPLAY_CONFLICT",
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
                "Blackquill production acceptance cannot be committed atomically.",
            );
        }
        return $r;
    }
}
