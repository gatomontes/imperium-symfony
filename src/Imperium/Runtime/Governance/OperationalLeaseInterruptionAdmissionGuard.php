<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalLeaseInterruptionAdmissionGuard
{
    private const string RESULTS = 'var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results';
    private const string AUTHORITIES = 'var/imperium/runtime/operational-cognition-lease-interruption-enforcement-authorities';

    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function assertMayCreateClaim(array $lease): void
    {
        foreach (glob($this->root.'/'.self::RESULTS.'/*.json') ?: [] as $path) {
            $result = $this->validator->read($path, 'OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
            if (($result['affected_scope']['lease']['id'] ?? null) !== ($lease['lease_id'] ?? null)) {
                continue;
            }
            $authorityRef = $result['source_authority'] ?? [];
            $authorityId = $authorityRef['id'] ?? null;
            if (!$this->validator->isIntact($result)
                || 'imperium.operational-cognition-lease-interruption-enforcement-result/v1' !== ($result['schema'] ?? null)
                || !is_string($authorityId)
                || ($result['affected_scope']['lease']['digest'] ?? null) !== ($lease['record_digest'] ?? null)
                || 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE' !== ($result['performed_transition'] ?? null)
                || true !== ($result['authority_consumed'] ?? null)
                || false !== ($result['claim_created'] ?? null)
                || false !== ($result['cognition_authority_consumed'] ?? null)
                || false !== ($result['lease_consumed'] ?? null)
                || false !== ($result['lease_mutated'] ?? null)
                || false !== ($result['lease_closed'] ?? null)
                || false !== ($result['credential_resolved'] ?? null)
                || false !== ($result['provider_invoked'] ?? null)
                || false !== ($result['provider_journal_created'] ?? null)
                || false !== ($result['network_access_performed'] ?? null)
                || false !== ($result['propagation_performed'] ?? null)
                || false !== ($result['continuation_authority'] ?? null)) {
                throw new \RuntimeException('OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
            }
            $authority = $this->validator->read($this->root.'/'.self::AUTHORITIES.'/'.$authorityId.'.json', 'OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
            if (!$this->validator->isIntact($authority)
                || ($authorityRef['digest'] ?? null) !== ($authority['record_digest'] ?? null)
                || ($authority['source_disposition'] ?? null) !== ($result['source_disposition'] ?? null)
                || ($authority['affected_scope'] ?? null) !== ($result['affected_scope'] ?? null)
                || ($authority['enforcer'] ?? null) !== ($result['enforcer'] ?? null)
                || 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE' !== ($authority['permitted_transition'] ?? null)) {
                throw new \RuntimeException('OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
            }

            throw new \RuntimeException('OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
        }
    }
}
