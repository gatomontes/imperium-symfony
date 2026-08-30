<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class ProviderExecutionBoundaryRedesignIssuanceContractValidator
{
    public function assertDecision(array $decision, string $contract, \DateTimeImmutable $at): void
    {
        $this->contract($contract);
        $this->common(
            $decision,
            $contract::REQUIRED_DECISION_FIELDS,
            $contract::DECISION_SCHEMA,
            'PEB200_SOURCE_DECISION_INVALID',
        );

        if (!$this->identifier($decision['decision_id'] ?? null)
            || !$this->identifier($decision['instance_id'] ?? null)
            || !$this->reference($decision['source_authority'] ?? null, $contract)
            || !$this->exact($decision['actor'] ?? null, $contract::REQUIRED_ACTOR_FIELDS)
            || !$this->principal($decision['actor'])
            || !$this->exact($decision['target'] ?? null, $contract::REQUIRED_TARGET_FIELDS)
            || $contract::TARGET_KIND !== ($decision['target']['kind'] ?? null)
            || !$this->identifier($decision['target']['id'] ?? null)
            || !$this->digest($decision['target']['digest'] ?? null)
            || !$this->identifier($decision['target']['schema'] ?? null)
            || !$this->exact($decision['basis'] ?? null, $contract::REQUIRED_BASIS_FIELDS)
            || !$this->references($decision['basis'], $contract)
            || !in_array($decision['disposition'] ?? null, $contract::DISPOSITIONS, true)
            || !is_string($decision['rationale'] ?? null)
            || '' === trim($decision['rationale'])
            || !is_string($decision['limitations'] ?? null)
            || '' === trim($decision['limitations'])
            || !$this->activeWindow($decision['decided_at'] ?? null, $decision['expires_at'] ?? null, $at)
            || false !== ($decision['external_action_performed'] ?? null)) {
            throw new \RuntimeException('PEB200_SOURCE_DECISION_INVALID');
        }

        if ('AUTHORIZED' === $decision['disposition']) {
            $this->issuanceAuthority($decision['issuance_authority'] ?? null, $decision, $contract);
        } elseif (null !== ($decision['issuance_authority'] ?? null)) {
            throw new \RuntimeException('PEB200_SOURCE_DECISION_INVALID');
        }
    }

    public function assertIssuance(array $issuance, array $decision, string $contract): void
    {
        $this->contract($contract);
        $this->common(
            $decision,
            $contract::REQUIRED_DECISION_FIELDS,
            $contract::DECISION_SCHEMA,
            'PEB210_SOURCE_DECISION_INVALID',
        );
        $this->common(
            $issuance,
            $contract::REQUIRED_ISSUANCE_FIELDS,
            $contract::ISSUANCE_SCHEMA,
            'PEB211_ISSUANCE_INVALID',
        );

        if ('AUTHORIZED' !== ($decision['disposition'] ?? null)
            || !$this->identifier($issuance['issuance_id'] ?? null)
            || ($issuance['instance_id'] ?? null) !== ($decision['instance_id'] ?? null)
            || !$this->matches($issuance['source_decision'] ?? null, $decision, 'decision_id', $contract)
            || !$this->consumedAuthority($issuance['consumed_issuance_authority'] ?? null, $contract)
            || ($issuance['consumed_issuance_authority']['id'] ?? null)
                !== ($decision['issuance_authority']['authority_id'] ?? null)
            || ($issuance['consumed_issuance_authority']['digest'] ?? null)
                !== ($decision['record_digest'] ?? null)
            || !$this->reference($issuance['issued_artifact'] ?? null, $contract)
            || !$this->exact($issuance['issuer'] ?? null, $contract::REQUIRED_ACTOR_FIELDS)
            || !$this->principal($issuance['issuer'])
            || !$this->date($issuance['issued_at'] ?? null)
            || true !== ($decision['issuance_authority']['authority_single_use'] ?? null)
            || true !== ($decision['issuance_authority']['authority_exercisable'] ?? null)
            || false !== ($decision['issuance_authority']['consumed'] ?? null)
            || false !== ($decision['issuance_authority']['continuing_authority'] ?? null)
            || true !== ($issuance['consumed_issuance_authority']['consumed'] ?? null)
            || false !== ($issuance['consumed_issuance_authority']['continuing_authority'] ?? null)
            || false !== ($issuance['principal_installed'] ?? null)
            || false !== ($issuance['provider_binding_activated'] ?? null)
            || false !== ($issuance['credential_capability_issued'] ?? null)
            || false !== ($issuance['credential_resolved'] ?? null)
            || false !== ($issuance['external_action_performed'] ?? null)) {
            throw new \RuntimeException('PEB211_ISSUANCE_INVALID');
        }
    }

    private function issuanceAuthority(mixed $authority, array $decision, string $contract): void
    {
        if (!$this->exact($authority, $contract::REQUIRED_ISSUANCE_AUTHORITY_FIELDS)
            || !$this->identifier($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || !$this->identifier($authority['issuer_service'] ?? null)
            || $contract::PERMITTED_TRANSITION !== ($authority['permitted_transition'] ?? null)
            || ($decision['target']['digest'] ?? null) !== ($authority['target_digest'] ?? null)
            || ($decision['expires_at'] ?? null) !== ($authority['expires_at'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)) {
            throw new \RuntimeException('PEB200_SOURCE_DECISION_INVALID');
        }
    }

    private function common(
        array $record,
        array $fields,
        string $schema,
        string $failure,
    ): void {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);

        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($failure);
        }
    }

    private function references(array $basis, string $contract): bool
    {
        foreach ($basis as $reference) {
            if (!$this->reference($reference, $contract)) {
                return false;
            }
        }

        return true;
    }

    private function reference(mixed $reference, string $contract): bool
    {
        return $this->exact($reference, $contract::REQUIRED_REFERENCE_FIELDS)
            && $this->identifier($reference['id'] ?? null)
            && $this->digest($reference['digest'] ?? null)
            && $this->identifier($reference['schema'] ?? null);
    }

    private function consumedAuthority(mixed $authority, string $contract): bool
    {
        return $this->exact($authority, $contract::REQUIRED_CONSUMED_AUTHORITY_FIELDS)
            && $this->identifier($authority['id'] ?? null)
            && $this->digest($authority['digest'] ?? null)
            && $this->identifier($authority['schema'] ?? null)
            && $this->date($authority['consumed_at'] ?? null)
            && true === ($authority['consumed'] ?? null)
            && false === ($authority['continuing_authority'] ?? null);
    }

    private function matches(
        mixed $reference,
        array $record,
        string $idField,
        string $contract,
    ): bool {
        return $this->reference($reference, $contract)
            && ($reference['id'] ?? null) === ($record[$idField] ?? null)
            && ($reference['digest'] ?? null) === ($record['record_digest'] ?? null)
            && ($reference['schema'] ?? null) === ($record['schema'] ?? null);
    }

    private function principal(array $principal): bool
    {
        return $this->identifier($principal['principal_id'] ?? null)
            && $this->identifier($principal['office'] ?? null)
            && $this->identifier($principal['seat'] ?? null)
            && $this->identifier($principal['binding_id'] ?? null)
            && is_int($principal['generation'] ?? null)
            && $principal['generation'] > 0;
    }

    private function activeWindow(mixed $start, mixed $end, \DateTimeImmutable $at): bool
    {
        if (!$this->date($start) || !$this->date($end)) {
            return false;
        }

        $effective = new \DateTimeImmutable($start);
        $expires = new \DateTimeImmutable($end);

        return $effective <= $at && $at < $expires && $expires <= $effective->modify('+15 minutes');
    }

    private function contract(string $contract): void
    {
        if (!class_exists($contract)
            || !defined($contract.'::DECISION_SCHEMA')
            || !defined($contract.'::ISSUANCE_SCHEMA')
            || !defined($contract.'::PERMITTED_TRANSITION')) {
            throw new \InvalidArgumentException('PEB100_ISSUANCE_CONTRACT_UNSUPPORTED');
        }
    }

    private function exact(mixed $value, array $fields): bool
    {
        return is_array($value) && $fields === array_keys($value);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function date(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value);

        return false !== $date && $date->format(DATE_ATOM) === $value;
    }
}
