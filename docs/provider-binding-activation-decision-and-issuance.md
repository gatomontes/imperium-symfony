# Provider Binding Activation decision and issuance

## Status

`BATCH_2_DECISION_AND_ISSUANCE_COMPLETE`

Imperator now owns two separately governed transitions gated by an active runtime principal's exact
`provider_binding_activation_authority`. The first decides whether the exact intact,
unexpired execution claim and exact intact inactive provider binding may receive activation
authority. The second consumes the authorized decision's single-use issuance authority and emits
one sealed `imperium.imperator.provider-binding-activation-authority/v1` record.

The route accepts only a `CLAIMED_PRE_IO` claim and a `BOUND_INACTIVE` binding whose operation and
effect-authorization target are identical. Its common expiry cannot exceed the claim or binding.
The issued authority binds the exact tool authority, source effect authorization, execution claim,
provider binding, assurance profile, destination policy, execution identity, operation and exact
destination. Deterministic identities make exact replay converge; one issuance-authority lock makes
contention single-winner and conflicting reuse fail closed.

## Preserved perimeter

This batch does not activate or mutate a provider binding. It does not produce an activation lease,
issue or take custody of a credential capability, deliver a capability, resolve credentials, start
provider resolution or external I/O, or create a runtime producer or consumer outside the decision
and issuance route. No credential reference, credential secret or serialized capability is added
to either record. Iron Gate and Lazaretto remain closed, and Provider Execution Assurance remains
paused.
