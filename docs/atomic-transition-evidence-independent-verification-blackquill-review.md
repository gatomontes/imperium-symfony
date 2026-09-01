# Atomic Transition Evidence Independent Verification Blackquill review

## Verdict

`AUTHENTICATED_OPERATIONAL_EVIDENCE_CLOSURE_REJECTED_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`

The closure at
`CAMPAIGN_CLOSURE_ACCEPTED_AFTER_AUTHENTICATED_OPERATIONAL_EVIDENCE_PROOF` is
not authoritative.

## Findings

The so-called independent reconstructor does not recompute the retained source,
build, runner, mission, provenance, result, graph or private-receipt evidence.
It compares producer-package values with constants copied from that package and
then assigns successful reconstruction booleans. This pins one package; it does
not independently verify that package's claims.

The private operational receipt remains operator-local. Only its digest is
committed. A digest identifies inaccessible bytes but cannot prove their
contents, acceptance derivation or relationship to the claimed execution.

The terminal auditor invokes the same reconstructor used to produce the
submitted reconstruction and requires equality. Two instances of one
implementation are not independent witnesses. The focused test constructs both
instances in one process from the same producer summary.

The claim that producer disposition is not imported is incomplete. The
reconstructor does not read the `disposition` field directly, but it requires
the exact digest of the complete package containing `disposition: PROVED` and
the producer-supplied acceptance and exclusion booleans. Those observations are
matched to expected constants rather than re-derived from underlying evidence.

No signature, MAC, independently anchored attestation or distinct verifier
implementation authenticates the package. Unkeyed hashes prove stability after
construction, not producer identity or execution truth.

## Surviving correction

The historical caller-boolean audit and historical self-recomputed closure are
genuinely disabled: both entry points refuse unconditionally and both classes
are excluded from Symfony service discovery. That correction remains valid.

## Consequence

The campaign improved the defect from accepting any internally consistent
counterfeit package to accepting one preselected internally consistent package.
Package pinning and tamper detection are useful. They are not independent
authentication.

The controlling posture is therefore requalified at
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

## Required stronger evidence

Closure requires a separately implemented verifier that consumes underlying
artifacts and the operator-local receipt, recomputes every admissible conclusion
without producer success booleans, emits a sanitized verification report, and
binds that report to an explicit detached trust anchor. A perfectly self-hashed
counterfeit package must fail for lack of valid independent attestation.
