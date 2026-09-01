# Atomic Transition Evidence Independent Verification Remediation Preparation Batch 0

## Result

`PREPARATION_BATCH_0_COMPLETE_INDEPENDENT_VERIFICATION_BOUNDARY_CLASSIFIED`

Preparation retains the controlling posture
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
It inventories and classifies only. It does not inspect the private receipt,
execute a verifier, rerun a mission, create or handle signing material, change
runtime source or restore campaign closure.

## Copied-constant acceptance surfaces

| Surface | Current acceptance | Defect | Classification |
| --- | --- | --- | --- |
| Package identity | The reconstructor compares schema, mission ID, source commit, PHP version, retention label and package digest with constants copied from the retained summary. | Exact package pinning proves only that the selected bytes were presented. | `PINNED_NOT_INDEPENDENTLY_VERIFIED` |
| Source/build bindings | Ten digests in `EXACT_BINDINGS` are copied from the producer summary and compared field by field. | Source tree, build artifact, lock, runner and mission implementation are never read or recomputed. | `COPIED_CONSTANT_ACCEPTANCE` |
| Receipt | `private_receipt_digest` is copied into `EXACT_BINDINGS`. | No receipt byte, section, seal, reference or conclusion is consumed. | `DIGEST_ONLY_UNAVAILABLE_TO_CONSUMER` |
| Acceptance matrix | The complete eight-entry producer matrix is a class constant. | No journal, winner, receipt, mutation or comparison is independently evaluated. | `COPIED_CONCLUSION_ACCEPTANCE` |
| Exclusion observation | The producer boolean `complete_chain_content_exclusion_observed=true` is required. | The retained chain is not independently scanned. | `COPIED_BOOLEAN_ACCEPTANCE` |
| Non-authority perimeter | Five producer `false` booleans are required. | No artifact or receipt derivation proves the absence of authority, state, credential, provider or effect behavior. | `COPIED_BOOLEAN_ACCEPTANCE` |
| Producer disposition | The reconstructor does not read `disposition` directly, but requires the digest of the complete summary that includes `disposition: PROVED`. | The disposition remains indirectly pinned inside the accepted package. | `INDIRECTLY_IMPORTED_BY_PACKAGE_DIGEST` |
| Reconstruction conclusions | Five successful reconstruction fields are assigned `true` after the comparisons. | They are assertions produced by the checker, not recomputed observations. | `ASSERTED_AFTER_PIN_MATCH` |
| Closure conclusions | The terminal auditor assigns defect correction, qualification removal and campaign closure after repeating the same reconstruction. | No detached attestation or distinct evidence derivation gates closure. | `UNSUPPORTED_CLOSURE_ACCEPTANCE` |

The unkeyed `record_digest` detects changes to the selected summary. It does
not authenticate a producer, private receipt, verifier or execution.

## Producer and verifier implementation overlap

The integrated producer writes both the private receipt and sanitized summary
from one `AtomicTransitionIntegratedDisposableMission` process. It creates the
origin, provenance, case material, trusted result, dependency graph, synthetic
acceptance matrix, exclusion observation and every summary conclusion.

The current reconstructor does not import that runner class, but it imports the
runner's output values as source constants and uses the repository's canonical
JSON implementation to reproduce the summary digest. The terminal auditor is
not a second implementation: it receives the reconstructor through its
constructor, calls `reconstruct()` again and requires byte-for-byte equality
with the submitted reconstruction. The focused Batch 7 test constructs the
producer-family reconstructor and terminal auditor together in one process.

| Boundary | Current state | Classification |
| --- | --- | --- |
| Distinct verifier implementation | No implementation consumes underlying artifacts plus receipt independently of the producer-family reconstructor. | `ABSENT` |
| Independent process/tool boundary | The current tests instantiate verifier and closure in-process; both classes are ordinary application services. | `ABSENT` |
| Producer conclusion exclusion | `disposition` is not indexed, but package digest and producer booleans remain accepted. | `INCOMPLETE` |
| Shared neutral primitives | Canonical JSON and cryptographic verification may be shared only as format/algorithm primitives; producer evaluators and conclusion builders may not be shared. | `PERMITTED_NARROWLY` |
| Verifier implementation identity | No digest or version of a distinct verifier is reported or attested. | `ABSENT` |

## Retained private-receipt availability and custody

Repository evidence establishes only that the Batch 5 runner was directed to
write one private receipt and that the sanitized summary declares
`OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT` with digest
`21933ac0e9d76326dfd8b8da10114a6029c6868adfb67ef8d65584a11c9d0896`.
The repository contains neither a private-receipt path nor a custody record,
availability attestation, media identity, backup status or completeness proof.

