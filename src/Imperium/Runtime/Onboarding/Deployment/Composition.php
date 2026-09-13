<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Deployment;

use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
use App\Imperium\Runtime\Onboarding\Augur\{AugurAdapter,BaseProjection,BaseEvidence,MissingBaseEvidence,FreshProducer,ConstitutionEvidence,MissingConstitutionEvidence,CognitionEvidence,MissingCognitionEvidence};
use App\Imperium\Runtime\Onboarding\Assignment\{AssignmentEvidence,MissingAssignmentEvidence,PersistentSettings};
use App\Imperium\Runtime\Onboarding\Console\{Gateway,FixedGateway};
use App\Imperium\Runtime\Onboarding\DeepSeek\{AccessAdapter,EvidenceVerifier,MissingEvidence,Runtime,EnvelopeStore};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,Recovery};
use Symfony\Component\HttpClient\MockHttpClient;

/** Explicit infrastructure wiring only. No service alias, environment discovery or provisioning. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Composition implements Gateway
{
    private ?FixedGateway $writer = null;
    private ?AugurAdapter $adapter = null;

    public function __construct(
        private readonly AuthorityStore $store,
        private readonly OperatorRootOwnership $owner,
        private readonly ?array $grant,
        private readonly string $custodyDirectory,
        private readonly string $responseDirectory,
        private readonly EvidenceVerifier $access = new MissingEvidence(),
        private readonly BaseEvidence $base = new MissingBaseEvidence(),
        private readonly ConstitutionEvidence $constitution = new MissingConstitutionEvidence(),
        private readonly CognitionEvidence $cognition = new MissingCognitionEvidence(),
        private readonly AssignmentEvidence $assignment = new MissingAssignmentEvidence(),
        private readonly ?MockHttpClient $mock = null,
    ) {}

    private function writer(): FixedGateway
    {
        if ($this->writer === null) {
            // Refuse absent/foreign owners before touching custody or response infrastructure.
            $this->owner->assertStore($this->store);
            $this->store->journal->inspectExisting(fn(array $f) => $this->store->state($f['state']));
            $keys = new FileKeySource($this->custodyDirectory);
            $base = new BaseProjection($this->base);
            $access = new AccessAdapter($this->grant, $this->access, $keys, $base);
            $founding = new FreshProducer($this->owner, $base, $this->constitution);
            $adapter = new AugurAdapter($access, $founding, $keys, $this->cognition);
            $runtime = new Runtime($this->store, $adapter, $keys, new EnvelopeStore($this->responseDirectory),
                $this->mock, assignmentEvidence: $this->assignment);
            $this->adapter = $adapter;
            $this->writer = new FixedGateway($this->store, $runtime);
        }
        return $this->writer;
    }

    public function onboard(string $request): array
    {
        $q = CommandLedger::request($request);
        if ($q['mode'] === 'preview') { return (new FixedGateway($this->store))->onboard($request); }
        return $this->writer()->onboard($request);
    }

    public function status(string $sequence): array { return (new FixedGateway($this->store))->status($sequence); }

    public function resume(string $request): array
    {
        Recovery::request($request);
        return $this->writer()->resume($request);
    }

    /** Same evidence and adapter as application; historical receipts cannot bypass current checks. */
    public function settings(): PersistentSettings
    {
        $this->writer();
        return new PersistentSettings($this->store, $this->adapter, $this->assignment);
    }
}
