<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative read-only reconstruction of issuance through receipt. */
final class NativeEffectReconciliationAuthorityReconstructionContract
{
    public const array INPUTS = ['receipt_id'];
    public const array JOIN_ORDER = [
        'receipt', 'claim_consumption', 'forward_recovery_claim',
        'authority_consumption', 'reconciliation_authority',
        'authority_issuance', 'native_authority', 'native_principal',
        'operator_root_act',
    ];
    public const array REQUIRED_INVARIANTS = [
        'read_only' => true,
        'repairs_records' => false,
        'creates_authority' => false,
        'creates_claim' => false,
        'completes_receipt' => false,
        'invokes_provider' => false,
        'resolves_credentials' => false,
    ];
}
