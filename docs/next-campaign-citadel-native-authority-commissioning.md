# Citadel native authority commissioning preparation — CP0–CP3

Disposition: **SELECTED_LOCAL_PREPARATION_ONLY**. Produce a concrete owner decision and commissioning package for the accepted native authority protocol. Complete CP0–CP3 locally; do not perform the proposed installation or institutional actions.

## Entry and accepted evidence

Start from the campaign-selection merge on main pinned in the owner's launch prompt. It must descend from P0–P3 integration `f35a0b33ddab9e8815470a697d8593984e050023`, tree `e4140d95cbb83b3dbf9125849953cec0c7eda989`, [PR #774](https://github.com/gatomontes/imperium-symfony/pull/774). [GitHub CI](https://github.com/gatomontes/imperium-symfony/actions/runs/34281808594) passed on accepted head `0624079f220d28dc725d39ba310c96a4a8467e82`. Read [the acceptance](citadel-native-authority-protocol-acceptance.md). P0–P3, NA-IR01/02, earlier campaigns and the exact Guildhall planning-acceptance lookup are closed within their recorded scopes.

Use fresh worktree `E:\htdocs\imperium-citadel-commissioning`, branch `codex/citadel-native-authority-commissioning`. Preserve `E:\htdocs\imperium`, all earlier worktrees and all evidence packets. The intended application root is `E:\htdocs\imperium`; its last supplied commit was `8244afa5630b06973cc4a52dce6ed96f42a444f3`. That is historical evidence, not a claim about today's installed source. Main integration does not update the installation.

## Required reading

- This campaign and [Loco handoff](handoffs/citadel-native-authority-commissioning-ready.md).
- [P0–P3 acceptance](citadel-native-authority-protocol-acceptance.md), protocol report, source map, changed-test map and runbook; `contracts/native-institutional-authority-v1.md`; `docs/native-authority-owner-policy.template.json`.
- `docs/citadel-readiness-matrix.md`, `docs/citadel-readiness-runbook.md`, `docs/citadel-native-lineage-acceptance.md`, `docs/citadel-native-lineage-report.md`, `docs/citadel-native-lineage-authority-matrix.md` and `docs/citadel-authority-interface-runbook.md`.
- `docs/citadel-formation-correction-acceptance.md`, `docs/citadel-mission-formation-decisions.md`, `docs/citadel-mission-formation-implementation.md`, `contracts/citadel-formation-runtime.md`, `docs/delegate-mission-flow.md` and `docs/next-lifecycle-delegate-mission-route.md`.
- Actual `NativeTrust`, `NativeProtocol`, `NativeJournal`, `NativeBoundary`, `NativeServices`, enrollment/ordinary commands, `AuthorityInput`, Garrison request/Recruiter projection, the two supported Garrison consumers and StateStore. Follow the source map for the 38 legacy entry points.
- Existing public collector, its Python tests, `NativeAuthorityProtocolTest`, `NativeAuthorityCorrectionTest`, supporting fixtures/workers, and public proof generator/verifier. Reuse their established boundaries.

## Authorized footprint

This campaign authorizes read-only Git/source-metadata assessment of the identified installation and inspection of previously supplied public evidence. Use no-optional-lock Git observations with external diff/text conversion disabled. Inspect commit/tree/branch and tracked-change names; compare committed public source from the campaign worktree. Do not fetch, switch, pull, write, install dependencies, boot Symfony or run native commands in the installed root. Do not open `.env` values, installed private bootstrap state, journals, credential stores, signing keys or arbitrary untracked files. Never treat a native `snapshot` or `resolve` command as physically read-only: even those commands may create a lock file.

Reuse retained public evidence; do not recollect the accepted Guildhall records. A new public observation, if needed, must use the existing fixed, bounded public collector scope and be written to a fresh external output path. Do not widen that collector into a private export. A missing private Recruiter projection remains a separately authorized future prerequisite. Missing access/evidence is recorded, not repaired by another root or invented currentness.

Edits are confined to preparation documents, public preparation helpers and their tests in the new worktree. Prefer standard-library tooling that does not boot the application. No new production authority schema, trust domain, runtime adapter, enrollment shortcut or lifecycle migration is selected. If preparation exposes a new production defect, document a concrete counterexample for review without silently expanding this campaign.