Preparation deliberately does not search for, open, copy, hash or otherwise
inspect candidate private receipts. Present availability is therefore
`UNVERIFIED_OPERATOR_ASSERTION_REQUIRED_AT_BATCH_4`, not `AVAILABLE`.

Later local verification must fail stopped when the exact receipt is absent,
unreadable, incomplete or digest-mismatched. It may not reconstruct the receipt
from the summary, accept a replacement, rerun the mission or mint new evidence.
The operator must select the receipt locally, keep its path and bytes outside
Git, prompts, logs, exceptions and reports, and destroy any verifier temporary
plaintext according to an explicit local custody procedure.

## Artifact-recomputation gaps

| Claimed binding or conclusion | Current observation | Required independent derivation |
| --- | --- | --- |
| Source commit | Compared with one copied string. | Resolve the exact local repository object/snapshot and derive commit identity without changing checkout state. |
| Source tree | Compared with one copied digest. | Define the canonical tree projection and hash its exact files from the retained source commit/snapshot. |
| Dependency lock | Compared with one copied digest. | Hash the exact retained lock artifact under a named byte/canonicalization rule. |
| Build artifact | Compared with one copied digest; the producer received it through an environment variable. | Define the artifact set and deterministic aggregate algorithm, then recompute it from retained artifacts. |
| Runner and mission implementation | Compared with copied digests; the runner supplied both identities. | Hash exact files from the pinned source and bind their repository-relative paths. |
| Runtime version | Copied summary value is compared. | Derive from receipt-bound execution metadata and classify whether it is independently corroborable. |
| Evidence origin and provenance | Only their producer digests are exposed. | Parse the private sections, validate exact schemas/seals/references and re-derive source/build/executor bindings. |
| Trusted result | Only its producer digest is exposed. | Validate the receipt section and independently derive its result from the admitted fixture, mutation, expected result and plan. |
| Dependency graph | Only its producer digest is exposed. | Reconstruct from exact verifier-admitted source/build metadata and validate every recorded node, edge and capability classification. |
| Acceptance matrix | Producer values are copied as expected constants. | Independently evaluate every retained evidence case; accept no producer status or expected-success boolean. |
| Complete-chain exclusion | A producer `true` is copied. | Scan all receipt sections and sanitized output under an independently implemented structural and value-aware policy. |
| Non-authority perimeter | Five producer `false` values are copied. | Derive narrowly from receipt structure, admitted dependency graph and absence of authority/effect artifacts; do not claim more than the evidence supports. |
| Sanitized package | Its self-hash is recomputed. | Generate a new verifier report from derived observations, then bind the report—not the producer summary—to detached attestation. |

If any artifact definition is ambiguous or its retained bytes are unavailable,
the verifier must report `INDETERMINATE` or `REFUSED`; it may not substitute a
copied producer digest as recomputation.

## Detached trust anchor and signing custody options

| Option | Custody posture | Assessment |
| --- | --- | --- |
| Ed25519 detached signature with operator-local private key and pinned public verification identity | Private key is created or selected only under explicit Batch 4 authorization, stays outside the repository and process output, and signs only the canonical sanitized report digest. Public key, key ID, algorithm and public-key digest are retainable. | `PREFERRED` |
| Hardware- or OS-backed Ed25519 signing capability | Non-exportable private capability remains operator-controlled; verifier receives only a narrow sign operation after successful verification. | `PREFERRED_IF_AVAILABLE_LATER` |
| Offline second-process signer | Verifier emits a canonical report digest; a separately invoked local signer signs that digest after operator review. | `ACCEPTABLE_STRONG_SEPARATION` |
| Repository bootstrap-manifest signer/public key | Existing `ManifestValidator` proves detached Ed25519 verification is available, but that trust policy and key purpose are unrelated. | `DO_NOT_REUSE_WITHOUT_EXPLICIT_NEW_KEY_PURPOSE` |
| HMAC/shared secret | Verification requires distributing the signing secret to the repository consumer, destroying detached public verifiability. | `REJECT` |
| Unkeyed hash, embedded public key, self-signed/generated-on-demand key or report-carried trust decision | A counterfeit producer can reproduce or substitute the anchor. | `REJECT` |

Batch 0 selects no live key, public identity, path, device or signing provider.
Batch 1 must define authority-empty contracts that support unavailable key and
receipt outcomes. Batch 4 alone may handle the operator-local signing
capability, under fresh explicit authorization.

## Sanitized independent-report requirements

The future report must be a separately versioned canonical record produced
only from independently derived observations. At minimum it must contain:

- report and verification-run identifiers; verifier schema/version, exact
  implementation digest and allowed dependency-set digest;
