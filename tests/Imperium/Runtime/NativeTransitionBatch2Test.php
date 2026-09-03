<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeState, NativePrincipal, NativeSuccessor, NativeAuthority};
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionService as Production;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationService as Activation;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract as Binding;

require_once __DIR__.'/NativeTransitionBatch1Test.php';

class NativeTransitionBatch2Test extends NativeTransitionBatch1Test
{
    public function testNativeProductionActivationAndSuccessorFeedExactAuthority(): void
    {
        [$p, $activation, $at] = $this->nativeInputs();
        $service = new NativeSuccessor($this->state, static fn () => $at);
        $r = $service->create($p['principal_version_id'], NativeState::ref($activation, 'principal_activation_id'));
        self::assertSame($r, $service->load($r['successor']['successor_id'], $at));
        self::assertSame('OPERATION_SCOPED_BINDING_ACTIVE', $r['successor']['status']);
        self::assertSame(NativeState::ref($activation, 'principal_activation_id'), $r['successor']['active_principal_activation']);
        $authority = (new NativeAuthority($this->state, static fn () => $at))->issue($p['principal_version_id'], $r['successor']['successor_id']);
        self::assertSame(NativeState::ref($r['successor'], 'successor_id'), $authority['decision']['successor']);
        self::assertFalse($r['successor']['original_binding_mutated']);
    }

    public function testMissingNativeProductionAndChangedOriginalBindingRefuse(): void
    {
        [$p, $activation, $at, $production] = $this->nativeInputs();
        $path = NativeState::SOURCES['production'].'/'.$production['production_id'].'.json';
        unlink($this->root.'/'.$path);
        $service = new NativeSuccessor($this->state, static fn () => $at);
        $this->fails('NIR_ACTIVATION_PRODUCTION_ABSENT_OR_AMBIGUOUS', fn () => $service->create($p['principal_version_id'], NativeState::ref($activation, 'principal_activation_id')));
        $this->write($path, $production);
        $b = $this->state->source('binding', $p['provider_binding']); $b['status'] = 'BOUND_ACTIVE';
        $this->write(NativeState::SOURCES['binding'].'/'.$b['binding_id'].'.json', NativeState::seal($b));
        $this->fails('NIR_SOURCE_CHANGED', fn () => $service->create($p['principal_version_id'], NativeState::ref($activation, 'principal_activation_id')));
        self::assertSame([], $this->state->ids('successors'));
    }

