# O2-B0 implementation — source and evidence review

Verdict: HOLD — O2-B0 requires currentness/revocation corrections before acceptance or merge. Four reviewer regression requirements exercise three concrete findings below. Fresh hosted execution results are recorded at the end of this report and in review-identities.json. No O2-B0 runtime has been merged into main by this review.

Reviewer provenance: this reviewer authored earlier O0/O1 and O2 preparation material. This is an attributed source/evidence review, not a claim of independent authorship. CY/FC accepted offline scope and integrated O1 remain accepted.

## Concrete blockers

### R1 — signed-mode slot expiry is not enforced

Admission::signedTerms matches effect, signed_act mode and the declared object reference, then returns without checking slot expires_at. Admission::retain calls it for a new dependent signed act. Resolver::current calls the same helper without any signed-slot expiry check. The policy_effect branch, by contrast, explicitly checks expiry.

A policy may legally have a slot expiring before the policy. With a signed evidence slot expiring at t+1 and a policy/act valid until t+1800, both a fresh dependent admission at t+2 and current resolution of an earlier admission can pass. All execution flags being false prevents immediate dispatch but does not make the claimed current static authorization correct.

Required correction: resolve the exact matching signed slot and enforce its current half-open validity in both admission and current resolution using the trusted clock under the owning lock. Keep historical recognition separate. Preserve the approved policy and allowlist; do not lengthen slot validity or substitute a different slot to make the tests pass.

Reviewer tests: testExpiredSignedSlotCannotAuthorizeNewAdmission and testSignedSlotExpiryStopsCurrentResolution.

### R2 — resupplied supporting bytes bypass retained-source revocation during admission

Admission::retain's traversal chooses an incoming bundle record before consulting retained storage. If the record is already retained, this bypasses AuthorityStore::checkSource and its original admission/revocation checks. The write loop subsequently preserves the first provenance entry.

Reproduction: admit policy A and its supporting originals; revoke A's signing act; sign policy B against the current head while resupplying the exact same supporting bytes. Incoming traversal accepts those bytes and commits B, while the retained sources still point to A's revoked admission. This admits a new dependent original over a chain that current resolution cannot authorize. The defect requires no storage tampering, forged signature or direct map insertion.

Required correction: when a supplied reference already exists, check its exact retained provenance and currentness under the same lock before allowing new dependent admission. Identical bytes must not conceal a revoked source or replace the first provenance. Refuse without publishing a frame. Any future separately authorized re-admission/recovery scheme must be explicit; none is selected here.

Reviewer test: testResuppliedSourcesCannotBypassRetainedRevocation. Also add the contract-required revocation/admission ordering coverage, including a concurrent or deterministically coordinated producer case.

### R3 — an expired policy can still resolve as current policy authorization

Policy admission permits a policy lifetime shorter than its signing act. Resolver::current for signed AUTHORIZE_BOOTSTRAP_POLICY verifies the act and source chain, but neither that branch nor AuthorityStore::checkSource checks the policy body's own expiry. Once the policy expires, the longer-lived act can therefore produce CURRENT_STATIC_AUTHORITY_PENDING_EXECUTION for the expired policy.

Some dependent paths separately check policy expiry, so this finding does not claim provider execution or universal authorization bypass. It is nevertheless a wrong current-authority result and violates the contract's current-policy boundary.

Required correction: enforce the original policy's own validity wherever a current policy/source authorization is returned. Preserve historical admission recognition without a write. Test before/at/after the boundary with deliberately different policy and act expiries.

Reviewer test: testExpiredPolicyDoesNotResolveAsCurrentAuthorization.

### R4 — new runtime candidates are missing from the explicit coverage inventory

Fresh full CI also fails TransactionalAuthorityConsumptionBatch12CoverageTest::testMechanicalRuntimeCoverageMatchesTheFrozenBatch12Snapshot and FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest::testExplicitlyInventoriedCandidateAdditionPasses. Both diffs identify the same two new candidates: AuthorityAdmission/Admission.php and AuthorityAdmission/Resolver.php. Their authority_consumed vocabulary is deliberately detected even though the value is false. The review test file is outside src and does not cause this candidate mismatch.

Required correction: explicitly classify these two paths in the existing supported post-Batch12 inventory mechanism with truthful dormant/admission-only, no-consumption semantics and named tests. The coverage reader already loads docs/citadel-mission-formation-runtime-inventory-v1.tsv as an additive inventory. Preserve frozen historical snapshots, classifications/counts and exact tripwire behavior. Do not hide the paths, rename false authority flags to evade scanning, regenerate the frozen baseline or loosen assertions. Run both failing suites and the inventory/adversarial tripwire regressions after the change.

## What is sound within inspected scope

The changeAtHead adapter keeps the predecessor head and mutable state under the existing citadel-formation lock, preserving the state-only change interface and frame publication mechanics. Its source does not introduce a read/change gap or a nested lock.

Incoming acts do not enroll keys. Enrollment is a separate deployment-admin API requiring configured instance/Citadel/Operator identity and independent fingerprint confirmation; genuine administrative custody/competence remains a deployment prerequisite. Formation/native trust competence is not imported. New services have Exclude and no operational wiring.

Strict JSON rejects duplicate decoded keys, ambiguous objects, unsupported numbers, invalid encoding and excessive size/depth. Ed25519 verification binds the canonical payload and exact object. Source identity and raw bytes are retained separately. Historical recognition and static authorization are distinct, and no B0 receipt claims F2 execution/consumption. Those boundaries remain useful; the findings above concern missing currentness checks inside them.

