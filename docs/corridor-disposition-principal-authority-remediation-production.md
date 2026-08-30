# Corridor Disposition Principal Authority Remediation production

`BATCH_5_SEPARATELY_AUTHORIZED_SCOPE_REMEDIATION_PRODUCER_COMPLETE`

The producer consumes exact Operator Root scope-grant authority and atomically commits one immutable
next-generation principal at `PENDING_ACTIVATION`. Its preserved authority remains unchanged and its
only scope addition is corridor disposition. The source principal is not rewritten or superseded.

A separate activation disposition must then be consumed and durably recorded. The pending principal
record is never mutated; lifecycle reconstruction alone establishes effective `ACTIVE` status. Only
after that separate activation may a further exact issuance authorization be consumed to create one
exact corridor caller authority for one target, dossier, eligibility record, and candidate.

Grant, activation, and issuance consumption are single-use and contention-safe. Grant and issuance
windows enforce expiry and revocation fails closed. Exact replay converges through immutable records;
changed evidence or competing consumers refuse. No disposition is selected or sealed, no binding or
activation artifact is mutated, and `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remains authoritative.
