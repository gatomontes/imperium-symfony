<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBindingActivationRevocationWinnerService
{
    public const string WINNERS =
        'var/imperium/offices/la-cortine/provider-binding-activation-revocation-winners';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private RecordReferenceValidator $references;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->references = new RecordReferenceValidator($root);
    }

    public function revoke(
        string $authorityId,
        string $reasonCode,
        \DateTimeImmutable $at,
    ): array {
        if (!preg_match(
            '/^provider-binding-activation-revocation-authority-[a-z0-9]{1,80}$/',
            $authorityId,
        )) {
            throw new \InvalidArgumentException('PEB770_REVOCATION_AUTHORITY_ID_INVALID');
        }
        $authority = $this->references->read(
            $this->root.'/'.ProviderBindingActivationRevocationAuthorityIssuanceService::AUTHORITIES
                .'/'.$authorityId.'.json',
            'PEB771_REVOCATION_AUTHORITY_ABSENT',
        );
        $activation = $this->references->resolve(
            $this->root.'/'.SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            $authority['provider_binding_activation'] ?? [],
            'PEB772_PROVIDER_ACTIVATION_ABSENT',
            'PEB773_PROVIDER_ACTIVATION_MISMATCH',
            'activation_id',
        );
        $activationId = $activation['activation_id'];
        $activationDigest = $activation['record_digest'];
        $winnerId = ProviderBindingActivationRevocationWinnerContract::ID_PREFIX
            .substr(hash('sha256', $activationId.'|'.$activationDigest), 0, 20);
        $admissionId = 'governed-provider-execution-combined-admission-'.substr(
            hash('sha256', $activationId.'|'.$activationDigest),
            0,
            20,
        );

        return $this->atomic->run(
            ProviderBindingActivationRevocationWinnerContract::LOCK_SCOPE_PREFIX.$activationId,
            function () use (
                $authority,
                $authorityId,
                $reasonCode,
                $activation,
                $activationId,
                $activationDigest,
                $winnerId,
                $admissionId,
                $at,
            ): array {
                try {
                    $existing = $this->records->read(self::WINNERS, $winnerId);
                    $this->assertExisting(
                        $existing,
                        $authority,
                        $reasonCode,
                        $activation,
                        $winnerId,
                    );

                    return $existing;
                } catch (\RuntimeException $exception) {
                    if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $exception->getMessage()) {
                        throw $exception;
                    }
                }

                try {
                    $this->records->read(
                        GovernedProviderExecutionCombinedAdmissionService::ADMISSIONS,
                        $admissionId,
                    );
                    throw new \RuntimeException('PEB774_COMBINED_ADMISSION_ALREADY_WON');
                } catch (\RuntimeException $exception) {
                    if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $exception->getMessage()) {
                        throw $exception;
                    }
                }

                $this->assertAuthority(
                    $authority,
                    $authorityId,
                    $reasonCode,
                    $activation,
                    $at,
                );
                $winner = [
                    'schema' => ProviderBindingActivationRevocationWinnerContract::SCHEMA,
                    'winner_id' => $winnerId,
                    'instance_id' => $authority['instance_id'],
                    'provider_binding_activation' => $authority['provider_binding_activation'],
                    'revocation_authority' => [
                        'id' => $authorityId,
                        'digest' => $authority['record_digest'],
                        'schema' => $authority['schema'],
                    ],
                    'revocation_authority_consumption' => [
                        'authority_id' => $authorityId,
                        'authority_digest' => $authority['record_digest'],
                        'single_use' => true,
                        'consumed' => true,
                        'continuing_authority' => false,
                    ],
                    'reason_code' => $reasonCode,
                    'winner_scope' => ProviderBindingActivationRevocationWinnerContract
                        ::WINNER_SCOPE_PREFIX.$activationId,
                    'revoked_at' => $at->format(DATE_ATOM),
                    'sealed' => true,
                ];
                $winner['record_digest'] = hash(
                    'sha256',
                    CanonicalJson::encode($winner),
                );
                $this->assertWinner($winner, $authority, $activation, $winnerId);

                return $this->records->put(self::WINNERS, $winnerId, $winner);
            },
        );
    }

    private function assertAuthority(
        array $authority,
        string $authorityId,
        string $reasonCode,
        array $activation,
        \DateTimeImmutable $at,
    ): void {
        if (!$this->intact(
            $authority,
            ProviderBindingActivationRevocationAuthorityContract::REQUIRED_FIELDS,
            ProviderBindingActivationRevocationAuthorityContract::SCHEMA,
        )
            || $authorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || new \DateTimeImmutable($authority['validity']['effective_at']) > $at
            || new \DateTimeImmutable($authority['validity']['expires_at']) <= $at
            || null !== ($authority['validity']['revocation_reference'] ?? null)
            || !in_array($reasonCode, $authority['allowed_reason_codes'] ?? [], true)
            || $authority['instance_id'] !== $activation['instance_id']
            || $authority['execution_boundary'] !== $activation['execution_boundary']
            || $authority['executor_principal'] !== $activation['executor_principal']
            || $authority['provider_binding'] !== $activation['provider_binding']) {
            throw new \RuntimeException('PEB775_REVOCATION_AUTHORITY_INVALID');
        }
    }

    private function assertWinner(
        array $winner,
        array $authority,
        array $activation,
        string $winnerId,
    ): void {
        if (ProviderBindingActivationRevocationWinnerContract::REQUIRED_FIELDS
                !== array_keys($winner)
            || ProviderBindingActivationRevocationWinnerContract::SCHEMA
                !== $winner['schema']
            || ProviderBindingActivationRevocationWinnerContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($winner['provider_binding_activation'])
            || ProviderBindingActivationRevocationWinnerContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($winner['revocation_authority'])
            || ProviderBindingActivationRevocationWinnerContract::REQUIRED_CONSUMPTION_FIELDS
                !== array_keys($winner['revocation_authority_consumption'])
            || $winnerId !== $winner['winner_id']
            || $authority['provider_binding_activation']
                !== $winner['provider_binding_activation']
            || $activation['record_digest']
                !== $winner['provider_binding_activation']['digest']
            || true !== $winner['revocation_authority_consumption']['single_use']
            || true !== $winner['revocation_authority_consumption']['consumed']
            || false !== $winner['revocation_authority_consumption']['continuing_authority']) {
            throw new \RuntimeException('PEB776_REVOCATION_WINNER_INVALID');
        }
    }

    private function assertExisting(
        array $winner,
        array $authority,
        string $reasonCode,
        array $activation,
        string $winnerId,
    ): void {
        if (!$this->intact(
            $winner,
            ProviderBindingActivationRevocationWinnerContract::REQUIRED_FIELDS,
            ProviderBindingActivationRevocationWinnerContract::SCHEMA,
        )
            || $winnerId !== ($winner['winner_id'] ?? null)
            || $authority['authority_id'] !== ($winner['revocation_authority']['id'] ?? null)
            || $authority['record_digest']
                !== ($winner['revocation_authority']['digest'] ?? null)
            || $activation['record_digest']
                !== ($winner['provider_binding_activation']['digest'] ?? null)
            || $reasonCode !== ($winner['reason_code'] ?? null)
            || true !== ($winner['revocation_authority_consumption']['consumed'] ?? null)) {
            throw new \RuntimeException('PEB777_REVOCATION_WINNER_CONFLICT');
        }
    }

    private function intact(array $record, array $fields, string $schema): bool
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);

        return $fields === array_keys($record)
            && $schema === ($record['schema'] ?? null)
            && true === ($record['sealed'] ?? null)
            && is_string($digest)
            && hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)));
    }
}
