# Provider Binding Successor Live Adoption campaign ready

## Authorization

Only Provider Binding Successor Live Adoption Preparation Batch 0 is authorized
for inventory and classification.

## Required sources

Read these sources completely before beginning Preparation Batch 0:

1. `docs/handoffs/provider-binding-successor-production-realization-campaign-complete.md`
2. `docs/provider-binding-successor-production-realization-batch-7-terminal-audit.md`
3. `docs/handoffs/provider-binding-successor-production-realization-batch-6-complete.md`
4. `docs/provider-binding-successor-production-realization-batch-6-adversarial-proof.md`
5. `docs/next-campaign-provider-binding-successor-live-adoption.md`
6. `docs/delegate-mission-flow.md`
7. `docs/delegate-mission-authority-consumption-matrix.md`
8. the production decision, creation-authority, atomic-creation winner, v3
   admission, adoption-decision, adoption-target and successor-to-v3 join
   contracts and validators;
9. the current principal-activation, provider-binding lifecycle, deterministic
   execution-claim and effect-start services and their focused tests; and
10. the deferred local-test ledger.

## Preparation result required

Preparation Batch 0 must classify every required live-adoption boundary as
`EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT` or
`DEFERRED_BOUNDARY`, state the smallest lawful batch sequence, and preserve
every non-authority.

## Non-authority

This handoff may not define a live-adoption runtime contract or change runtime
behavior. It may not decide or perform live adoption. It may not admit
execution. It may not issue or consume live-adoption authority. It may not
create or activate a live successor binding.

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
