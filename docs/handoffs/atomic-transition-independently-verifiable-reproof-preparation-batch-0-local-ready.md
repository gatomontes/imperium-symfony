# Atomic Transition Independently Verifiable Reproof Preparation Batch 0 local ready

## Pinned start

Start from clean `main` after the campaign-selection merge containing commit
`1ac9ede` (whose parent is merged main `4746f91`):

```bash
git switch main
git pull --ff-only origin main
git status --short --branch
```

Confirm the selected campaign is present:

```bash
git merge-base --is-ancestor 1ac9ede HEAD
test -f docs/handoffs/atomic-transition-independently-verifiable-reproof-campaign-ready.md
```

Read `docs/handoffs/atomic-transition-independently-verifiable-reproof-campaign-ready.md`
and every required source it names.

## Authorized work

Only Preparation Batch 0 may be performed. Inventory missing v1 case evidence,
current proof/verifier coupling, the v2 public/private/forbidden split,
provenance, crash and replay boundaries, separate execution and signing custody,
closure reachability and the smallest ordered v2 sequence.

Expected marker:
`PREPARATION_BATCH_0_COMPLETE_INDEPENDENTLY_VERIFIABLE_REPROOF_BOUNDARY_CLASSIFIED`.

Before committing Preparation Batch 0 locally, run its focused tests and the
campaign-selection boundary test:

```bash
php vendor/bin/phpunit \
  tests/Imperium/Runtime/AtomicTransitionIndependentlyVerifiableReproofCampaignReadyTest.php \
  tests/Imperium/Runtime/AtomicTransitionIndependentlyVerifiableReproofPreparationBatch0Test.php
```

The second test file is a required Preparation Batch 0 deliverable and does not
exist at campaign entry.

Do not implement v2, inspect a private receipt, execute a mission or verifier,
create or use signing material, invoke a provider, perform external I/O, handle
live credentials or capabilities, mutate runtime state, repair or replace v1,
admit v2, remove the closure qualification or close the campaign.

## New-chat prompt

> Continue Imperium from clean `main` after the campaign-selection merge
> containing commit `1ac9ede` (parent main `4746f91`). Confirm that
> `git merge-base --is-ancestor 1ac9ede HEAD` succeeds.
>
> Read `docs/handoffs/atomic-transition-independently-verifiable-reproof-campaign-ready.md`
> and every required source it names.
>
> Begin Atomic Transition Independently Verifiable Reproof Preparation Batch 0
> only. Inventory missing acceptance-case evidence, current proof/verifier
> coupling, the public/operator-local/forbidden evidence split, provenance,
> persistence and replay boundaries, execution and signing custody, closure
> consumers, and the smallest ordered v2 proof sequence.
>
> Do not inspect private material, implement v2, execute a mission or verifier,
> create or use signing material, invoke a provider, perform external I/O,
> handle live credentials or capabilities, mutate runtime state, repair or
> replace v1, admit v2, remove the qualification or close the campaign.