No real policy approval, deployment, key generation/use, enrollment, private-state export, institutional act, provider/credential activity, mission execution, process stop or service reconfiguration. Generated disposable synthetic roots and ephemeral in-memory fixture keys remain permitted for offline rehearsal. Do not copy installed private state into a fixture.

## Steps and concrete outputs

| Step | Work | Required result |
| --- | --- | --- |
| CP0 — installation assessment | Record actual read-only source identity and tracked drift; compare with the explicit proposed target; reuse public evidence and trace installation-impact paths. | `docs/citadel-commissioning-installation-assessment.md` and sanitized machine-readable assessment outside Git. Distinguish observed installed source, historical evidence, proposed target and missing runtime/custody facts. |
| CP1 — owner policy and compatibility | Build a proposal from the actual native contract and identify every unresolved owner decision, signing/custody requirement and workflow impact. | `docs/citadel-commissioning-owner-decisions.md`, a public unapproved proposal schema/example, and `docs/citadel-commissioning-compatibility.md`. Each item has source evidence, proposed choice, consequence, owner input and approval status. |
| CP2 — preflight and exact runbook | Implement a bounded preparation checker for the public package and produce exact future commissioning steps, validating their real command shapes. | Minimal preparation helper plus `docs/citadel-commissioning-owner-runbook.md`; distinguish structural consistency, source compatibility, prerequisites and actual authorization. Produce explicit blockers, not a commissioning certificate. |
| CP3 — offline rehearsal and review packet | Exercise the proposed sequence against fresh fixtures through actual supported commands/consumers and adverse cases; close the documents with precise attribution. | `docs/citadel-commissioning-preparation-report.md`, evidence index, final source identity, public rehearsal outputs and complete independently reviewable packet. |

### CP0: installation and deployment comparison

Record the installation's actual root/commit/tree/branch and tracked drift without reading private contents. An unavailable root or dirty/unidentified installation remains a blocker; complete the source-based assessment and other steps anyway. Compare the identified installed commit with the exact proposed target from the preparation worktree. Identify source/configuration/dependency changes, required runtime extensions and filesystem permissions from repository evidence; do not install them or claim the running process matches Git metadata.

Keep approved deployment target separate from preparation-code HEAD. If CP2 adds only preparation tooling, record whether the proposal targets the accepted runtime tree or the later preparation commit, and bind that explicitly. Do not select a moving `main`. Runtime data, process quiescence, backups and restore feasibility remain observed, source-inferred or unverified as appropriate. A rollback command must not be presented as safe after enrollment without support for the resulting custody and writer state.

### CP1: owner decisions that cannot be inferred

The proposal must cover the identified installation, responsible deployment/authority custodian, `IMPERIUM_NATIVE_INSTITUTIONAL_V1`, `NATIVE_INSTITUTIONAL_CUSTODIAN`, all six implemented effects, public-key algorithm/fingerprint verification, validity, signer/custody procedure, storage protection and administrative command access. Preserve the existing protocol's exact fields and restrictions. Describe proposed values as proposals; real identity/key/times not provided by the owner remain null. Preparing a JSON field cannot approve or enroll it.

Map every fenced legacy entry point to its affected operational workflow. Explicitly distinguish supported native admission/inventory, unaffected pre-enrollment behavior, unsupported post-enrollment Conscription/Garrison work, missing legacy Guildhall transport for native inventory, and unsupported Garrison succession. Present a concrete recommendation on whether a bounded native enrollment is compatible with the intended use, with evidence and unresolved choices. Do not force a favorable outcome: an owner decision to defer enrollment is valid. No new migration is part of CP1.

Document exact public inputs for initial Recruiter/Garrison adoption and the two-scope Garrison revision, using retained genuine public evidence where available. Distinguish structural evidence and owner attestation from authenticated historical producer execution. No synthetic example becomes an installed institutional fact; no planning grant becomes formation competence.

A real signer has not been selected. Define the exact canonical payload/public detached-signature exchange and independent fingerprint procedure. Do not invent signing-tool syntax, create a real key or insert private material into the public package. Unresolved signer/custody selection is a named owner blocker with a concrete interface to satisfy.

### CP2: useful refusing preparation

The preparation checker reads only explicit public package inputs/source metadata in its allowlisted scope and writes only fresh external outputs. It must not call enrollment, native state-mutating/lock-creating commands, StateStore, a credential broker or a provider while assessing the installation. Handle missing fields, mismatched source identity, stale or changed public evidence, duplicate/unsupported fields and path escapes according to the actual input contract. Preserve original evidence bytes and digests. Do not let a self-declared `approved: true` turn structural evidence into owner authorization.

