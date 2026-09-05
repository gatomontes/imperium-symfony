# Canonical mission authority — post-Batch-3 review disposition

Reviewed candidate: `8df34679beab0ba8699a68fdd458570bf658c4c8`.
Branch: `codex/canonical-mission-authenticity-real-snapshot-remediation`.
Disposition: QUARANTINED; Operator refusal accepted; implementation acceptance withheld.

Source-review findings requiring reproduction and correction:

- MissionLifecycleStore::consume accepts public, caller-constructible authorization/capability objects without authentication or signature verification at its own public write boundary.
- MissionCapabilityKeyStore::existing and initialize expose raw HMAC signing material. Custody separation is not established by a Runtime class name or same-user file permissions.
- OperatorApprovalAuthenticator trusts project-local operator-approval-trust.json. Protection against an actor able to replace that configuration is not demonstrated.
- CanonicalMissionTransitionService authenticates before the lifecycle store acquires exclusion. Coherent revocation/supersession-versus-consumption behavior is unproved.

These are source-based boundary findings, not a claim that remote exploits were executed. Severity depends on actual caller/process/filesystem reachability. The next campaign must distinguish trusted implementation from untrusted callers and state its deployment threat model. Arbitrary code already controlling the trusted process cannot be contained by PHP visibility alone.

The Git adapter's real object reads/re-hashing and independent-process contention harness are useful recoverable evidence, not blanket acceptance of budgets, deployment protection or end-to-end mission execution.

The local runner reported 2661 tests / 52390 assertions. No local suite was rerun by this source review. At review time the status API returned no statuses and the PR-workflow query returned no runs; that query alone does not prove absence of every possible workflow event. No remote CI acceptance is claimed.

The old human gate was correctly refused. The subsequent admission that enrollment, exact-payload export, external signing, authenticated submission and verification commands did not exist invalidates the claim that a runnable approval ceremony was ready. It does not authorize manufacturing trust or approval.

Preserve candidate and historical evidence; do not merge or delete it. Corrective selection:
`docs/next-campaign-protected-mission-authority.md`.
