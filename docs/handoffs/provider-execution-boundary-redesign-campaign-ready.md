# Provider Execution Boundary Redesign campaign ready

## Result

`CAMPAIGN_READY_PREPARATION_BATCH_0_ONLY`

Provider Execution Boundary Redesign is selected to examine the architecture identified by the
Blackquill review. Selection grants no execution, binding-activation, credential-use, provider-call
or external-I/O authority.

## Required sources

Read all of these before beginning Preparation Batch 0:

1. `docs/provider-execution-boundary-redesign-blackquill-review.md`;
2. `docs/next-campaign-provider-execution-boundary-redesign.md`;
3. `docs/handoffs/provider-binding-activation-capability-custody-campaign-terminal-refusal.md`;
4. `docs/provider-binding-activation-capability-custody-preparation-inventory.md`;
5. `docs/provider-execution-assurance-reconsideration-preparation-inventory.md`;
6. `docs/handoffs/provider-execution-assurance-reconsideration-preparation-batch-0-complete.md`;
7. `docs/iron-gate-runtime-principal-caller-authority-and-integrity-threat-model.md`;
8. `docs/delegate-mission-flow.md`;
9. `src/Imperium/Runtime/LaCortine/CredentialCapability.php`;
10. `src/Imperium/Runtime/LaCortine/CredentialBroker.php`;
11. `src/Imperium/Runtime/LaCortine/EnvironmentCredentialBroker.php`;
12. `src/Imperium/Runtime/Clavium/DeterministicJournalBoundCredentialBroker.php`;
13. `src/Imperium/Runtime/Clavium/ProviderBoundCredentialEligibilityService.php`;
14. `tests/Imperium/Runtime/ProviderBindingActivationCapabilityCustodyBatch4Test.php`; and
15. `tests/Imperium/Runtime/DeterministicBoundaryExecutorTest.php`.

## Authorized preparation

Inventory and classify only: credential ownership, execution authority, process principals,
provider-binding activation, atomic pre-I/O admission, effect-start ordering, crash recovery,
replay, contention, expiry, revocation, reconstruction, secret exclusion, trust assumptions,
candidate execution boundaries and non-authorities.

Do not define runtime contracts, change runtime behavior, activate a principal or binding, issue or
consume authority, handle a credential or capability, invoke a provider, perform external I/O,
migrate a live command, or open Iron Gate or Lazaretto.

## New-chat prompt

> Continue Imperium from `main` after the merge commit that prepares Provider Execution Boundary
> Redesign. Read `docs/handoffs/provider-execution-boundary-redesign-campaign-ready.md` and every
> required source it names. Begin Provider Execution Boundary Redesign Preparation Batch 0 only.
> Inventory and classify credential possession versus execution authority, durable authority versus
> process-local capability identity, the credential-owning execution boundary, exact executor
> principal, provider-binding activation, atomic authority consumption and effect-start ordering,
> crash recovery, replay, contention, expiry, revocation, reconstruction, secret exclusion, threat-
> model alignment, candidate boundary postures and non-authorities. Do not define runtime contracts,
> change runtime behavior, activate a principal or binding, issue or consume authority, handle a
> credential or capability, invoke a provider, perform external I/O, migrate a live command, or open
> Iron Gate or Lazaretto.
