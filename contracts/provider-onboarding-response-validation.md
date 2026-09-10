# O1-B1 — complete response validation boundary

Implementation specification for the next offline batch. This refines the nested wire shapes left implicit in docs/provider-onboarding/o0-workloads.json. It does not revise owner policy, the original workload/approval bytes, authority semantics or the existing medium-capacity algorithm. Preserve those historical bytes. The fixed decoder must use this declared shape, not infer types from an example response.

## Inputs and output

Input is raw response bytes plus an immutable expected context: group W1/W2/W3, input digest, exact holder/Profile refs, complete approved candidate refs, and frozen public evidence refs. Context construction rejects malformed/duplicate refs or unknown groups. Context equality/membership is a consistency test against supplied inputs, not proof those inputs were genuinely admitted. No implicit file, network, clock, trust-root or credential lookup.

Output is a typed parsed response with raw bytes/raw SHA-256, declared schema/group/holder/Profile/input identity and validated claim data. Its name and status must describe parsing/consistency only. It is not a BootstrapAssessmentAdmission record, H record, selection result, verified fitness proof, receipt or effect authority. Keep it impossible to pass directly to AssignmentSelector as a RoleInput: a later actual admission/fitness verifier owns that explicit conversion.

Raw JSON is UTF-8 without BOM; at most 1,048,576 bytes, maximum 32 nested object/array containers (the root counts as one). Invalid encodings, trailing data, duplicate decoded member names at any depth, invalid escapes/surrogates and unsupported types refuse. Object names differing only by JSON escape spelling are duplicates. Object and array types must remain distinguishable until shape checks finish; in particular `{}` is not an empty list and an object with numeric keys is not a list. These structural bounds follow the existing public envelope limits and the selected response-byte ceiling; they grant no remote guarantee.

Accept ordinary legal JSON whitespace and member ordering. The serializer proposal's canonical output preference does not justify rejecting semantically valid provider whitespace. Retain the original byte digest separately from any normalized representation. Do not convert huge JSON numbers to floating-point evidence: these response shapes contain no numeric fields, so any number or boolean in a declared string/reference/list/object position refuses.

## Exact closed shapes

Every object has exactly its listed keys. Every list is a JSON array. Text means a nonempty UTF-8 string (whitespace-only refuses); no coercion or trimming of identity values. Empty text lists are permitted. No URL in free text becomes admitted evidence.

| Object / field | Required shape |
| --- | --- |
| D2 ref | `{schema,id,digest}`; nonempty schema; ID matches `[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}`; digest is `sha256:` plus 64 lowercase hex |
| W1 root | `{schema,group_id,input_digest,holder_ref,profile_ref,candidate_rows,contradictions,unknowns,evidence_refs}` |
| W2/W3 root | W1 root fields plus `capacity_order` |
| schema | W1 `imperium.bootstrap-assessment-result/v1`; W2/W3 `imperium.bootstrap-role-assessment-response/v2` |
| candidate_rows | Exactly one row for each approved candidate, none extra, no duplicate reference; row order is immaterial |
| Candidate row | `{binding_ref,predicates,fit,evidence_refs,limitations}` |
| predicates | Object containing exactly the ten keys listed below; each value is `{disposition,evidence_refs}` |
| disposition | Exact `PASS`, `FAIL` or `UNKNOWN` |
| fit | Exact `FIT`, `NOT_FIT` or `UNKNOWN` |
| evidence_refs | Unique array of exact D2 refs belonging to frozen evidence; empty when support is absent; PASS must have at least one reference |
| limitations, contradictions, unknowns | Arrays of nonempty text strings; retain their contents as claims, never as instructions or authority |

The ten predicate keys are identity_runtime_mapping, evidence_support, minimum_capability, exact_role_profile_fit, access, admissibility_data_constraints, complete_tariff, bounds_usage_support, contradictions and uncertainty. Schema/group must agree with expected context, and input_digest/holder_ref/profile_ref must match it exactly. Equality of refs includes schema, ID and digest, not ID alone.

