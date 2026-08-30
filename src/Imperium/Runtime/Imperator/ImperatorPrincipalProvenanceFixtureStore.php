<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImperatorPrincipalProvenanceFixtureStore
{
    public const string CONSTITUTION_AUTHORITIES = 'var/imperium/evidence/imperator-principal-provenance/constitution-authorities';
    public const string PRINCIPAL_VERSIONS = 'var/imperium/evidence/imperator-principal-provenance/principal-versions';
    public const string LIFECYCLE_DISPOSITIONS = 'var/imperium/evidence/imperator-principal-provenance/lifecycle-dispositions';
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function putConstitutionAuthority(array $fixture): array
    {
        $this->assertConstitutionAuthority($fixture);
        return $this->records->put(self::CONSTITUTION_AUTHORITIES, $fixture['authority_id'], $fixture);
    }

    public function assertConstitutionAuthority(array $fixture): void
    {
        $this->common($fixture, ImperatorPrincipalConstitutionAuthorityContract::REQUIRED_FIELDS, ImperatorPrincipalConstitutionAuthorityContract::SCHEMA, 'PPV100_CONSTITUTION_AUTHORITY_INVALID');
        $route = $fixture['route'] ?? null;
        $transition = $fixture['permitted_transition'] ?? null;
        $expectedTransition = 'FUTURE_INSTANCE_ROOT_ESTABLISHMENT' === $route ? 'CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL' : 'REMEDIATE_MISSING_IMPERATOR_PRINCIPAL';
        if (!in_array($route, ImperatorPrincipalConstitutionAuthorityContract::ROUTES, true)
            || $expectedTransition !== $transition
            || !$this->operatorRoot($fixture['operator_root'] ?? null)
            || !$this->reference($fixture['operationalization'] ?? null)
            || !$this->identity($fixture['imperator_identity'] ?? null)
            || !$this->target($fixture['target_principal'] ?? null)
            || !$this->scope($fixture['scope'] ?? null)
            || true !== ($fixture['authority_single_use'] ?? null)
            || true !== ($fixture['authority_exercisable'] ?? null)
            || false !== ($fixture['consumed'] ?? null)
            || false !== ($fixture['continuing_authority'] ?? null)
            || !$this->identifier($fixture['authority_id'] ?? null)
            || !$this->identifier($fixture['instance_id'] ?? null)
            || !$this->timeWindow($fixture['issued_at'] ?? null, $fixture['expires_at'] ?? null, 15)) {
            throw new \RuntimeException('PPV100_CONSTITUTION_AUTHORITY_INVALID');
        }
    }

    public function putPrincipalVersion(array $fixture): array
    {
        $this->assertPrincipalVersion($fixture);
        return $this->records->put(self::PRINCIPAL_VERSIONS, $fixture['principal_version_id'], $fixture);
    }

    public function assertPrincipalVersion(array $fixture): void
    {
        $this->common($fixture, ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionContract::SCHEMA, 'PPV110_PRINCIPAL_VERSION_INVALID');
        $lifecycle = $fixture['lifecycle'] ?? null;
        if (!$this->identifier($fixture['principal_version_id'] ?? null)
            || !$this->identifier($fixture['principal_id'] ?? null)
            || !$this->identifier($fixture['instance_id'] ?? null)
            || !$this->identifier($fixture['binding_id'] ?? null)
            || !is_int($fixture['principal_generation'] ?? null) || $fixture['principal_generation'] < 1
            || !in_array($fixture['constitution_route'] ?? null, ImperatorPrincipalConstitutionAuthorityContract::ROUTES, true)
            || !$this->reference($fixture['source_constitution_authority'] ?? null)
            || !$this->reference($fixture['source_operator_root'] ?? null)
            || !$this->identity($fixture['identity'] ?? null)
            || !$this->scope($fixture['authority_scope'] ?? null)
            || !$this->exact($lifecycle, ImperatorRuntimePrincipalVersionContract::REQUIRED_LIFECYCLE_FIELDS)
            || !in_array($fixture['status'] ?? null, ImperatorRuntimePrincipalVersionContract::STATUSES, true)
            || false !== ($fixture['credential_reference_persisted'] ?? null)
            || false !== ($fixture['credential_secret_persisted'] ?? null)
            || false !== ($fixture['serialized_capability_persisted'] ?? null)
            || !$this->lifecycle($lifecycle, $fixture['principal_generation'])) {
            throw new \RuntimeException('PPV110_PRINCIPAL_VERSION_INVALID');
        }
    }

    public function putLifecycleDisposition(array $fixture): array
    {
        $this->assertLifecycleDisposition($fixture);
        return $this->records->put(self::LIFECYCLE_DISPOSITIONS, $fixture['disposition_id'], $fixture);
    }

    public function assertLifecycleDisposition(array $fixture): void
    {
        $this->common($fixture, ImperatorPrincipalLifecycleDispositionContract::REQUIRED_FIELDS, ImperatorPrincipalLifecycleDispositionContract::SCHEMA, 'PPV120_LIFECYCLE_DISPOSITION_INVALID');
        $disposition = $fixture['disposition'] ?? null;
        $sourceStatus = $fixture['source_status'] ?? null;
        $successor = $fixture['successor_principal_version'] ?? null;
        $successorRequired = in_array($disposition, ['RENEW', 'SUPERSEDE'], true);
        $issuancePermitted = in_array($disposition, ['ACTIVATE', 'RENEW'], true);
        if (!$this->identifier($fixture['disposition_id'] ?? null)
            || !$this->identifier($fixture['instance_id'] ?? null)
            || !$this->reference($fixture['operator_root'] ?? null)
            || !$this->reference($fixture['source_principal_version'] ?? null)
            || !in_array($sourceStatus, ImperatorRuntimePrincipalVersionContract::STATUSES, true)
            || !in_array($disposition, ImperatorPrincipalLifecycleDispositionContract::DISPOSITIONS, true)
            || !$this->sourceStatusAllows($sourceStatus, $disposition)
            || !is_string($fixture['rationale'] ?? null) || '' === trim($fixture['rationale'])
            || !$this->date($fixture['effective_at'] ?? null)
            || ($successorRequired ? !$this->reference($successor) : null !== $successor)
            || false !== ($fixture['authority_scope_changed'] ?? null)
            || true !== ($fixture['historical_attribution_preserved'] ?? null)
            || $issuancePermitted !== ($fixture['caller_authority_issuance_permitted_after_effective_at'] ?? null)
            || false !== ($fixture['external_action_performed'] ?? null)) {
            throw new \RuntimeException('PPV120_LIFECYCLE_DISPOSITION_INVALID');
        }
    }

    private function common(array $fixture, array $fields, string $schema, string $failure): void
    {
        $digest = $fixture['record_digest'] ?? null;
        $unsealed = $fixture;
        unset($unsealed['record_digest']);
        if ($fields !== array_keys($fixture) || $schema !== ($fixture['schema'] ?? null) || true !== ($fixture['sealed'] ?? null)
            || !is_string($digest) || !hash_equals($digest, hash('sha256', CanonicalJson::encode($unsealed)))) {
            throw new \RuntimeException($failure);
        }
    }

    private function scope(mixed $scope): bool
    {
        return $this->exact($scope, ImperatorPrincipalConstitutionAuthorityContract::REQUIRED_SCOPE_FIELDS)
            && true === $scope['provider_binding_activation_authority']
            && false === $scope['outbound_email_authority']
            && false === $scope['credential_authority']
            && false === $scope['provider_execution_authority']
            && false === $scope['corridor_disposition_authority'];
    }

    private function operatorRoot(mixed $operator): bool
    {
        return $this->exact($operator, ImperatorPrincipalConstitutionAuthorityContract::REQUIRED_OPERATOR_ROOT_FIELDS)
            && $this->identifier($operator['operator_id']) && $this->digest($operator['source_identity_digest'])
            && $this->identifier($operator['decision_id']) && $this->digest($operator['decision_digest']);
    }

    private function identity(mixed $identity): bool
    {
        return $this->exact($identity, ImperatorRuntimePrincipalVersionContract::REQUIRED_IDENTITY_FIELDS)
            && $this->identifier($identity['operator_id']) && $this->digest($identity['operator_identity_digest'])
            && $this->identifier($identity['imperator_subject_id']) && $this->digest($identity['imperator_subject_digest']);
    }

    private function target(mixed $target): bool
    {
        return $this->exact($target, ImperatorPrincipalConstitutionAuthorityContract::REQUIRED_TARGET_FIELDS)
            && $this->identifier($target['principal_id']) && $this->identifier($target['binding_id'])
            && is_int($target['generation']) && $target['generation'] > 0;
    }

    private function lifecycle(mixed $lifecycle, int $generation): bool
    {
        if (!is_array($lifecycle) || !$this->date($lifecycle['constituted_at'] ?? null) || !$this->date($lifecycle['effective_at'] ?? null) || !$this->date($lifecycle['expires_at'] ?? null)) return false;
        $constituted = new \DateTimeImmutable($lifecycle['constituted_at']);
        $effective = new \DateTimeImmutable($lifecycle['effective_at']);
        $expires = new \DateTimeImmutable($lifecycle['expires_at']);
        return $constituted <= $effective && $effective < $expires
            && (1 === $generation ? null === $lifecycle['prior_version'] : $this->reference($lifecycle['prior_version']))
            && (null === $lifecycle['superseding_version'] || $this->reference($lifecycle['superseding_version']))
            && (null === $lifecycle['current_disposition'] || $this->reference($lifecycle['current_disposition']));
    }

    private function sourceStatusAllows(string $status, string $disposition): bool
    {
        return match ($disposition) {
            'ACTIVATE' => 'PENDING_ACTIVATION' === $status,
            'RENEW', 'SUSPEND' => 'ACTIVE' === $status,
            'SUPERSEDE' => in_array($status, ['ACTIVE', 'SUSPENDED'], true),
            'REVOKE', 'EXPIRE' => in_array($status, ['PENDING_ACTIVATION', 'ACTIVE', 'SUSPENDED'], true),
            'RETIRE' => in_array($status, ['PENDING_ACTIVATION', 'ACTIVE', 'SUSPENDED', 'SUPERSEDED', 'REVOKED', 'EXPIRED'], true),
            default => false,
        };
    }

    private function timeWindow(mixed $start, mixed $end, int $minutes): bool
    {
        if (!$this->date($start) || !$this->date($end)) return false;
        $issued = new \DateTimeImmutable($start);
        $expires = new \DateTimeImmutable($end);
        return $issued < $expires && $expires <= $issued->modify('+'.$minutes.' minutes');
    }

    private function reference(mixed $value): bool
    {
        return $this->exact($value, ImperatorPrincipalConstitutionAuthorityContract::REQUIRED_REFERENCE_FIELDS)
            && $this->identifier($value['id']) && $this->digest($value['digest']) && $this->identifier($value['schema']);
    }

    private function exact(mixed $value, array $fields): bool
    {
        return is_array($value) && $fields === array_keys($value);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function date(mixed $value): bool
    {
        if (!is_string($value)) return false;
        $date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value);
        return false !== $date && $date->format(DATE_ATOM) === $value;
    }
}
