# Canonical Native Effect Reconciliation Shared-Exclusion Remediation — Batch 2

`BATCH_2_COMPLETE_RECONCILIATION_DECISION_PUBLICATION_SHARED_EXCLUSION_PROVED`

The authorization service now resolves present source currentness and builds
the exact deterministic target while holding `NativeState::locked()`. It then
acquires the one issuance semantic-target lock and publishes the authority-empty
decision followed by its single-use issuance authority. DP01 cannot admit a
cooperative native/source mutation between currentness and publication.

No provider, credential, callback, transport, or external I/O occurs in the
governed cut. Multi-host and hostile-writer exclusion remain deferred.
