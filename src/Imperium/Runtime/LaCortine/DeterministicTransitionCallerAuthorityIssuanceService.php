<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicTransitionCallerAuthorityIssuanceService
{
    public const string AUTHORITIES = 'var/imperium/runtime-principals/deterministic-transition-caller-authorities';
    public const string IMPERATOR_PRINCIPALS = 'var/imperium/imperator/runtime-principals';
    private const string CURIA_OCCUPANCY = 'var/imperium/offices/curia/occupancy';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function issueSeneschal(string $bindingId, array $target, \DateTimeImmutable $issuedAt, \DateTimeImmutable $expiresAt): array
    {
        $source = $this->validator->read($this->root.'/'.self::CURIA_OCCUPANCY.'/'.$bindingId.'.json', 'IGA100_SENESCHAL_PRINCIPAL_ABSENT');
        if (!$this->validator->isIntact($source) || 'imperium.curia-seneschal-occupancy/v1' !== ($source['schema'] ?? null) || 'ACTIVE' !== ($source['status'] ?? null) || true !== ($source['outbound_email_request_authority'] ?? null)) throw new \RuntimeException('IGA101_SENESCHAL_PRINCIPAL_INVALID');
        return $this->issue($source, ['principal_id' => $source['manifestation_id'], 'office' => 'curia', 'seat' => 'curia.seneschal', 'binding_id' => $bindingId, 'generation' => $source['occupancy_generation']], 'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $target, $issuedAt, $expiresAt);
    }

    public function issueImperator(string $principalId, string $transition, array $target, \DateTimeImmutable $issuedAt, \DateTimeImmutable $expiresAt): array
    {
        if (!in_array($transition, ['DECIDE_EXACT_OUTBOUND_EMAIL_REQUEST', 'ISSUE_EXACT_OUTBOUND_EMAIL_AUTHORIZATION'], true)) throw new \InvalidArgumentException('IGA102_IMPERATOR_TRANSITION_INVALID');
        $source = $this->validator->read($this->root.'/'.self::IMPERATOR_PRINCIPALS.'/'.$principalId.'.json', 'IGA103_IMPERATOR_PRINCIPAL_ABSENT');
        if (!$this->validator->isIntact($source) || 'imperium.imperator-runtime-principal/v1' !== ($source['schema'] ?? null) || 'ACTIVE' !== ($source['status'] ?? null) || true !== ($source['outbound_email_authority'] ?? null)) throw new \RuntimeException('IGA104_IMPERATOR_PRINCIPAL_INVALID');
        return $this->issue($source, ['principal_id' => $principalId, 'office' => 'imperator', 'seat' => 'imperator', 'binding_id' => $source['binding_id'], 'generation' => $source['principal_generation']], $transition, $target, $issuedAt, $expiresAt);
    }

    private function issue(array $source, array $principal, string $transition, array $target, \DateTimeImmutable $issuedAt, \DateTimeImmutable $expiresAt): array
    {
        if (DeterministicTransitionCallerAuthorityContract::REQUIRED_REFERENCE_FIELDS !== array_keys($target) || !is_string($target['id']) || '' === trim($target['id']) || !preg_match('/^[a-f0-9]{64}$/', (string) $target['digest']) || $expiresAt <= $issuedAt || $expiresAt > $issuedAt->modify('+15 minutes')) throw new \InvalidArgumentException('IGA105_CALLER_AUTHORITY_INPUT_INVALID');
        $sourceId = (string) ($source['binding_id'] ?? $source['principal_id'] ?? '');
        $authorityId = 'deterministic-transition-caller-authority-'.substr(hash('sha256', CanonicalJson::encode([$principal, $sourceId, $source['record_digest'], $transition, $target, $issuedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)])), 0, 20);
        $record = ['schema' => DeterministicTransitionCallerAuthorityContract::SCHEMA, 'authority_id' => $authorityId, 'instance_id' => $source['instance_id'], 'principal' => $principal, 'source' => ['id' => $sourceId, 'digest' => $source['record_digest']], 'permitted_transition' => $transition, 'target' => $target, 'authority_single_use' => true, 'authority_exercisable' => true, 'issued_at' => $issuedAt->format(DATE_ATOM), 'expires_at' => $expiresAt->format(DATE_ATOM), 'consumed' => false, 'continuing_authority' => false, 'sealed' => true];
        return $this->atomic->run('iron-gate-caller-authority:'.$authorityId, fn (): array => $this->records->put(self::AUTHORITIES, $authorityId, $record));
    }
}
