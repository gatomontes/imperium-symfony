> Current status: [CY/FC acceptance and integration](courtyard-fc-acceptance.md)
> supersede pending-review wording below. [Provider onboarding O0](next-campaign-provider-onboarding-o0.md)
> is selected for contract/prerequisite preparation only. O1–O5 and live activity
> remain deferred. Existing commands do not grant missing authority or readiness.

# CP2 — phased future owner commissioning runbook

Current identity update (CY0–CY3): [Courtyard runbook](courtyard-identity-runbook.md)
and [compatibility contract](../contracts/courtyard-identity-compatibility.md)
control fresh reception/formation. Courtthane requires the exact new Seat and
appointment; old Castellan evidence grants no successor or oversight authority.
Citadel jurisdiction, Seneschal's Curia mandate, B1 and DEFER_ENROLLMENT remain.
FC0–FC3 independent acceptance remains pending. The prior campaign entries and
examples below retain their historical attribution; use the linked current
runbook for implemented command names and future prerequisites.

Owner update (2026-09-09): **DEFER_ENROLLMENT selected**; [acceptance and decision](citadel-commissioning-preparation-acceptance.md). The owner continues toward the Castellan interview and has not accepted a bounded native deployment or its workflow losses. All other real identity/key/signer/validity and operational prerequisites remain unresolved. The original proposal below is retained; its deployment/enrollment commands are not authorized by this choice.

**REVIEWED PROPOSAL ONLY; NO INSTALLATION ACTION IS AUTHORIZED HERE.** First review the completed package, [owner decisions](citadel-commissioning-owner-decisions.md) and [compatibility matrix](citadel-commissioning-compatibility.md). Recommended disposition is defer enrollment until every affected workflow and custodian prerequisite is explicitly resolved. This campaign executes only generated-root rehearsal.

All `E:\owner` names below are illustrative fresh external public files. Actual identities, key, signature, validity and current head/predecessors are unavailable for the installation; never substitute rehearsal artifacts. Keep original UTF-8 bytes without BOM, save stdout to a fresh output, and retain `$LASTEXITCODE` immediately. Every native operation may create transition-lock files even when called snapshot/prepare/assemble/resolve/inventory. Never run those as read-only installation probes during CP0.

## Phase 0 — review and source deployment proposal

Working directory for preparation: `E:\htdocs\imperium-citadel-commissioning`. Run only the Python checker described in the package contract on the external public package, with a fresh external output. Expected exit 2, review-ready with explicit blockers and all five authority/readiness flags false. Exit 1 stops that check; preserve refusal and original bytes. Its output does not replace owner review or independent source verification.

Before a future deployment, the owner must separately approve the fixed target, identify all service and administrative writers, establish quiescence, confirm protected storage and runtime requirements, and demonstrate backup/restore feasibility. Service names, stop commands and backup commands are unknown; none are invented. A non-Git deployment mechanism needs its own reviewed procedure. For the identified Git checkout, the following is an exact **future source-only proposal**, gated on those prerequisites and renewed clean source observation:

```powershell
Set-Location 'E:\htdocs\imperium'
$target = 'f35a0b33ddab9e8815470a697d8593984e050023'
$before = git --no-optional-locks -c core.fsmonitor=false status --porcelain --untracked-files=no
if ($LASTEXITCODE -ne 0 -or $before) { throw 'Preserve source drift; deployment stops.' }
$targetTree = git --no-optional-locks rev-parse ($target + '^{tree}')
if ($LASTEXITCODE -ne 0 -or $targetTree -ne 'e4140d95cbb83b3dbf9125849953cec0c7eda989') { throw 'Target unavailable or mismatched.' }
git switch --detach $target
if ($LASTEXITCODE -ne 0) { throw 'Source deployment failed; preserve output.' }
git --no-optional-locks rev-parse HEAD
git --no-optional-locks rev-parse 'HEAD^{tree}'
```

Expected native Git exits 0 and the exact target identities. `switch` writes the checkout and Git metadata and may refuse untracked-file collisions; never force, clean or overwrite them. Preserve the old branch and its history. Source switching does not reload running processes, validate runtime dependencies, migrate data or commission the app. Those facts need separately reviewed operational evidence before progressing. No package installation is proposed: committed dependency manifests/lock are unchanged; actual runtime compatibility remains to be checked in an authorized window. No source rollback is asserted safe after enrollment; reverting source while retaining native custody can reopen old writers. Before enrollment, even reverting source requires review of any intervening data writes. There is no supported native rollback, reset, pending-repair or key-rotation command.