- references to the producer summary, exact source commit/tree, defined build
  artifact set, dependency lock, runner and mission implementation;
- the observed private-receipt digest and exact receipt schema, without path,
  bytes, section payloads, filesystem metadata or private identifiers;
- per-domain outcomes for source/build, receipt structure, origin/provenance,
  trusted result, dependency graph, eight-case acceptance matrix, complete-chain
  exclusion and bounded non-authority perimeter;
- explicit `PASS`, `REFUSED` or `INDETERMINATE` disposition for every required
  domain, with stable sanitized reason codes and no raw exception text;
- confirmation that no producer disposition, success boolean, copied expected
  digest or producer reconstruction was treated as derived evidence;
- report canonicalization algorithm, report digest, detached-signature
  algorithm, public-key identity/digest and signature reference; and
- explicit false authority fields preserving `BOUND_INACTIVE`,
  `NOT_IMPLEMENTED`, `UNKNOWN_REPLAY_PROHIBITED`, no runtime write, no provider,
  no external I/O, no retry, no live adoption and no continuing authority.

The report must exclude receipt bytes and paths, source contents, environment
values, private key or capability material, credentials, host/user identity,
process-local identities, stack traces and proprietary topology beyond the
minimum public digests and stable reason codes.

## Counterfeit-package refusal gaps

The current path refuses altered pinned fields and malformed self-hashes, but
it accepts a byte-exact copy of the producer package without proof of receipt
possession or independent verification. It also has no distinct refusals for:

- an exact self-hashed package with no receipt;
- a counterfeit receipt having a copied digest claim but invalid internal
  sections, references or conclusions;
- substituted source, build, runner, mission or verifier implementations;
- a report signed by an untrusted, wrong-purpose, rotated, malformed or
  report-supplied public identity;
- a valid signature over a different report, package, receipt digest or
  verifier identity;
- producer-supplied conclusions injected into the independent-report fields;
- missing, extra, duplicated, reordered or partially verified receipt/report
  domains; and
- secret, path, receipt-content or signing-material leakage in success,
  refusal, exception or diagnostic output.

Every refusal must be proved later with synthetic fixtures. The real private
receipt may not be used as a counterfeit-test fixture.

## Closure-consumer reachability

