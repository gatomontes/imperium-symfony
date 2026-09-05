> Superseded local entry: use `docs/handoffs/mission-amendment-correction-local-ready.md`.
> The completed candidate is preserved for focused AM01/AM02 correction under
> `docs/next-campaign-mission-amendment-correction.md`. Do not restart this old runner.

# Protected Mission Authority and Operator Ceremony — corrective campaign

`PROTECTED_MISSION_AUTHORITY_CAMPAIGN_SELECTED`
`LOCAL_IMPLEMENTATION_BATCHES_0_THROUGH_5_AUTHORIZED`
`REAL_OPERATOR_ENROLLMENT_AND_MISSION_EXECUTION_NOT_AUTHORIZED`

## Outcome and scope

Deliver a locally runnable, end-to-end approval ceremony and one mandatory authority-consumption boundary, with explicit enforcement assumptions and adversarial evidence. A missing human signature is a valid final deployment gate; a nonexistent command must not be presented as an executable next step.

Selection is documentation-only from accepted main `b267e2c2b6a122694418ce59d2bf16319e602b07`. Begin implementation from the main commit that merges this selection, recording that exact SHA. Accepted runtime baseline remains `2527b33925bf3ef47d029786e60a6aefe752737b`; do not confuse it with the newer documentation entry.

Quarantined evidence: branch `codex/canonical-mission-authenticity-real-snapshot-remediation`, commit `8df34679beab0ba8699a68fdd458570bf658c4c8`. No acceptance, merge, deletion, force-push, wholesale cherry-pick or execution of its selected reference mission is authorized. The earlier mission-thread candidate also remains quarantined.

Controlling review: `docs/canonical-mission-authority-post-batch-3-review.md`.
Local entrypoint and full runner prompt: `docs/handoffs/protected-mission-authority-local-ready.md`.
Existing mission-planning and mission-authorization contracts remain controlling; this campaign does not grant execution authority.

## Steps

| Batch | Work | Required evidence |
| --- | --- | --- |
| 0 | Preserve prior evidence; inventory trust, consumers, deployment identities, locks, tests and all existing approval/derivation paths. Commit threat model and reuse map before production changes. | Exact baseline; reproducible old-path counterexamples in disposable roots; source-to-claim inventory; explicit threat actors and enforcement owner. |
| 1 | Establish protected trust configuration and a narrow issuer/verifier/consumer boundary. | Negative trust replacement and key-extraction tests; no caller-controlled runtime root or verifier on production entry; explicit bootstrap custody and rotation rules. |
| 2 | Make verified durable consumption the sole authoritative write path and couple currentness with consumption. | Direct-store bypass refused; revoke/consume and supersede/consume races with defined linearization; restart, replay, expiry and interruption proofs. |
| 3 | Implement the complete Operator-facing local ceremony over canonical planning and authorization services. | Executable commands and full test-only round trip: enroll public trust, prepare stable bytes, external sign, submit authenticated approval, derive and verify authorization; refusal tests for each stage. |
| 4 | Prove integrated behavior through the real application/CLI and real separate processes. | One disposable read-only Git mission, observable lifecycle/evidence/receipt, adversarial matrix, budget/allowlist enforcement and unchanged target. Explicitly test-only, not the previous real Batch 4. |
| 5 | Separately sequenced local audit after the Batch 4 commit. Update steps, flow and exact-head handoff. | Focused and complete suite, test-change ledger, boundary limitations, runnable human handoff, no fabricated closure. |

Implementation batches 0–5 are authorized sequentially locally, with separate commits and focused tests per batch. Full suite after Batches 2, 4 and terminal audit. A failing safety proof blocks its dependent batch. Do not silently change scope, weaken tests or count skips as proof.

## Enforcement contract: decide before building

Distinguish an untrusted mission caller from trusted Runtime implementation, the implementation agent, OS administrators, and hostile same-process code. A public PHP API does not automatically constitute a security vulnerability if every caller is trusted; nevertheless, an authority-bearing store must not accept an unverified DTO as proof of approval. Record actual reachability and reproduce each claimed bypass before declaring it fixed.

A class name, private constructor, container-private service, ordinary digest, directory outside the repository, or mode 0600 is not isolation from code running as the same account. Do not claim resistance to arbitrary same-UID or same-process execution without an OS-enforced boundary.

The protected deployment design must place the trust anchor, issuer secret and authoritative lifecycle state outside the untrusted caller's writable/readable authority as appropriate. Prefer existing canonical custody facilities if they meet the required boundary. A separate protected Runtime process/account with an authenticated narrow local IPC API is an acceptable design; a hardware-backed signer is not mandatory. The consumer receives verification material, not an exportable signing secret. An asymmetric signature alone does not protect an editable trust root or writable authoritative ledger.

The implementation agent may implement and test this design in disposable roots. It must not provision real OS identities, change production ACLs, enroll real trust, run a real signer, or read/create a real Operator private key. If genuine process/account isolation needs human installation, provide an exact installer/runbook for review and label deployment isolation UNPROVED until measured under those identities. Never fake that proof with same-user processes. This restriction does not block ordinary local implementation or test-only keys.

Runtime must resolve the installed trust root without caller-selected paths or silent project-config fallback. Initial enrollment requires an explicit deployment-owner bootstrap action with fingerprint confirmation and recorded custody; rotation/revocation requires existing authority or a separately documented recovery procedure. Do not solve root genesis by accepting any new key from an ordinary application caller.

## Atomic consumption

At the authoritative owner, authenticate the exact persisted approval/authorization chain, check current revocation/supersession/expiry, bind the exact mission/transition/actor/target/issuer and nonce, consume once and durably publish state as one coherent transaction or common exclusion boundary. Every cooperating revocation and supersession writer must participate. A lock only around the final JSON write is insufficient.

