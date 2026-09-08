<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Bootstrap\CanonicalJson;
use App\Command\{NativeAuthorityCommand, NativeAuthorityEnrollmentCommand};
use App\Imperium\Runtime\Citadel\Authority\{AuthorityInput as A, GarrisonAuthorityRequest, RecruiterEvidence};
use App\Imperium\Runtime\Citadel\NativeAuthority\{NativeJournal, NativeProtocol, NativeTrust};
use App\Imperium\Runtime\Clock;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NativeAuthorityFixture
{
    public CitadelAuthorityFixture $base;
    public string $root;
    public string $secretKey;
    public array $policy;
    public array $projection;
    public array $occupancy;
    public array $outputs = [];
    public const DELIVERY = 'guildhall-garrison-persona-admission-delivery-cccccccccccccccccccc';
    public const INQUIRY = 'garrison-inquiry-dddddddddddddddddddd';
    public function __construct(?array $occupancy = null)
    {
        $this->base = new CitadelAuthorityFixture(); $this->root = $this->base->root; $this->base->seed();
        $this->occupancy = $occupancy ?? CitadelAuthorityFixture::occupancy();
        $this->projection = $this->base->recruiter()->export('synthetic-authority-test');
        $pair = sodium_crypto_sign_keypair(); $this->secretKey = sodium_crypto_sign_secretkey($pair);
        $this->policy = ['schema' => 'imperium.native-authority-enrollment/v1', 'domain' => NativeTrust::DOMAIN,
            'instance_id' => 'synthetic-authority-test', 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'issuer_role' => NativeTrust::ROLE, 'effects' => NativeTrust::EFFECTS, 'not_before' => $this->base->now - 10,
            'expires_at' => $this->base->now + 7200, 'writer_boundary' => NativeTrust::BOUNDARY];
        sodium_memzero($pair);
        $this->write('var/imperium/offices/garrison/occupancy/'.$this->occupancy['binding_id'].'.json', $this->occupancy);
        $this->write('var/imperium/offices/garrison/inbox/canonical-subordinate-persona-admissions/'.self::DELIVERY.'.json', self::delivery());
        $this->write('var/imperium/offices/garrison/inbox/'.self::INQUIRY.'.json', A::seal(['inquiry_id' => self::INQUIRY,
            'status' => 'CONSTABLE_ACTIVATION_REQUIRED', 'instance_id' => 'synthetic-authority-test', 'proceeding_id' => 'synthetic-proceeding',
            'requester' => ['seat' => 'guildhall.guildmaster'], 'inventory_questions' => [], 'requested_facts' => []]));
    }
    public static function delivery(): array
    {
        $persona = ['id' => 'synthetic-persona', 'name' => 'Synthetic Complete Persona', 'version' => '1.0.0'];
        return A::seal(['route_class' => 'CANONICAL_GUILDHALL_TO_GARRISON', 'status' => 'DELIVERED_PENDING_CONSTABLE_ADMISSION_DISPOSITION',
            'recipient' => ['seat' => 'garrison.constable'], 'instance_id' => 'synthetic-authority-test', 'admission_authority' => false,
            'candidate_id' => $persona['id'], 'candidate_digest' => A::digest($persona), 'persona_name' => $persona['name'],
            'persona_specification_version' => $persona['version'], 'persona' => $persona,
            'senate_confirmation_record_id' => 'synthetic-senate-confirmation', 'senate_confirmation_record_digest' => str_repeat('e', 64),
            'originating_guildhall_commission_id' => 'synthetic-guildhall-commission', 'originating_guildhall_commission_digest' => str_repeat('f', 64)]);
    }
    public function protocol(?\Closure $checkpoint = null, ?Clock $clock = null): NativeProtocol
    {
        return new NativeProtocol(new NativeJournal($this->root, $checkpoint), $clock ?? $this->base, $this->base->garrison(), $this->base->recruiter());
    }
    public function fingerprint(): string { return hash('sha256', base64_decode($this->policy['public_key'])); }
    public function enroll(): array { return $this->command('enroll', $this->policy); }
    public function sign(string $effect, array $object, ?string $nonce = null): array
    {
        $payload = $this->protocol()->prepare(['effect' => $effect, 'object' => $object, 'issued_at' => $this->base->now,
            'expires_at' => min($this->policy['expires_at'], $this->base->now + 60), 'nonce' => $nonce ?? bin2hex(random_bytes(24))])['payload'];
        return ['object' => $object, 'decision' => ['payload' => $payload, 'signature' => $this->signature($payload)]];
    }
    public function signature(array $payload): string { return base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $this->secretKey)); }
    public function adoption(string $seat): array
    {
        $o = $this->occupancy;
        return ['expected_head' => $this->protocol()->snapshot()['registry_head'], 'seat' => $seat,
            'actor' => $seat === 'garrison.constable' ? $o['manifestation_id'] : $this->projection['source']['successor']['manifestation_id'],
            'occupancy_generation' => $seat === 'garrison.constable' ? 1 : 2, 'prior_roster' => null,
            'evidence' => $seat === 'garrison.constable' ? $o : $this->projection,
            'effective_at' => $this->base->now, 'expires_at' => min($this->policy['expires_at'], $this->base->now + 3600)];
    }
    public function revision(): array
    {
        $snapshot = $this->protocol()->snapshot(); $v = $snapshot['garrison_revision']; $i = $this->base->requestInput();
        $i['occupancy'] = $this->occupancy;
        $i['expires_at'] = min($this->policy['expires_at'], $this->base->now + 1800);
        $i['request_nonce'] = bin2hex(random_bytes(24));
        $i['prior_revision'] = $v === null ? null : ['id' => $v['revision_id'], 'digest' => $v['record_digest']];
        return ['expected_head' => $snapshot['registry_head'], 'roster_digest' => $snapshot['roster']['garrison.constable']['record_digest'],
            'prior_revision' => $v['record_digest'] ?? null, 'request' => $this->base->garrison()->prepare($i), 'occupancy' => $i['occupancy']];
    }
    public function ready(): array
    {
        if ($this->enroll()[0] !== 0) throw new \RuntimeException('Synthetic enrollment failed');
        foreach (['conscription.recruiter', 'garrison.constable'] as $seat) {
            $result = $this->command('apply', $this->sign('ADOPT_ROSTER', $this->adoption($seat)));
            if ($result[0] !== 0) throw new \RuntimeException('Synthetic adoption failed: '.json_encode($result));
        }
        $signed = $this->sign('REVISE_GARRISON', $this->revision());
        if ($this->command('apply', $signed)[0] !== 0) throw new \RuntimeException('Synthetic revision failed');
        return $signed;
    }
    public function command(string $operation, array $arguments): array
    {
        $container = new ContainerBuilder(); $container->setParameter('kernel.project_dir', $this->root);
        $container->register(Clock::class)->setSynthetic(true)->setPublic(true);
        foreach ([NativeJournal::class, NativeProtocol::class, NativeTrust::class, GarrisonAuthorityRequest::class, RecruiterEvidence::class,
            NativeAuthorityCommand::class, NativeAuthorityEnrollmentCommand::class] as $class) $container->register($class, $class)->setAutowired(true)->setPublic(true);
        $container->compile(); $container->set(Clock::class, $this->base);
        $this->write('native-public-input.json', $operation === 'enroll' ? $arguments : ['operation' => $operation, 'arguments' => $arguments]);
        $tester = new CommandTester($container->get($operation === 'enroll' ? NativeAuthorityEnrollmentCommand::class : NativeAuthorityCommand::class));
        $exit = $tester->execute($operation === 'enroll' ? ['policy' => $this->root.'/native-public-input.json', 'confirmed-fingerprint' => $this->fingerprint()]
            : ['input' => $this->root.'/native-public-input.json']);
        $text = $tester->getDisplay(); $value = json_decode($text, true, 48, JSON_THROW_ON_ERROR);
        $this->outputs[] = ['operation' => $operation, 'exit' => $exit, 'result' => $value]; return [$exit, $value, $text];
    }
    public function write(string $path, array $value): void
    {
        $target = $this->root.'/'.$path; if (!is_dir(dirname($target))) mkdir(dirname($target), 0770, true);
        file_put_contents($target, json_encode($value, JSON_THROW_ON_ERROR));
    }
    public function close(): void { sodium_memzero($this->secretKey); $this->base->close(); }
}
