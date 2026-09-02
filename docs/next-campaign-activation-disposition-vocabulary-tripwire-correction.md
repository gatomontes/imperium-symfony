# Activation Disposition Vocabulary Tripwire Correction

## Selection

`ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_SELECTED`

This campaign corrects the material defect identified by the post-PR #730
Blackquill review: activation-disposition discovery recognizes only
single-quoted PHP string literals, so an unapproved producer using another valid
encoding can evade the exact role-classified inventory.

## Controlling defect

The current detector searches source text for:

- `'QUARANTINED_PENDING_REMEDIATION'`; and
- `'RETIRE_CORRIDOR'`.

That is an encoding check, not a vocabulary-producer check. Double-quoted
literals are the demonstrated escape. Concatenation, constant indirection,
comments and unrelated string occurrences require explicit classification so
the repaired claim is neither porous nor inflated.

The prior campaign closure is requalified as:

`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CLOSURE_REJECTED_WITH_MATERIAL_VOCABULARY_TRIPWIRE_GAP`.

The controlling independent-verification posture remains:

`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

Every active handoff in this correction campaign must state that posture explicitly.

## Batches

### Preparation Batch 0 — detector-semantics inventory

Inventory the exact governed tokens, every current producer, the lexical forms
the runtime permits, false-positive surfaces, dynamic-construction limits,
inventory coupling, disposable-root behavior and current adversarial coverage.

Result required:

`PREPARATION_BATCH_0_COMPLETE_VOCABULARY_DETECTOR_SEMANTICS_CLASSIFIED`.

This batch may change documentation and preparation tests only.

### Batch 1 — mechanical detector correction

Replace quote-sensitive matching with a deterministic quote-independent
mechanism. Prefer PHP token inspection that identifies complete string-literal
values without treating comments as producers. Preserve exact path equality
against the versioned six-role inventory.

Mutation proof must include:

- single-quoted governed value fails when unclassified;
- double-quoted governed value fails when unclassified;
- comments containing a governed token do not become producers;
- unrelated larger strings are classified according to the documented exact
  value contract;
- concatenation and constant indirection receive explicit fail-closed or
  out-of-contract dispositions;
- all six currently classified producers remain detected; and
- an explicitly classified synthetic producer passes.

Result required:

`BATCH_1_COMPLETE_QUOTE_INDEPENDENT_VOCABULARY_TRIPWIRE_PROVED`.

### Batch 2 — separately sequenced terminal Blackquill audit

Batch 2 may begin only after Batch 1 has been merged and the audit starts from
the resulting `main`. It must not be authored in the Batch 1 implementation
PR. It re-runs adversarial mutations against the actual detector and checks the
campaign's narrow claim and exclusions.

Only this batch may decide whether the rejected tripwire-restoration closure can
be superseded by a corrected closure.

## Prohibited work

No batch may execute a Delegate mission, invoke a provider, perform external
I/O, handle a live credential or capability, mutate live runtime state, open
Iron Gate or Lazaretto, repair the historical operational-evidence audit, remove
the independent-verification qualification, or claim transactional correctness.

Runtime source under `src/Imperium/Runtime` is outside this campaign. Batch 1
may change test detection mechanics and versioned detector contracts only.

## Exit criterion

The campaign may close only when alternate valid PHP quoting cannot evade the
exact producer inventory, documented non-producer forms do not create accidental
matches, mutation tests exercise the actual detector, and an independently
sequenced terminal audit accepts the corrected claim.

## Completed campaign disposition

Preparation Batch 0, Batch 1 mechanical correction and the separately sequenced
Batch 2 audit are complete. Batch 2 started from merged local main at
`76d0803ae9931c4470b4f49af216a56a14c111c3` and passed at
`BATCH_2_TERMINAL_BLACKQUILL_AUDIT_PASSED_LITERAL_VOCABULARY_CLAIM`.
The campaign result is
`ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_COMPLETE`.

The accepted corrected closure is
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CORRECTED_CLOSURE_ACCEPTED_LITERAL_VOCABULARY_ONLY`.
It replaces the rejected closure only at the explicit literal-value boundary
in `docs/activation-disposition-vocabulary-tripwire-correction-detector-contract-v1.md`.
It does not prove arbitrary expression, constant-reference or dynamic producer
coverage. The rejection above remains historical evidence. No batch remains.
The active handoff is
`docs/handoffs/activation-disposition-vocabulary-tripwire-correction-campaign-complete.md`.
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling and is not removed.
