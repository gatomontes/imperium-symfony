<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutorPrincipalCanonicalActivationService
{
    private AtomicTransition $atomic;
    private ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor $reconstructor;
    private ProviderExecutorPrincipalActivationService $activation;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->reconstructor =
            new ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor($root);
        $this->activation = new ProviderExecutorPrincipalActivationService($root);
    }

    public function activateCanonical(
        string $admissionId,
        string $inputId,
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $observed = $this->reconstruct(
            $admissionId,
            $inputId,
            $production,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );
        $this->assertReady($observed);

        $rootId = $observed['chain']['replay_contention_root']['root_id'];

        return $this->atomic->run(
            'canonical-principal-activation:'.hash('sha256', $rootId),
            function () use (
                $admissionId,
                $inputId,
                $production,
                $decision,
                $attestation,
                $assurance,
                $boundary,
                $at,
                $observed,
            ): array {
                $winning = $this->reconstruct(
                    $admissionId,
                    $inputId,
                    $production,
                    $decision,
                    $attestation,
                    $assurance,
                    $boundary,
                    $at,
                );
                $this->assertReady($winning);
                if (!hash_equals($observed['proof_digest'], $winning['proof_digest'])) {
                    throw new \RuntimeException('PRA401_CANONICAL_ACTIVATION_PROOF_CHANGED');
                }
                $this->assertExactTarget($winning, $decision, $attestation);

                return $this->activation->activate(
                    $decision,
                    $attestation,
                    $assurance,
                    $boundary,
                    $at,
                );
            },
        );
    }

    private function reconstruct(
        string $admissionId,
        string $inputId,
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        return $this->reconstructor->reconstruct(
            $admissionId,
            $inputId,
            $production,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );
    }

    private function assertReady(array $proof): void
    {
        if ('READY_OFFLINE_ACTIVATION_INPUT' !== ($proof['classification'] ?? null)
            || true !== ($proof['read_only'] ?? null)
            || !is_string($proof['proof_digest'] ?? null)
            || !is_array($proof['chain']['activation_target'] ?? null)
            || !is_array($proof['chain']['activation_authority'] ?? null)
            || !is_array($proof['chain']['replay_contention_root'] ?? null)) {
            throw new \RuntimeException('PRA400_CANONICAL_ACTIVATION_NOT_READY');
        }
    }

    private function assertExactTarget(array $proof, array $decision, array $attestation): void
    {
        $target = $proof['chain']['activation_target'];
        $authority = $proof['chain']['activation_authority'];
        $root = $proof['chain']['replay_contention_root'];
        $principal = $attestation['principal'] ?? [];
        $scope = $decision['scope'] ?? [];
        $sourceAuthority = $decision['activation_authority'] ?? [];

        if ($target['principal_id'] !== ($principal['principal_id'] ?? null)
            || $target['binding_id'] !== ($principal['binding_id'] ?? null)
            || $target['generation'] !== ($principal['generation'] ?? null)
            || $target['process_boundary_id'] !== ($principal['process_boundary_id'] ?? null)
            || $target['principal_id'] !== ($scope['principal_id'] ?? null)
            || $target['generation'] !== ($scope['principal_generation'] ?? null)
            || $target['process_boundary_id'] !== ($scope['process_boundary_id'] ?? null)
            || $target['provider_id'] !== ($scope['provider_id'] ?? null)
            || $target['operation'] !== ($scope['operation'] ?? null)
            || $authority['authority_id'] !== ($sourceAuthority['authority_id'] ?? null)
            || $authority['decision_digest'] !== ($decision['record_digest'] ?? null)
            || $authority['target_attestation_digest'] !== ($attestation['record_digest'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || $root['principal_id'] !== $target['principal_id']
            || $root['principal_generation'] !== $target['generation']
            || $root['process_boundary_id'] !== $target['process_boundary_id']
            || $root['authority_id'] !== $authority['authority_id']) {
            throw new \RuntimeException('PRA402_CANONICAL_ACTIVATION_TARGET_MISMATCH');
        }
    }
}
