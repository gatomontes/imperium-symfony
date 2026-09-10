# O2-B0 implementation boundary

Declared before implementation: new AuthorityAdmission classes use the existing
FormationJournal via `changeAtHead(callable)`, whose callback receives mutable
state and immutable predecessor `{generation,digest}` under citadel-formation.
Existing `change(callable)` delegates to it without changing its caller contract.
No read/change gap or nested lock. Frame format and publication mechanics stay intact.

Ingress limits: 1 MiB JSON, container depth 32, nonempty text up to 65,536 bytes,
256 supporting records per bundle, 4 MiB total ingress, reference depth 16 with
8,192 visits per traversal, 4,096 admissions and 8,192 source/policy records.
JSON permits strict objects, lists, strings, booleans, null and nonnegative integer
literals through PHP_INT_MAX. Empty/numeric-key objects, floats, negative values,
exponents, duplicate keys, invalid UTF-8 and BOM refuse. Public timestamps are Unix
seconds from 1 through 253402300799; this rejects contemporary milliseconds and
states an explicit supported range. No implicit O1 millisecond conversion.

Public APIs are fixed-root `Enrollment::enroll(receiptJson, confirmedFingerprint,
expectedHead)` (deployment administrator only), `Admission::retain(envelopeJson,
objectJson, supportingJsonList)`, `Resolver::current(authority,effect,termsRef)` and
`Resolver::original(ref)`. Caller data never picks root, key source, clock or service.
Production wiring is excluded. Privileged constructor root/instance/Citadel/operator
identity and enrollment confirmation are deployment inputs, not incoming act fields.
No current human competence can be established by PHP alone; protected deployment
custody and independent confirmation are prerequisites of the administrative API.

All retained public objects use exact D2 H. Enrollment H schema is
imperium.bootstrap-enrollment/v1; body is `public_key,fingerprint,issuer,competence,
effects,not_before,expires_at,custody_receipt`. Its source-free receipt is the
deployment-administrator original; custody_receipt is a nonempty administrative
provenance identifier, never a verified boolean. Key/fingerprint, identity, competence
and allowed effects must exactly match independently configured expectations.
Trust H schema is imperium.bootstrap-trust/v1 and uses the unchanged D2 trust body
including enrollment_receipt_ref. Initial enrollment alone creates onboarding state.

Supporting H schema imperium.bootstrap-source/v1 has body `kind,content,
content_digest,limitations`. Content is bounded nonempty public text; its SHA-256
binds the exact bytes. It is retained source material, not an admitted Profile,
credential, downstream effect or verified provider claim. Signed objects reference
all supporting sources through their H sources. Every bundle member must be reachable;
missing/duplicate/cyclic/conflicting refs refuse. Source originals and their signing
admission remain linked; current resolution rechecks those source admissions.

Act H imperium.bootstrap-retained-act/v1 contains envelope, object_ref and raw
envelope/object hashes. Admission H imperium.bootstrap-original-admission/v1 body
contains act_ref, object_ref, predecessor_head, authority_consumed=false,
effect_completed=false. Raw submitted strings are retained separately in the same
frame. Historical recognition returns the original receipt with current_authority
false; current resolution verifies retained originals again at the observed head.

Policy H remains imperium.operator-bootstrap-policy/v1 with all D2 body fields.
The selected graph has the exact finite step/slot IDs and group/run/input selector
structure of the reviewed o0-public-preparation.json. Unresolved template descriptors
are never accepted: each is replaced with a full retained D2 source reference.
Declared IDs are a bounded v1 implementation choice; renaming/reordering the graph
requires a new supported contract. Signed/P supply follows the exhaustive F2 registry.
All selected public policy values, target roles, request configuration, alias
limitation, exact candidates and profiles are validated; source retention does not
resolve future genuine competence. Detailed field checks and examples live in the
policy validator and producer-to-consumer test fixture.

