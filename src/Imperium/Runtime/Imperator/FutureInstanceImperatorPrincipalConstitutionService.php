<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class FutureInstanceImperatorPrincipalConstitutionService
{
    public const string PRINCIPAL_VERSIONS = 'var/imperium/runtime/imperator-principal-versions';
    private const string CONSUMER = 'mastermason.future-instance-imperator-principal-constitution';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;
    private RecordReferenceValidator $validator;
    private ImperatorPrincipalProvenanceFixtureStore $contracts;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic);
        $this->validator = new RecordReferenceValidator($root);
        $this->contracts = new ImperatorPrincipalProvenanceFixtureStore($root);
    }

    public function constitute(string $authorityId, \DateTimeImmutable $constitutedAt): array
    {
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:-]{2,220}$/', $authorityId)) throw new \InvalidArgumentException('PPR400_CONSTITUTION_AUTHORITY_ID_INVALID');
        return $this->atomic->run('imperator-principal-constitution:'.hash('sha256', $authorityId), fn (): array => $this->constituteLocked($authorityId, $constitutedAt));
    }

    private function constituteLocked(string $authorityId, \DateTimeImmutable $at): array
    {
        $path = $this->root.'/'.ImperatorPrincipalProvenanceFixtureStore::CONSTITUTION_AUTHORITIES.'/'.$authorityId.'.json';
        $authority = $this->validator->requireIntact($this->validator->read($path, 'PPR401_CONSTITUTION_AUTHORITY_ABSENT'), 'PPR402_CONSTITUTION_AUTHORITY_INVALID');
        try { $this->contracts->assertConstitutionAuthority($authority); } catch (\RuntimeException) { throw new \RuntimeException('PPR402_CONSTITUTION_AUTHORITY_INVALID'); }
        $target = $authority['target_principal'];
        if ('FUTURE_INSTANCE_ROOT_ESTABLISHMENT' !== $authority['route'] || 'CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL' !== $authority['permitted_transition'] || 1 !== $target['generation']) throw new \RuntimeException('PPR403_FUTURE_INSTANCE_ROUTE_REQUIRED');
        if (is_file($this->root.'/var/imperium/operator-root/operationalization-seal.json')) throw new \RuntimeException('PPR404_OPERATIONALIZATION_ALREADY_SEALED');
        if (new \DateTimeImmutable($authority['issued_at']) > $at || new \DateTimeImmutable($authority['expires_at']) <= $at) throw new \RuntimeException('PPR405_CONSTITUTION_AUTHORITY_EXPIRED');
        $versionId = 'imperator-principal-version-'.substr(hash('sha256', CanonicalJson::encode([$authorityId, $authority['record_digest'], $target])), 0, 20);
        $record = ['schema' => ImperatorRuntimePrincipalVersionContract::SCHEMA, 'principal_version_id' => $versionId, 'principal_id' => $target['principal_id'], 'instance_id' => $authority['instance_id'], 'binding_id' => $target['binding_id'], 'principal_generation' => 1, 'constitution_route' => $authority['route'], 'source_constitution_authority' => ['id' => $authorityId, 'digest' => $authority['record_digest'], 'schema' => $authority['schema']], 'source_operator_root' => ['id' => $authority['operator_root']['operator_id'], 'digest' => $authority['operator_root']['source_identity_digest'], 'schema' => 'imperium.operator-root-identity/v1'], 'identity' => $authority['imperator_identity'], 'authority_scope' => $authority['scope'], 'lifecycle' => ['constituted_at' => $at->format(DATE_ATOM), 'effective_at' => $at->format(DATE_ATOM), 'expires_at' => $authority['expires_at'], 'prior_version' => null, 'superseding_version' => null, 'current_disposition' => null], 'status' => 'PENDING_ACTIVATION', 'credential_reference_persisted' => false, 'credential_secret_persisted' => false, 'serialized_capability_persisted' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $this->contracts->assertPrincipalVersion($record);
        $this->consumptions->consume($authorityId, $authorityId, $authority['record_digest'], self::CONSUMER, $at);
        return $this->records->put(self::PRINCIPAL_VERSIONS, $versionId, $record);
    }
}
