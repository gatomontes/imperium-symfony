<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GuildhallGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private const STAGES = [
        'disciplinary-fit' => ['disciplinary_fit', 'guildhall.committee.disciplinary-fit', 'determine-disciplinary-fit', []],
        'composition' => ['composition', 'guildhall.committee.composition', 'determine-composition', ['disciplinary_fit']],
        'boundary-challenge' => ['boundary_challenge', 'guildhall.committee.boundary-challenge', 'challenge-boundaries', ['disciplinary_fit', 'composition']],
        'guildmaster-synthesis' => ['guildmaster', 'guildhall.guildmaster', 'synthesize-guildhall-determination', ['disciplinary_fit', 'composition', 'boundary_challenge']],
    ];

    private string $guildhall;
    private RecordReferenceValidator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        private ProceedingStore $proceedings,
        ?RecordReferenceValidator $validator = null,
    ) {
        $this->guildhall = $root.'/var/imperium/offices/guildhall';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function supports(string $cluster, string $authorityType): bool
    {
        return 'guildhall' === $cluster && isset(self::STAGES[$authorityType]);
    }

    public function resolve(string $cluster, string $type, string $id): array
    {
        if (!$this->supports($cluster, $type) || !preg_match('/^guildhall-acceptance-[a-f0-9]{20}$/', $id)) {
            throw new \RuntimeException('GCA800_GUILDHALL_AUTHORITY_UNSUPPORTED');
        }
        [$stage, $seat, $purpose, $required] = self::STAGES[$type];
        $acceptance = $this->read('acceptances', $id, 'GCA801_GUILDHALL_AUTHORITY_ABSENT');
        $binding = $this->read('occupancy', (string) ($acceptance['binding_id'] ?? ''), 'GCA802_GUILDHALL_AUTHORITY_INVALID');
        [$commission, $turn, $plan] = $this->context($acceptance);
        $occupancy = $this->occupancy($acceptance, $binding);
        $committee = $this->predecessors($id, $acceptance, $turn, $required);
        $inputs = [$plan, $acceptance['authorized_scope'] ?? [], $occupancy, $committee];

        return [
            'cluster' => 'guildhall', 'authority_type' => $type, 'authority_id' => $id,
            'instance_id' => $acceptance['instance_id'], 'case_id' => $acceptance['commission_id'],
            'case_digest' => $commission['record_digest'], 'seat' => $seat, 'purpose' => $purpose,
            'input_digest' => hash('sha256', CanonicalJson::encode($inputs)),
            'source' => ['id' => $id, 'digest' => $acceptance['record_digest']],
            'single_use' => true, 'exercisable' => true, 'consumed' => $this->consumed($id, $stage),
            'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    private function context(array $acceptance): array
    {
        $proceedingId = $acceptance['proceeding_id'] ?? null;
        $commission = null;
        foreach (is_string($proceedingId) ? $this->proceedings->commissions($proceedingId) : [] as $candidate) {
            if (($acceptance['commission_id'] ?? null) === ($candidate['commission_id'] ?? null)) $commission = $candidate;
        }
        $sequence = is_array($commission) ? ($commission['authority']['plan_turn'] ?? null) : null;
        $turn = is_int($sequence) && is_string($proceedingId) ? $this->proceedings->turn($proceedingId, $sequence) : null;
        $plan = is_array($turn) ? ($turn['seneschal']['mission_plan'] ?? null) : null;
        if (!$this->validator->isIntact($acceptance)
            || 'imperium.guildhall-commission-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || 'ACCEPTED_FOR_INSTITUTIONAL_DELIBERATION' !== ($acceptance['disposition'] ?? null)
            || true !== ($acceptance['recipient_acceptance'] ?? null) || true !== ($acceptance['deliberation_authority'] ?? null)
            || true === ($acceptance['execution_authority'] ?? null) || !is_array($commission)
            || !$this->validator->isIntact($commission) || ($acceptance['commission_digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || 'planning-only' !== ($commission['phase'] ?? null) || true === ($commission['execution_authority'] ?? null)
            || !is_array($turn) || !is_array($plan) || ($commission['authority']['plan_digest'] ?? null) !== ($turn['record_digest'] ?? null)) {
            throw new \RuntimeException('GCA802_GUILDHALL_AUTHORITY_INVALID');
        }
        return [$commission, $turn, $plan];
    }

    private function occupancy(array $acceptance, array $binding): array
    {
        if (!$this->validator->isIntact($binding) || ($acceptance['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null) || 4 !== count($binding['bindings'] ?? [])) {
            throw new \RuntimeException('GCA804_GUILDHALL_OCCUPANCY_INVALID');
        }
        $occupancy = [];
        foreach (['guildhall.guildmaster', 'guildhall.committee.disciplinary-fit', 'guildhall.committee.composition', 'guildhall.committee.boundary-challenge'] as $seat) {
            $actor = $binding['bindings'][$seat] ?? null;
            if (!is_array($actor) || $seat !== ($actor['seat'] ?? null) || 1 !== ($actor['occupancy_generation'] ?? null)
                || 'BOUND_PENDING_COMMISSION_ACCEPTANCE' !== ($actor['status'] ?? null)) {
                throw new \RuntimeException('GCA804_GUILDHALL_OCCUPANCY_INVALID');
            }
            $occupancy[$seat] = ['manifestation_id' => $actor['manifestation_id'], 'occupancy_generation' => 1];
        }
        return $occupancy;
    }

    private function predecessors(string $id, array $acceptance, array $turn, array $required): array
    {
        if ([] === $required) return [];
        $checkpoint = $this->read('deliberation-checkpoints', $id, 'GCA803_GUILDHALL_PREDECESSOR_INVALID');
        if ('imperium.guildhall-deliberation-checkpoint/v1' !== ($checkpoint['schema'] ?? null)
            || $id !== ($checkpoint['acceptance_id'] ?? null) || ($acceptance['record_digest'] ?? null) !== ($checkpoint['acceptance_digest'] ?? null)
            || ($turn['record_digest'] ?? null) !== ($checkpoint['mission_plan_digest'] ?? null)) {
            throw new \RuntimeException('GCA803_GUILDHALL_PREDECESSOR_INVALID');
        }
        $stored = $checkpoint['decision']['committee'] ?? null;
        if (!is_array($stored)) throw new \RuntimeException('GCA803_GUILDHALL_PREDECESSOR_INVALID');
        $prior = [];
        foreach ($required as $stage) {
            if (!is_array($stored[$stage] ?? null)) throw new \RuntimeException('GCA803_GUILDHALL_PREDECESSOR_INVALID');
            $prior[$stage] = $stored[$stage];
        }
        return $prior;
    }

    private function consumed(string $id, string $stage): bool
    {
        $path = $this->guildhall.'/deliberation-checkpoints/'.$id.'.json';
        if (!is_file($path)) return false;
        $checkpoint = $this->validator->requireIntact($this->validator->read($path, 'GCA805_GUILDHALL_CONSUMPTION_INVALID'), 'GCA805_GUILDHALL_CONSUMPTION_INVALID');
        return 'guildmaster' === $stage
            ? is_array($checkpoint['decision']['guildmaster'] ?? null)
            : is_array($checkpoint['decision']['committee'][$stage] ?? null);
    }

    private function read(string $directory, string $id, string $error): array
    {
        if ('' === $id) throw new \RuntimeException($error);
        return $this->validator->requireIntact($this->validator->read($this->guildhall.'/'.$directory.'/'.$id.'.json', $error), $error);
    }
}
