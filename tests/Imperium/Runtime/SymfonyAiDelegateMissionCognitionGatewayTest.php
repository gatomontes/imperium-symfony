<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\DelegateProviderInvoker;
use App\Imperium\Runtime\Citadel\SymfonyAiDelegateMissionCognitionGateway;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Message\MessageBag;

final class SymfonyAiDelegateMissionCognitionGatewayTest extends TestCase
{
    public function testGatewayRequiresBrokeredClaimAwareProviderInvoker(): void
    {
        $claim = ['claim_id' => 'provider-invocation-'.str_repeat('a', 20)];
        $activation = ['model' => [
            'runtime_binding' => [
                'provider' => 'deepseek',
                'platform_service' => 'ai.platform.generic.deepseek',
                'runtime_model' => 'deepseek-v4-flash',
            ],
            'configuration' => ['temperature' => 0.2],
        ]];
        $commission = ['commission_contract' => [
            'objective' => 'Assess.',
            'scope' => ['bounded'],
            'deliverables' => ['answer'],
            'required_inputs' => ['input'],
            'expected_outcomes' => ['result'],
            'stop_conditions' => ['done'],
        ]];
        $provider = new class implements DelegateProviderInvoker {
            public array $received = [];

            public function invoke(array $claim, string $runtimeModel, MessageBag $messages, array $configuration): string
            {
                $this->received = [$claim, $runtimeModel, $configuration];

                return json_encode([
                    'disposition' => 'COMPLETED',
                    'output' => 'Complete.',
                    'evidence_references' => [],
                    'uncertainties' => [],
                    'stop_condition_triggered' => false,
                    'stop_rationale' => null,
                ], JSON_THROW_ON_ERROR);
            }
        };

        $result = (new SymfonyAiDelegateMissionCognitionGateway($provider))->invoke($claim, $activation, $commission);

        self::assertSame('COMPLETED', $result['disposition']);
        self::assertSame($claim, $provider->received[0]);
        self::assertSame('deepseek-v4-flash', $provider->received[1]);
        self::assertSame(['temperature' => 0.2], $provider->received[2]);
    }

    public function testUnsupportedRuntimeFailsBeforeProviderInvoker(): void
    {
        $provider = new class implements DelegateProviderInvoker {
            public function invoke(array $claim, string $runtimeModel, MessageBag $messages, array $configuration): string
            {
                throw new \LogicException('Provider invoker must not be reached.');
            }
        };

        $this->expectExceptionMessage('CT310_DELEGATE_RUNTIME_PLATFORM_UNSUPPORTED');
        (new SymfonyAiDelegateMissionCognitionGateway($provider))->invoke(
            [],
            ['model' => ['runtime_binding' => [
                'provider' => 'openai',
                'platform_service' => 'ai.platform.openai',
                'runtime_model' => 'gpt-test',
            ]]],
            [],
        );
    }

    public function testUnknownConfigurationFailsBeforeProviderInvoker(): void
    {
        $provider = new class implements DelegateProviderInvoker {
            public function invoke(array $claim, string $runtimeModel, MessageBag $messages, array $configuration): string
            {
                throw new \LogicException('Provider invoker must not be reached.');
            }
        };

        $this->expectExceptionMessage('CT312_DELEGATE_MODEL_CONFIGURATION_INVALID');
        (new SymfonyAiDelegateMissionCognitionGateway($provider))->invoke(
            [],
            ['model' => [
                'runtime_binding' => ['provider' => 'deepseek', 'platform_service' => 'ai.platform.generic.deepseek', 'runtime_model' => 'deepseek-v4-flash'],
                'configuration' => ['temperature' => 0.2, 'tools' => true],
            ]],
            [],
        );
    }
}
