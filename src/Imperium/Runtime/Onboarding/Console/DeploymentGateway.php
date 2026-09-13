<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Console;

use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,Recovery};

/** Default deployment observes its existing root. Live producer composition is separately commissioned. */
final readonly class DeploymentGateway implements Gateway
{
    public function __construct(private FormationJournal $journal,private Clock $clock) {}

    private function reader(): FixedGateway
    {
        $trust = $this->journal->inspectExisting(static fn(array $frame): array => R::record($frame['state']['onboarding']['trust'] ?? null));
        return new FixedGateway(new AuthorityStore($this->journal,$this->clock,$trust['instance_id'],$trust['citadel_id'],
            $trust['body']['issuer']['id'],$trust['producer']['source_commit']));
    }

    public function onboard(string $request): array
    {
        CommandLedger::request($request);
        return $this->reader()->onboard($request);
    }
    public function status(string $sequence): array { R::id($sequence); return $this->reader()->status($sequence); }
    public function resume(string $request): array { Recovery::request($request); return $this->reader()->resume($request); }
}
