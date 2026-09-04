# Canonical Native Effect Reconciliation Shared-Exclusion Remediation — Batch 1 contract v1

`BATCH_1_COMPLETE_RECONCILIATION_SHARED_EXCLUSION_CONTRACT_DEFINED`

The canonical cooperative single-host order is native shared exclusion, then
one semantic target lock, then its immutable publication locks. Currentness and
use belong to one exclusion cut. A target lock is winner serialization, not a
substitute for mutation exclusion. Reverse acquisition, reentry, target
nesting, and external I/O under a governed lock are prohibited.

Interruption before consumption leaves no output. Interruption after
consumption may finish only the identical deterministic publication; any
changed decision, lineage, target, issuer, or validity window conflicts.
Distributed filesystems, other hosts, and hostile writers remain outside the
cooperative proof boundary.
