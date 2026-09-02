# Provider Binding Successor Executable Atomic Transition Preparation Batch 0 local ready

## Pinned start

Start from synchronized clean `main` containing the campaign-selection merge:

```bash
git switch main
git pull --ff-only origin main
git status --short --branch
```

Confirm the campaign marker:

```bash
test -f docs/handoffs/provider-binding-successor-executable-atomic-transition-campaign-ready.md
grep -q 'PROVIDER_BINDING_SUCCESSOR_EXECUTABLE_ATOMIC_TRANSITION_CAMPAIGN_READY' \
  docs/handoffs/provider-binding-successor-executable-atomic-transition-campaign-ready.md
```

Read that handoff and every required source it names.

## Authorized work

Only Preparation Batch 0 may be performed. Inventory and classify the exact
executable entry point, principal and authority lineage, v3 admission boundary,
binding and successor state, stores and write set, lock and transaction
primitives, interruption cuts, separate-process contention support, replay,
expiry, revocation, recovery, receipt, reconstruction, secret exclusion,
platform assumptions and the final boundary before provider effects.

Expected marker:
`PREPARATION_BATCH_0_COMPLETE_EXECUTABLE_ATOMIC_TRANSITION_BOUNDARY_CLASSIFIED`.

Before committing Preparation Batch 0 locally, run:

```bash
php vendor/bin/phpunit \
  tests/Imperium/Runtime/ProviderBindingSuccessorExecutableAtomicTransitionCampaignReadyTest.php \
  tests/Imperium/Runtime/ProviderBindingSuccessorExecutableAtomicTransitionPreparationBatch0Test.php
```

The second test file is a required Preparation Batch 0 deliverable and does not exist at campaign entry.

Do not implement an executable transition, mutate runtime state, persist a live
journal, acquire a live transition lock, issue or consume authority, admit v3
execution, adopt a successor, change provider binding, handle credentials or
capabilities, invoke a provider, perform external I/O, start an effect, authorize
retry, or open Iron Gate or Lazaretto.

## New-chat prompt

> Continue Imperium from synchronized clean `main` after the Provider Binding
> Successor Executable Atomic Transition campaign-selection merge.
>
> Confirm that
> `docs/handoffs/provider-binding-successor-executable-atomic-transition-campaign-ready.md`
> contains
> `PROVIDER_BINDING_SUCCESSOR_EXECUTABLE_ATOMIC_TRANSITION_CAMPAIGN_READY`.
>
> Read that handoff and every required source it names.
>
> Begin Provider Binding Successor Executable Atomic Transition Preparation
> Batch 0 only. Inventory and classify the exact executable entry point,
> competent principal and decision-to-authority lineage, v3 execution-admission
> boundary, current binding and eligible successor state, persistence stores and
> combined write set, lock and transaction primitives, irreversible cuts,
> separate-process contention support, replay, expiry, revocation, recovery,
> durable receipt, read-only reconstruction, secret exclusion, platform
> assumptions, proof gaps and the smallest ordered implementation sequence.
>
> Classify every finding as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT`
> or `DEFERRED_BOUNDARY`. Produce the versioned inventory, completion handoff,
> focused documentary tests, and flow and Blackquill-ledger updates. Record all
> additionally followed source files in the reading ledger.
>
> Do not implement an executable contract or change runtime behavior. Do not
> persist a live journal, acquire a live transition lock, issue or consume
> authority, admit v3 execution, adopt a successor, change provider binding,
> create a live winner or receipt, handle credentials or capabilities, invoke a
> provider, perform external I/O, start an effect, authorize retry, or open Iron
> Gate or Lazaretto. Preserve `BOUND_INACTIVE`, `NOT_IMPLEMENTED` and
> `UNKNOWN_REPLAY_PROHIBITED`.
