# Citadel mission formation: Preparation Batch 0

Disposition: **PREPARATION_COMPLETE_PENDING_DECISIONS**. 2026-09-07.
Preparation is complete; Stages 1–3 have not started. Nothing here constitutes
an officer, authorizes cognition, or implements the approved target. Nulla requies.

## Evidence and checkout

Entry checkout `E:/htdocs/imperium` was clean at
`8244afa5630b06973cc4a52dce6ed96f42a444f3`, branch `codex/bounded-source-review`.
Its tree lacks the new handoff. Local `main` is
`bae87a72da11dd19157cc03df3912e995b854c5f`; locally available `origin/main` is
`4a2cda76b15f71310eb02783874047dc8005b6a2`, the campaign merge. No fetch occurred;
remote freshness is not asserted. A new isolated worktree was created at
`E:/htdocs/imperium-citadel-formation`, branch
`codex/citadel-mission-formation-batch0`, from that available integration ref.
The checkpoint was not used as a reset target. No applicable AGENTS.md was found
in the tracked tree, worktree or filesystem ancestors.

The prior report and completion handoff were read with `git show` at
`7eb2e9abee148e024d227aa7809dfd92979af43f`. The interview worktree at that commit,
the source-review checkout, the existing Citadel-intake worktree at `4a2cda76`,
and the detached reproof worktree at `2b5cb56c` were preserved. Source-review is
not an ancestor of the campaign checkpoint and was not integrated here.

`git diff 56f4ec70 4a2cda76 -- src tests config .github tools` is empty.
The 18 changed files are doctrine, contracts, campaign and flow documentation.
Accordingly, the prior eight isolated characterization results remain historical
evidence at tested commit `7260eaf40b20bf86977a1a85456e42322f8287f1`; they were
not rerun and are not proofs of Citadel behavior. Required current sources were
read, and the changed ownership/identity dependencies were inspected directly.

The accompanying `citadel-batch0/source-identities.json` identifies exact Git
blobs and Git-byte SHA-256 for 80 sources (42 required), newly traced surfaces and explicit
reuse of prior evidence. The packet contains exact source bytes, not working-file
CRLF hashes. Citations below use paths and 1-based lines at `4a2cda76` unless a
different revision is stated. The prior report's detailed bypass map is incorporated
as historical evidence, with the changed gate requirements below superseding it.
Private installation, occupied officers, actual budgets and active Profiles were
not inspected or certified. Negative findings are bounded to tracked source.

## Finding: the missing institution-to-runtime connections

The new default sequence is approved policy in `contracts/citadel-mission-intake.md`
and Canon IV. No executable Citadel front door, coordinating mission registry,
Citadel intake cognitive Seat, distinct drafting decision, or approved-mission
Curia constitution/handoff path was found in the traced implementation.
These are separate missing connections; renaming the Curian caller cannot supply them.

| Source | Existing implementation or doctrine | Required disposition |
| --- | --- | --- |
| `offices/README.md:14–23`; tracked `offices/` tree | Cognition belongs to an occupied Seat under its Profile. No Citadel Office/Seat/Profile directory is present. | Define the missing holder's jurisdiction and qualification before dispatch. A directory's absence alone is not the proof: required-seat registry, resolvers and callers also lack this holder. |
| `RequiredV0SeatRegistry.php:7–120` | Founding registry includes Curian Seneschal, Chamberlain and Secretary plus other Office seats, no Citadel intake seat and no Clavium Locksmith. | Neither generic founding occupancy nor namespace creates the missing interview mandate/resource grant. |
| `todo/citadel-officer-model-assignments.md:3–29` | Explicitly planned standing model assignments; examples include Seneschal and other officers. | Not an appointment, occupied holder, active model binding or grant. |
| `src/Imperium/Runtime/Citadel/LegateGovernedCommissionIssuanceService.php:28–146` | Requires already active target, attestation, distinct authorized issuer and commissionable seat; issues bounded work. | It cannot identify or qualify the Citadel intake holder. Do not invent a Legate to satisfy this consumer. |
| `src/Imperium/Runtime/Citadel/LegateCognitionTurnAuthorizationService.php:32–175` | Authorizes a turn against accepted commission and existing issuer/target occupancy. | Not a standing interview entitlement. Its seat-only competing-occupancy scan also needs instance scoping before shared-root reuse. |
| `src/Imperium/Runtime/Citadel/DelegateMissionBoundedCognitionTurnService.php:22–105` | Consumes mission commission, model binding, access attestation and invocation activation. | Mission-bound execution machinery cannot serve as pre-mission interview authority. Other Citadel files are gateways, adapters, delivery/review or recovery of these same bounded routes. |
| `CurianAudience.php:24–105`; `CurianDeliberation.php:22–96` | T10-ready Curia and mission occupants precede cognition. Opening is persisted after cognition. | New intake must be durable before cognition and independent of any Curia. Existing mission communication remains a separate route. |
| `CurianCognitionAuthorityService.php:20–69`; resolver `:35–116`; `config/services.yaml:54–81` | Hard-coded `curia.seneschal`, Curian purposes and T10 occupancy; no Citadel resolver registration. | Add a competent Citadel authority producer/resolver, explicitly bind its actual cognitive identity, and reuse the generic governed provider chain. |

