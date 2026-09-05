<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Reconstructs authority only from the exact persisted Mission Authorization approval chain. */
final readonly class AuthenticatedMissionAuthorizationBridge
{
    private const string COMPETENT_OPERATOR_KIND = 'imperator';
    private const string COMPETENT_OPERATOR_ID = 'imperator-development-root';
    private string $authorizations;
    private string $dossiers;
    private string $reviews;
    private string $root;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->root = $root;
        $this->authorizations = $root.'/var/imperium/authorizations/missions';
        $this->dossiers = $root.'/var/imperium/offices/curia/planning-dossiers';
        $this->reviews = $root.'/var/imperium/offices/curia/planning-dossier-reviews';
    }

    public function authenticate(string $authorizationId, \DateTimeImmutable $at): AuthenticatedMissionAuthorization
    {
        if (1 !== preg_match('/^mission-authorization-[a-f0-9]{20}$/', $authorizationId)) {
            throw new \RuntimeException('MIS401_MISSION_AUTHORIZATION_ID_INVALID');
        }
        $authorization = $this->read($this->authorizations.'/'.$authorizationId.'.json');
        $source = $authorization['authority_source'] ?? [];
        $dossierId = $source['dossier']['id'] ?? '';
        $reviewId = $source['imperator_review']['id'] ?? '';
        $dossier = $this->read($this->dossiers.'/'.$dossierId.'.json');
        $review = $this->read($this->reviews.'/'.$reviewId.'.json');

        if (!$this->valid($authorization) || !$this->valid($dossier) || !$this->valid($review)) {
            throw new \RuntimeException('MIS402_MISSION_AUTHORIZATION_TAMPERED');
        }
        $mission = CanonicalMissionPlan::fromMissionPlan($authorization['mission_plan']);
        $derivation = $authorization['derivation_authority'] ?? [];
        $reviewDerivation = $review['mission_authorization_derivation_authority'] ?? [];
        if ('imperium.mission-authorization/v1' !== ($authorization['schema'] ?? null)
            || $authorizationId !== ($authorization['authorization_id'] ?? null)
            || 'MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION' !== ($authorization['status'] ?? null)
            || true !== ($authorization['sealed'] ?? null)
            || true !== ($authorization['direct_execution_prohibited'] ?? null)
            || true !== ($authorization['silent_scope_expansion_prohibited'] ?? null)
            || true !== ($derivation['consumed'] ?? null)
            || false !== ($derivation['continuing_authority'] ?? null)
            || false !== ($authorization['execution_authority'] ?? null)
            || 'imperium.curia-planning-dossier/v1' !== ($dossier['schema'] ?? null)
            || $dossierId !== ($dossier['dossier_id'] ?? null)
            || ($source['dossier']['version'] ?? null) !== ($dossier['dossier_version'] ?? null)
            || ($source['dossier']['digest'] ?? null) !== ($dossier['record_digest'] ?? null)
            || CanonicalJson::encode($authorization['mission_plan'] ?? null) !== CanonicalJson::encode($dossier['mission_plan'] ?? null)
            || CanonicalJson::encode($authorization['authorized_dossier_lines'] ?? null) !== CanonicalJson::encode($dossier['lines'] ?? null)
            || 'imperium.imperator-planning-dossier-review/v1' !== ($review['schema'] ?? null)
            || $reviewId !== ($review['review_id'] ?? null)
            || ($source['imperator_review']['digest'] ?? null) !== ($review['record_digest'] ?? null)
            || ['kind' => self::COMPETENT_OPERATOR_KIND, 'id' => self::COMPETENT_OPERATOR_ID] !== ($review['actor'] ?? null)
            || 'APPROVE_DOSSIER' !== ($review['disposition'] ?? null)
            || true !== ($review['dossier_approval'] ?? null)
            || true !== ($review['all_lines_acknowledged'] ?? null)
            || 'IMPERATOR_PLANNING_DOSSIER_APPROVED_PENDING_MISSION_AUTHORIZATION' !== ($review['status'] ?? null)
            || ($review['dossier']['id'] ?? null) !== $dossierId
            || ($review['dossier']['version'] ?? null) !== ($dossier['dossier_version'] ?? null)
            || ($review['dossier']['digest'] ?? null) !== ($dossier['record_digest'] ?? null)
            || ($reviewDerivation['authority_id'] ?? null) !== ($source['derivation_authority_id'] ?? null)
            || true !== ($reviewDerivation['authority_single_use'] ?? null)
            || true !== ($reviewDerivation['derivation_authority'] ?? null)
            || false !== ($reviewDerivation['execution_authority'] ?? null)
            || ($derivation['id'] ?? null) !== ($source['derivation_authority_id'] ?? null)) {
            throw new \RuntimeException('MIS403_MISSION_AUTHORIZATION_LINEAGE_INVALID');
        }

        (new OperatorApprovalAuthenticator($this->root))->verify($review, $dossier, $mission);

        try {
            $reviewedAt = new \DateTimeImmutable($review['reviewed_at']);
            $derivedAt = new \DateTimeImmutable($authorization['derived_at']);
        } catch (\Throwable) {
            throw new \RuntimeException('MIS404_MISSION_AUTHORIZATION_TIME_INVALID');
        }
        if ($reviewedAt > $derivedAt || $derivedAt > $at || $at >= $mission->expiresAt()) {
            throw new \RuntimeException('MIS404_MISSION_AUTHORIZATION_TIME_INVALID');
        }
        foreach (['revoked', 'superseded', 'expired'] as $closed) {
            if (true === ($authorization[$closed] ?? false)) {
                throw new \RuntimeException('MIS405_MISSION_AUTHORIZATION_INACTIVE');
            }
        }

        return new AuthenticatedMissionAuthorization(
            $authorizationId,
            $authorization['record_digest'],
            $dossierId,
            $dossier['dossier_version'],
            $dossier['record_digest'],
            $reviewId,
            $review['record_digest'],
            $review['actor']['id'],
            $review['reviewed_at'],
            $mission,
        );
    }

    private function read(string $path): array
    {
        if (!is_file($path)) { throw new \RuntimeException('MIS406_MISSION_AUTHORIZATION_RECORD_ABSENT'); }
        try {
            return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new \RuntimeException('MIS402_MISSION_AUTHORIZATION_TAMPERED');
        }
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }
}