Only Operator FRESH effects are supported. Institution/standing/cutover effects
refuse. Non-policy/revocation effect terms are retained as bootstrap-proposed-terms H
with `effect,terms,required_completed_refs`; terms is a retained public source ref.
This wrapper is expressly proposed intent, never a completed D2 transition record.
Dynamic prerequisites cannot be satisfied by any B0 source/admission receipt.
Current static resolution returns CURRENT_STATIC_AUTHORITY_PENDING_EXECUTION with
execution_authority, authority_consumed and effect_completed false. Policy-derived
eligible-binding/assessment terms remain DYNAMIC_PREREQUISITES_MISSING until later
producers exist; no callback evaluates arbitrary conditions. Exact rules can only
resolve the predeclared object. O2-B1 must recheck and consume atomically.

## Exact implemented policy choices

Policy body has exactly the nineteen D2 fields declared in Policy::validate.
policy_version is o2-fresh-v1. SelectedPolicy freezes the reviewed template's finite
graph and approved numeric values in compiled source; it never reads deployment
documents. Placeholders require full retained refs; ordinary input placeholders
become {kind: public_ref, ref}. Groups/dependencies and twelve attempt nodes are
exact. Both S/P supply modes use the complete F2 registry; batch is not a third mode.

Candidate binding keys: binding_ref, provider, model_id, model_version,
configuration_ref, adapter_ref, mapping_ref, revision_pin. Exactly one Flash and
one Pro binding is accepted in v1, with the explicit alias limitation. Targets have
role, profile_ref, predicate_ids, permitted_bindings, ordered Courtthane/Locksmith.
Permitted bindings must be a nonempty subset of those exact bindings. Requirements
content has selection_rule=role_capacity_middle_tier_v1 and unique
required_predicates including all ten generic predicates plus a Profile predicate.
The rule identifier is O1's actual algorithm. Exact source references do not make
retained material institutionally approved Profiles; later consumers need genuine
competence and currentness evidence.

credential_ref resolves a credential-binding source with content containing
exactly authentication=api-key, provider=deepseek and opaque_binding. It identifies
public intent and cannot release credentials. configuration_ref resolves a
request-configuration source with the exact disabled-thinking JSON settings.
Budget, adapter/mapping/binding/Profile refs retain supplied original material;
they cannot prove remaining budget, adapter support or current incumbency.

expected_assignments resolves an expected-assignments source with two ordered rows:
role, generation (nonnegative integer), binding_ref (nullable). All values must be
supplied; zero generation is never defaulted. Later application must compare these
expectations against actual authoritative state. Workload content has original_sha256,
original_bytes and groups=[W1,W2,W3]. SHA-256
0811784aae9263b2507ea1ade05586a64ae9ee8c550559612520a1694945212f binds the reviewed
medium-capacity amended workload bytes. The earlier P1–P9 hash
b8260d894396373ff3068567601e807167a9dcb8e469892528b3fbec99018a28 stays historical and
unchanged; it is not the amended workload hash. See the retained medium-capacity
decision/selection contract. Every assessment group must use that same workload.

Exact/eligible slot refs resolve proposed-terms H objects of the matching effect.
A new signed dependent act must name pre-enumerated terms in a matching signed_act
slot. Identical existing objects preserve their first provenance entry when a
separate signed admission is added. The policy link is retained in the signed
envelope; inserting it into pre-enumerated terms H would create a policy/terms
hash cycle. Conflicting IDs refuse. Bundle order does not affect recognition;
original raw bytes are never rewritten.

Every policy-derived slot in the selected graph still has ordinary progression
prerequisites, so current resolution refuses missing completion evidence. Founding,
access, assessment and application refuse missing genuine dynamic producers.
Signed policy and exact evidence/mapping intent can expose static observations
with all effect/execution flags false. No observation is a completion receipt or
an O1 conversion. No downstream transition producer is implemented here.

Revocation uses H schema imperium.bootstrap-revocation/v1 and exact body
{target_kind,target_id,expected_head}. Act targets are exact 48-hex nonces; policy
targets are retained policy IDs; issuer targets are the configured Operator ID.
Missing/unsupported/already revoked targets refuse. Originals remain retained.
Enrollment alone sets the fixed Citadel identity and onboarding subtree while
preserving prior aggregate data. Protected storage remains the custody boundary
against wholesale frame-chain replacement, as in FormationJournal.
