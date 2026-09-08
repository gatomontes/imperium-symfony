# Citadel native institutional lineage and authority preparation

Status: **SELECTED_LOCAL_PUBLIC_EVIDENCE_AND_OFFLINE_IMPLEMENTATION_AUTHORIZED**.
Runner: [Loco handoff](handoffs/citadel-native-institutional-lineage-ready.md).
Accepted baseline: [integration acceptance](citadel-readiness-integration-acceptance.md).
Read-only collection scope: [exact public evidence](citadel-native-lineage-public-evidence.md).

## Outcome

Make the actual installation's institutional lineage usable for commissioning
assessment without inventing incumbency or authority. Collect the bounded public
records already identified; provide a reproducible public export/verification
surface; implement dormant formation witness support only for genuinely supported
lineages and powers; end with an exact owner action for every unresolved boundary.

This campaign advances the known Garrison/Guildhall compatibility gaps. It does
not repeat R0–R3, integration review, a founding ceremony or a broad source audit.
Missing owner evidence does not require repeated pauses: complete independent
work, retain a refusing result, and return the specific missing public artifact
or substantive authority decision once at closeout.

## Entry and authority

Start in a fresh `E:\htdocs\imperium-citadel-native-lineage` worktree on
`codex/citadel-native-institutional-lineage`, from merged main containing
`fc653db59bd2d9d54f659a4078f5a7af19a3a4e5` and these instructions. Record the actual
campaign-start commit/tree. Preserve the main installation, readiness, source-review
and integration branches/worktrees; do not repurpose them as fixture roots.

The owner's current campaign instruction authorizes local source/tool/test/docs
changes, bounded read-only collection of the specified existing public artifacts,
offline tests, and local commits for independent review. It does not authorize
installation changes, runtime authority mutation, authentic signing/enrollment,
new institutional acts, deployment, provider calls or mission execution. Publication
of these campaign instructions is not permission to publish future raw evidence
or automatically merge the resulting implementation.

Read applicable AGENTS.md, the acceptance and collection documents, current flow,
readiness matrix/runbook, formation decisions/implementation, correction acceptance,
IR01 report and `contracts/citadel-formation-runtime.md`. Trace the installed and
candidate source versions separately; a source update does not prove runtime change.

## Steps

| Step | Work | Completion evidence |
| --- | --- | --- |
| N0 | Collect exact existing public lineage and classify powers | Bounded input manifest, source/currentness identity, native record validation and explicit gaps |
| N1 | Implement the minimum read-only public export and verification surface | Reproducible public schema, exact commands, no writes to authority/runtime and no secret/provider access |
| N2 | Support only eligible native witness variants, dormant and refusing by default | Action-specific checks and historical proof compatibility, or a precise implementation/authority blocker |
| N3 | Integrated offline proof and owner handoff | Exact tested/final source, adverse tests, full-suite result where code changed, runbook and complete review ZIP |

### N0 — use actual public evidence

Reuse the reviewed packets already available locally. Compare supplied record
digests to the installation's exact public files only within the collection
allowlist. Preserve old bytes and report changed/deleted/ambiguous records; never
silently replace an earlier source identity. For discovered references, enforce
identifier/path/schema limits and follow only the source-defined public lineage
needed for the stated actor/action. Do not recurse through the full runtime.

Separate: content intact; source chain valid; custodian attribution verified;
current incumbent established; exact institutional action authorized. A matching
digest alone establishes only the first. Track UNAVAILABLE, UNSUPPORTED, CORRUPT,
AMBIGUOUS, INACTIVE, OUT_OF_SCOPE and VERIFIED_FOR_EXACT_ACTION separately.
Absence of a bounded export is not proof of absence elsewhere. Do not infer current
occupancy by highest generation or add powers from newer producer defaults.

Garrison: validate delivery/qualification/commission/provisioning closure and
currentness, then the actual admission/custody competence required for formation.
The known older record lacks those powers. Collect existing attributable public
authority if available; otherwise describe the missing legitimate producer/owner
act. Do not install, upgrade, rewrite or grant authority during this campaign.

Guildhall: preserve its atomic pending cohort; look for the exact separate
acceptance by supplied public identity. Validate summons, commission and scope.
Historical planning acceptance is not generic suitability authority. Do not
rewrite the cohort to ACTIVE or treat lack of an attached acceptance as proof that
none exists. Handle all required committee actors and the Guildmaster distinctly.

For the other seven formation seats, use exact public occupancy/source/acceptance
references when supplied. A public Recruiter receipt may be unavailable because
the current reader uses private bootstrap state. Record that exporter boundary;
do not open the private state or re-bootstrap an ordinary Recruiter.

### N1 — make public verification reproducible

Prefer an existing native read-only export where suitable. Where absent, add the
smallest public-only collector/validator for the source-defined artifacts above.
Its output must retain original public record bytes or clearly identified public
projections, source digests, actor/seat/instance/action, exact references, collection
time, validation scope and unresolved checks. Report projection and original
identities separately; a redacted projection cannot retain the original byte hash.

Run new tooling from the campaign checkout. If a standalone collector accepts the
owner-confirmed installation path, treat it solely as read-only collection scope:
enforce canonical paths, regular files, no traversal/symlink/reparse escapes,
bounded byte/count limits and no output inside the input tree. Never pass that
argument into production authority resolution, signing, transport or activation.
Production FormationInstitution remains fixed to its trusted project root.

