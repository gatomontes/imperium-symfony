<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorAtomicLiveTransitionInertTransactionSeam
{
    public function __construct(
        private readonly ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator $validator,
    ) {
    }

    public function classify(
        array $journal,
        array $winner,
        array $receipt,
    ): string {
        $this->validator->assertReceipt($receipt, $winner, $journal);

        return 'VALID_CONTRACT_ONLY_NO_TRANSACTION_PERFORMED';
    }
}