    protected function nativeInputs(string $suffix = ''): array
    {
        $fixture = new class('testExactOfflineDecisionAndActivationValidateStoreAndReplay') extends ProviderExecutionEffectReadinessBatch7Test {
            public function export(): array { return $this->fixtures(); }
        };
        $f = $fixture->export();
        $basis = new class('testOneCombinedWinnerProducesExactDecisionAndUnconsumedAuthority') extends PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5CProductionTest {
            public function export(): array { return $this->productionBasis(); }
        };
        $b = $basis->export(); $at = $b['at']->getTimestamp();
        if ('' !== $suffix) {
            $f['attestation']['principal_attestation_id'] .= $suffix;
            $f['attestation']['principal']['principal_id'] .= $suffix;
            $f['decision']['scope']['principal_id'] .= $suffix;
            $b['authorization']['issuance_authorization_id'] .= $suffix;
            $b['authorization']['decision_id'] .= $suffix;
            $b['authorization']['activation_authority_id'] .= $suffix;
            $b['envelope']['decision_id'] .= $suffix;
            $b['envelope']['activation_authority']['authority_id'] .= $suffix;
        }
        $f['attestation']['validity']['expires_at'] = gmdate(DATE_ATOM, $at + 3600);
        $f['attestation'] = NativeState::seal($f['attestation']);
        $f['assurance']['validity']['review_due_at'] = gmdate(DATE_ATOM, $at + 3600);
        $f['assurance'] = NativeState::seal($f['assurance']);
        foreach (['principal_attestation' => ['attestation', 'principal_attestation_id'],
            'provider_assurance_admission' => ['assurance', 'admission_id'], 'execution_boundary' => ['boundary', 'boundary_id']] as $key => [$name, $id]) {
            $b['authorization'][$key] = NativeState::ref($f[$name], $id);
            $b['envelope'][$key] = $b['authorization'][$key];
            $this->write(NativeState::SOURCES[$name].'/'.$f[$name][$id].'.json', $f[$name]);
        }
        $b['authorization'] = NativeState::seal($b['authorization']);
        $auth = NativeState::ref($b['authorization'], 'issuance_authorization_id');
        $b['aggregate']['references']['issuance_authorization'] = $auth;
        $b['envelope']['issuance_authorization'] = $auth; $b['envelope']['source_authority'] = $auth;
        $b['envelope']['scope'] = $f['decision']['scope'];
        $b['envelope']['activation_authority']['target_attestation_digest'] = $f['attestation']['record_digest'];
        $b['envelope']['validity']['effective_at'] = $b['at']->format(DATE_ATOM);
        $b['envelope'] = NativeState::seal($b['envelope']);
        $production = (new Production($this->root))->produce($b['aggregate'], $b['source'], $b['transition'],
            $b['activation'], $b['principal'], $b['envelope'], $b['authorization'], $b['at'], true);
        $activation = (new Activation($this->root))->activate($production['activation_decision'], $f['attestation'], $f['assurance'], $f['boundary'], $b['at']);
        $ref = ['id' => 'fixture-reference', 'digest' => str_repeat('e', 64), 'schema' => 'imperium.fixture/v1'];
        $descriptor = NativeState::seal(['schema' => Binding::SCHEMA, 'binding_id' => 'provider-binding', 'instance_id' => 'imperium-test',
            'source_authority' => $ref, 'tool_operation' => $ref,
            'provider_implementation' => ['provider_id' => 'agentmail', 'adapter_id' => 'agentmail-email-transport', 'adapter_version' => 'v1'],
            'assurance_profile' => $ref, 'credential_family' => ['family_id' => 'agentmail-api-key', 'provider_id' => 'agentmail', 'secret_persistence_permitted' => false],
            'request_encoder' => $ref, 'evidence_decoder' => $ref,
            'destination_policy' => ['policy_id' => 'exact', 'policy_digest' => str_repeat('f', 64), 'exact_destination_required' => true],
            'scope' => ['operation' => 'email.send', 'authorization_target_id' => 'target-test', 'authorization_target_digest' => str_repeat('e', 64), 'provider_substitution_permitted' => false],
            'validity' => ['effective_at' => gmdate(DATE_ATOM, $at - 10), 'expires_at' => gmdate(DATE_ATOM, $at + 600)],
            'status' => 'BOUND_INACTIVE', 'bound_at' => gmdate(DATE_ATOM, $at - 10), 'sealed' => true]);
        $this->write(NativeState::SOURCES['binding'].'/provider-binding.json', $descriptor);
        $this->source['lifecycle']['expires_at'] = gmdate(DATE_ATOM, $at + 3600);
        if ('' !== $suffix) {
            $this->source['principal_version_id'] .= $suffix; $this->source['principal_id'] .= $suffix;
            $this->act['act_id'] .= $suffix;
            $this->act['target_id'] = 'native-principal-'.\App\Imperium\Runtime\ProviderTransition\TransitionContract::digest(['imperium-test', $this->source['principal_id'], 2]);
        }
        $this->source = NativeState::seal($this->source);
        $this->write(NativeState::SOURCES['principal'].'/'.$this->source['principal_version_id'].'.json', $this->source);
        $this->anchor['expires_at'] = $at + 3600; $this->write(NativeState::TRUST.'/identity.json', $this->anchor);
        $this->act['source_principal'] = NativeState::ref($this->source, 'principal_version_id');
        $this->act['binding'] = NativeState::ref($descriptor, 'binding_id');
        $this->act['execution_basis'] = ['activation' => NativeState::ref($activation, 'principal_activation_id'), 'production' => NativeState::ref($production, 'production_id')];
        $this->act['operation'] = 'email.send'; $this->act['effective_at'] = $at - 10; $this->act['expires_at'] = $at + 600;
        $service = new NativePrincipal($this->state, static fn () => $at);
        $p = $service->constitute($this->sign($this->act));
        $act = $this->act; $act['action'] = 'ACTIVATE'; $act['act_id'] = 'activate-native'.$suffix;
        $service->lifecycle($p['principal_version_id'], $this->sign($act));
        return [$p, $activation, $at, $production];
    }
}