PHP shorthand above and below resolves under `src/Imperium/Runtime/` except
`Curian*`, `ProceedingStore` and dossier classes, which resolve under its `Curia/`.
Full paths are unambiguous in the source identity manifest.

## Decision 1 resolved by owner: Castellan

During this preparation the owner directed: **“The Citadel's cognitive agent is
Castellan.”** This settles the holder assignment. Castellan is Citadel's cognitive
holder for mission formation; a Persona version, officer class, qualification or
active occupancy is not inferred from that assignment. Its single purpose is to establish new-request intent
and form proposals under the Imperator's authority. The Seat owns relevance
judgment, interview questions, attributable understanding, separate drafting
readiness, proposal reasoning and approval-ready presentation. It does not govern
mission execution, select personnel, appoint officers, approve its own spending
or become another executive approval tier.

The minimum source change would define Citadel's institutional jurisdiction,
one Seat and one qualification contract/Profile source, then an authority and
identity binding to an actually eligible manifestation. Qualification must cover
cross-mission relevance, uncertainty, dissent, exact intent preservation, distinct
drafting/mission decisions, lawful information disclosure and handoff fidelity.
Record exact Persona/Profile lineage where applicable, qualification evidence,
appointment/mandate, manifestation and occupancy generation; bind the applicable
cognitive artifact into the actual invocation. The generic v0 exception must remain
truthful where applicable, but this campaign cannot reopen an operational instance's
founding window or fabricate a mature Profile for it.

The source does not establish an existing active Castellan binding. A mission
Seneschal is expressly not automatically this
holder (`offices/curia/seat-resident-seneschal.md`, initial jurisdiction and prohibitions).
Isolde and Chamberlain do not inherit substantive understanding judgment.

**Implementation prerequisite, not a repeated naming decision:** formalize Castellan's
Citadel Seat, qualification and mandate and connect legitimate appointment and actual
cognition binding. Candidate/qualification evidence belongs to later readiness, not
owner-invented IDs or commissioning in Batch 0.

Relevant historical conflict: `docs/curia-migration-plan.md:9–17,96–102` retired the
old Castellan architecture; `contracts/bootstrap-manifest.md:24` prohibits Castellan
as a constituent of the pinned initial bootstrap. The owner's current assignment
establishes the new Citadel responsibility; it does not restore the old architecture,
reopen that bootstrap or revive old authority. Future source migration must explicitly
scope the new Castellan mandate and mark superseded intake allocations while preserving
the sealed bootstrap's constraints. No Secretariat or provisional Curia is introduced.

## Shared identity, registry and routing

`src/Bootstrap/StateStore.php:12–16` binds one bootstrap file and lock to a project
root. `V0ActivationService.php:31–56` refuses another instance in that root;
`:91–125` records one T10 runtime. `ProceedingStore.php:12–15` stores records in
one Curia directory. Dossier/review/authorization services likewise use fixed
root-relative paths. `CurianDeliberation.php:32–39` compares a proceeding with
that single bootstrap instance. `config/routes.yaml` and `config/packages/routing.yaml`
are HTTP routing configuration, not mission lookup. `PlanningCommissionRouter`
delivers already authorized Office commissions, not requests across Curiae.
The generic cognition registry selects an authority resolver; it is not a mission
registry. These distinctions rule out treating today's `instance_id` as an already
implemented Citadel/mission/Curia hierarchy.