Selected FRESH/DeepSeek/API-key, D2-A and P1–P9 remain fixed. Target-role selection uses medium capacity; the initial Augur base uses least-cost eligibility. Three retries means four attempts per W1/W2/W3 and twelve total, bounded by $0.10 per attempt/$1.20 total; the actual safe-retry allowlist remains empty. Persistent assignments remain operator-controlled. DEFER_ENROLLMENT, unresolved B1 remote guarantees and five false authority flags remain. No provider facts, credentials, installed authority or live commissioning were verified or activated here.

The policy/source wrappers retain signed public intent; generic retained adapter, Profile, budget or source text is not a genuine institutional producer or ready-to-execute authority. O2-B1 and later consumers still have to resolve those typed dynamic prerequisites. No later-stage implementation was begun.

## Source and packet identities

| Role | Identity |
| --- | --- |
| Entry / integrated preparation | bf8e60d7b6bfc28cbb781e8615c2e3a90a48f36a |
| Entry tree | 23e9f6c3a0d5c1e7639f03c241dc032ce16aecf8 |
| Uploaded author commit | 0fbd6eb876e8ccbbdf6586dff1ea420e6f689f47 |
| Uploaded author tree | dd755d1810470374175957a63f2d0dc382587630 |
| Local reviewer test commit | a2e06be503814f1bad31083cd4d357b55dc0e0c8 |
| Published draft review commit | 3170f6fb2e11f1fae8158ae751aac84ac45c9e62 |
| Review tree | 48b2cbb324285d46068eb50c6fd62b9079f91ddc |

The reviewer changed no submitted runtime. One new regression test file is the entire author-to-review source difference. The local bundle preserves author/reviewer history; the published commit imports the same review tree on the current main parent. [Draft PR #788](https://github.com/gatomontes/imperium-symfony/pull/788) is for review and correction, not accepted integration.

## Checks rerun here

- ZIP CRC, unique safe paths, exact complete inner/outer membership, inner SHA-256 and all 48 manifest payloads: PASS; 51 outer entries.
- Git entry/final identities and ancestry, all 18 changed canonical paths and source copies, exact binary patch and 51 protected canonical hashes: PASS.
- Twenty local documentation links and diff whitespace checks: PASS.
- All 86 Python specification checks: PASS. These are static/abstract examples, not PHP admission or concurrency proof.
- Imported bundle verification and published/tested tree comparisons: recorded in the accompanying identity/log files.

## Uploaded evidence inspected

The author reports PHP 8.4.14, locked PHPUnit 13.3.0; focused suites 128 tests/218 assertions; O1/source-pin regressions 380/1,519; targeted legacy compatibility 4/27; 14 PHP lint checks and kernel boot. These total 512 tests/1,764 assertions, with the standalone process log a subset rather than additional tests. Logs report those results. Initial fixture errors and the stopped broader regression run are retained, not counted as passing.

The every-registry-supply-mode test exercises Rules::effect directly. It proves that helper’s allowlist matrix, not producer-to-consumer success for every F2 mode/effect. Unsupported institutional effects remain refused.

The process evidence covers two real admissions at one head, exit inside a pre-publication journal callback, and exit after actual admission plus replay. It does not establish power-loss/fsync behavior or every crash point; the author correctly limits that claim. The uploaded process suite does not include the required revocation-versus-admission ordering case.

Post-check evidence limitation: fourteen tested-PHP hashes agree between the author audit and identity file, but five PHP checkout hashes differ from canonical Git hashes. Seven paths differ overall, including documentation. Two PHP differences can be reproduced by converting canonical LF to CRLF; the others cannot be reconstructed by that uniform conversion. Only canonical copies are supplied. Therefore, the claim that no substantive PHP changed after local tests remains author-attributed, not independently established from raw tested bytes. This is not by itself proof of source alteration. Fresh CI below tests the exact published canonical tree, resolving its execution attribution without rewriting the historical claim.

No PHP or Composer ran in this scratch environment. Prior preparation/B2 CI was inspected and verified against retained bytes; it is not proof of new O2 runtime. Fresh hosted execution below is separately attributed.

## Next executable action

Correct R1–R4 in the isolated O2-B0 worktree using the published draft branch and reviewer regression file. Run the reviewer cases first, then required focused/O1/journal/trust/source-pin checks, and return a source-bound packet. Include revocation/admission ordering coverage and exact tested-source bytes or an exact committed-tree test identity.

O2-B0 remains open. Six implementation batches remain unaccepted across O2–O5; this corrective pass stays inside the already selected O2-B0. Full repository CI and source review must pass before merge. O2-B1 and live commissioning remain deferred.

## Fresh hosted execution and final disposition

[CI run 34512596315](https://github.com/gatomontes/imperium-symfony/actions/runs/34512596315), job 102990145524, completed on PHP 8.4.25 / PHPUnit 13.3.0: **3,453 tests, 55,451 assertions, 6 failures, 4 skips**, elapsed 08:23.943. All four new reviewer assertions failed because the operations returned without refusal, confirming R1–R3 through real synthetic producer/admission/resolver paths. The other two failures are the explicit runtime-inventory gap R4. These are failures of the requirements, not successful correctness checks.

The logged checkout is `2d6b6c00b52c01a6c39c2891590a16d781c75315`. Its tree equals published `3170f6fb2e11f1fae8158ae751aac84ac45c9e62` and local reviewer tree `48b2cbb324285d46068eb50c6fd62b9079f91ddc`. Runtime bytes equal the uploaded author tree; only reviewer tests were added. No source changed after CI. Full logs and identities are retained.

Draft PR #788 remains open and unmerged. A fresh fetch verified main still equals `bf8e60d7b6bfc28cbb781e8615c2e3a90a48f36a`. The correction prompt includes all four findings. O2-B0 is not accepted, and no later batch or live commissioning is enabled.
