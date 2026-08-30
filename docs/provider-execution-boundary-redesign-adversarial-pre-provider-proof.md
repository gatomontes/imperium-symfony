# Provider Execution Boundary Redesign — Batch 8 adversarial pre-provider proof

## Result

`BATCH_8_ADVERSARIAL_PRE_PROVIDER_CORRIDOR_PROVED_NO_IO`

Batch 8 adversarially proves the redesigned corridor from durable authority and exact executor
principal through provider-binding identity, atomic admission and stationary credential resolution.
The proof stops before provider invocation and external I/O.

## Proven failure and recovery matrix

| Condition | Proven disposition |
|---|---|
| interruption after admission but before credential resolution | no resolution proof exists; a later local attempt may resume the exact admission |
| exact replay after completed resolution | returns the one immutable proof without rereading the credential |
| competing callers for one admission | the admission-scoped atomic transition converges on one proof identity and one durable proof |
| unproved expired admission | refuses before credential resolution or proof persistence |
| revoked durable execution authority | refuses before credential resolution |
| revoked executor principal | refuses before credential resolution |
| corrupt durable proof | reconstruction refuses; it does not reread or reconstruct credential material |
| credential absent before first proof | refuses without a resolution proof; no external effect exists to duplicate |
| recursive durable-record inspection | excludes both credential secret and environment-variable name |
| all successful and refused cases | zero provider invocation, zero external I/O, zero outbound bytes, zero provider-outcome claims |

## Ordering and contention

Authority consumption and local effect-start are already committed in the exact admission before
credential resolution is permitted. Resolution is serialized by
`stationary-credential-resolution:<admission-id>`. A completed proof is returned before any
lineage-validity or deployment-credential reread. An absent proof requires current, unrevoked
lineage and the stationary credential.

This is intentionally local retry only. Because provider execution has not started, retry cannot
duplicate a provider effect. It grants no automatic replay after provider start.

## Reconstruction and revocation

Completed proof reconstruction is read-only evidence reconstruction. It does not renew authority,
reactivate a binding, revive a principal, or reconstruct a credential. Corrupt proof state refuses.

Before a first proof, expiry or revocation in the admission, durable authority, or executor
principal blocks resolution. Provider-binding expiry is likewise already rejected by the Batch 7
service.

## Threat-model alignment

The result remains bounded to one authoritative root and
`TRUSTED_WRITER_CANONICAL_INTEGRITY`. Atomic filesystem locking proves same-root serialization.
It does not claim hostile-writer non-forgeability, split-brain resistance, distributed consensus,
or multi-host uniqueness.

## Closed perimeter

Batch 8 does not activate a provider binding, activate an executor principal, issue or consume new
authority, issue a credential capability, invoke a provider, perform external I/O, send an outbound
byte, migrate a live command, open Iron Gate or Lazaretto, or claim provider outcome.

`UNKNOWN_REPLAY_PROHIBITED` remains mandatory after any future provider-effect start interruption.

## Batch 9 gate

Only Batch 9 may next be considered: resume Provider Execution Assurance against the redesigned
pre-provider corridor and determine the smallest evidence-only continuation. Batch 9 must not infer
that this proof authorizes provider invocation, retry, external I/O, Iron Gate, Lazaretto, or
provider-outcome admission.
