# Activation Disposition Vocabulary Tripwire Correction Preparation Batch 0 local ready

## Continuation point

Start from merged `main` at or after:

`14bd7e5bbc9b6bbcf8f2ea44df105a25c5dca9c0`

Campaign state:

`ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_CAMPAIGN_READY`

Next authorized result:

`PREPARATION_BATCH_0_COMPLETE_VOCABULARY_DETECTOR_SEMANTICS_CLASSIFIED`

Only Preparation Batch 0 may be performed.

## Local synchronization

From the repository root:

```powershell
git switch main
git pull --ff-only origin main
git status --porcelain
git rev-parse HEAD
php vendor/bin/phpunit tests/Imperium/Runtime/ActivationDispositionVocabularyTripwireCorrectionCampaignReadyTest.php
```

The status command must produce no tracked or untracked output before campaign
work begins. If local-only configuration is present, preserve it outside the
campaign commit.

## Required reading

Read every source below before changing files:

1. `docs/handoffs/activation-disposition-vocabulary-tripwire-correction-campaign-ready.md`
2. `docs/next-campaign-activation-disposition-vocabulary-tripwire-correction.md`
3. `docs/frozen-runtime-coverage-tripwire-restoration-batch-3-blackquill-audit.md`
4. `docs/handoffs/frozen-runtime-coverage-tripwire-restoration-campaign-complete.md`
5. `docs/frozen-runtime-coverage-tripwire-restoration-activation-disposition-exceptions-v1.tsv`
6. `tests/Imperium/Runtime/ProviderBindingActivationIntegrityRemediationBatch6Test.php`
7. `tests/Imperium/Runtime/FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest.php`
8. `docs/delegate-mission-flow.md`
9. `todo/blackquill-todos.md`

## Preparation Batch 0 deliverables

Inventory and classify:

- both governed disposition values and every current runtime occurrence;
- single-quoted, double-quoted and alternate lexical representations;
- exact-value, substring, interpolated and concatenated forms;
- constant indirection and dynamically assembled values;
- comments, documentation strings and other non-producer text;
- the six-role inventory's coupling to the detector;
- disposable-root behavior and mutation-test reuse;
- present positive and negative test coverage;
- every demonstrated or plausible acceptance escape; and
- the smallest safe Batch 1 detector and adversarial-proof boundary.

Produce documentation and preparation tests only. Record a Batch 0 completion
handoff that authorizes Batch 1 only if the inventory is complete.

## Prohibited work

Do not repair the detector, change runtime production source, execute a mission,
invoke a provider, perform external I/O, handle a live credential or capability,
mutate live runtime state, open Iron Gate or Lazaretto, repair the historical
audit, create the terminal Blackquill audit, supersede the rejected closure, or
remove:

`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

## New-chat prompt

```text
Continue Imperium from main after merged commit 14bd7e5bbc9b6bbcf8f2ea44df105a25c5dca9c0.

Read docs/handoffs/activation-disposition-vocabulary-tripwire-correction-preparation-batch-0-local-ready.md and every required source it names.

Begin Activation Disposition Vocabulary Tripwire Correction Preparation Batch 0 only.

Inventory the governed disposition values, current producer roles, PHP lexical forms, exact-value and substring semantics, comments and non-producer text, concatenation and constant indirection, disposable-root coupling, mutation coverage gaps, and the smallest safe Batch 1 detector boundary.

Do not repair the detector, change runtime production source, execute a mission, invoke a provider, perform external I/O, handle a live credential or capability, mutate live runtime state, repair the historical audit, create a terminal audit, supersede the rejected closure, or remove the independent-verification qualification.
```

## Exit condition

The local preparation batch is complete only when its inventory is versioned,
its classifications are executable through preparation tests, and its handoff
authorizes no work beyond Batch 1 mechanical correction.
