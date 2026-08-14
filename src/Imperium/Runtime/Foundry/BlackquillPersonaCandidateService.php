<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillPersonaCandidateService
{
    private string $acceptances;
    private string $candidates;
    private string $sourcePath;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $this->acceptances =
            $p .
            "/var/imperium/offices/foundry/blackquill-production-acceptances";
        $this->candidates =
            $p . "/var/imperium/offices/foundry/blackquill-persona-candidates";
        $this->sourcePath =
            $p . "/offices/foundry/personas/blackquill-adversarial-reviewer.md";
    }
    public function seal(string $acceptanceId): array
    {
        if (
            !preg_match(
                '/^foundry-blackquill-production-acceptance-[a-f0-9]{20}$/',
                $acceptanceId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F138_BLACKQUILL_PRODUCTION_ACCEPTANCE_ID_INVALID",
            );
        }
        $a = $this->read(
            $this->acceptances . "/" . $acceptanceId . ".json",
            "F139_BLACKQUILL_PRODUCTION_ACCEPTANCE_ABSENT",
        );
        if (
            !$this->ok($a) ||
            "imperium.foundry-blackquill-production-authorization-acceptance/v1" !==
                ($a["schema"] ?? null) ||
            $acceptanceId !== ($a["acceptance_id"] ?? null) ||
            "ACCEPTED_FOR_EXACT_BLACKQUILL_PRODUCTION_PROCESSING" !==
                ($a["disposition"] ?? null) ||
            true !== ($a["recipient_acceptance"] ?? null) ||
            true !== ($a["production_authority"] ?? null) ||
            true !== ($a["production_authority_exercisable"] ?? null) ||
            "foundry.artificer" !== ($a["actor"]["seat"] ?? null) ||
            "foundry.external.blackquill-adversarial-reviewer" !==
                ($a["persona"]["persona_id"] ?? null) ||
            "1.0.0" !== ($a["persona"]["persona_version"] ?? null) ||
            $this->downstream($a)
        ) {
            throw new \RuntimeException(
                "F140_BLACKQUILL_PRODUCTION_ACCEPTANCE_INVALID",
            );
        }
        if (!is_file($this->sourcePath)) {
            throw new \RuntimeException(
                "F141_BLACKQUILL_PERSONA_SOURCE_ABSENT",
            );
        }
        $content = (string) file_get_contents($this->sourcePath);
        $source = [
            "path" =>
                "offices/foundry/personas/blackquill-adversarial-reviewer.md",
            "content_digest" => "sha256:" . hash("sha256", $content),
            "derivation_basis" =>
                "user-designated Blackquill critical-analysis contract",
            "authority_imported" => false,
        ];
        if (
            "" === trim($content) ||
            CanonicalJson::encode($source) !==
                CanonicalJson::encode($a["persona"]["source"] ?? null)
        ) {
            throw new \RuntimeException(
                "F142_BLACKQUILL_PERSONA_SOURCE_CHANGED",
            );
        }
        foreach (
            glob($this->candidates . "/blackquill-persona-candidate-*.json") ?:
            []
            as $p
        ) {
            $old = $this->read($p, "F143_BLACKQUILL_CANDIDATE_REPLAY_CONFLICT");
            if (
                $acceptanceId === ($old["production_acceptance_id"] ?? null) &&
                $this->ok($old)
            ) {
                return $old;
            }
        }
        $id =
            "blackquill-persona-candidate-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $acceptanceId,
                        $a["record_digest"],
                        $source,
                        $content,
                        "1.0.0",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-blackquill-persona-candidate/v1",
            "persona_candidate_id" => $id,
            "persona_id" => "foundry.external.blackquill-adversarial-reviewer",
            "persona_version" => "1.0.0",
            "supersedes" => null,
            "instance_id" => $a["instance_id"],
            "production_acceptance_id" => $acceptanceId,
            "production_acceptance_digest" => $a["record_digest"],
            "authorization_act_id" => $a["authorization_act_id"],
            "authorization_act_digest" => $a["authorization_act_digest"],
            "source_case_id" => $a["source_case_id"],
            "source_case_digest" => $a["source_case_digest"],
            "artificer" => $a["actor"],
            "template" => [
                "schema" => "imperium.persona/v1",
                "version" => "1.0.0",
            ],
            "source" => $source,
            "persona_content" => $content,
            "status" => "SEALED_PENDING_FOUNDRY_REVIEW",
            "production_processing_complete" => true,
            "production_approval" => false,
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
            !is_dir($this->candidates) &&
            !mkdir($this->candidates, 0770, true) &&
            !is_dir($this->candidates)
        ) {
            throw new \RuntimeException(
                "Foundry Blackquill Persona candidate directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->candidates . "/" . $id . ".json";
        if (is_file($p)) {
            $o = $this->read($p, "F143_BLACKQUILL_CANDIDATE_REPLAY_CONFLICT");
            if (CanonicalJson::encode($o) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "F143_BLACKQUILL_CANDIDATE_REPLAY_CONFLICT",
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
                "Blackquill Persona candidate cannot be committed atomically.",
            );
        }
        return $r;
    }
}
