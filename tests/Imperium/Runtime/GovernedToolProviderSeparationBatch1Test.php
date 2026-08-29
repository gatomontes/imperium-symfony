<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Armory\GovernedToolOperationContract;
use App\Imperium\Runtime\LaCortine\NormalizedToolResultContract;
use App\Imperium\Runtime\LaCortine\ProviderEvidenceDecoderContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderRequestEncoderContract;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch1Test extends TestCase
{
    public function testFiveContractsAreSeparatelyVersionedAndProviderNeutral(): void
    {
        self::assertSame('imperium.armory.governed-tool-operation/v1', GovernedToolOperationContract::SCHEMA);
        self::assertSame('imperium.la-cortine.provider-implementation-binding/v1', ProviderImplementationBindingContract::SCHEMA);
        self::assertSame('imperium.la-cortine.provider-request-encoder/v1', ProviderRequestEncoderContract::SCHEMA);
        self::assertSame('imperium.la-cortine.provider-evidence-decoder/v1', ProviderEvidenceDecoderContract::SCHEMA);
        self::assertSame('imperium.la-cortine.normalized-tool-result/v1', NormalizedToolResultContract::SCHEMA);

        self::assertSame('armory.armorer-governed-tool-definition', GovernedToolOperationContract::PRODUCER_POSTURE);
        self::assertContains('la-cortine.provider-implementation-binding', GovernedToolOperationContract::CONSUMER_POSTURES);
        self::assertContains('clavium.provider-bound-credential-validation', ProviderImplementationBindingContract::CONSUMER_POSTURES);
        self::assertContains('lazaretto.normalized-tool-result-admission', ProviderEvidenceDecoderContract::CONSUMER_POSTURES);
        self::assertContains('lazaretto.normalized-tool-result-admission', NormalizedToolResultContract::CONSUMER_POSTURES);

        foreach ([
            GovernedToolOperationContract::SCHEMA,
            ProviderImplementationBindingContract::SCHEMA,
            ProviderRequestEncoderContract::SCHEMA,
            ProviderEvidenceDecoderContract::SCHEMA,
            NormalizedToolResultContract::SCHEMA,
        ] as $schema) {
            self::assertStringNotContainsString('agentmail', strtolower($schema));
        }
    }

    public function testContractsForbidAuthorityProviderCredentialAndEffectAssembly(): void
    {
        foreach (GovernedToolOperationContract::CONTRACT_BOUNDARY as $allowed) self::assertFalse($allowed);
        foreach (ProviderImplementationBindingContract::CONTRACT_BOUNDARY as $allowed) self::assertFalse($allowed);
        foreach (ProviderRequestEncoderContract::CONTRACT_BOUNDARY as $allowed) self::assertFalse($allowed);
        foreach (ProviderEvidenceDecoderContract::CONTRACT_BOUNDARY as $allowed) self::assertFalse($allowed);
        foreach (NormalizedToolResultContract::CONTRACT_BOUNDARY as $allowed) self::assertFalse($allowed);

        self::assertFalse(ProviderImplementationBindingContract::SUBSTITUTION_RULES['provider_substitution_permitted']);
        self::assertFalse(ProviderImplementationBindingContract::SUBSTITUTION_RULES['credential_family_substitution_permitted']);
        self::assertFalse(ProviderImplementationBindingContract::SUBSTITUTION_RULES['evidence_decoder_substitution_permitted']);
        self::assertFalse(ProviderRequestEncoderContract::ENCODING_RULES['secret_persistence_permitted']);
        self::assertFalse(ProviderRequestEncoderContract::ENCODING_RULES['external_io_permitted']);
        self::assertFalse(ProviderEvidenceDecoderContract::DECODING_RULES['caller_supplied_provider_truth_permitted']);
        self::assertFalse(ProviderEvidenceDecoderContract::DECODING_RULES['provider_reinvocation_permitted']);
        self::assertFalse(NormalizedToolResultContract::RESULT_RULES['automatic_replay_permitted']);
    }

    public function testDocumentationKeepsImplementationClosedAndNamesOnlyBatchTwo(): void
    {
        $root = dirname(__DIR__, 3);
        $contracts = (string) file_get_contents($root.'/docs/governed-tool-provider-separation-contracts.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/governed-tool-provider-separation-batch-1-complete.md');

        foreach (['`BATCH_1_CONTRACTS_DEFINED_NO_PRODUCER_OR_CONSUMER_IMPLEMENTED`', 'No contract names AgentMail', 'cannot select or produce itself', 'Secret persistence is categorically forbidden', 'No producer or consumer is implemented'] as $proof) {
            self::assertStringContainsString($proof, $contracts);
        }
        foreach (['Only Batch 2 may next be considered', 'owned by Armory', 'No producer or consumer was implemented or migrated', 'Batch 2 is not authorized', 'Runtime behavior is unchanged'] as $proof) {
            self::assertStringContainsString($proof, $handoff);
        }
    }
}
