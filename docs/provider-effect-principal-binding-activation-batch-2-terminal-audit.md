# Provider Effect Principal and Binding Activation — Batch 2 terminal audit

## Result

BATCH_2_TERMINAL_AUDIT_REFUSED_UNPROVEN_DECISION_AUTHORITY_PROVENANCE

Disposition:
PRINCIPAL_PRODUCTION_SUB_BOUNDARY_REFUSED_PENDING_DECISION_AUTHORITY_REMEDIATION.

The Batch 1 transition correctly makes authority consumption and principal
activation one immutable generation-keyed winner. Its crash, replay, conflict,
contention, expiry, revocation, reconstruction and secret-exclusion mechanics
remain valid.

The audit nevertheless refuses the production principal boundary. Atomic
consumption does not prove that the consumed authority was competently issued.

## Blocking finding

The decision contract still declares its producer as
future-imperator-provider-executor-principal-activation-decision. No canonical
production decision issuer, immutable production decision custody path or
read-only decision aggregate reconstruction is established.

ProviderExecutorPrincipalActivationService accepts the complete decision as a
caller-supplied array. The validator proves canonical shape, digest, exact
lineage values, validity and the internal activation-authority shape. Its
source_authority check proves only that a syntactically valid reference exists.
It does not resolve that reference to an immutable competent source, prove that
the named Imperator generation held the exact decision scope, or prove that one
canonical issuer produced the decision.

A caller capable of constructing locally self-consistent, canonically sealed
arrays can therefore satisfy the mechanical activation path without the audit
being able to prove competent issuance. Under
TRUSTED_WRITER_CANONICAL_INTEGRITY this is still an authority-provenance defect:
trusted storage integrity does not turn caller possession into jurisdiction.

## Findings retained as valid

| Finding | Disposition |
| --- | --- |
| Combined consumption-and-activation record | Mechanically sound |
| Pre-commit interruption | No consumption-only state |
| Post-commit interruption | Exact winner survives |
| Exact replay | Converges |
| Changed decision under same generation | Conflicts |
| Same-root contention | One generation-keyed winner |
| Expiry, revocation, refusal, wrong generation | Fail closed |
| Activation reconstruction | Read only against supplied chain |
| Credential and capability exclusion | Preserved |
| Competent decision issuance and custody | UNPROVED — blocking |
| Binding activation eligibility | REFUSED |

The existing Batch 1 result is retained as proof of the atomic mechanism. It is
not promoted into proof that the repository's live principal is competently
active.

## Required remediation

A separate **Principal Activation Decision Authority Provenance Remediation**
campaign must begin with Preparation Batch 0 only. It must inventory:

- the exact competent authority that grants principal-activation decision scope;
- the exact active Imperator principal generation and its jurisdiction;
- decision issuance authority, single-use or continuing posture and custody;
- canonical immutable decision storage and target-wide contention;
- source-authority resolution rather than structural reference acceptance;
- expiry, revocation, supersession, reconstruction and crash recovery;
- the join from canonical decision custody to the existing atomic activation
  transition; and
- all non-authorities and secret exclusions.

The remediation may preserve or replace the Batch 1 service. It may not infer
competence from actor labels, contract shape, digest validity, filesystem
possession or caller-supplied evidence.

## Closed perimeter

The provider binding remains BOUND_INACTIVE. No repository live principal is
accepted as active by this audit. No binding or execution authority is issued or
consumed, no credential or capability is handled, no live-call contract is
defined, no provider is invoked, no external I/O or retry occurs, no consumer is
migrated, and Iron Gate and Lazaretto remain closed.

UNKNOWN_REPLAY_PROHIBITED remains binding.
