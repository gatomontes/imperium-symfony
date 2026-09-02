# Atomic Transition Reproof Batch 3: independent verifier

`REPROOF_BATCH_3_INDEPENDENT_VERIFIER_COMPLETE`

ReproofV2Verifier has a separate case evaluator, Git object parser and exclusion
scanner. These import only neutral Records/CanonicalJson serialization and
hashing; none calls the runner, runtime classifier, runtime validator, producer
profile or producer exclusion/graph implementation. The source parser checks
Git commit framing, raw tree framing, every path traversal and blob membership,
exact file set and an independently supplied source manifest root. Graph nodes
and source imports are independently projected from those pinned bytes.

The case evaluator validates the finite synthetic auxiliary payloads, exact
transaction schemas, seals, false effect fields, roots, reference joins, all
cut shapes, comparison relations and mutation replacements. It independently
derives classifications/directives/comparisons/findings and compares retained
expectations and observations. It recomputes all ordered roots and origin,
receipt and candidate bindings. Counterfeit self-seals alone cannot pass.

The independently provided trust record pins proof identity, source commit,
source manifest, actual execution-authorization digest, runtime version,
verifier implementation/dependency root, public identity digest and evidence
class. Taking those pins from the candidate would defeat the trust boundary.
The API does not authenticate operator intent by itself: later authorized local
intake must establish the trust record outside producer custody. A digest of
an operator authorization is not itself an execution capability.

The verifier derives its implementation root from its six explicit local source
files. It assumes trusted PHP/native infrastructure and reviewed pinned source;
it does not prove host isolation or the absence of hostile concurrent file
replacement. No installed vendor tree is used by the future mission loader.
These limits remain part of the graph claim and cannot be dropped at admission.

Reports contain only strict public bindings and domain outcomes; no private
payload, path, exception text or topology. Synthetic success is explicitly
SYNTHETIC_PASS_NOT_ADMISSIBLE. Qualification removal and campaign closure are
always false. Preflight eligibility is neither verification nor authorization.
The v1 verifier and historical refusal remain untouched.

Exclusion now retains actual safe negative-vector refusal observations, not
only vector names. Both independent and producer scanners exercise the five
safe vectors; no forbidden value is retained in the receipt. Exact profile
validation also closes arbitrary/unknown payload slots beyond pattern scanning.

PHPUnit after Batch 3: 10 tests, 207 assertions across Batches 1–3. The dependency
test was corrected to ignore PHP string literals/comments when checking code
references; source-file inventory paths are not executable dependencies.
No mission CLI, operator-local receipt intake or signing occurred.
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling; the campaign is open.
