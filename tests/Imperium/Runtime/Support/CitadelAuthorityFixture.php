<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\StateStore;
use App\Command\CitadelAuthorityCommand;
use App\Imperium\Runtime\Citadel\Authority\{AuthorityInput, GarrisonAuthorityRequest, RecruiterEvidence};
use App\Imperium\Runtime\Clock;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

/** Entire fixture is synthetic. No operator-root installation or genuine trust. */
final class CitadelAuthorityFixture implements Clock
{
    public const SECRET = 'SYNTHETIC_PRIVATE_SENTINEL_NEVER_PUBLIC_721940';
    public string $root;
    public int $now = 1900000000;
    public array $outputs = [];
    public function __construct()
    {
        $this->root = sys_get_temp_dir().'/citadel-authority-synthetic-'.bin2hex(random_bytes(8));
        mkdir($this->root, 0770, true);
    }
    public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.$this->now); }
    public function recruiter(): RecruiterEvidence { return new RecruiterEvidence($this->root, $this); }
    public function garrison(): GarrisonAuthorityRequest { return new GarrisonAuthorityRequest($this); }
    public function seed(?array $state = null): void
    {
        $store = new StateStore($this->root);
        $store->locked(fn () => $store->write($state ?? self::state()));
    }
    public static function state(): array
    {
        $instance = 'synthetic-authority-test';
        $p = ['manifestation_id' => $instance.'.officer.provisional-recruiter.1', 'seat' => 'conscription.recruiter', 'occupancy_generation' => 1, 'authority' => 'succession-only'];
        $n = ['manifestation_id' => $instance.'.officer.ordinary-recruiter.1', 'seat' => 'conscription.recruiter', 'occupancy_generation' => 2, 'authority' => 'ordinary-recruiter', 'predecessor' => $p['manifestation_id']];
        $q = ['commission_id' => 'primordial.recruiter-succession.1', 'commission_digest' => str_repeat('a', 64), 'candidate_id' => $n['manifestation_id'],
            'profile' => 'conscription.recruiter.ordinary@1.0.0', 'substrate' => 'generic-officer.ordinary-recruiter@1.0.0',
            'qualification_contract' => 'qualification.conscription.recruiter.ordinary.v1',
            'checks' => ['exact_profile_installation' => true, 'declared_authority_restraint' => true, 'version_and_provenance_preservation' => true]];
        return ['schema' => 'imperium.bootstrap-state/v1', 'state' => 'CURIA_READY', 'generation' => 10,
            'binding' => ['instance_id' => $instance, 'manifest_id' => 'synthetic-manifest', 'private_extra' => self::SECRET],
            'private_key' => self::SECRET, 'errors' => [self::SECRET], 'events' => [
                ['transition' => 'T03', 'result' => 'SUCCESS', 'generation' => 3, 'output' => $p],
                ['transition' => 'T04', 'result' => 'SUCCESS', 'generation' => 4, 'output' => [
                    'commission' => ['id' => $q['commission_id'], 'digest' => $q['commission_digest'], 'consumed' => true],
                    'retired' => ['manifestation_id' => $p['manifestation_id'], 'seat' => $p['seat'], 'occupancy_generation' => 1, 'disposition' => 'retired-after-succession'],
                    'successor' => $n, 'qualification_packet' => $q, 'qualification_packet_digest' => AuthorityInput::digest($q)]],
                ['transition' => 'T10', 'result' => 'SUCCESS', 'generation' => 10, 'output' => ['private' => self::SECRET]],
            ]];
    }
    public static function occupancy(): array
    {
        return AuthorityInput::seal(['schema' => 'imperium.garrison-constable-occupancy/v1',
            'binding_id' => 'garrison-constable-binding-'.str_repeat('a', 20), 'instance_id' => 'synthetic-authority-test',
            'office' => 'garrison', 'seat' => 'garrison.constable', 'manifestation_id' => 'synthetic-constable',
            'occupancy_generation' => 1, 'status' => 'ACTIVE', 'binding_atomic' => true,
            'inventory_response_authority' => true, 'selection_authority' => false, 'execution_authority' => false]);
    }
    public function requestInput(): array
    {
        return ['schema' => 'imperium.garrison-authority-preparation/v1', 'occupancy' => self::occupancy(),
            'prior_revision' => null, 'effective_at' => $this->now, 'expires_at' => $this->now + 60, 'request_nonce' => str_repeat('b', 48)];
    }
    public function command(string $mode, array|string $input): array
    {
        $container = new ContainerBuilder(); $container->setParameter('kernel.project_dir', $this->root);
        $container->register(Clock::class)->setSynthetic(true)->setPublic(true);
        foreach ([RecruiterEvidence::class, GarrisonAuthorityRequest::class, CitadelAuthorityCommand::class] as $class) {
            $container->register($class, $class)->setAutowired(true)->setAutoconfigured(true)->setPublic(true);
        }
        $container->compile(); $container->set(Clock::class, $this);
        if (is_array($input)) { $path = $this->root.'/public-input.json'; file_put_contents($path, json_encode($input, JSON_THROW_ON_ERROR)); }
        else { $path = $input; }
        $tester = new CommandTester($container->get(CitadelAuthorityCommand::class));
        $exit = $tester->execute(['mode' => $mode, 'input' => $path]);
        $text = $tester->getDisplay(); $result = json_decode($text, true, 48, JSON_THROW_ON_ERROR);
        $this->outputs[] = ['mode' => $mode, 'exit' => $exit, 'output' => $result];
        return [$exit, $result, $text];
    }
    public function close(): void
    {
        $resolved = realpath($this->root); $temp = realpath(sys_get_temp_dir());
        if ($resolved === false || $temp === false || dirname($resolved) !== $temp || !str_starts_with(basename($resolved), 'citadel-authority-synthetic-')) {
            throw new \RuntimeException('Synthetic cleanup boundary mismatch');
        }
        (new Filesystem())->remove($resolved);
    }
}