| Consumer/surface | Reachability | Classification |
| --- | --- | --- |
| `AtomicTransitionEvidenceTerminalAdversarialAuditor` | Auto-discovered by the broad `App\` service resource and directly constructible. It can emit qualification removal and campaign closure from the defective reconstructor. No production caller was found, but internal container reachability remains. | `REACHABLE_INTERNAL_DEFECTIVE` |
| `AtomicTransitionEvidenceIndependentReconstructor` | Auto-discovered, directly constructible and injected into the terminal auditor. | `REACHABLE_INTERNAL_DEFECTIVE` |
| Batch 6/7 focused tests | Directly instantiate the defective path and assert accepted reconstruction/closure. | `REACHABLE_TEST_ACCEPTANCE` |
| `AtomicTransitionEvidenceAuthenticatedClosureContract` | Retains the rejected accepted status and is consumed by the terminal auditor/test. | `STALE_ACCEPTANCE_CONTRACT_REACHABLE` |
| Historical caller-boolean audit | Direct calls refuse with `PBL1015`; excluded from service discovery. | `DISABLED_SURVIVING_CORRECTION` |
| Historical self-recomputed closure | Direct calls refuse with `PBL1016`; excluded from service discovery. | `DISABLED_SURVIVING_CORRECTION` |
| Documentation consumers | The prior completion handoff and Batch 7 document retain historical accepted-closure text, while Blackquill review, campaign selection, TODO and canonical flow requalify it. | `HISTORICAL_RETAINED_BUT_REQUALIFIED` |
| Independent attestation consumer | No repository consumer verifies a separately generated report, trusted public identity and detached signature. | `ABSENT` |

Preparation does not disable or modify the reachable defective consumer. Batch
5 must make independently attested evidence the sole admissible repository-side
path and refuse unsigned and legacy closure inputs. Until then, the
requalification remains controlling.

## Smallest separate verifier boundary

The smallest acceptable verifier is a standalone, read-only local boundary
with its own versioned contracts and implementation identity. It is not an
application service wrapper around the current reconstructor.

1. Accept explicit paths/references to the pinned source snapshot, defined
   build artifacts, producer sanitized summary and operator-selected private
   receipt. Accept no producer reconstruction or success disposition.
2. Read only those admitted inputs; never discover a receipt by scanning,
   mutate the checkout, resolve credentials, invoke providers or execute the
   mission/runner.
3. Implement receipt parsing, seal/reference validation, artifact hashing,
   conclusion derivation and sanitization without importing the producer
   runner, current reconstructor, terminal auditor or their conclusion logic.
4. Recompute each required domain and retain an explicit domain outcome. Shared
   canonical JSON and audited cryptographic primitives are allowed; shared
   producer evaluators and expected constants are not.
5. Emit only the canonical sanitized verification report. Keep receipt bytes,
   paths and diagnostics inside the local verification process.
6. After all domains pass, expose only the report digest to a separately
   custodied signer. Signing cannot convert `REFUSED` or `INDETERMINATE` into
   success.
7. Return report, detached signature and pre-trusted public verification
   identity to the repository-side consumer. The consumer independently
   verifies all three bindings before any later closure decision.

This boundary verifies retained evidence; it does not execute, authorize or
admit an atomic transition.

## Later local verification acceptance matrix

This matrix defines later acceptance and refusal. It authorizes no verifier or
signer execution in Preparation Batch 0.

| Case | Required local observation | Required disposition |
| --- | --- | --- |
| Exact retained package | Exact summary, exact receipt and every defined source/build artifact recompute under the pinned verifier. | `PASS` only when every required domain passes and the report is detached-signed by the trusted identity. |
| Receipt absent/unreadable | Operator-selected receipt cannot be read. | `REFUSED_RECEIPT_UNAVAILABLE`; no mission rerun or replacement. |
| Receipt digest mismatch | Locally computed receipt digest differs from the committed digest. | `REFUSED_RECEIPT_DIGEST_MISMATCH`. |
| Receipt incomplete/tampered | Any schema, field, seal, reference, order or required section fails. | `REFUSED_RECEIPT_INVALID`. |
| Source/build artifact absent | Any defined source, lock, build, runner or mission artifact is unavailable. | `INDETERMINATE_ARTIFACT_UNAVAILABLE`, never copied-constant success. |
| Source/build substitution | Recomputed identity differs from receipt/summary binding. | `REFUSED_ARTIFACT_SUBSTITUTION`. |
| Producer-conclusion injection | A disposition, success boolean, copied expected digest or producer reconstruction is offered as verifier input. | `REFUSED_PRODUCER_CONCLUSION_INPUT`. |
| Acceptance-matrix mismatch | Any of the eight independently derived outcomes differs, is absent or is duplicated. | `REFUSED_ACCEPTANCE_MATRIX_MISMATCH`. |
| Dependency/verifier substitution | Verifier implementation or allowed dependency-set identity differs from the trusted version. | `REFUSED_VERIFIER_SUBSTITUTION`. |
| Counterfeit self-hashed package | Package is internally consistent but lacks a valid receipt-derived report and trusted detached signature. | `REFUSED_INDEPENDENT_ATTESTATION_ABSENT`. |
| Wrong trust anchor/key purpose | Signature verifies only under an unknown, report-supplied or wrong-purpose identity. | `REFUSED_TRUST_ANCHOR`. |
| Signature malformed/mismatched | Signature is malformed or binds a different report digest, package, receipt or verifier. | `REFUSED_DETACHED_SIGNATURE`. |
| Secret/private leakage | Report or diagnostic output contains prohibited receipt, path, credential, capability, key, environment or topology material. | `REFUSED_SANITIZATION`. |
| Closure consumer legacy input | Unsigned producer summary, v1 reconstruction or historical accepted closure is submitted. | `REFUSED_LEGACY_OR_UNSIGNED_CLOSURE`. |

Synthetic tests must cover every refusal before the retained receipt is ever
selected. A successful local run alone is insufficient; repository-side
signature and report validation remains a separate Batch 5 concern.

## Smallest lawful campaign sequence

1. define authority-empty verifier-input, report, public-identity and detached
   attestation contracts, including unavailable receipt/key outcomes;
2. implement the separate read-only artifact-and-receipt verifier;
3. prove counterfeit, substitution, producer-injection and leakage refusals
   using synthetic fixtures only;
4. under explicit authorization, run local verification and detached signing
   against the retained receipt without retaining private material;
5. implement the repository-side independent report/signature consumer and
   disable every legacy or unsigned closure route; and
6. perform terminal adversarial audit and conditionally restore closure only
   if every independent-verification gate survives.

## Closed perimeter

Preparation inspected no private receipt and handled no signing key or
capability. It executed no verifier, test, mission, provider or external I/O;
mutated no runtime state; changed no provider binding; implemented no v3
execution admission; removed no requalification; and did not close the
campaign.

Provider binding remains `BOUND_INACTIVE`. Required v3 execution admission
remains `NOT_IMPLEMENTED`. `UNKNOWN_REPLAY_PROHIBITED` remains binding.
