# Canonical Native Effect Process Custody and Formal Closure Remediation — custody/recovery matrix v1

`PREPARATION_BATCH_0_CUSTODY_RECOVERY_DESIGN_ONLY`

| State or act | Current authority/custody | Classification | Required later-batch rule |
| --- | --- | --- | --- |
| Create continuation for new winner | Issuer object + authority `execution_boundary.id` | `EXISTS_FRAGMENTED` | Bind to actual PID, issuer-owned random incarnation nonce, issuer identity and exact object. |
| Serialize capability | Default PHP serialization | `EXISTS_CANONICALLY` | Throw from serialization; reject every unserialization entrypoint. |
| Serialize issuer | Private registries are serialized | `EXISTS_CANONICALLY` | Throw; registry may never be restored. |
| Serialize issuer/outcome graph | Shared reference restores recognized custody | `EXISTS_CANONICALLY` | Entire graph must fail closed even when nested. |
| Clone capability | Allowed, clone unrecognized by original issuer | `EXISTS_FRAGMENTED` | Explicitly throw; do not rely on incidental object inequality. |
| Clone issuer/outcome | Shallow copy preserves recognized reference | `EXISTS_CANONICALLY` | Explicitly throw. |
| Fork inherited issuer | No runtime PID check | `ABSENT` | Child PID mismatch invalidates inherited issuer/capability before any mutation. |
| Fresh spawned process | Empty registry refuses copied fields | `EXISTS_CANONICALLY` | Preserve refusal and add incarnation evidence. |
| PID reuse | No posture | `ABSENT` | Fresh interpreter nonce differs; PID alone never authenticates. |
| Process metadata unavailable | Not handled | `ABSENT` | `getmypid()` failure/change is terminal custody refusal; platform start metadata is optional corroboration. |
| Same process, fresh issuer | Empty registry refusal | `EXISTS_CANONICALLY` | Preserve exact issuer custody. |
| Same process, cloned issuer | Recognizes original capability | `ABSENT` | Clone refusal. |
| Same service exact admission replay | Cached outcome can return same capability | `EXISTS_FRAGMENTED` | May return the already-held outcome only while current incarnation and unused custody remain exact; never mint anew. |
| Admission replay in fresh service/process | No continuation returned | `EXISTS_CANONICALLY` | Reconciliation/status only. |
| First callback | Registry consume before start | `EXISTS_FRAGMENTED` | Actual incarnation validation and consumption precede callback-start publication. |
| Existing callback-start, no response | Terminal unknown | `EXISTS_CANONICALLY` | Never callback again, regardless of claim, expiry or provider idempotency. |
| Existing sealed response, no receipt | `execute()` binds before custody | `EXISTS_FRAGMENTED` | Only `forwardComplete` with exact governed reconciliation claim. |
| Existing receipt | `execute()` returns before custody; `reconstruct()` reads | `EXISTS_FRAGMENTED` | Remove return branch from first-execute API; reconstruct is read-only and idempotent. |
| Reconciliation claim scope | None | `ABSENT` | Admission id/digest, response id/digest, deterministic receipt id, issuer, issued/expiry facts, act=`FORWARD_COMPLETE_ONLY`, provider/credential/retry all false. |
| Fabricated continuation used for recovery | Early branch ignores it | `EXISTS_CANONICALLY` | Recovery API accepts no continuation object. |
| Fabricated reconciliation claim used for execute | No claim type exists | `ABSENT` | First-execute API accepts no reconciliation claim. |
| Claim replay after receipt | No claim | `ABSENT` | Converge to exact existing receipt; never provider callback or second receipt. |
| Claim after admission expiry | Current early response branch permits binding | `EXISTS_FRAGMENTED` | Expiry never permits callback; forward completion may remain permitted only if claim policy explicitly covers sealed pre-expiry response. |
| Response lineage tamper | Immutable digest read plus partial join | `EXISTS_FRAGMENTED` | Validate admission, callback-start and response references/digests before claim use. |
| Callback failure/throw | Start exists, terminal unknown | `EXISTS_CANONICALLY` | Preserve and prove callback count remains one. |
| Provider double boundary | Callable only; no auth/network implementation | `EXISTS_CANONICALLY` | Retain provider doubles through Batch 4; no credential edge. |
| Real provider/network | Suspended | `DEFERRED_BOUNDARY` | Not part of Batches 0–5; Batch 7 remains independently gated. |
| Multi-host custody/locking | Local memory + `flock` only | `DEFERRED_BOUNDARY` | No distributed claim until a separate design proves it. |

## Lock order

Admission retains the merged order:

1. `native-provider-transition`;
2. sorted source/trust immutable scopes;
3. exact authority scope;
4. semantic tuple scope;
5. admission immutable-store scope.

First callback uses only the admission continuation scope plus one store scope
at a time. Actual process custody is checked before callback-start publication.
No filesystem lock is held across the provider double.

Forward recovery uses:

1. admission continuation scope;
2. exact reconciliation-claim scope, only if claim state must be serialized;
3. receipt immutable-store scope.

It never acquires native/authority/tuple locks and never accepts a callback.
This keeps the graph acyclic and makes a repeated recovery converge on one
receipt.

## Process-loss disposition

| Durable facts after loss | Permitted next act | Prohibited next act |
| --- | --- | --- |
| No admission | New admission only if repository inspection proves a clean pre-winner state | Treat orphan/ambiguous write as authority to continue |
| Admission only | Read status; record governed abandoned/stranded disposition later | Reissue/reconstruct custody; invoke callback |
| Admission + consumed ephemeral custody, no start | Reconcile as abandoned pre-callback | Invoke callback |
| Callback-start only | Return terminal unknown status | Invoke callback; automatic retry |
| Callback-start + sealed response | Obtain exact reconciliation claim; forward-bind receipt | Invoke callback; resolve credential |
| Receipt | Read-only reconstruct | Mutate effect meaning; invoke callback |

Provider-side idempotency, an authority label, expiry rollback, a Latin motto,
or a green test is never local retry authority.
