# Provider Effect Principal and Binding Activation Resumption Batch 4 entry point

## Result

RESUMPTION_BATCH_4_CANONICAL_ATOMIC_PRINCIPAL_ACTIVATION_ENTRY_POINT_COMPLETE

Batch 4 introduces one canonical entry point for the already-proved principal
activation transition.

## Admission and winner

The entry point accepts only a `READY_OFFLINE_ACTIVATION_INPUT` reconstruction.
It obtains the exact shared replay/contention root, enters that root's atomic
boundary, reconstructs again inside the boundary, and refuses if the
deterministic proof digest changed.

Inside the winner boundary it rechecks the exact principal ID, binding,
generation, process boundary, provider, operation, decision digest,
attestation digest, unconsumed single-use authority and replay root. Only then
does it call the existing transition that writes one single combined
authority-consumption and principal-activation winner.

There is no separate consumption-only state. Exact replay converges on the same
immutable activation. Two canonical entry points converge on one winner.
Absence, conflict, expiry, revocation, changed evidence or proof drift refuses
before activation.

## Closed downstream boundary

The exact principal generation becomes `ACTIVE`, and its exact activation
authority is consumed with no continuing authority. The provider binding remains
`BOUND_INACTIVE`.

This entry point contains no credential or capability path, no provider
invocation, no external I/O, no provider effect, no retry authority and no live
provider-consumer migration. Iron Gate and Lazaretto remain closed.
`UNKNOWN_REPLAY_PROHIBITED` remains binding.
