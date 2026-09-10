# O2-B0 — original admission and current authority

Status: PREPARED_FOR_NEXT_LOCAL_IMPLEMENTATION. This is a bounded engineering
specification, not runtime authority, trust enrollment or implementation evidence.
O1 is reviewed/integrated; this batch closes the admission boundary that its pure
projections deliberately do not implement. Read the [D2 proposal](../docs/provider-onboarding-authority-proposal.md),
[F1/F2 continuation](provider-onboarding-continuation.md), [retry amendment](provider-onboarding-retries.md),
[approved values](../docs/provider-onboarding-policy-approval.md) and
[campaign](../docs/next-campaign-provider-onboarding-o2-authority-admission.md).
Their exact authority registry and approved policy values remain governing.

## Finish line and excluded effects

Implement a fixed-root, original-record admission and resolution boundary for the
selected FRESH bootstrap route. It must verify separate public trust, signed exact
objects, complete policy terms, retained original references, currentness and
revocation, and produce reproducible admission records under the existing Citadel
aggregate. Real cryptographic verification and protected-source resolution must
be exercised with clearly synthetic keys and temporary roots in tests. No success
from a fixture is a genuine installed Operator, institution or admission.

In scope: administrative public-trust ingress as code only; signed act/object and
policy admission; bounded source retention/reference resolution; revocation of
act/policy/issuer; read-only current-authority resolution. Admission uniqueness is
required here. Shared sequence/command/step/slot consumption, budget reservation,
provider/credential custody and dispatch remain O2-B1. Adapter production is O3-B0;
founding, root-window contention and Augur bridge are O3-B1; application O4-B0;
CLI O5-B0. Do not add a second claim ledger or perform those later effects.

An O2-B0 admission says that a specific original and its bounded source chain were
admitted. It never means a call, slot, budget, founding placement or application
has been consumed. Current authority resolution is valid only for the observed
aggregate snapshot; O2-B1 must re-resolve inside its own atomic reservation and
at each required custody checkpoint. No transferable boolean/token bypasses this.

## Trusted boundary and source reuse

Use `Runtime/Onboarding/AuthorityAdmission/` for new classes, class-level Symfony
Exclude and explicit fixed project-root construction. Do not rewrite services.yaml,
existing O1 classes, legacy schemas or historical test pins. New operational wiring
is not part of this batch. Use existing dependencies and CanonicalJson, Clock and
FormationJournal as appropriate; inspect their exact behavior before reuse.

FormationJournal supplies the `citadel-formation` lock and verified frame chain;
it does not confer competence. New state lives in its versioned `state.onboarding`
subtree, initially only `schema`, `trust`, `acts`, `policies`, `evidence`,
`revocations`, `admissions`. Schema is `imperium.onboarding-authority-state/v1`.
Maps are keyed by validated identities and contain retained original records, not
caller paths. Only the separately authorized deployment-administrator enrollment interface may
initialize an absent subtree and atomically retain its original enrollment receipt
and trust record. Incoming act/policy admission and read-only resolution must refuse
absent trust without initializing or learning it. Unknown versions, corrupt state
or unexpected keys refuse. Define a deliberate
version migration when O2-B1 adds its maps; do not silently accept new fields.
Preserve all existing aggregate fields and frame format.

The protected root is deployment-owned and never selected by an incoming request.
All complete original bytes and derived receipts for a local admission are retained
in one frame transition. Set bounded input/record/graph/reference-depth limits and
document them before coding. No network fetch, arbitrary path traversal, remote
URL resolver or callbacks supplied by record content. Source locators remain text.
Validate bytes before mutation; recheck currentness and head under the lock. Avoid
reentrant locks, credential/network work or writes to a second store in callbacks.
FormationJournal::change currently exposes state, not the full predecessor frame.
Declare a compatible transition interface that observes head and state under the
same citadel-formation lock before coding. A read() followed by change() is not an
atomic head check; nested inspect()/change() is not an acceptable substitute. Any
mechanical adapter must preserve the existing frame format, custody and lock.

Existing FormationSignatures trust is solely CITADEL_MISSION_FORMATION; NativeTrust
has its own domain/effect set. Neither imports bootstrap competence, even for an
identical public key. Legacy development acts, checksum seals, CURRENT_ACTIVE
labels, BaseInput, BaseProposal and ParsedResponse are not trusted source adapters.
No test-only production switch, self-signing fallback or caller-provided verified
flag is allowed. Missing genuine producer compatibility yields a typed refusal.

