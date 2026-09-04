# Canonical Native Effect Reconciliation Shared-Exclusion Remediation — Preparation Batch 0 complete

`PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_SHARED_EXCLUSION_RACES_CLASSIFIED`
`PRODUCTION_CORRECTION_NOT_AUTHORIZED`
`BATCH_1_NOT_AUTHORIZED`
`REMOTE_PUBLICATION_NOT_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

The accepted base has a coherent native mutation exclusion, but reconciliation
issuance and claim-use target locks do not participate in it. CU01 is
deterministically reproduced with process barriers and real accepted writers:
native revocation and source suspension each commit after capability resolution
and before stale claim consumption/publication. DP01 is an `ORDERING_HAZARD`,
but the accepted base lacks an operational issuance-decision publisher and a
validation/publication checkpoint. IU01 is a `DEFERRED_BOUNDARY` because only
constants-only future issuance custody contracts exist.

## Smallest proposed repair sequence — five remaining stages

1. Batch 1: define the shared-exclusion and global lock-order contract,
   including non-reentrant entry and shared-before-target order.
2. Batch 2: implement decision currentness/construction/publication under the
   shared exclusion and prove DP01 refusal.
3. Batch 3: move issuance-use and claim-use currentness, consumption and
   publication under shared-before-target ordering; prove IU01/CU01 refusal.
4. Batch 4: adversarial process, interruption, contention and Windows/Linux
   proof with explicit distributed/hostile-writer exclusions.
5. Batch 5: separately sequenced terminal audit from clean merged Batch 4.

No production source, issuer, resolver, capability, state, consumption,
corridor, container or provider behavior changed. All runtime records created
by the harness live only beneath disposable test roots and are removed during
teardown. No provider, credential, network, external-I/O, mission, email, Iron
Gate, Lazaretto, live-trial or remote action occurred.

Local PHPUnit evidence:

- focused preparation class: `27 tests / 151 assertions` in `00:00:08.438`,
  passed after correcting a fixture-only payload/idempotency mismatch;
- complete suite: `2544 tests / 51719 assertions` in `00:05:37.846`, passed.

Preparation stops here; Batch 1 is not authorized.