**Proposed minimum identity mapping:** one registered Citadel identity and trusted
root locator; immutable intake identity; mission identity allocated without creating
a Curia; and, only after approval, a distinct Curia runtime identity and registered
root. Keep source-owner Citadel identity separate from target mission/Curia identity.
Reuse the existing one-root runtime behind an explicit root-scoped adapter per Curia
where competent. Never rewrite Citadel dossier `instance_id` into a Curia ID or copy
bootstrap files to create an instance. Every foreign reference resolves by registered
owner, object, version and digest; arbitrary CLI paths, traversal and unregistered
roots refuse. Future principal delegation must bind both source and target scopes.

**Decision 2 recommendation:** a Citadel-owned minimal coordination ledger, with
pending intakes as well as approved/pending-constitution, active, suspended and
relevant completed missions. Store exact source references, status generation,
bounded objective/scope summary, visibility policy, freshness, Curia locator and
material commitment references. Do not replicate all mission source into the shared
index. Existing authorized mission status events update its projection; stale,
unavailable or inaccessible evidence remains explicit uncertainty. A missing legacy
registration is not evidence that no mission exists. Later migration must register
approved legacy references without rewriting their records or implying fresh approval.

An exact, authorized mission ID can route mechanically to its registered Curia.
An ambiguous request or relevance judgment goes to the Citadel holder under valid
interview resources. Consult only the Seneschal needed to resolve a material gap;
consultation and any added disclosure need their own applicable coverage. Present
reuse, join, amend, defer or deliberate independent work to the Imperator. Similarity
is evidence, never a mechanical veto or authority to amend existing work.

For the first implementation, serialize admission under one Citadel registry lock
and generation rather than inventing semantic lock keys. Persist pending intake
visibility before overlap analysis. Bind a cognitive overlap disposition to the
exact retrieved generation and visible evidence. Immediately before mission
admission, compare the current generation and referenced commitment versions;
if changed, return to attributable reassessment outside the lock. No provider call
holds a registry lock. Then atomically reserve one mission/constitution identity
against that generation, exact approval and overlap disposition, incrementing the
generation so two concurrent 'nothing found' observations cannot both commit.

Reservations carry owner, exact approval, expected generation, unique attempt token,
bounded expiry and status. Under the same lock, retry of identical input returns
the same reservation; changed input conflicts. A reservation is not spending or
appointment authority. On expiry before side effects, release with a tombstone;
after uncertain constitution effects, mark reconciliation-required and retain the
identity fence. Do not recycle it on a timer. Use durable intent/receipt/outbox
recovery across roots: the filesystem lock (`Persistence/AtomicTransition.php:17–42`)
is reusable mutual exclusion, not a cross-root transaction or durable commit log.
An intentional duplicate binds the exact disclosed overlap and owner disposition;
it receives a distinct mission identity and cannot reuse another mission's grant.

## Decision 3: interview resource policy

No standing interview grant was found. The prior first-call gap still holds:
Curian entry jumps from internal cognition authority to invoker without the
request → Imperator resource decision → Locksmith lease → durable claim steps.
That internal authority explicitly denies resource/credential/execution authority.
The generic chain is reusable after a Citadel resolver and authentic authority
mapping exist; the present fixed development Imperator actor is not authentication.

| Option | Owner interaction | Necessary enforcement; neither is currently complete |
| --- | --- | --- |
| Per-attempt, recommended smallest first slice | Present exact outgoing interview payload, recipient/model configuration, purpose, ceilings and expiry before each metered call; obtain one authentic decision. | Exact input/holder/phase binding; one short lease and durable claim; enforce input/output/cost limits at transport, reject unknown pricing/limit evidence. Carry session totals for disclosure, with no claim of a session grant. |
| Bounded session | One explicit interview-only grant covers a disclosed evolving transcript and narrowly described registry context; per-call checks avoid repeated owner approvals within those unchanged bounds. | New grant issuer/consumer mapping; maximum calls, cumulative input/output/cost/time, allowed destinations/data, per-call caps, expiry/revocation, atomic pre-I/O worst-case reservations, settlement from trustworthy usage, and no overspend by concurrent calls. Unknown outcomes retain reserved maximum until governed resolution; never refund on timeout alone. |