## Phase 1 — public evidence and independent enrollment decision

Working directory for all future PHP commands: **the separately deployed, owner-confirmed `E:\htdocs\imperium`**. PHP uses `%kernel.project_dir%`; no caller root override exists. Public native inputs follow AuthorityInput's exact fields, 1 MiB/depth 48 and duplicate-key restrictions. Parents must be protected local paths. Permission to deploy source is separate from reading private state or enrollment.

Genuine Recruiter projection is missing. Only after separately authorized private-state observation may the actual `imperium:citadel:authority recruiter-export INSTANCE_ID` interface be used. It reads private bootstrap state under StateStore's lock and may create lock directories/files. It emits a structural public projection, exit 2, not historical producer authentication or continuing incumbency. INSTANCE_ID is an unresolved parameter, not a literal command to execute. Preserve that exported public original outside Git; do not export raw private state.

Populate the exact unchanged enrollment policy from the owner-decisions document and independently confirm SHA-256 of the decoded 32 public bytes. **Enrollment immediately fences unsupported legacy Conscription/Garrison operations and StateStore reads/writes, including when a directory remains after uncertain enrollment.** Native admission still needs adoption/revision; enrollment alone leaves roster absent. Obtain explicit owner approval of this consequence before:

```powershell
php bin/console imperium:native-authority:enroll E:\owner\native-policy.json CONFIRMED_PUBLIC_KEY_SHA256
$enrollmentExit = $LASTEXITCODE
```

Replace the fingerprint token only with the independently confirmed 64-lowercase-hex value. Expected exit 0 and `PUBLIC_TRUST_ENROLLED_ROSTER_ABSENT`; exit 1 is a fixed-code refusal. Footprint: native transition lock plus initial immutable frame under `var/imperium/native-authority`; no signing private key or original occupancy rewrite. Repeated enrollment refuses. Do not retry uncertain enrollment, remove its directory or switch old binaries back in. Preserve evidence and stop for reviewed recovery.

## Phase 2 — one chosen institutional act at a time

Every operation file is exactly `{"operation":"NAME","arguments":{...}}` and is invoked using:

```powershell
php bin/console imperium:native-authority E:\owner\operation-0001.json
$operationExit = $LASTEXITCODE
```

Use a new named file/output per step. Native frames and results are public trust/effect evidence under protected local custody. Do not bulk apply, automatically rebase signed heads or reuse a nonce for changed input.

| Phase / operation | Exact arguments and prerequisites | Expected stdout / exit | Footprint and stop |
| --- | --- | --- | --- |
| 2a snapshot | `{}`; owner-authorized runtime observation after enrollment | Native observation with frame and registry head, exit 2 | Lock; no frame append. Historical observation only; unknown pending or corrupt chain stops |
| 2b prepare | `{effect,object,issued_at,expires_at,nonce}`; chosen effect, exact public original, scoped owner intent | `imperium.native-unsigned-decision/v1`, payload and signature:null, exit 2 | Lock, no act. Output is unsigned; retain original object separately |
| 2c external signing | Canonical payload/public exchange in owner-decisions; signer interface still unresolved | Base64 detached signature, exact public payload | Outside runtime. No actual tool syntax supplied; stop until real signer/custody approved |
| 2d assemble | `{object,payload,signature}` using original object and detached signature | `{object_digest,decision}`, exit 2 | Lock, no authority consumption. Exact enrolled current signature required; mismatch/expiry exit 1 |
| 2e apply | `{object,decision}`; returned decision, same object/head and separate act approval | Immutable native institutional act or exact completed result, exit 0 | One frame for a fresh act. Exit 1 stops; preserve refusal and signed input |
| 2f resolve | `{seat:"conscription.recruiter"}` or `{seat:"garrison.constable"}` | Current roster and locked observation time/head/frame, exit 0 | Lock only; no indefinite currentness certificate; expiry/revocation/original mismatch exit 1 |

Apply Recruiter adoption and Garrison adoption **separately**, using their exact objects in owner-decisions. Refresh snapshot after each apply; the second act binds the new head. Retain roster digests and unchanged occupancy generations. Initial adoption is owner attestation, not proof that the historical producer ran or formation competence was granted.