A claimed FIT row must report all ten predicates PASS with supporting frozen refs; otherwise refuse the inconsistent report. This is necessary consistency, not sufficient genuine fitness. NOT_FIT/UNKNOWN remain explicit claims; never promote them because their other fields look favorable. Free-text contradictions/unknowns do not resolve themselves or supply permission. Later evidence admission evaluates their substantive impact; this decoder returns them without claiming all mandatory evidence passed.

W2/W3 capacity_order uses the exact ranked/unknown variants already defined in contracts/provider-onboarding-assignment-selection.md. Ranked tiers require nonempty binding/evidence refs and rationale, ascending declared capacity order, and each declared FIT binding exactly once with none extra. Validate full ranked coverage even if future permission filtering would leave zero or one. Unknown may be structurally valid; it cannot resolve a later choice among multiple candidates. The decoder can check references/coverage but cannot independently prove the model's ordering is true or evidence-supported. Reuse the existing CapacityOrder value parser where appropriate after preserving JSON object/list distinctions and enforcing reference bounds.

All malformed or inconsistent input returns a typed refusal or a narrowly documented exception. It creates no record and consumes no slot. Do not catch arbitrary internal failures and relabel them as a validated response. Tests must exercise the real raw-byte entrypoint, not only pre-decoded arrays.

## Compatibility and isolation

W1's raw schema name intentionally differs from the stored H assessment-result body's meaning: do not confuse the raw response with a genuine institutional record. W2/W3 retain the v2 extension; no new authority-bearing schema is introduced. No fake signed act, hard-coded genuine holder, forced FIT candidate or default provider Profile.

Use class-level Symfony Exclude attributes, as established by the merged O1 correction. config/services.yaml and historical ledgers remain byte-for-byte unchanged. The existing selector's raw-array projection API is not widened or relabeled as authenticated. This batch has no operational consumer, automatic selection, CLI, provider SDK dependency, persistence or live I/O.

## O1-B1 local implementation

`ExpectedContext::fromInputs` accepts the group/digest and closed PHP reference arrays,
with list-valued candidate/evidence collections. It copies these into immutable parsed
references. The context is supplied expectation data, not an admission certificate.
`ParsedResponse::parse($originalBytes, $context)` is the sole complete raw-response
entrypoint. Its private constructor and immutable nested claim objects retain the
original bytes, prefixed SHA-256 digest, identities, claims and optional CapacityOrder.
There is no RoleInput conversion or operational consumer.

`RawJsonDecoder` recognizes the response subset of JSON: strings, arrays and objects.
Numbers (including huge integers/exponents), booleans and null are rejected at the raw
boundary because no field in this contract permits them. The scanner checks decoded
member names before assignment, preserves objects in a distinct JsonObject wrapper,
and delegates string escape/UTF-8/surrogate validation to PHP's JSON string decoder.
It accepts legal JSON whitespace and arbitrary member order. The root container is
one; each nested object/array adds one, while strings add none. Exactly 32 containers
and 1,048,576 bytes are accepted by the raw decoder, subject to the declared shapes.
No canonicalized bytes replace the original response attribution.

Malformed bytes or inconsistent shapes throw InvalidArgumentException with fixed
reason messages; a JSON string error may retain its JsonException cause. Programming
errors (including wrong PHP argument types) are not caught or relabeled. The parser
performs no file/network/clock lookup and creates no institutional records. Capacity
objects are projected into the existing CapacityOrder parser only after strict raw
object/list, reference, frozen membership and full FIT coverage checks. Ascending
order remains the provider's declared order, not independently proven capability.

A later admission/fitness verifier must authenticate the actual context and original
evidence, evaluate substantive contradictions/unknowns and all mandatory predicates,
and only then explicitly construct trusted selector projections. Parsing cannot
satisfy that obligation. This implementation remains pending source/integration review.
