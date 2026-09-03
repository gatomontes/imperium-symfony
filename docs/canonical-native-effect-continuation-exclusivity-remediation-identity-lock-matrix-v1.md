# Canonical Native Effect Continuation and Exclusivity Remediation — identity and lock matrix v1

`PREPARATION_BATCH_0_IDENTITY_LOCK_DESIGN_ONLY`

## Current identities

| Identity | Exact derivation | Classification | Consequence |
| --- | --- | --- | --- |
| Authority id | Required caller/fixture field; fixture uses `native-effect-authority-<native-root>` | `EXISTS_FRAGMENTED` | No production canonical issuer/deriver. |
| Authority digest | SHA-256 canonical seal of all authority fields except `record_digest` | `EXISTS_CANONICALLY` | Admission validates it; continuation does not. |
| Effect replay identity | SHA-256 canonical digest of native root, transition/receipt digests, **authority id/digest**, operation, destination, payload digest, provider/adapter facts, credential family, return contract and idempotency-key digest | `EXISTS_FRAGMENTED` | Deterministic but authority-specific. |
| Semantic effect tuple id | None | `ABSENT` | Distinct authorities do not converge. |
| Admission id | `canonical-native-effect-admission-` + first 20 replay hex | `EXISTS_FRAGMENTED` | Authority-specific and 80-bit truncated. |
| Callback id | Prefix + first 20 hex of SHA-256(admission id) | `EXISTS_CANONICALLY` | Unique only within current admission identity. |
| Response id | Prefix + first 20 hex of SHA-256(callback id) | `EXISTS_CANONICALLY` | Immutable conflict is fail-closed. |
| Receipt id | Prefix + first 20 hex of SHA-256(admission id) | `EXISTS_CANONICALLY` | Existing receipt is returned read-only. |
| Capability id | Random 12 bytes, process-local object identity registered in issuer | `EXISTS_CANONICALLY` | Not durable or reconstructible, but unused by continuation. |

## Corrected authority-independent semantic tuple

Later Batch 1 must define one canonical digest, never caller-supplied:

```text
semantic_effect_tuple_id = sha256(canonical-json({
  native_root,
  native_transition: {id, schema, digest},
  native_receipt: {id, schema, digest},
  successor: {id, schema, digest},
  v3_admission: {id, schema, digest},
  executor_principal: {id, schema, digest},
  execution_boundary: {id, schema, digest},
  provider_binding: {id, schema, digest},
  operation,
  destination,
  payload_digest,
  request_fingerprint,
  provider: {
    provider_id,
    adapter_id,
    adapter_version,
    assurance_admission: {id, schema, digest}
  },
  credential_family,
  expected_return_contract,
  provider_idempotency_key_digest
}))
```

Authority id/digest, issuer, holder, effective/expiry time and revocation/
cancellation state are deliberately excluded: they govern who may win, not the
meaning of the effect. They remain bound through a separate identity:

```text
authority_consumption_id = sha256(canonical-json({
  semantic_effect_tuple_id,
  authority_id,
  authority_digest
}))
```

The corrected effect replay identity equals the full semantic tuple digest.
The admission record stores that full digest. Any human/path id may be a prefix
only if immutable conflict plus full-digest comparison makes collisions a
terminal refusal; it must never select a different tuple silently.

## Current lock order

| Order | Scope | Held during | Classification |
| --- | --- | --- | --- |
| 1 | `native-provider-transition` | Entire admission, including validation and publication | `EXISTS_CANONICALLY` |
| 2 | `canonical-native-effect-authority:<sha256(authority-id)>` | Same-authority scan/validation/publication | `EXISTS_CANONICALLY` |
| 3 | `canonical-native-effect:<authority-specific-replay>` | Admission scan/validation/publication | `EXISTS_FRAGMENTED` |
| 4 | `immutable:<sha256(admission-directory)>` | Admission seal/temp-write/rename | `EXISTS_CANONICALLY` |
| — | Sorted source/trust immutable locks | Not acquired by effect admission | `ABSENT` |
| 1c | `canonical-native-effect-continuation:<sha256(admission-id)>` | Entire double continuation, including callback | `EXISTS_CANONICALLY` |
| 2c | callback, response or receipt directory immutable lock | One publication at a time inside continuation lock | `EXISTS_CANONICALLY` |

The current admission order is acyclic, but its third scope is different for
the very competitors it claims to serialize.

## Required later-batch lock order

The minimum cooperative single-filesystem order is fixed globally as:

1. `native-provider-transition`;
2. sorted `immutable:<sha256(source-or-trust-directory)>` scopes when exact
   source reads are performed through `NativeState::locked()`;
3. `canonical-native-effect-authority:<sha256(authority-id)>`;
4. `canonical-native-effect-tuple:<semantic-effect-tuple-id>`;
5. `immutable:<sha256(tuple-winner/admission-directory)>` for the one atomic
   tuple-winner plus authority-consumption publication.

This preserves the existing native-before-authority direction and replaces the
broken authority-specific effect scope. Holding authority A while waiting for
the shared tuple scope cannot cycle with authority B because no path may acquire
another authority scope, and no path may acquire native/authority after tuple.
Revocation and cancellation must use the same native -> authority direction;
if they inspect/publish tuple state, they then acquire that authority's exact
tuple scope. Any inverse acquisition is prohibited.

The tuple winner is checked before any authority-consumed fact is published.
If another authority already won, the candidate is explicitly refused and its
authority remains unconsumed. The losing authority remains unconsumed and may
not be silently deleted,
marked consumed or automatically redirected to a different tuple.

After atomic publication all five locks are released. Only the call that
observed `newly_published=true` may mint the ephemeral continuation object.
Minting/retrieving it on exact replay is prohibited.

## First callback and forward-recovery locks

First callback start uses an admission-id continuation scope only after the
exact process-local object is recognized and atomically marked consumed in its
in-memory custody registry. It publishes callback start before invoking the
double. No native, source, authority, tuple or immutable publication lock may
remain held across a provider callback.

Forward completion from an already sealed response may acquire only the
continuation and receipt publication scopes. It must not mint/require a first-
callback capability, resolve a credential or invoke a provider. It derives all
meaning from admission and response records.

## Durable versus ephemeral truth

| Fact | Durable | Ephemeral | Recovery rule |
| --- | --- | --- | --- |
| Semantic tuple and full digest | Yes | No | Reconstruct and compare read-only. |
| Tuple winner + winning authority consumption | Yes, one atomic aggregate | No | Never republish a second winner. |
| Losing-authority refusal/disposition | Yes if a disposition is published | No | Must state unconsumed; no automatic reuse. |
| Complete admitted provenance | Yes | Raw payload/key may remain ephemeral | Verify ephemeral bytes against durable digests. |
| Continuation capability | No | Exact object + registry entry only | Dies on process loss; never reconstruct/reissue. |
| Continuation consumed flag | Callback-start is durable; pre-start registry use is ephemeral | Yes | Any ambiguity after winner is stop/reconcile, not callback permission. |
| Callback start | Yes | No | Start present without response is terminal unknown. |
| Provider observation before response seal | No | Yes | Loss is unknown; no reinvocation. |
| Sealed response | Yes | No | Forward completion only. |
| Receipt | Yes | No | Read-only reconstruction only. |
| Credential secret/reference | No | Callback-local only | Never persist in these records. |

## Host boundary

`AtomicTransition` uses local `flock`; `ImmutableRecordStore` uses a temporary
file plus atomic rename. The design proves cooperative processes sharing the
same filesystem only. Distributed/multi-host exclusion is
`DEFERRED_BOUNDARY`, not implied by these locks.