## Trust, signed originals and references

Retain the D2 common H header and signed envelope exactly: `{payload,signature}`;
payload fields `schema,domain,instance_id,citadel_id,trust_fingerprint,issuer,effect,
object_digest,policy_ref,expected_head,issued_at,expires_at,nonce`. Schema is
`imperium.operator-bootstrap-act/v1`, domain `IMPERIUM_OPERATOR_BOOTSTRAP_V1`.
Verify detached Ed25519 against the separately enrolled raw 32-byte public key,
canonical payload and exact 64-byte signature. Canonical base64, strict types,
duplicate-key rejection and exact schema field sets must precede verification.
Do not borrow a foreign schema's signature or repair signed payloads in place.

Public enrollment code is a separate deployment-administrator interface, absent
from act/policy admission. It requires independently confirmed key fingerprint,
instance/Citadel identity, Operator competence, exact allowed effects, validity
and original enrollment receipt in trusted custody. The incoming act cannot supply
its key or invoke enrollment. Fail closed when deployment custody/competence
evidence is absent. No live enrollment is authorized; DEFER_ENROLLMENT remains.
Synthetic tests may exercise that exact administrative interface in isolated roots.
Key rotation and automatic inheritance of formation/native enrollment are unsupported.

For this FRESH batch the supported issuer is `{kind: operator,id}` with actual
OPERATOR_BOOTSTRAP_POLICY competence. Institution issuer variants in the full D2
registry remain recognizable but refuse `UNSUPPORTED_ISSUER_SOURCE` unless a real,
compatible delegated-competence/current-incumbent producer is implemented and
explicitly within the selected scope. This preparation selects no such extension.
Do not make institution-only effects succeed using an Operator-shaped substitute.
Existing-installation qualification, succession and cutover remain unsupported.

Every H record resolves its full `{schema,id,digest}` reference, verifies its own
canonical content digest and its retained producer/source chain. Digest prefix is
`sha256:` for D2 references; existing FormationJournal heads use its existing bare
hex digest. Define the typed boundary explicitly; never relabel a signed original
or feed a D2 digest to an old consumer by stripping its prefix silently. H's
record_digest excludes itself. Do not add commit_head to the closed common H
header. Where an admission-receipt body declares a predecessor head, bind the
observed predecessor frame explicitly; body-specific heads in other D2 records
keep their own declared semantics.
Retain raw-byte hash separately from canonical semantic identity. Reject ambiguous
JSON objects/lists/numbers before canonicalization; preserve original signed bytes.

Full identity includes instance, Citadel, issuer, schema/version, object digest and
policy reference. Reject missing, conflicting, cyclic, foreign or unresolved sources.
No ID-only lookup, latest-version substitution or arbitrary caller-supplied object
can satisfy a retained reference. A genuine authorized public-source admission
retains bytes and source limitations; it does not declare their claims true or
grant Oracle canonical stewardship. OFFICIAL remains a claim needing provenance.

## Admission and currentness rules

Use the trusted Clock in production. D2 act/trust/H timestamps are integer UTC Unix
seconds, distinct from O1's milliseconds. Any explicit conversion must be checked
for overflow and tested; never infer units. Enforce half-open validity, no future
issue, issue within trust validity and expiry no later than trust/policy where
applicable. Current policy, act, issuer and source revocations apply to new dependent
work. Resolve the original enrollment and exact permitted effect; a valid signature
alone does not establish competence, scope, object or current authority.

New signed admission compares expected_head to the actual aggregate head under
the lock and rejects stale input. Admission uniqueness key is
`(instance_id,trust_fingerprint,nonce)`. Identical already-admitted envelope/object
recognizes its immutable admission without a second write, even after later heads
or expiry; changed content for the key refuses conflict. Recognition is explicitly
historical and must not return current authorization. Current resolution separately
revalidates every dependent source; it does not demand equality to an old act's
historical expected_head. Returned observations bind the newly observed head.

Policy authorization cannot be policy-derived or self-authorizing. Null policy_ref
is allowed only for AUTHORIZE_BOOTSTRAP_POLICY or exact REVOKE_BOOTSTRAP, per D2.
Revocation is an independently competent signed Operator act over the exact target
and expected head; append it atomically, never delete or rewrite prior originals.
Historical receipts remain recognizable; future dependent effects fail. A revoked
issuer cannot sign a new act that restores its own rights. Unsupported recovery or
key rotation refuses; do not create an emergency authority path.

