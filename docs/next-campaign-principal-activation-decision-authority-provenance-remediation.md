# Next campaign: Principal Activation Decision Authority Provenance Remediation

## Status

CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY

Provider Effect Principal and Binding Activation is paused after
BATCH_2_TERMINAL_AUDIT_REFUSED_UNPROVEN_DECISION_AUTHORITY_PROVENANCE.

The Batch 1 combined consumption-and-activation mechanism is retained as
mechanically sound. This remediation must prove the competent origin, canonical
custody and exact consumption path of the decision authority it accepts.

Only Preparation Batch 0 is authorized.

## Preparation questions

Preparation must inventory:

1. the authority owner competent to grant the exact principal-activation
   decision scope;
2. the exact canonical active Imperator principal generation and lifecycle;
3. whether the current generation may hold the required scope;
4. the lawful successor-generation route if scope widening is required;
5. the exact decision and issuance caller-authority transitions;
6. the canonical decision producer and immutable custody path;
7. source-authority resolution, consumption and target identity;
8. contention, expiry, revocation, supersession and crash recovery;
9. read-only reconstruction from Operator Root through activation;
10. secret exclusion and non-authorities; and
11. the return gate to the paused principal-and-binding campaign.

Every requirement must be classified as EXISTS_CANONICALLY,
EXISTS_FRAGMENTED, ABSENT or DEFERRED_BOUNDARY.

## Prohibited work

Preparation may not grant scope, create a successor, activate a principal or
binding, issue or consume a decision or authority, modify the Batch 1 activation
winner, handle a credential or process-local capability, define a live-call
contract, invoke a provider, perform external I/O, authorize retry, migrate a
consumer, or open Iron Gate or Lazaretto.

## Preparation Batch 0 result

Preparation Batch 0 is complete at
PREPARATION_BATCH_0_COMPLETE_OPERATOR_ROOT_SCOPE_SUCCESSOR_REQUIRED.

Operator Root is the only competent scope-grant owner. The canonical active v2
Imperator principal and lifecycle reconstruction exist, but its fixed scope does
not include provider-executor-principal activation-decision authority. The
current generation cannot self-widen.

Only Batch 1 may next be considered: separately versioned authority-empty
contracts for an Operator Root narrow-scope grant, a successor Imperator
principal generation, and later decision-issuance authorization. Contract
existence grants no scope, successor, authority, decision or activation.

The active handoff is
docs/handoffs/principal-activation-decision-authority-provenance-remediation-preparation-batch-0-complete.md.

## Batch 1 result

Batch 1 is complete at
BATCH_1_AUTHORITY_EMPTY_SCOPE_SUCCESSOR_AND_DECISION_ISSUANCE_CONTRACTS_COMPLETE.

The exact Operator Root narrow-scope grant, immutable successor generation held
at PENDING_ACTIVATION, and later active-successor-bound decision-issuance
authorization are now separately versioned authority-empty contracts. Existing
scope is preserved and the only scope delta is
provider_executor_principal_activation_decision_authority.

Only remediation Batch 2 may next be considered: pure fail-closed validators
and segregated immutable stores for caller-supplied offline fixtures of those
three contracts. No live principal, scope, authority, decision or activation is
created. Provider Effect Principal and Binding Activation remains paused.
Estimated remediation countdown: approximately six batches.

The active handoff is
docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-1-complete.md.

## Batch 2 result

Batch 2 is complete at
BATCH_2_FAIL_CLOSED_VALIDATORS_AND_IMMUTABLE_FIXTURE_STORES_COMPLETE.

Pure validators now enforce the exact scope-grant, pending-successor and later
decision-issuance-authorization lineage. Segregated immutable stores accept
only caller-supplied offline fixtures and are not a live registry.

Only remediation Batch 3 may next be considered: disposable-root offline
interruption, replay, changed-evidence conflict and same-root contention proof.
No live principal, scope, authority, decision or activation is created.
Provider Effect Principal and Binding Activation remains paused.
Estimated remediation countdown: approximately five batches.

The active handoff is
docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-2-complete.md.