A valid structural package can be ready for owner review while enrollment and live readiness remain blocked. Return separately named statuses and explain each unresolved prerequisite. Never imply that a hash verifies custody, historical execution or current incumbency. Do not widen the preparation helper into a production trust/admission resolver.

The runbook must identify phase, exact working directory, input shape/path, preconditions, expected stdout/exit, filesystem footprint and stop conditions for each future action. Include proposed source deployment, independently confirmed enrollment, roster adoption, Garrison revision, separate admission and inventory, and exact completed recovery where supported. Use real implemented command syntax; illustrative filenames must be labelled and artifacts generated when their values are available. No blind update of the main app, bulk native apply, unattended ceremony or fabricated rollback/recovery command.

Enrollment immediately fences unsupported legacy paths; record that consequence before the enrollment command. Unknown pending frames must be preserved for separately reviewed recovery, never removed or repaired automatically. Acceptance time is the one locked observation before physical publication; retain the honest timing/durability limits. Inventory is not selection, reservation or execution authority.

### CP3: evidence and exit conditions

Prove the checker cannot touch seeded synthetic private-state/credential sentinels or mutate an assessed root. Exercise missing/unapproved policy, mismatched commit/tree/fingerprint, stale evidence, unsupported compatibility and malformed/path-escaping inputs. A complete synthetic public proposal should pass structural preparation while still being labelled synthetic and unapproved for real use. Exercise supported future command shapes in disposable roots using the existing protocol proof and actual DI/consumers; include retained legacy custody and advancing-time recovery regressions without weakening them.

Run focused tests appropriate to the changed helper and existing 16 Python compatibility tests. Run the existing native protocol/correction and relevant command/consumer PHP tests plus the synthetic proof when rehearsing those paths. Use retained restricted harness patterns, existing offline dependencies and explicit environment names; no dependency install/update or credential-bearing environment inheritance. A PHP full-suite rerun is required if production behavior is changed in a separately accepted scope; otherwise distinguish focused rehearsal from the preserved full-suite baseline. Documentation-only changes do not justify fabricated new test claims.

Commit any executable preparation/helper change before the final associated gates. Retain commands, native exits, UTC times, test counts, raw failures, warnings, environment names and source status. Separate source-inspected, reviewer-rerun, locally executed and historical supplied evidence. Any executable post-test change needs fresh affected validation. Preserve disclosed generated-reference changes explicitly.

Return tested and final commit/tree, clean tracked status or exact remaining differences, complete source ZIPs and file/mode/blob/byte manifests, bounded Git bundle with prerequisites, final/post-test diffs, public-only proof and metadata, whole-folder SHA manifest, ZIP SHA-256 and a verification script. Reference existing accepted packets by exact identity; do not invent or silently replace missing historical evidence. Real deployment-specific public inputs stay outside Git; commit only sanitized reports/templates and reusable source. Never package raw private installation or credential-adjacent material.

Success is **COMMISSIONING_PACKAGE_PREPARED_FOR_OWNER_REVIEW**, with explicit unresolved prerequisites and `deployment_approved`, `enrollment_authorized`, `live_ready`, `activation`, `execution_authority` all false. This is complete preparation even if an owner decision remains unavailable, provided the proposal, source-impact assessment, checker and runbook are concrete. Stop at local commits for independent review. Implementation publication/merge and any real commissioning are later gates.

## Preserved mission flow

Citadel receives; Castellan interviews. “I understand” closes interview authority. Separate explicit approval permits drafting. Separate mission approval precedes legitimate child-Curia constitution and handoff. Receiving assessment grants no execution authority. CF01 refusal is terminal; CF02 recognizes only authenticated already-completed child publication after expiry; NA-IR01/02 retain custody uniqueness and consistent acceptance/recovery. Unknown provider outcomes retain exposure without retry/refund; default formation transport refuses.

Following this preparation: independent package review → explicit owner decisions → separately authorized deployment/enrollment and genuine native acts when their prerequisites are met. Formation-specific trust, other Seats, suitability/delegation, candidates/appointments and B1's exact provider/model/destination/credential and supportable cost/timeout contract remain later unresolved gates. No provider or tariff is selected by this campaign.

*Hoc est pretium solitudinis. Imperium via solitaria est.*