Separate the supplied authority variants exactly: `signed_act` resolves original
act plus admission; `policy_effect` resolves original policy plus admission, exact
slot/digest/terms and derivation-input refs. The F2 registry is exhaustive; P is
allowed only in its enumerated rows. No null act placeholder, fake signature or
runtime signer. Batch supply is ordered independent signed originals, not a new
authority type or excuse to precompute future heads. Never admit unconsumed rights
from the presence of a slot. O2-B0 resolves static authorization and conditions;
O2-B1 owns global consumption keys and transactionally checks availability.
Dynamic prerequisites from later stages must refuse when absent, not be satisfied
by a raw O1 proposal or fabricated receipt. Do not execute terms as expressions.

Retention is not completion of ADMIT_AUTHORIZED_RECORD. Apart from separate trust
enrollment, policy authorization and revocation, B0 may retain signed originals,
exact proposed objects and bounded supporting bytes for later resolution. It must
not publish a completed F2 effect, a successful progression result, or evidence
that a step/slot was consumed. In particular, retaining public source bytes in
state.onboarding.evidence does not complete ADMIT_BOOTSTRAP_EVIDENCE or satisfy
a downstream prerequisite requiring that completed effect. Completion of any
progressing F2 effect requires O2-B1's atomic step/authority consumption, including
S and P supply. Static resolution must report pending execution separately from
current authorization and refuse missing dynamic results. Test that no B0 receipt
can be substituted for that future execution/consumption receipt.

## Policy closure

Validate all D2 owner-policy body fields, complete F1 steps, F2 slots/tagged rules
and v1.3.1 assessment groups/dependencies. Explicitly record chosen bounded
implementation shapes in the implementation report without altering the approved
source bytes. Unknown actions/effects, extra fields, duplicate IDs, cycles, invalid
run conditions, wrong action/effect pairing, forward/undefined dependencies,
non-one-use slots, expired slots, wildcards and incomplete finite tuples refuse.
Validate the full graph before filtering it to the selected route.

Require FRESH, DeepSeek/API key, D2-A, unchanged P1–P9 limits/configuration/aliases,
exact W1/W2/W3 and public-data scope, empty safe-retry allowlist and complete exact
permitted candidate/Profile/target refs. Original approval JSON is an auditable
chat decision record, never a signed executable policy or enrollment receipt.
No silent defaults for unresolved genuine Profiles, deployment custody, root/window,
credential binding, access, tariff or holder. Those sources have different future
stages: a policy may refer to predeclared future outputs using only the reviewed
typed graph rules, but dependent authority stays unresolved until genuine originals
exist. Never replace future-output references with invented IDs or completed receipts.
Static policy admission does not imply readiness of every future effect.

## Required adversarial evidence

- Real signing/verification and protected admission in temporary roots; no incoming
  key learning; absent/wrong competence, fingerprint, domain, instance, Citadel,
  issuer, effect, signature, object or enrollment provenance all refuse.
- Strict malformed JSON/schema/type/number/duplicate-key handling; bounded input,
  nested source cycles, invalid refs/digests, tampered retained originals, ID reuse,
  version substitution, path/URL injection and source text cannot alter behavior.
- Complete selected policy/graph/group/slot validation, every F2 S/P combination,
  no policy self-authorization; unsupported institution/existing-route effects;
  unknown/extra/missing source and conditional prerequisites refuse.
- Currentness at each boundary, second/millisecond confusion and conversion overflow;
  future/expired/revoked trust/act/policy/source and wrong expected head.
- Identical historical recognition versus fresh current resolution; same-nonce
  changed-body conflict; intervening valid frames do not require re-signing policy.
- Two concurrent admissions at one head, atomic original-plus-receipt publication,
  revocation/admission ordering, interruption before/after frame publication,
  tampered chain, restart resolution and refusal without partial mutation.
- Real producer-to-consumer evidence through the new trust/admission/resolver code,
  not direct fixture array insertion. Development acts, foreign native/formation
  keys and O1 result objects cannot become admitted bootstrap authority.
- No slots/claims/budget/credential/dispatch/assignment writes or operational caller;
  unchanged old records, source pins, O1 semantics and five false live flags.

These are required future implementation tests, not results of this preparation.
The campaign specifies validation commands and delivery. Genuine installed trust
and all live prerequisites remain separate commissioning work; their absence must
not block writing this offline software or be hidden by synthetic acceptance.
