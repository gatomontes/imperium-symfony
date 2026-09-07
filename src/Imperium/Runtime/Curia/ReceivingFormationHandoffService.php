<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use App\Imperium\Runtime\MasterMason\ChildCuriaFormationService;
use App\Imperium\Runtime\Persistence\{AtomicTransition, ImmutableRecordStore};
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Durable receiving-side custody of an already admitted attributable assessment.
 * Retrying a missing receipt reuses the sealed response; it cannot invoke cognition.
 */
final readonly class ReceivingFormationHandoffService
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, private FormationJournal $journal) {}

    public function receive(string $sessionId, string $attemptId): array
    {
        $state = $this->journal->read()['state'];
        $session = $state['sessions'][$sessionId] ?? [];
        $record = $session['attempts'][$attemptId]['admitted'] ?? null;
        $handoff = $state['handoffs'][$session['intake_id'] ?? ''] ?? [];
        if (($session['phase'] ?? null) !== 'acceptance' || !is_array($record)
            || ($record['claim']['holder'] ?? null) !== ($handoff['constitution']['occupants']['curia.seneschal'] ?? null)
            || ($session['terms']['source']['packet_digest'] ?? null) !== FormationJournal::digest($handoff['packet'] ?? [])
            || !in_array($record, $handoff['acceptances'] ?? [], true)) {
            throw new \RuntimeException('CMF129_ATTRIBUTABLE_RECEIVING_RECORD_REQUIRED');
        }
        $child = ChildCuriaFormationService::childRoot($this->root, $handoff['curia_id']);
        $records = new ImmutableRecordStore($child, new AtomicTransition($child));
        $delivered = $records->read('var/imperium/curia/handoffs', $handoff['handoff_id']);
        if ($delivered['record_digest'] !== $handoff['delivery_receipt']['digest']) {
            throw new \RuntimeException('CMF092_CHILD_RECEIPT_SUBSTITUTION');
        }
        return $records->put('var/imperium/curia/handoff-assessments', $record['claim']['claim_id'], $record);
    }
}
