<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionContract;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationContract;

final class ProviderActivationConsumptionRemediationBatch2Test extends ProviderExecutionBoundaryRedesignBatch6Test
{
    public function testOneRecordConsumesActivationAndAuthorityBeforeEffectStart(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T22:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $service = new GovernedProviderExecutionCombinedAdmissionService($this->root);

        $admission = $service->admit($authority['authority_id'], $at);
        $replayed = $service->admit(
            $authority['authority_id'],
            $at->modify('+20 minutes'),
        );

        self::assertSame($admission, $replayed);
        self::assertSame(
            GovernedProviderExecutionCombinedAdmissionContract::SCHEMA,
            $admission['schema'],
        );
        self::assertTrue($admission['activation_consumption']['single_operation']);
        self::assertTrue($admission['activation_consumption']['consumed']);
        self::assertFalse(
            $admission['activation_consumption']['continuing_authority'],
        );
        self::assertTrue($admission['authority_consumption']['single_use']);
        self::assertTrue($admission['authority_consumption']['consumed']);
        self::assertFalse(
            $admission['authority_consumption']['continuing_authority'],
        );
        self::assertStringEndsWith(
            $authority['provider_binding_activation']['id'],
            $admission['activation_consumption']['winner_scope'],
        );
        self::assertSame(
            GovernedProviderExecutionCombinedAdmissionContract::CHECKPOINT,
            $admission['effect_start']['checkpoint'],
        );
        self::assertTrue($admission['effect_start']['local_effect_start_committed']);
        self::assertFalse($admission['effect_start']['credential_resolved']);
        self::assertFalse($admission['effect_start']['external_io_started']);
        self::assertFalse($admission['effect_start']['provider_invoked']);
    }

    public function testSecondAuthorityForSameActivationRefusesUnderOneWinner(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T22:00:00+00:00');
        $first = $this->seedLineage($at, $at->modify('+10 minutes'));
        $second = $first;
        unset($second['record_digest']);
        $second['authority_id'] = 'durable-provider-execution-authority-2';
        $second['source_decision'] = $this->reference(
            'execution-authority-decision-2',
        );
        $second = $this->records->put(
            DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES,
            $second['authority_id'],
            $second,
        );
        $service = new GovernedProviderExecutionCombinedAdmissionService($this->root);
        $service->admit($first['authority_id'], $at);

        $this->expectExceptionMessage('PEB613_EXECUTION_ADMISSION_CONFLICT');
        $service->admit($second['authority_id'], $at);
    }

    public function testExistingRevocationFactRefusesFirstCombinedWinner(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T22:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $activation = $authority['provider_binding_activation'];
        $revocationId = ProviderBindingActivationRevocationContract::ID_PREFIX
            .substr(hash('sha256', $activation['id'].'|'.$activation['digest']), 0, 20);
        $this->records->put(
            GovernedProviderExecutionCombinedAdmissionService::REVOCATIONS,
            $revocationId,
            [
                'schema' => ProviderBindingActivationRevocationContract::SCHEMA,
                'revocation_id' => $revocationId,
                'instance_id' => 'instance-1',
                'provider_binding_activation' => $activation,
                'source_revocation_authority' => $this->reference(
                    'activation-revocation-authority-1',
                ),
                'reason_code' => 'OPERATOR_REVOKED',
                'revoked_at' => $at->format(DATE_ATOM),
                'sealed' => true,
            ],
        );

        $this->expectExceptionMessage('PEB622_PROVIDER_ACTIVATION_REVOKED');
        (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);
    }

    public function testSourceUsesActivationLockAndHasNoProviderEffectPath(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/LaCortine/'
            .'GovernedProviderExecutionCombinedAdmissionService.php',
        );

        foreach ([
            "'governed-provider-execution-admission:'.\$activationId",
            "'activation_consumption' => [",
            "'authority_consumption' => [",
            "'credential_resolved' => false",
            "'external_io_started' => false",
            "'provider_invoked' => false",
            'PEB622_PROVIDER_ACTIVATION_REVOKED',
        ] as $proof) {
            self::assertStringContainsString($proof, $source);
        }
        foreach ([
            'EnvironmentCredentialBroker',
            'CredentialCapability',
            'DeterministicTransport',
            'AgentMail',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