Prefer static tooling over booting the old installation. Do not copy its private
environment, credential store or runtime journals to make an export work. No
inspection/preparation operation may access a provider or credential broker.
Prove this using recording/refusing doubles and source path analysis. Public
errors must not dump private paths/content or turn a failed check into eligibility.

### N2 — evidence-grounded, dormant witness support

Implement only an existing legitimate lineage supported by N0 and its actual
producer/consumer authority contract. No generic "owner asserts eligible" escape,
ProtectedMission competence reuse, synthetic operator-root receipt, blanket
historical exemption, automatic migration or caller-selected production verifier.
Missing authority remains refusing even if structural lineage becomes supported.

Use explicit schema/version dispatch for native witness variants; bind exact
instance, seat, manifestation, generation, original bytes and their public chain.
Check the powers and role-limited delegation needed for the specific formation
action. Define currentness/supersession from real supported evidence, not arbitrary
array flags. If source lacks a legitimate authority-upgrade or successor producer,
document the exact missing act/interface instead of creating it implicitly.

Trace all affected consumers, including FormationPersonnel and
FormationPublicationEvidence, not just FormationInstitution::witness. Current
validation and historical publication verification must use compatible versioned
proofs. Preserve existing operator-root witnesses byte-for-byte and CF02 recognition
after expiry. Never backfill a signature, publication time, authority or receipt.
Historical recognition verifies what was authorized then; it does not revive
authority now. If no complete native lineage can be supported, finish N1/N3 and
return a precise blocker; do not implement speculative positive authority.

### N3 — prove and return the owner package

Use disposable isolated roots, synthetic keys explicitly labeled as such, and
recording/refusing provider/credential interfaces. Raw installed record copies
are private review evidence and stay outside Git. Synthetic variants may test
failure paths, but cannot count as genuine institutional or appointment evidence.

Meaningful adverse coverage: unknown schema; missing/corrupt/wrong-instance
upstream record; wrong actor/seat/generation; multiple or superseded occupants;
Garrison missing powers; Guildhall missing/mismatched/out-of-scope acceptance;
projection-original mismatch; path escape; refusal before credential/provider
activity; existing historical witnesses plus altered new witness provenance.
Add tests for actual changed boundaries, not a new full framework.

Run relevant Citadel and institutional/export tests during implementation, then
the complete suite on committed final executable code:

```powershell
php vendor/bin/phpunit tests --filter 'Citadel|SourceReview|Constable|Guildhall|RecordReferenceValidator'
php vendor/bin/phpunit tests
```

Include any newly named collector tests in the focused selection. Record actual
commands, environment, dependency lock, tested commit/tree, timings, counts,
warnings and statuses. Do not require the historic assertion count to be identical;
explain changed tests and never weaken a safety assertion to obtain green results.
If only reports change and no executable/test code changes, retain the accepted
integration evidence and explicitly state no new PHP run instead of wasting a
repeat full suite. Code changes require the final full-suite gate. Track generated
files honestly; disclose and preserve diffs before restoring generated reference
documentation. Attribute post-test docs separately; revalidate executable changes.

Return: public lineage/current-authority matrix; supported schema/action map;
exact executable export commands and expected exits; unsigned owner-action
requirements; implemented changes and changed-test map; test/proof evidence;
exact tested/final identities, source ZIP/manifest, bounded bundle with verified
prerequisites, complete packet SHA256SUMS.txt and separate ZIP hash. Package the
entire review folder, not a partial set of files named by its manifest.

Prepare public custody/trust/appointment requests from real values only. Unresolved
fields stay null and unsigned. Named custodian, independently confirmed public
fingerprint and genuine judgments must come from their actual owners. Loco cannot
attest for them. Exact later signing/import commands must be grounded in inspected
interfaces, with missing interfaces explicitly identified.

B1 remains a separate owner choice: exact provider/model/destination and supportable
pricing/usage/bounds, or an explicit policy decision separating local time/cost
reservation from remote cancellation/billing guarantees. No provider selection,
price assumption, policy amendment or credential adapter activation is made here.
Unknown outcomes retain maximum exposure without retry/refund. Leave the default
formation transport refusing and the readiness flags false.

## Stop point and disposition

Finish N0–N3 without proceeding into live commissioning. Use
`NATIVE_LINEAGE_PREPARATION_COMPLETE_PENDING_REVIEW` for supported dormant work,
or `NATIVE_LINEAGE_PREPARATION_COMPLETE_WITH_EXPLICIT_OWNER_BLOCKERS` when genuine
evidence/authority prevents further support. Neither means live-ready. Enumerate
exact remaining owner records/decisions once; do not propose another broad
inventory to compensate for their absence. Keep local implementation commits for
independent review; no future push/merge, deployment or activation is automatic.

Settled flow: Citadel receives → Castellan interviews → understanding closes the
interview → separate explicit drafting approval → proposal → separate mission
approval → legitimate child-Curia constitution/handoff → receiving assessment →
non-executing Step 1 validation. Execution needs its own valid authority. Preserve
CF01/CF02/IR01, bounded source review and historical Delegate Steps 1–69.

*Imperium via solitaria est.*