The session option must not be simulated by repeating `AUTHORIZED` with the
development actor. Each call still needs an exact derived authority, lease and
claim; scope change or budget exhaustion returns for amendment. Neither option
covers research, other Offices, protected source or proposal drafting by inference.
Disclosure includes that request/transcript and authorized registry context would
leave the machine. Current model/provider constants are source facts, not a model
choice or present price verification. No tariff, budget amount or live grant is
selected in Batch 0.

Concrete source bounds: `GovernanceCognitionRequestService.php:40–150` allows a
15-minute request; `GovernanceProviderResourceDecisionService.php:40–116` a decision
up to 10 minutes with positive token/cost ceilings; `GovernanceCognitionLeaseService.php:32–82`
a lease up to five minutes and exact same-instance active Locksmith occupancy.
`GovernanceCognitionInvocationClaimService.php:40–169` consumes lease and authority
before I/O. `GovernanceCognitionInvoker.php:19–44` passes configuration to the adapter
without enforcing those ceilings and returns text despite sealing response evidence.
Those limits and the authentic Locksmith/principal connection must be enforced,
not merely disclosed. The current source is not a usable interview grant.

## Distinct drafting object and pre-elaboration gate

The approved sequence is fixed; the object mapping below is **proposed implementation**.
Preserve a Citadel proceeding with exact original bytes, actor, question cursor,
transcript chain, intent version and evidence access provenance. Append the holder's
attributable understanding declaration, including understood intent, uncertainties
and dissent, with exact authority/claim/response-envelope and cognitive-identity
references. Only the holder makes this judgment. 'I understand' closes the interview;
agreement, feasibility and filled-field counts are not added completion criteria.

The holder may also state separate readiness and present an immutable **drafting
request**: identity/version/digest, proceeding/intent/understanding head, author and
occupancy, scope of proposal work, allowed inputs and disclosure/destinations,
resources/model/ceilings, exclusions, expected draft return, stop/amendment conditions,
expiry/revocation and planning-only limits. This is a request to prepare a proposal,
not an elaborated proposal smuggled into the interview response.

**Decision 4 recommendation:** use one exact planning authorization object for the
disclosed drafting scope. Map resource-bearing drafting requests to the Planning
Charter content in `contracts/mission-planning.md:96–130`; bind explicit Imperator
drafting approval to it and derive Planning Authorization. For present-material
drafting, use a proportionate drafting-request form with explicit no-investigation
bounds, still requiring the distinct approval. There is no executable Charter
producer/approval consumer in the traced source; this is new integration under
existing semantics, not a claim that legacy `CommissioningService` implements it.
Provider approval and planning approval may be presented in one exact owner act
when all terms are disclosed, with typed effects kept distinct; do not ask again
for an unchanged action already covered. Mission review remains later and separate.

A shared production gate must resolve both current attributable understanding AND
authentic, unexpired, unrevoked approval of this exact drafting version **before**
proposal prompt construction/dispatch or deterministic proposal building. Check
identity, intent, disclosure, authorized scope and resources again at result admission
and downstream consumption. The response declaring understanding cannot also contain
the actual proposal. A user keyword, readiness statement, clerical record, bare
boolean, rehashed file, old mission approval or interview lease cannot open the gate.

Decline closes applicable unused resources; deferral preserves records and suspends
continuation. Resume revalidates current scope, expiry, occupancy and budget; it
never revives a grant automatically. Intent changes invalidate dependent drafting
until the holder reassesses; a material change needs a new exact request/approval.
A successor cannot inherit the predecessor's cognitive judgment merely by copying
records. Reaffirmation must be attributable under its own authority. Unknown provider
outcomes remain fenced; a sealed response can be admitted after restart without
another provider call only through verified provenance and current eligibility.

## Reuse and consumer migration