## Phase 3 — two-scope Garrison revision

From the same separately authorized installation, use `php bin/console imperium:citadel:authority garrison-request E:\owner\garrison-preparation.json` with exact `{schema,occupancy,prior_revision,effective_at,expires_at,request_nonce}`. Expected exit 2 and deterministic unsigned `imperium.garrison-authority-request/v1`; this mode reads only that public input and writes no runtime file. Save original output. No authority was installed by preparation.

Build `REVISE_GARRISON` object `{expected_head,roster_digest,prior_revision,request,occupancy}` from the latest verified native observation and same original occupancy. The request's prior reference is null or `{id,digest}`; outer prior_revision is null or the exact digest. Null denotes verified absence only at the matching native head. Execute prepare/sign/assemble/apply as distinct Phase 2 actions, each with fresh evidence. Expected apply exit 0 publishes one immutable revision and consumption/request replay fence; original actor, occupancy generation and bytes remain unchanged. Only the two narrow admission/custody scopes are extended. Revoked/expired/stale head/revision/roster, changed original, reused request nonce or excess scope refuses. Do not silently generate a replacement signed act.

## Phase 4 — separate admission and inventory

Admission is a separately authorized institutional act on a complete already-retained canonical Guildhall delivery; revision is not admission. Use operation `admit`, arguments `{delivery_id,binding_id}`. Delivery ID must match `guildhall-garrison-persona-admission-delivery-[20 lowercase hex]`; binding is the exact original Garrison binding. Expected exit 0 with ADMITTED disposition; one native frame co-publishes custody/disposition. No legacy raw custody/disposition materialization occurs. Runtime source path is `var/imperium/offices/garrison/inbox/canonical-subordinate-persona-admissions/DELIVERY_ID.json`. No command here fabricates or transports a real delivery.

Retained legacy custody for the same Persona refuses with NAT037 before a new effect, even under a second delivery ID. Unsupported/duplicate held records refuse NAT040 or their existing parsing/integrity code. Preserve original bytes; no migration or new legacy recovery protocol exists. A distinct Persona can coexist with intact retained legacy custody.

Inventory is operation `inventory`, arguments `{inquiry_id}` with `garrison-inquiry-[20 lowercase hex]` for an already-retained valid inquiry in `var/imperium/offices/garrison/inbox`. Expected exit 0 with `imperium.native-garrison-inventory-response/v1`, current observation and combined held records. Lock may be created; no frame append or legacy Guildhall response file is produced. Independent original inventory power and current roster are required. Inventory grants no selection, reservation, handoff or execution authority; legacy transport of this schema is unsupported.

## Phase 5 — completed recovery and uncertain outcomes

Replay the **identical** `apply` object/decision to recognize an already completed act; replay `admit` with the exact same retained delivery/binding to recover an already completed native admission. Expected exit 0, byte-identical retained result and unchanged frame generation, including after later expiry/revocation. Recovery rechecks original signatures, acceptance intervals and revocation ordering; it does not renew authority or create a new effect. Missing completion requires fresh current authorization and does not justify replay after expiry. Changed bytes/nonce conflict stops.

NAT005_UNKNOWN_OUTCOME_FENCED means preserve `.pending` bytes and all history for separately reviewed recovery. No delete, forced rename, timeout release, automatic retry/refund or repair command is supported. Source rollback is not recovery. Fresh apply/admit uses one locked acceptance sample before physical publication; it does not promise publication-before-expiry, a trusted timestamp, directory fsync or power-loss durability. Competing approved writers are excluded until publication; administrator replacement, old binaries and unrelated writers remain outside this guarantee. Capacity is 1 MiB/frame and 1,024 frames; stop for reviewed migration on capacity refusal.

CP3 rehearsal files contain exact synthetic public inputs and actual command/DI exits for every supported phase above. They are reusable review evidence only. Real commissioning still leaves formation-specific trust/competence, other Seats, candidate judgments/appointments and B1's provider/model/destination/credential and cost/timeout contract unresolved. Citadel receives; Castellan interviews; understanding closes interview authority; drafting and mission approval remain separate; receiving assessment grants no execution authority. CF01/CF02/IR01 remain preserved and default transport refuses.
