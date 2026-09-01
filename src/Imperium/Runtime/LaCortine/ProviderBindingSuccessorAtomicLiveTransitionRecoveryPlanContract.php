<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-atomic-live-transition-recovery-plan/v1';
    public const string STATUS = 'READ_ONLY_CONTRACT_NOT_APPLIED';
    public const array DIRECTIVES = [
        'ABSENT' => 'NO_ACTION',
        'PREPARED' => 'REFUSE_AUTOMATIC_REPAIR',
        'COMMITTING' => 'REFUSE_PARTIAL_STATE',
        'COMMITTED' => 'ACCEPT_EXACT_READ_ONLY',
        'INCOMPLETE' => 'REFUSE_INCOMPLETE_EVIDENCE',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'recovery_plan_id', 'instance_id', 'replay_contention_root',
        'classification_directives', 'automatic_repair_permitted',
        'state_write_permitted', 'authority_action_permitted',
        'plan_applied', 'continuing_authority', 'status', 'sealed',
        'record_digest',
    ];
    public const array NON_AUTHORITIES = [
        'persists_journal' => false,
        'acquires_lock' => false,
        'writes_or_repairs_state' => false,
        'issues_or_consumes_authority' => false,
        'admits_execution' => false,
        'adopts_successor' => false,
        'changes_binding_state' => false,
        'creates_winner_or_receipt' => false,
        'handles_credential_capability' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'starts_provider_effect' => false,
    ];

    private function __construct()
    {
    }
}
