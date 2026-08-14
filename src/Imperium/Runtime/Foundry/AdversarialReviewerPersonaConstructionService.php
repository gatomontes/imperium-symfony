<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerPersonaConstructionService
{
    private string $acceptanceDirectory;
    private string $candidateDirectory;
    private string $profilePath;
    private string $seatPath;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->acceptanceDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-construction-acceptances";
        $this->candidateDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates";
        $this->profilePath =
            $projectDir . "/offices/foundry/profile-reviewer-adversarial.md";
        $this->seatPath =
            $projectDir .
            "/offices/foundry/seat-demand-reviewer-adversarial.md";
    }

    public function construct(string $acceptanceId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-reviewer-construction-acceptance-[a-f0-9]{20}$/',
                $acceptanceId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F110_ADVERSARIAL_REVIEWER_ACCEPTANCE_ID_INVALID",
            );
        }
        $acceptance = $this->read(
            $this->acceptanceDirectory . "/" . $acceptanceId . ".json",
            "F111_ADVERSARIAL_REVIEWER_ACCEPTANCE_ABSENT",
        );
        if (
            !$this->digestMatches($acceptance) ||
            "imperium.foundry-adversarial-reviewer-construction-authorization-acceptance/v1" !==
                ($acceptance["schema"] ?? null) ||
            $acceptanceId !== ($acceptance["acceptance_id"] ?? null) ||
            "ACCEPTED_FOR_EXACT_ADVERSARIAL_REVIEWER_PERSONA_CONSTRUCTION" !==
                ($acceptance["disposition"] ?? null) ||
            true !== ($acceptance["recipient_acceptance"] ?? null) ||
            true !== ($acceptance["construction_authority"] ?? null) ||
            true !==
                ($acceptance["construction_authority_exercisable"] ?? null) ||
            "foundry.artificer" !== ($acceptance["actor"]["seat"] ?? null) ||
            "foundry.reviewer.adversarial" !==
                ($acceptance["target_seat"]["seat"] ?? null) ||
            $this->hasDownstreamAuthority($acceptance)
        ) {
            throw new \RuntimeException(
                "F112_ADVERSARIAL_REVIEWER_ACCEPTANCE_INVALID",
            );
        }

        $profile = $this->source(
            $this->profilePath,
            "offices/foundry/profile-reviewer-adversarial.md",
            "F113_ADVERSARIAL_REVIEWER_PROFILE_ABSENT",
        );
        $seat = $this->source(
            $this->seatPath,
            "offices/foundry/seat-demand-reviewer-adversarial.md",
            "F114_ADVERSARIAL_REVIEWER_SEAT_ABSENT",
        );
        if (($acceptance["profile_source"] ?? null) !== $profile["path"]) {
            throw new \RuntimeException(
                "F115_ADVERSARIAL_REVIEWER_PROFILE_CHANGED",
            );
        }
        $designBasis = [
            "name" => "Blackquill",
            "kind" => "persona-design-basis",
            "derivation_basis" =>
                "user-designated Blackquill critical-analysis contract",
            "method" => [
                "identify the exact claim or operative instruction under examination",
                "expose the weakest premise, contradiction, ambiguity, unsupported assumption, or incentive failure",
                "trace each defect to its practical consequence",
                "separate known fact, warranted inference, uncertainty, and speculation",
                "demand exact evidence, definitions, precedence, and boundary conditions",
                "test the strongest plausible hostile reading rather than a convenient caricature",
                "keep rhetoric subordinate to precise analysis",
            ],
            "identity_imported" => false,
            "institution_imported" => false,
            "authority_imported" => false,
        ];

        foreach (
            glob(
                $this->candidateDirectory .
                    "/adversarial-reviewer-persona-candidate-*.json",
            ) ?:
            []
            as $path
        ) {
            $existing = $this->read(
                $path,
                "F116_ADVERSARIAL_REVIEWER_CONSTRUCTION_REPLAY_CONFLICT",
            );
            if (
                $acceptanceId ===
                    ($existing["construction_acceptance_id"] ?? null) &&
                $this->digestMatches($existing)
            ) {
                return $existing;
            }
        }
        $persona = [
            "identity" => "Independent Adversarial Reviewer",
            "purpose" =>
                "Attack one exact completed immutable Persona Candidate for construction defects while occupying the demand-bound Foundry Adversarial Reviewer Seat.",
            "cognition" => [
                "trace definitions, priorities, permissions, prohibitions, obligations, escalation paths, and failure behavior",
                "expose contradictions, gaps, ambiguity, circularity, unreachable duties, authority leaks, and exploitable wording",
                "construct hostile readings and edge cases while separating hypothetical exploitation from manifested behavior",
                "separate known facts, warranted inferences, uncertainty, and speculation",
                "trace each defect to its practical consequence and state what evidence or revision would resolve it",
                "cite every finding to exact material in the reviewed candidate version",
            ],
            "behavior" => [
                "preserve independence from candidate authorship and repair",
                "attack the strongest plausible interpretation of the artifact",
                "report uncertainty and competing readings explicitly",
                "keep every finding attributable, reproducible, and version-bound",
                "fail closed when identity, integrity, provenance, scope, or independence is defective",
            ],
            "prohibitions" => [
                "do not rewrite, repair, or approve the reviewed candidate",
                "do not invent evidence, doctrine, professions, traits, or replacement substance",
                "do not instantiate the candidate or claim manifested-behavior results",
                "do not approve, admit, qualify, spawn, bind, credential, tool, or deploy",
                "do not generalize a disposition from one candidate identity, version, or digest to another",
            ],
            "return_contract" => [
                "issue an attributable adverse or clean review for only the exact occupied candidate scope",
                "return exact artifact references and stop on any failed boundary",
            ],
        ];
        $id =
            "adversarial-reviewer-persona-candidate-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $acceptanceId,
                        $acceptance["record_digest"],
                        "foundry.adversarial-reviewer",
                        "1.0.0",
                        $profile,
                        $seat,
                        $designBasis,
                        $persona,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-candidate/v1",
            "persona_candidate_id" => $id,
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "supersedes" => null,
            "instance_id" => $acceptance["instance_id"],
            "construction_acceptance_id" => $acceptanceId,
            "construction_acceptance_digest" => $acceptance["record_digest"],
            "authorization_act_id" => $acceptance["authorization_act_id"],
            "authorization_act_digest" =>
                $acceptance["authorization_act_digest"],
            "source_case_id" => $acceptance["source_case_id"],
            "source_case_digest" => $acceptance["source_case_digest"],
            "authorized_review_target" => [
                "candidate_id" => $acceptance["candidate_id"],
                "candidate_digest" => $acceptance["candidate_digest"],
            ],
            "artificer" => $acceptance["actor"],
            "template" => [
                "schema" => "imperium.persona/v1",
                "version" => "1.0.0",
            ],
            "sources" => [
                "profile" => $profile,
                "seat" => $seat,
                "design_basis" => $designBasis,
            ],
            "persona" => $persona,
            "status" => "SEALED_PENDING_FOUNDRY_REVIEW",
            "construction_complete" => true,
            "sealed" => true,
            "production_approval" => false,
            "review_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ]);
    }

    private function hasDownstreamAuthority(array $record): bool
    {
        foreach (
            [
                "commission_authority",
                "persona_selection_authority",
                "profile_approval_authority",
                "review_authority",
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
    private function source(
        string $path,
        string $relative,
        string $error,
    ): array {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        $content = (string) file_get_contents($path);
        return [
            "path" => $relative,
            "content_digest" => "sha256:" . hash("sha256", $content),
        ];
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
    private function persist(string $id, array $candidate): array
    {
        if (
            !is_dir($this->candidateDirectory) &&
            !mkdir($this->candidateDirectory, 0770, true) &&
            !is_dir($this->candidateDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry Adversarial Reviewer Persona candidate directory cannot be created.",
            );
        }
        $candidate["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($candidate),
        );
        $path = $this->candidateDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F116_ADVERSARIAL_REVIEWER_CONSTRUCTION_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($candidate)
            ) {
                throw new \RuntimeException(
                    "F116_ADVERSARIAL_REVIEWER_CONSTRUCTION_REPLAY_CONFLICT",
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
                        $candidate,
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
                "Adversarial Reviewer Persona candidate cannot be committed atomically.",
            );
        }
        return $candidate;
    }
}
