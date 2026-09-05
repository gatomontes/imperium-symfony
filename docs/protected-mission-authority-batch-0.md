# Protected mission authority — Batch 0

Baseline: `0099c5f7e4008fe06cafc9c5dfa3458dc68f4db9` (verified local origin/main).
Local main remains `b267e2c2b6a122694418ce59d2bf16319e602b07`.
Preserved remediation: `8df34679beab0ba8699a68fdd458570bf658c4c8`.
Preserved older mission thread: `3c4890ffd30f403f72a35b92f1e639d51c8c98f8`.
Clean entry; no fetch required; no remote mutation. No applicable AGENTS.md found in repository or ancestor directories.

## Threat model and enforcement owner

Untrusted mission callers may construct arbitrary requests, DTOs, signatures, paths and timestamps,
and alter their own checkout. They must have no write access to installed runtime code, trust or
canonical state, and no read access to issuer secrets. The deployment owner installs those assets
under a separate Runtime account and owns bootstrap/recovery. Runtime code, its account, PHP,
OS administrators and the implementation agent are trusted. Arbitrary code executing inside that
account is outside the claimed boundary. Same-user tests cannot prove account isolation.

Proposed authoritative owner: fixed installed root; narrow serialized request protocol; no request
selects a root, verifier, clock, store, callback or signing key. Public trust enrollment is a separate
owner-only bootstrap operation with fingerprint confirmation. No automatic trust genesis or fallback.
Canonical planning/review/derivation execute inside the owner. The consumer obtains verification
material only. All challenge, revocation, supersession, issuance and consumption state uses one
common exclusion domain. A committed single state publication is the linearization point.
Crash-before-publication has no authority effect; crash-after-publication retains consumption.
Power-loss guarantees must be limited explicitly if the directory entry cannot be fsynced.

## Required source reads and claims

Read completely at baseline: the three requested campaign/review/handoff documents;
`contracts/mission-planning.md`, `contracts/mission-authorization.md`;
`PlanningDossierAssemblyService`, `ImperatorPlanningDossierReviewService`,
`MissionAuthorizationDerivationService`, `ProceedingStore`, `AtomicTransition`,
`ImmutableRecordStore`, `AuthorityConsumptionStore`; existing assembly/review tests;
Clavium `OpaqueCapabilityCustodyContract`, reconciliation authority and issuance custody contracts.
Read current top 150 lines of `docs/delegate-mission-flow.md`, README, composer and service wiring.
Canonical services live under `src/Imperium/Runtime/Curia`; persistence under `Persistence`.

At preserved SHA, read the lifecycle store, transition service, key store, approval authenticator,
plan and public DTOs, all four campaign test classes and preparation inventory.
The isolated reproduction script loads exact Git blobs without checking out or merging that branch.
It creates entirely new disposable values and proves direct unsigned, expired DTO consumption and
raw issuer key extraction. It neither reads historical keys nor inspects a real target.
Project-local trust replacement and pre-lock currentness remain source findings until reproduced;
do not report them as measured exploits merely from source order.

## Previous tests: provisional disposition of every campaign method

Historical result: **2661 tests / 52390 assertions**, reported in the controlling review.
It remains historical local evidence, not remote acceptance or proof of the new boundary.
All historical test source remains unchanged at the preserved SHA.

| Old method | Disposition | Required successor |
|---|---|---|
| Batch0 inventory freezes entry | INSUFFICIENT_PROOF | New source-bound baseline inventory |
| Batch0 mandatory Operator gate | SUPERSEDED_WITH_REPLACEMENT | New no-real-enrollment/execution gates |
| Batch1 exact persisted lineage | PORT_WITH_JUSTIFICATION | Fresh canonical ceremony, authenticated persisted chain |
| Batch1 fabricated actor/approval/plan | PORT_WITH_JUSTIFICATION | Changed bytes, identity and path refusals, state absence |
| Batch1 tamper/expiry/revoke/supersede | PORT_WITH_JUSTIFICATION | At-use checks inside common owner transaction |
| Batch2 complete capability tuple | PORT_WITH_JUSTIFICATION | Signed exact tuple and owner currentness |
| Batch2 constructor reflection | INSUFFICIENT_PROOF | Actual protocol substitution and deployment isolation distinction |
| Batch2 forged bindings | PORT_WITH_JUSTIFICATION | Authentication rejection plus no lifecycle residue |
| Batch2 fabricated authorization/key absence | PORT_WITH_JUSTIFICATION | No implicit issuer genesis; absent authority refuses |
| Batch3 exact Git objects | PORT_WITH_JUSTIFICATION | Preserve byte/object assertions, add bounds and no-lazy-fetch |
| Batch3 durable consumption/restart | PORT_WITH_JUSTIFICATION | Sole verified writer, terminal/replay/restart proofs |
| Batch3 independent contenders | PORT_WITH_JUSTIFICATION | Keep actual processes; add revoke/supersede/crash schedules |

No old dossier, approvals, trust, capabilities, runtime records or timestamps are imported.
No old tests have been weakened or deleted. This map describes intended adaptations, not completed
proof. Existing baseline tests are retained unchanged until a specific justified adaptation is recorded.

Historical transcript discovery searched repository docs/var filenames and `E:/ai/imperium` (empty).
No original full-suite transcript, terminal ledger or private handoff was located there. Two old
`imperium-canonical-auth-b3-*` temporary directories exist; their contents are not reused or modified.
The controlling review and preserved Git source are available evidence; absent transcripts must not
be fabricated. The old real mission remains blocked and unexecuted.
