<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Console;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\DeepSeek\Runtime;
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,Recovery};

/** The deployment supplies this composition. Public ingress cannot select infrastructure. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FixedGateway implements Gateway
{
    private Projection $projection;
    public function __construct(private AuthorityStore $store, private ?Runtime $runtime = null)
    {
        $runtime?->assertOwner($store);
        $this->projection = new Projection(new CommandLedger($store));
    }

    public function onboard(string $request): array
    {
        $q = CommandLedger::request($request);
        if ($q['mode'] === 'preview') { return $this->projection->preview($q); }
        // Even a mutating request cannot initialize an absent owner.
        $this->store->journal->inspectExisting(fn(array $f) => $this->store->state($f['state']));
        R::require($this->runtime !== null,'COMPATIBLE_PRODUCER_MISSING');
        try { $operation = $this->runtime->advance($request); }
        catch (\Throwable $error) { return $this->failed($q,$error); }
        return $this->observed($q,$operation);
    }

    public function status(string $sequence): array { return $this->projection->status($sequence); }

    public function resume(string $request): array
    {
        $q = Recovery::request($request);
        $this->store->journal->inspectExisting(fn(array $f) => $this->store->state($f['state']));
        R::require($this->runtime !== null,'COMPATIBLE_PRODUCER_MISSING');
        try { $operation = $this->runtime->resume($request); }
        catch (\Throwable $error) { return $this->failed($q,$error); }
        return $this->observed($q,$operation);
    }

    private function observed(array $q,array $operation): array
    {
        try { return $this->projection->status($q['sequence_id'],$operation); }
        catch (\Throwable $error) {
            $out = PublicResult::empty($q['sequence_id'],$q['command_id'],$q['mode'] ?? 'resume');
            foreach (['head','sequence_head','result_ref'] as $key) { $out[$key] = $operation[$key]; }
            $out['effects']['new_effects_this_command'] = $operation['effects']['new_effects_this_command'];
            return PublicResult::refusal($error,$out);
        }
    }

    private function failed(array $q,\Throwable $error): array
    {
        // A delivery failure can follow a committed claim. Recover its public identity
        // and exposure from custody before classifying the command's outcome.
        $out = $this->projection->status($q['sequence_id']);
        $out['command_id'] = $q['command_id']; $out['mode'] = $q['mode'] ?? 'resume';
        $command = $this->store->journal->inspectExisting(function(array $frame) use ($q): ?array {
            $s = $this->store->state($frame['state']);
            $key = \App\Imperium\Runtime\Onboarding\Ledger\LedgerState::key('command',[$this->store->instance,$q['sequence_id'],$q['command_id']]);
            $command = $s['commands'][$key] ?? null;
            return $command !== null && R::same($command['request'],$q)?$command:null;
        });
        if ($command !== null) { $out['result_ref'] = $command['ref']; }
        if ($error->getMessage() === 'O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN' && $command !== null) {
            $out['effects']['new_effects_this_command'] = true;
            $out['reason_codes'] = [ReasonCodes::public($error)];
            return $out;
        }
        return PublicResult::refusal($error,$out);
    }
}
