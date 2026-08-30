# Provider Effect Principal and Binding Activation — Preparation Batch 0

## Result

PREPARATION_BATCH_0_COMPLETE_SEPARATE_ORDERED_ACTIVATION_AUTHORITIES_REQUIRED

The two stop conditions may remain in one campaign, but they are not one
authority and may not be consumed in one fused activation-and-call action.

The exact executor principal generation must first become durably ACTIVE through
its own single-use authority. Only then may a separate operation-scoped
authority establish live-capable binding sufficiency for the exact active
principal generation, inactive implementation binding, admitted assurance and
operation. Neither transition authorizes a provider call.

## Inventory

| Requirement | Classification | Exact posture | Non-authority |
| --- | --- | --- | --- |
| Executor-principal attestation | EXISTS_CANONICALLY | Exact generation is durably ATTESTED_INERT | Attestation, class name, process identity and location do not activate |
| Principal activation decision/result schemas | EXISTS_CANONICALLY | Separate authority-empty contracts, validators, immutable fixtures and reconstruction exist | Offline AUTHORIZED or ACTIVE-shaped evidence is not runtime truth |
| Competent principal activation producer | ABSENT | No service consumes the exact single-use activation authority into the durable activation lifecycle | Imperator attribution and provider-execution admission are not the missing competence |
| Principal activation atomicity | EXISTS_FRAGMENTED | Generic immutable consumption and fixture interruption patterns exist; the exact production consume-to-commit transition is unproved | A reusable store does not prove this transition |
| Durable lifecycle vs process-local capability | EXISTS_CANONICALLY | Durable generation/status/validity may survive restart; capability identity is process-local and excluded | Durable records cannot resurrect or serialize capability identity |
| Immutable implementation binding | EXISTS_CANONICALLY | Exact provider, adapter, credential family, encoder, decoder, destination and assurance lineage remain BOUND_INACTIVE | Binding identity and credential resolvability are not activation |
| Existing single-operation activation evidence | EXISTS_CANONICALLY | Combined v2 admission consumes operation activation with execution authority under the activation key | Its winner does not mutate or globally activate the implementation binding |
| Live-capable binding sufficiency | EXISTS_FRAGMENTED | Exact operation evidence exists, but no distinct production authority proves the inactive binding may cross the future first-byte gate | ACTIVATED_UNCONSUMED vocabulary and combined admission are not I/O authority |
| Binding activation authority and producer | ABSENT | Must be operation-scoped, single-use, expiring, revocable and bound to the active principal generation and admitted assurance | Principal activation cannot issue or imply it |
| Live-call contract | DEFERRED_BOUNDARY | Remains a separate later campaign | Contract existence would not be authority |
| Sterile provider conformance | DEFERRED_BOUNDARY | Required only if a future contract cannot preserve an unknown truthfully | Documentary evidence is not remote conformance |
| Live-consumer adoption | DEFERRED_BOUNDARY | Separate later campaign | Existing command or transport ability is not adoption authority |
| Threat model | EXISTS_CANONICALLY | TRUSTED_WRITER_CANONICAL_INTEGRITY on SINGLE_AUTHORITATIVE_ROOT_ONLY | Local locking and digests do not prove hostile-writer or distributed guarantees |

## Authority and ordering matrix

| Order | Transition | Authority and lock | Refusal |
| --- | --- | --- | --- |
| 1 | Reconstruct prerequisites | Read only; no lock may create state | Refuse absent, corrupt, conflicted, expired or revoked evidence |
| 2 | Activate exact principal generation | Consume one principal-activation authority under the generation lifecycle lock | Refuse wrong generation, process boundary, assurance, transition, expiry, revocation or prior consumption |
| 3 | Reconstruct active principal | Read only after durable commit | Refuse if ACTIVE is absent, expired, revoked or superseded |
| 4 | Activate exact operation-scoped binding | Consume a distinct binding-activation authority under the operation/activation lock | Refuse inactive principal, binding mismatch, assurance mismatch, expiry, revocation, contention or changed request |
| 5 | Stop | No live-call contract is present | Credential resolution and first byte remain prohibited |

Principal activation completes before binding activation begins. The design must
not nest the principal-generation and operation-activation locks or treat a
partial two-lock transaction as one atomic act.

Activation and lawful revocation for the same target must use the same target
lock and produce mutually exclusive immutable winners.

## Atomicity, crash and replay

| Cut or event | Lawful result |
| --- | --- |
| Before principal authority consumption | No activation record; exact authority remains unconsumed |
| After consumption begins but before principal activation commit | Recovery must prove one consume-to-commit outcome; absence may not be guessed into retry |
| After principal activation commit | Exact replay returns the same winner; changed evidence conflicts |
| Competing principal callers | One generation-scoped winner; every loser refuses |
| Principal expiry or revocation before binding activation | Binding activation refuses |
| Before binding authority consumption | No binding activation winner |
| After binding consumption begins but before commit | Recovery reconstructs the exact operation-keyed transition; it does not consume again |
| Competing binding callers or revocation | One mutually exclusive operation-keyed winner |
| Corrupt reconstruction | Refuse without repair, authority creation or capability access |
| Process restart | Reconstruct durable lifecycle only; never recreate process-local capability identity |
| Possible provider effect | UNKNOWN_REPLAY_PROHIBITED; no reinvocation |

Batch 1 must prove the principal consume-to-commit transition before any binding
production transition is considered.

## Secret exclusion

No durable authority, activation record, proof, log, exception, test fixture or
reconstruction output may contain credential bytes, credential references,
environment-variable names, headers, provider tokens or process-local capability
identity. The production principal transition has no credential dependency.

Credential possession, credential resolvability, provider SDK presence,
callback availability and same-process location remain non-authorities.

## Final pre-first-byte posture

This campaign cannot reach first byte. Even after both future activation
sub-boundaries close, the absent authority-empty live-call contract remains a
separate stop condition. Credential resolution cannot precede that contract and
cannot itself cross the refusal boundary.

No provider invocation, external I/O, retry or live adoption is authorized.

## Batch 1 gate

Only Batch 1 may next be considered: implement and prove the exact atomic
principal-activation production transition using the existing decision,
single-use authority and immutable activation schemas.

Batch 1 must remain independent of provider binding, credentials, capabilities,
live-call code, provider adapters and transports. It may exercise only
caller-supplied disposable local fixtures. It may not activate the repository's
live principal, activate a binding, invoke a provider, perform external I/O,
authorize retry, migrate a consumer, or open Iron Gate or Lazaretto.
