# Canonical Consumer Integration Correction Preparation Batch 0 local ready

## Pinned start

Start from synchronized clean `main` containing the campaign-selection merge:

```bash
git switch main
git pull --ff-only origin main
git status --short --branch
```

Confirm the campaign marker:

```bash
test -f docs/handoffs/executable-atomic-transition-canonical-consumer-integration-correction-campaign-ready.md
grep -q 'EXECUTABLE_ATOMIC_TRANSITION_CANONICAL_CONSUMER_INTEGRATION_CORRECTION_CAMPAIGN_READY' \
  docs/handoffs/executable-atomic-transition-canonical-consumer-integration-correction-campaign-ready.md
```

Read that handoff and every required source it names.

## Authorized work

Perform Preparation Batch 0 only. Inventory exact established provider-
execution/effect-corridor entrypoints, all relevant descriptor readers and their
callers, current dependency injection and command wiring, operation/root identity,
native interpretation reachability, competing bypass paths, state classifications
and the smallest acyclic integration sequence.

Expected marker:
`PREPARATION_BATCH_0_COMPLETE_CANONICAL_CONSUMER_BYPASS_CLASSIFIED`.

Before committing Preparation Batch 0 locally, run:

```bash
php vendor/bin/phpunit \
  tests/Imperium/Runtime/ExecutableAtomicTransitionCanonicalConsumerIntegrationCorrectionCampaignReadyTest.php \
  tests/Imperium/Runtime/ExecutableAtomicTransitionCanonicalConsumerIntegrationCorrectionPreparationBatch0Test.php
```

The second test is a required Preparation Batch 0 deliverable and does not exist
at campaign entry. Run the full suite if Preparation changes any shared test
inventory or documentation tripwire.

Do not implement runtime integration, change service wiring, add a reader or
wrapper, invoke a mission or provider, perform external I/O, handle a live
credential/capability, provision or sign a Root act, issue or consume live
authority, write transition state, mutate a binding descriptor, authorize retry,
or open Iron Gate or Lazaretto. Preserve `BOUND_INACTIVE`, historical v3
`NOT_IMPLEMENTED` and `UNKNOWN_REPLAY_PROHIBITED`.

## New-chat prompt

> Continue Imperium from synchronized clean `main` after the Executable Atomic
> Transition Canonical Consumer Integration Correction campaign-selection merge.
>
> Confirm that
> `docs/handoffs/executable-atomic-transition-canonical-consumer-integration-correction-campaign-ready.md`
> contains
> `EXECUTABLE_ATOMIC_TRANSITION_CANONICAL_CONSUMER_INTEGRATION_CORRECTION_CAMPAIGN_READY`.
>
> Read that handoff and every required source it names. Follow and fully read the
> direct call sites and descriptor readers required to identify the actual
> downstream provider-execution/effect corridor. Record every additional source
> in the reading ledger.
>
> Begin Executable Atomic Transition Canonical Consumer Integration Correction
> Preparation Batch 0 only. Inventory and classify every established downstream
> entrypoint that can interpret a provider binding or approach provider effect;
> its exact callers, descriptor reads, operation and replay-root identity,
> dependency/container wiring, native-reader reachability, competing bypasses,
> inactive/current/noncurrent/incomplete/corrupt behavior, and the smallest
> acyclic route that makes one canonical interpretation unavoidable for the exact
> operation while preserving unrelated legacy meanings.
>
> Classify each finding as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT` or
> `DEFERRED_BOUNDARY`. Produce the versioned inventory, completion handoff,
> focused documentary tests, Delegate-flow update and Blackquill-ledger update.
> The expected marker is
> `PREPARATION_BATCH_0_COMPLETE_CANONICAL_CONSUMER_BYPASS_CLASSIFIED`.
>
> Do not accept the new command consuming `NativeBindingReader` as proof of
> downstream integration. Do not add another wrapper, rename a route canonical,
> rely on search results without reading selected files, declare historical
> readers unrelated without call-graph evidence, or test only direct service
> construction. Do not implement runtime integration or change service wiring.
> Do not invoke a mission or provider, perform external I/O, handle a live
> credential/capability, provision or sign a Root act, issue or consume live
> authority, write transition state, mutate a binding descriptor, authorize
> retry, or open Iron Gate or Lazaretto. Preserve `BOUND_INACTIVE`, historical v3
> `NOT_IMPLEMENTED` and `UNKNOWN_REPLAY_PROHIBITED`.
