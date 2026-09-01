# Provider Binding Successor Atomic Live Transition campaign ready

## Authorization

Only Provider Binding Successor Atomic Live Transition Preparation Batch 0 is
authorized for inventory and classification.

## Required sources

Read these sources completely before beginning Preparation Batch 0:

1. `docs/handoffs/provider-binding-successor-live-adoption-campaign-complete.md`
2. `docs/provider-binding-successor-live-adoption-batch-7-terminal-audit.md`
3. `docs/handoffs/provider-binding-successor-live-adoption-batch-6-complete.md`
4. `docs/provider-binding-successor-live-adoption-batch-6-adversarial-audit.md`
5. `docs/next-campaign-provider-binding-successor-atomic-live-transition.md`
6. `docs/delegate-mission-flow.md`
7. `docs/delegate-mission-authority-consumption-matrix.md`
8. the live-adoption decision, authority, custody, combined-winner,
   interruption-proof, reconstruction and adversarial-audit contracts,
   validators and focused tests;
9. `AtomicTransition`, `AuthorityConsumptionStore`,
   `ImmutableRecordStore`, `MutableStateStore`, provider-binding lifecycle
   services and their focused tests; and
10. the deferred local-test ledger.

## Preparation result required

Preparation Batch 0 must classify every required executable transition boundary
as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT` or
`DEFERRED_BOUNDARY`, identify the first irreversible write and smallest lawful
batch sequence, and preserve every non-authority.

The planning estimate is nine batches including Preparation Batch 0. The
inventory may require refusal or correction batches.

## Non-authority

This handoff may not define an executable runtime contract or change runtime
behavior.
It may not produce a decision.
It may not issue or consume live authority.
It may not admit execution.
It may not adopt a successor or change binding state.
It may not handle or resolve a credential or capability.
It may not invoke a provider.
It may not perform external I/O.
It may not start a provider effect.
It may not authorize retry.
It may not migrate a live command.
It may not open Iron Gate or Lazaretto.

The provider binding remains BOUND_INACTIVE.
The v3 execution admission remains NOT_IMPLEMENTED.
UNKNOWN_REPLAY_PROHIBITED remains binding.