Reuse the previous report's complete producer/consumer map; the two-condition
Citadel gate replaces its understanding-only proposal eligibility recommendation.

| Reusable surface | Citadel mapping and minimum correction |
| --- | --- |
| Curian entry, gateway and ProceedingStore | Preserve legacy evidence. New entry has Citadel owner/holder and non-Curian state; split interview, drafting-request and proposal contracts. Preserve exact bytes and question context; reserve attempts before I/O and compare complete replay fingerprints. No Curia-shaped intake fixture. |
| Generic governance request/decision/lease/claim/journal/envelope | Register a Citadel authority resolver; bind actual cognitive artifact and manifestation, exact transmitted payload and authenticated decisions. Propagate sealed response provenance to understanding/proposal records. Add real budget enforcement and revocation checks. |
| PlanningDossierAssemblyService `:15–29` | Extract/reuse numbered-line assembly and disclosure rules with explicit owner/target identities. It currently requires `turn.seneschal.mission_plan`, labels assembler Curia, fixes version 1 and returns existing proceeding/turn despite changed inputs. Add full fingerprint checks and immutable revision lineage, not copied Seneschal fields. |
| ModelRequirementCommissionService, DefaultModelFallbackOrderService, ModelSelectionPlanningDecisionService | Map Curia-specific issuer/occupancy and source proceeding to competent Citadel planning authority. Bind every returned decision to that exact proposal. Office/model investigation only through authorized planning commissions; never auto-generate model suitability evidence. |
| ImperatorPlanningDossierReviewService `:14–48` | Reuse numbered approval/line objections; extend authenticated exact-object acceptance to ordinary Citadel approval, objection and revision. Protected approval entry is a bounded existing route, not a generic authentication shortcut. Compare replay input, preserve old lines, revise under covered resources only. |
| MissionAuthorizationDerivationService `:15–29` | Preserve exact dossier/review lineage and false direct-execution flag. Introduce source Citadel and target mission/Curia scopes explicitly; existing holder is the dossier instance runtime. Its generic personnel-preparation flag is not a Curia constitution or Seneschal appointment disposition. |
| DelegateMissionCapabilityDemandService `:76–118,183–276` | After acceptance, validate approved target-scoped references instead of silently rewriting the source dossier. Proposal schema must include expected outcomes, mission seat, bounded duration, credential/perimeter and return/unbinding/custody/retirement terms before approval. Keep Step 1 non-executing. |

First-admission enforcement also covers direct `ProceedingStore::persist/appendTurn`,
`ImperatorActs` and approve-plan/authorize, `CommissioningService`, Profile derivation
requests, supplied-plan `ProtectedMission/Cli.php` → `Ceremony.php`, and any later
integration of `8244afa5:src/SourceReview/Proposal.php` / `Workflow.php`. Do not let
a missing origin field exempt fresh input: new-origin admission requires positive
lineage; allow legacy evidence only by its preserved verified identity and route.
Keep smoke/fixture producers outside production admission. A source-review snapshot
is input evidence, not interview completion or permission to build the new proposal.
No source-review merge or expansion of the closed protected mission is needed here.

## Decision 5: legitimate constitution and exact handoff

Institutional authority exists in doctrine, but the complete production path does
not. `offices/curia/doctrine.md` assigns constitution and Seneschal appointment to
the Imperator. `contracts/seneschal-suitability.md:20–69` makes Guildhall suitability
distinct from appointment; its demand assumes an authoring Curial proceeding and
Seneschal, requiring a Citadel mapping. `contracts/seneschal-succession.md:49–81`
describes qualification and authorized MasterMason transfer, not an implemented
automatic initial appointment policy.

`src/Bootstrap/MasterMason.php:16–30` delegates activation to v0 and explicitly
retires its old self-constructing path. `contracts/bootstrap-state-machine.md`
describes an initial-bootstrap-only sequence; it cannot be repurposed as normal
mission admission. `FutureInstanceImperatorPrincipalConstitutionService.php:33–55`
requires a future-instance root authority, refuses an operationalization seal and
creates only a pending-activation principal version. It does not constitute a Curia,
appoint a Seneschal, qualify Isolde or accept a mission. Its fixture-store contract
validator does not supply authentic owner authority. No such service was run.

