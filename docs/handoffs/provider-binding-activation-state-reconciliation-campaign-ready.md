# Provider Binding Activation State Reconciliation campaign ready

## Result

CAMPAIGN_READY_PREPARATION_BATCH_0_ONLY

Provider Binding Activation State Reconciliation is selected to reconcile the
existing operation-scoped activation evidence with the canonical implementation
binding that remains BOUND_INACTIVE. Selection grants no activation, credential,
provider, external-I/O, retry, Iron Gate or Lazaretto authority.

## Required sources

Read all of these before beginning Preparation Batch 0:

1. `docs/next-campaign-provider-binding-activation-state-reconciliation.md`;
2. `docs/handoffs/provider-effect-principal-binding-activation-resumption-campaign-complete.md`;
3. `docs/provider-effect-principal-binding-activation-resumption-batch-6-terminal-audit.md`;
4. `docs/provider-effect-principal-binding-activation-preparation-inventory.md`;
5. `docs/handoffs/provider-effect-principal-binding-activation-preparation-batch-0-complete.md`;
6. `docs/provider-binding-activation-capability-custody-preparation-inventory.md`;
7. `docs/handoffs/provider-binding-activation-capability-custody-campaign-terminal-refusal.md`;
8. `docs/handoffs/provider-execution-effect-readiness-campaign-complete.md`;
9. `docs/delegate-mission-flow.md`;
10. `src/Imperium/Runtime/LaCortine/ProviderImplementationBindingContract.php`;
11. `src/Imperium/Runtime/LaCortine/SingleOperationProviderBindingActivationIssuanceService.php`;
12. `src/Imperium/Runtime/LaCortine/ProviderBindingActivationRevocationWinnerService.php`;
13. `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationService.php`;
14. `tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationResumptionBatch6TerminalAuditTest.php`; and
15. `docs/deferred-local-test-ledger.md`.

## Authorized preparation

Inventory and classify only: binding lifecycle vocabulary and ownership,
operation-scoped activation evidence versus durable binding state, the exact
active principal prerequisite, competent activation and revocation authorities,
provider assurance, activation-to-effect ordering, replay, contention, expiry,
revocation, crash recovery, reconstruction, secret exclusion, capability
non-authority, live migration gaps, candidate boundary postures and refusal
conditions.

Do not define a runtime contract, change runtime behavior, activate a provider
binding, issue or consume authority, handle or resolve a credential or
capability, invoke a provider, perform external I/O, start a provider effect,
authorize retry, migrate a live consumer or command, or open Iron Gate or
Lazaretto.

The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.

## New-chat prompt

> Continue Imperium from `main` after the merge commit that prepares Provider
> Binding Activation State Reconciliation. Read
> `docs/handoffs/provider-binding-activation-state-reconciliation-campaign-ready.md`
> and every required source it names. Begin Provider Binding Activation State
> Reconciliation Preparation Batch 0 only. Inventory and classify binding
> lifecycle vocabulary and ownership, operation-scoped activation evidence
> versus durable implementation-binding state, the exact active executor
> principal prerequisite, competent activation and revocation authorities,
> provider assurance, activation-to-effect ordering, replay, contention, expiry,
> revocation, crash recovery, reconstruction, secret exclusion, process-local
> capability non-authority, live migration gaps, candidate boundary postures and
> refusal conditions. Do not define runtime contracts, change runtime behavior,
> activate a provider binding, issue or consume authority, handle or resolve a
> credential or capability, invoke a provider, perform external I/O, start a
> provider effect, authorize retry, migrate a live consumer or command, or open
> Iron Gate or Lazaretto. Preserve `UNKNOWN_REPLAY_PROHIBITED`.
