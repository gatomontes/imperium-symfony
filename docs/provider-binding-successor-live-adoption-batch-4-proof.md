# Provider Binding Successor Live Adoption Batch 4 proof

## Result

BATCH_4_DISPOSABLE_INTERRUPTION_REPLAY_CONTENTION_EXPIRY_AND_REVOCATION_PROOF_COMPLETE

The caller-supplied proof exercises the inert exact-root winner boundary without
persistence or live authority.

Interruption before immutable commit leaves no winner.
Interruption after immutable commit exposes one deterministic proof winner.
Exact replay converges on that winner.
Changed evidence under the same root conflicts.
Expiry and revocation refuse before proof evaluation.
No interruption path creates a partial record.
No proof path starts a provider effect.

The after-commit projection proves that authority consumption, v3 execution
admission, successor adoption and binding transition share one commit boundary.
It does not perform those transitions or create durable production evidence.

## Non-authority

Batch 4 may not produce a decision or issue authority.
Batch 4 may not consume live authority.
Batch 4 may not admit live execution.
Batch 4 may not adopt a live successor or change live binding state.
Batch 4 may not handle or resolve a credential or capability.
Batch 4 may not invoke a provider.
Batch 4 may not perform external I/O.
Batch 4 may not start a provider effect.
Batch 4 may not authorize retry.
Batch 4 may not migrate a live command.
Batch 4 may not open Iron Gate or Lazaretto.

The provider binding remains BOUND_INACTIVE.
The v3 execution admission remains NOT_IMPLEMENTED.
UNKNOWN_REPLAY_PROHIBITED remains binding.