**Recommended future mapping:** disclose mission Curia formation and its bounded
resource/personnel dependencies in the mission dossier. The authenticated Imperator
mission decision may cover an exact constitution disposition if disclosed and
competent; do not infer it from generic approval. Where a candidate is known and
eligible, an exact appointment can be included in the same presentation while
remaining a distinct typed act. Where unknown, stop at the specific pending selection/
appointment boundary after approved investigation. Do not invent mechanical
appointment authority or ask for a redundant decision on an unchanged covered act.

The future mechanical constitutor consumes only that exact constitution authority
and reserved mission identity. MasterMason applies admitted rules; Guildhall evaluates
suitability against Garrison evidence; Laboratorium derives applicable Profiles;
competent authorities approve/designate them; Conscription assembles and qualifies;
MasterMason binds under exact appointment authority. Each Curia has distinct Seneschal,
Chamberlain and cognitive Isolde manifestations. A shared Persona source may be reused
only lawfully; occupancy or authority is never shared implicitly. No generic v0
founding reactivation is used as a mission-spawning shortcut.

The handoff packet must bind: Citadel/intake/mission/target Curia identities; exact
plan and numbered dossier version; mission approval and derivation evidence;
constitution/appointment/qualification/binding references; original request and
complete ordered exchange (not summary alone); understood intent and its declaration;
drafting request/decision and relevant planning provenance; rationale, dissent,
unknowns and unresolved questions; overlap evidence/disposition and registry generation;
evidence custody/access references; mandate limits, expiry, amendments, stop conditions,
pending prerequisites and canonical source digests. Citadel retains originals; the
Curia receives an immutable digest-bound copy/reference set within lawful visibility.
Unavailable necessary source is a specific handoff gap, never a silently omitted annex.

Use a durable handoff ID keyed to exact approval+target+packet digest. Delivery is
not acceptance; retry returns the same receipt, and a changed packet needs a new
version. A receiving Seneschal's authorized cognition accepts responsibility within
the mandate or identifies a concrete gap/incompatibility, bound to its exact occupancy
and response provenance. No automatic repetition of the unchanged original interview
or second mission approval is required. A material amendment returns to competent
approval; acceptance alone authorizes no provider use, personnel action or execution.
Receipt/registry reconciliation after a crash must prevent a second Curia or duplicate
appointment, and incomplete constitution must remain fenced and non-addressable.

**Unresolved:** endorse explicit per-mission constitution and exact appointment acts
as the first implementation policy, including their combined presentation when terms
are known, or separately design a bounded appointment rule. The latter needs eligibility,
delegation, exclusion, conflict and revocation semantics; none is presumed today.
Source also requires a decision to represent mission Curiae as children under one
Citadel with registered root adapters, rather than independent newly founded Imperia.
The child model is recommended to avoid multiplying founding privileges/principals.

## Smallest complete implementation and proof plan

The minimum complete path is Citadel intake through approved, attributable Curia
handoff and receiving Seneschal acceptance. An interview-only patch is an intermediate
deliverable. Source work must supply the missing admitted contracts and production
adapters; fake prerequisites cannot justify calling constitution operational.

| Stage, only after discussion | Complete bounded deliverable |
| --- | --- |
| 1 | Selected holder/identity/root contracts; Citadel durable ingress and minimal registry; exact existing-work routing; authenticated interview resource path and enforceable limits; attributable understanding. |
| 2 | Drafting-request/Planning Charter mapping, explicit approval gate before every producer, authorized present-material proposal, complete disclosures/Step 1-compatible plan, numbered review and real immutable revision; new-entry consumer gates. Out-of-scope investigation stops at its specific pending commission. |
| 3 | Exact mission approval/target authority mapping, legitimate constitution and appointment consumers, fenced idempotent handoff and cognitive acceptance, integration proof through the preserved Step 1 boundary without executing the mission. |

Do not add a universal scheduler, mandatory all-Curia consultation, a Secretariat,
automatic personnel construction, or live activation to make this slice appear complete.
The immediate blockers are holder jurisdiction/qualification, resource authentication
and enforcement, multi-instance authority identity, drafting/Charter consumers, and
constitution/appointment consumers. Historic defects outside new-entry reachability
remain preserved; relevant authority bypasses above are part of the slice.

