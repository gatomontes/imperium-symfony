<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutorPrincipalActivationService
{
    public const string ACTIVATIONS =
        'var/imperium/runtime/provider-executor-principal-activations';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private ProviderExecutorPrincipalActivationContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->validator = new ProviderExecutorPrincipalActivationContractValidator();
    }

    public function activate(
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertDecision(
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );
        if ('AUTHORIZED' !== $decision['disposition']) {
            throw new \RuntimeException('PPB100_PRINCIPAL_ACTIVATION_NOT_AUTHORIZED');
        }

        $principal = $attestation['principal'];
        $target = implode(':', [
            $decision['instance_id'],
            $principal['principal_id'],
            (string) $principal['generation'],
            $principal['process_boundary_id'],
        ]);

        return $this->atomic->run(
            'principal-activation:'.hash('sha256', $target),
            function () use ($decision, $attestation, $assurance, $boundary, $at, $target): array {
                $activation = $this->seal([
                    'schema' => ProviderExecutorPrincipalActivationContract::SCHEMA,
                    'principal_activation_id' =>
                        'principal-activation-'.hash('sha256', $target),
                    'instance_id' => $decision['instance_id'],
                    'source_decision' => $this->reference($decision, 'decision_id'),
                    'consumed_activation_authority' => [
                        'id' => $decision['activation_authority']['authority_id'],
                        'digest' => $decision['record_digest'],
                        'schema' => $decision['schema'],
                        'consumed_at' => $at->format(DATE_ATOM),
                        'consumed' => true,
                        'continuing_authority' => false,
                    ],
                    'provider_assurance_admission' =>
                        $this->reference($assurance, 'admission_id'),
                    'execution_boundary' =>
                        $this->reference($boundary, 'boundary_id'),
                    'principal_attestation' =>
                        $this->reference($attestation, 'principal_attestation_id'),
                    'principal' => $attestation['principal'],
                    'scope' => [
                        'provider_id' => $decision['scope']['provider_id'],
                        'operation' => $decision['scope']['operation'],
                        'same_process_execution_required' => true,
                        'provider_substitution_permitted' => false,
                        'operation_substitution_permitted' => false,
                        'principal_generation_substitution_permitted' => false,
                    ],
                    'validity' => $decision['validity'],
                    'reconstruction' => [
                        'read_only' => true,
                        'exact_replay_only' => true,
                        'reactivation_permitted' => false,
                        'generation_upgrade_permitted' => false,
                    ],
                    'status' => 'ACTIVE',
                    'activated_at' => $at->format(DATE_ATOM),
                    'sealed' => true,
                ]);

                $this->validator->assertActivation(
                    $activation,
                    $decision,
                    $attestation,
                    $assurance,
                    $boundary,
                    $at,
                );

                return $this->records->put(
                    self::ACTIVATIONS,
                    $activation['principal_activation_id'],
                    $activation,
                );
            },
        );
    }

    public function reconstruct(
        string $activationId,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $activation = $this->records->read(self::ACTIVATIONS, $activationId);
        $this->validator->assertActivation(
            $activation,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );

        return $activation;
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