Use a trusted clock at the production boundary; test-clock injection is test-only. Define crash semantics and durability guarantees precisely, including limitations of rename without fsync. A new nonce or new capability must not reopen a terminal mission. Direct store access, constructor-forged verified handles, deserialization and caller-selected adapters must not create authoritative transitions.

## Operator ceremony deliverable

Do not invent command names in advance of implementation. Discover established commands/services and extend the canonical route rather than adding a parallel authorization island.

Provide PowerShell-friendly executable instructions, help/exit-code tests and a complete test-only transcript for:

1. Explicit deployment-owner public-key enrollment, identity/competence and fingerprint validation; unknown/replaced/revoked trust refuses.
2. Persist a pending, non-authorizing approval challenge containing every immutable value needed by the signed payload. Preallocate required IDs without granting authority. This resolves the old circular dependency on review values that existed only after approval.
3. Export exact canonical bytes and a human-readable rendering of the same complete numbered dossier, bound to challenge ID, version, digest, target commit/tree, permissions, limits, expiry and nonce.
4. Human external signing of those bytes, with a separately held Operator key. Tooling must not send the private key through the implementation agent, arguments, logs, repository or chat.
5. Submit the signed response through the canonical review service, persist operator authenticity and consume the challenge once. Validate that persisted values match the signed bytes.
6. Derive the exact Mission Authorization through the existing derivation lineage and verify it through a read-only command. Do not fabricate or predict a mission-authorization identifier.
7. Display status and precise next action, including refusal, expiry, cancellation and supersession. Approval is not execution.

Rejected submissions must not create approval, mission authority or success-state residue. Pending challenges are allowed but must remain non-authorizing. An amendment invalidates the affected challenge; no silent renewal or replay.

All stages must work end to end with disposable test-only identities before declaring CEREMONY_IMPLEMENTED. If any production handoff command is absent, report IMPLEMENTATION_INCOMPLETE, not merely AWAITING_OPERATOR.

## Previous test and evidence disposition

Preserve the old branch at its exact SHA. Preserve existing local transcripts, audit, ledger and handoff; record their paths and hashes in a sanitized inventory, without committing private evidence. Do not overwrite old results or relabel them as new-candidate proof.

The reported 2661 tests / 52390 assertions remain a historical local result, not independent remote verification or proof of the four reviewed boundaries. The old mission was blocked and did not execute; do not describe it as a successful real mission.

Classify each old test as RETAIN_UNCHANGED, PORT_WITH_JUSTIFICATION, SUPERSEDED_WITH_REPLACEMENT or INSUFFICIENT_PROOF. Keep meaningful crypto rejection, exact Git-object and real-process contention cases. Add counterexamples for the missing authority boundaries. Assert exploit refusal in the corrected suite; any expected-vulnerable demonstration belongs only in isolated quarantine reproduction, never in normal production acceptance.

Keep old synthetic keys and records test-only. Never carry their trust, capabilities, mission records or timestamps into the new deployment. Do not alter/delete actual runtime records during preparation. Test-generated temporary files may be cleaned only by exact owned path after evidence capture. No broad cleanup, branch deletion or Git reset.

The old dossier digest `fd914fc3ce5f0d6eba431a03349446fd2caf3a01feff961254f67d1d735d21da` targets the quarantined snapshot. It is historical and must not be reused to authorize a new candidate. A future real mission needs a fresh explicit dossier and approval for its actual target; changing implementation and changing inspection target are distinct and must both be disclosed.

## Proof matrix and closure

Test direct store/DTO construction, key extraction, root/path/verifier substitution, modified trust, absent trust, wrong identity/competence, changed canonical bytes, pending challenge replay, stale dossier, revoked/superseded authorization, cross-mission/actor/target substitution, terminal re-entry, expiry at use, real concurrent winners, restart and crash cuts. Prove key/authority absence after rejected operations, not just exception text.

The disposable reference mission must read real Git objects using an exact allowlist and enforce bytes/files/time/findings budgets before exceeding them. Read-only inspection must not trigger network retrieval (including partial-clone lazy fetch), hooks, arbitrary config-driven execution or target mutation. Record environment assumptions, subprocess bounds and failures. The test receipt must identify its test trust root and may not be presented as real Operator evidence.

At terminal audit return exact implementation HEAD/tree, baseline, batch commits, runtime versions, commands/results, skips, sanitized evidence ledger, old-to-new test map, boundary status and human runbook. Re-run full `php vendor/bin/phpunit tests`; any changed count requires a named explanation. Commit documentation separately if needed and state exactly which commit each run tested; never imply tests ran on a later commit.

Allowed successful local status:
`PROTECTED_MISSION_AUTHORITY_LOCAL_CANDIDATE_COMPLETE_PENDING_REVIEW_AND_OPERATOR_SETUP`.

If installation isolation is unproved, add:
`DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED`.

Otherwise use `IMPLEMENTATION_INCOMPLETE` with the exact blocker. No full security closure, real Operator authority, real mission completion or remote CI claim follows from local tests.

## Later gates — not authorized by this runner

Independent review of the exact candidate precedes its publication/merge acceptance. Human deployment-owner setup and genuine Operator signing remain separate actions. Only then may a separately authorized real read-only mission execute against a freshly disclosed target. Provider execution, credentials, external systems, Iron Gate/Lazaretto opening and live Batch 7 remain suspended.

The current GitHub push/merge instruction applies only to this documentation selection. The local runner may perform an initial read-only Git fetch to obtain this selection and quarantine references, then work offline. No local implementation push, PR, merge, main update or branch deletion is authorized here.

*Imperium Maximus.*