| Future isolated proof | Required observation at actual boundary |
| --- | --- |
| Bare synthetic Nomina-like objective; existing mission inquiry | Real command/container wiring with fake transport: exact intake survives resource refusal; zero Curia/appointment calls before mission approval; existing ID routes to its registered Curia; ambiguous ID asks under valid cognition authority. |
| Cognitive identity and dissent | Actual dispatch contains applicable authorized cognitive artifact and exact exchange; response claim/envelope binds holder and generation. Scripted understanding with objection closes interview while all drafting/execution calls remain zero. This proves attribution mechanics, not semantic competence. |
| Drafting guard matrix | Spy on every real proposal dispatch/build: zero calls for missing/forged/stale understanding, user/clerical declaration, readiness alone, no/refused/deferred/expired/revoked/changed-version drafting approval, wrong actor/instance or resource scope. Positive: separate approved call returns exactly one attributable dossier. |
| Resources | Actual transport sees bounded inputs and output limits; unknown tariff/ceiling refuses before I/O. For session option, concurrent calls cannot exceed aggregate reservations; cancellation releases only demonstrably unused allocations; unknown outcome holds budget and never retries automatically. |
| Shared registry | Two processes read generation N then attempt admission: only one wins; loser reassesses N+1. Pending/stale/hidden evidence is handled explicitly. Deliberate independent mission succeeds only with exact overlap disposition and own authority. Expired reservation before effect releases; uncertain effect remains fenced. |
| Version, replay, restart | Same identity+bytes yields same result/no extra calls; changed bytes conflicts. Crash after sealed response resumes without I/O; post-I/O unknown stays unresolved. Intent change and succession fence affected continuations; old transcript and dossier bytes remain unchanged. |
| Proposal consumers | Direct-store, legacy act/commission, Profile request, supplied-plan and future source-review new input cannot bypass the two-condition gate. Test authoritative records and real DI, not parser-only or source-string assertions. Historical identified evidence stays readable without gaining new authority. |
| Constitution | Zero effects with drafting-only approval, generic personnel flag, wrong mission/target, stale qualification, missing appointment, existing-root v0 attempt or pending principal record. Positive uses explicit synthetic authority through the real constitutor, distinct three occupants and one target. Two deliveries/crash retries produce one constitution and one handoff. |
| Handoff | Tampered/omitted original exchange, wrong approval/mandate or reused other-Curia occupancy refuses. Correct packet permits attributable acceptance with dissent or specific gap; Isolde never accepts for Seneschal; missing Secretary does not block direct authenticated access. Acceptance produces zero execution/commissioning calls. |
| Downstream and migration | Approved plan resolves from Citadel into target Curia without changing digest/meaning; unchanged terms need no repeat approval. Missing Step 1 fields refuse before approval/entry, no fresh CLI supplementation; old worktrees and completed mission evidence remain unchanged. |

Use fresh isolated synthetic roots, fake provider/credential boundary, controlled
clock and crash/concurrency barriers. Never boot real request/respond for discovery.
After implementation, exercise the production commands and DI before the full
PHP 8.4 locked-dependency CI command `vendor/bin/phpunit tests` on the exact candidate.
No new runtime characterization was needed in Batch 0: source equality proves the
prior tests' implementation unchanged, while new architectural gaps have no executable
path to characterize. Documentation/static source and packet checks are the actual
preparation verification; no PHPUnit or live cognition pass is claimed.

## Discussion disposition

The holder assignment is settled: **Castellan**. Four choices remain: child-Curia
registry/root/admission model; per-attempt versus bounded-session interview resources;
drafting-request/Charter object mapping; and explicit constitution/appointment policy.
The approved sequence is not reopened. Castellan's qualification and runtime binding
remain implementation/readiness prerequisites. Recommendations for the four remaining
choices are preparation proposals, not adopted policy. Discuss them before Stage 1.
No credentials, private source,
live cognition, commissioning, bootstrap/Profile activation, protected-state changes,
mission execution, deployment, push, merge or branch deletion occurred.
