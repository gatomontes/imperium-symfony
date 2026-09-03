# Canonical Native Effect Corridor Activation — authority/effect cut matrix v1

`PREPARATION_BATCH_0_AUTHORITY_EFFECT_MATRIX_ONLY`

Preparation Batch 0 records this matrix without creating any authority or effect.

## Authority separation

| Artifact | Can authorize | Cannot authorize | Classification |
| --- | --- | --- | --- |
| Operator Root transition act | Exact native principal/successor transition scope | Email payload, credential use, provider callback or retry | `EXISTS_CANONICALLY` |
| Native transition authority | One pre-effect native transition | Any provider effect after it is consumed | `EXISTS_CANONICALLY` |
| Native v3 admission/receipt | Evidence of adopted pre-effect binding interpretation | Effect start, credential resolution, I/O or retry | `EXISTS_CANONICALLY` |
| Historical deterministic email authorization/claim | Historical legacy-unbound proof path only | A current native root | `EXISTS_CANONICALLY` |
| Stationary credential proof | Evidence that a secret was locally resolvable without I/O | Provider invocation or a native effect | `EXISTS_CANONICALLY` |
| Proposed canonical native effect authority | Exactly one effect tuple after exact native-root validation | Issuance, widening, second effect, retry or continuing authority | `ABSENT` |
| Process-local credential capability | One broker-local authenticated callback after durable admission | Durable/cross-process authority, reconstruction or recovery | `EXISTS_FRAGMENTED` |

## Required order and cuts

| Cut | Required action | Durable truth afterward | Retry posture | Current state |
| --- | --- | --- | --- | --- |
| 0 | Load exact existing native root/receipt and effect authority | None | Validation may be repeated | `ABSENT` effect join |
| 1 | Validate current principal/successor/binding/provider/authority, expiry and cancellation | None | Same exact input only while current | `EXISTS_FRAGMENTED` |
| 2 | Under exact lock order, atomically consume effect authority and commit effect-start winner | One immutable winner; outcome unknown; continuing authority false | Automatic retry prohibited | `ABSENT` |
| 3 | Resolve stationary credential or mint broker-local handle in same process | No secret-bearing durable record | Failure remains unknown/no retry after Cut 2 | `EXISTS_FRAGMENTED` |
| 4 | Consume broker-local handle and commit callback-start immediately before callback | Provider may have acted | No reinvocation | `EXISTS_FRAGMENTED` historical only |
| 5 | Provider callback returns exact observation | Volatile response observation | Seal forward; no caller substitution | `EXISTS_CANONICALLY` historical only |
| 6 | Seal content and callback-bound response envelope | Exact bytes/digests/times | Forward recovery only | `EXISTS_CANONICALLY` historical only |
| 7 | Seal raw accepted/rejected result | Truthful provider outcome | Rejection/unknown grants no retry | `EXISTS_CANONICALLY` historical only |
| 8 | Admit expected return through deterministic Lazaretto/normalization | Non-authorizing admitted artifact | No provider call | `EXISTS_CANONICALLY` historical only |
| 9 | Bind receipt and reconstruct read-only | Complete evidence chain | Never credential resolution/reinvocation | `EXISTS_CANONICALLY` historical only |

Cut 2 permits only the original uninterrupted winner to continue once through
Cuts 3–5. A process restarted after Cut 2 has evidence/reconciliation authority
only; it cannot treat “credential not yet resolved” as permission to begin the
provider attempt.

## Duplicate, expiry, revocation and cancellation

| Race | Winner rule | Loser/result rule |
| --- | --- | --- |
| Same authority, same effect tuple | One effect-authority/effect-root aggregate | Exact replay returns the immutable winner read-only. |
| Same authority, changed payload/destination/provider/key | Authority lock serializes comparison | Conflict; never a second permissible effect. |
| Different authorities, same replay identity | Effect-root lock serializes comparison | Only one winner; the other authority remains unconsumed or is explicitly refused, never silently burned. |
| Expiry before first winner | Final pre-rename validation | Refuse with no effect-start fact. |
| Expiry after winner | Winner dominates | Evidence/forward reconciliation only; expiry does not reopen authority. |
| Revocation/cancellation before first winner | Same authority lock as admission | Revocation/cancellation winner excludes admission. |
| Revocation/cancellation after first winner | Effect winner dominates | Cannot unconsume or imply provider cancellation; governed reconciliation only. |
| Process death before aggregate rename | No committed winner, unless pending state is ambiguous | Clean absence may retry; any pending/partial evidence is unknown and stopped. |
| Process death after aggregate rename | Winner exists | No automatic retry, reissue, redelivery or second callback. |

## Exact replay identity

The proposed replay identity must be derived, never supplied:

```text
sha256(canonical-json({
  native_root,
  native_transition_digest,
  native_receipt_digest,
  effect_authority_id,
  effect_authority_digest,
  operation,
  destination,
  payload_digest,
  provider_id,
  adapter_id,
  adapter_version,
  credential_family,
  expected_return_contract,
  provider_idempotency_key_digest
}))
```

This identity is distinct from the native transition root and from the historical
message replay fingerprint. A durable join must retain all three identities.

## Prohibited automatic retry cases

Automatic retry is prohibited after any committed effect winner; any pending or
partial winner publication; credential resolution/consumption attempt; callback
start; transport timeout/disconnect; malformed, rejected or missing response;
response observed but not sealed; provider 5xx/429 unless a future separately
governed provider contract explicitly proves safe deduplication; clock rollback;
process termination after the irreversible cut; or uncertainty about whether an
outbound byte left the host.

Only a clean, fully observed pre-winner refusal may be resubmitted, and only with
the same effect tuple while every authority remains current. Sealed-response
forward recovery is not retry.

## Smallest staged implementation/proof sequence

1. **Batch 1 — contracts only:** effect decision/authority, exact holder, replay
   identity, winner/start aggregate, revocation/cancellation and result contracts.
2. **Batch 2 — inert native join:** read exact current root/receipt and validate
   authority; no credential/callback dependency and no effect winner.
3. **Batch 3 — custody and atomic cut:** same-process stationary credential
   posture; atomic authority/start winner; stop terminally if capability semantics
   require unprovable durable/cross-process consumption.
4. **Batch 4 — provider doubles:** callback-start, unknown outcome, response
   envelope, raw result, Lazaretto/normalization and receipt joins; never network.
5. **Batch 5 — adversarial/application proof:** separate-process contention,
   every interruption, expiry/revocation/cancellation, exact replay, all bypasses,
   real container and no-effect sentinels.
6. **Batch 6 — live-trial package only:** freeze command, allowlisted inputs,
   evidence/sanitation/retention plan and provider assumptions; do not execute.
7. **Batch 7 — one live effect:** only with the exact campaign marker and approved
   destination/operation; retain private and sanitized evidence.
8. **Batch 8 — independent verification/terminal audit:** clean merged baseline,
   evidence verification and separate Blackquill audit.

None of Batches 1–8 is authorized by this matrix.
