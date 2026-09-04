# Canonical Native Effect Reconciliation Authority Provenance Remediation — issuance/custody/consumption matrix v1

`PREPARATION_BATCH_0_ISSUANCE_CUSTODY_CONSUMPTION_DESIGN_ONLY`
`NO_AUTHORITY_OR_CLAIM_CREATED_OR_CONSUMED`

| Stage / object | Present source and custody | Classification | Smallest later-batch rule |
| --- | --- | --- | --- |
| Operator Root lineage | Constitution/principal records retain Root references; some entry data is fixture-backed | `EXISTS_FRAGMENTED` | Reuse retained lineage and explicitly preserve the authenticated-ingress boundary; do not invent a new Root or treat fixture validation as issuance. |
| Active Imperator competence | Read-only lifecycle reconstruction and scoped principal versions exist | `EXISTS_CANONICALLY` | Require the exact active principal scope selected by Batch 1. Constant `issuer` text is never enough. |
| Issuance caller authority | Typed domain caller-authority issue/consume path exists for existing Imperator transitions | `EXISTS_CANONICALLY` | Add only the exact reconciliation transition/target in a later contract; short-lived and single-use. |
| Reconciliation issuance decision | None | `ABSENT` | Immutable source decision must bind exact effect lineage, target authority digest, issuer principal and validity/revocation policy. |
| Reconciliation issuer | None | `ABSENT` | Only canonical writer of the authority directory; no array accepted from downstream callers. |
| Issuance authority consumption | Reusable canonical store exists, unused here | `EXISTS_FRAGMENTED` | Consume exact issuance authority under `AuthorityConsumptionStore`; retry only for identical source and issuer consumer. |
| Reconciliation authority record | Written by admission from caller bytes | `EXISTS_FRAGMENTED` | Pre-exist admission, include source decision/issuance/principal/Root references, single-use/exercisable state, validity and revocation reference. |
| Reconciliation issuance evidence | None | `ABSENT` | Separate immutable record references source decision, consumed issuance authority, issued authority and exact issuer. |
| Authority serialization | Plain caller array | `EXISTS_CANONICALLY` | Durable record may serialize as evidence; a resolver-issued custody object must not. Do not confuse these roles. |
| Authority resolution | None | `ABSENT` | Resolve by authority ID from canonical store and verify issuance, source principal, exact digests, expiry and revocation. |
| Typed authority custody | None | `ABSENT` | Resolver returns an exact non-serializable/non-cloneable object recognized by its resolver/issuer instance; caller cannot construct accepted custody. |
| Fresh-process recovery | Claim ID and records work across process loss | `EXISTS_CANONICALLY` | Preserve durable recovery: a fresh process may resolve an unused stored authority or an already-derived claim; it never restores first-callback custody. |
| Authority admission input | Arbitrary `array $authority` | `EXISTS_FRAGMENTED` | Replace with authority ID plus internal resolution or resolver-issued type. Public array ingress must disappear. |
| Effect-lineage verification | Admission/callback/response references are exact | `EXISTS_CANONICALLY` | Preserve and perform again against issuer-selected sources, not caller-selected references alone. |
| Issuer-lineage verification | Constant issuer string | `ABSENT` | Resolve issuance record and active principal/root lineage. |
| Revocation | No field/store/resolution | `ABSENT` | Refuse revoked authority before first consumption; define post-consumption retry semantics without reopening authority. |
| Authority expiry | Caller chooses; checked at admit | `EXISTS_FRAGMENTED` | Issuer bounds it to all source expiries; resolver and consumer enforce it. |
| Reconciliation authority consumption | None | `ABSENT` | Consume once for exact claim derivation; competing authority users lose. |
| Claim derivation | Deterministic from authority ID + response digest | `EXISTS_FRAGMENTED` | Derive only after resolved authority consumption; include authority issuance and consumption references. |
| Claim durability | Immutable claim record | `EXISTS_CANONICALLY` | Preserve for process-loss recovery. Durability does not imply repeatable authority. |
| Claim expiry | Enforced at use | `EXISTS_CANONICALLY` | Preserve, with explicit rule for exact retry after an already-recorded consumption cut. |
| Claim revocation | None | `ABSENT` | Batch 1 must decide whether pre-consumption authority revocation projects into claim invalidity and freeze the post-consumption cut. |
| Claim consumption | None | `ABSENT` | Atomically consume on first receipt mutation for exact source/consumer. |
| Same claim replay before receipt | Revalidates reusable claim | `EXISTS_FRAGMENTED` | Exact retry after recorded consumption may only complete the same receipt. |
| Same claim replay after receipt | Returns same receipt while claim unexpired | `EXISTS_FRAGMENTED` | Return/read exact receipt based on bound provenance; do not re-consume or re-authorize. |
| Distinct claim, same response | Multiple caller authority IDs can create multiple claims | `EXISTS_FRAGMENTED` | One consumed canonical authority yields one deterministic claim; competing derivations conflict. |
| Receipt binding | Deterministic immutable one-receipt put | `EXISTS_CANONICALLY` | Preserve, add consumed-claim provenance if Batch 1 contract requires it. |
| Receipt reconstruction | Read-only API exists | `EXISTS_CANONICALLY` | Keep separate from claim use; no mutation or authority required. |
| First callback continuation | PID/private nonce/exact object and one-use | `EXISTS_CANONICALLY` | Preserve unchanged; reconciliation work must not create or reconstruct continuation custody. |
| Provider/credential path | Recovery has none | `EXISTS_CANONICALLY` | Preserve source-level and reflection proof of no callback, payload, key, provider or credential input. |
| Local atomic locks | Authority, claim, continuation and immutable scopes exist separately | `EXISTS_FRAGMENTED` | Establish one documented order and interruption-convergent source/consumer identities. |
| Multi-host coordination | None | `DEFERRED_BOUNDARY` | Do not claim distributed single use. |

## Authority-state transitions required later

```text
ISSUANCE_AUTHORIZED
  -> ISSUANCE_AUTHORITY_CONSUMED
  -> AUTHORITY_AND_ISSUANCE_EVIDENCE_PUBLISHED
  -> AUTHORITY_RESOLVED_CURRENT
  -> AUTHORITY_CONSUMED_FOR_EXACT_CLAIM
  -> CLAIM_PUBLISHED
  -> CLAIM_CONSUMED_FOR_EXACT_RECEIPT
  -> RECEIPT_BOUND
  -> RECEIPT_READ_ONLY_REPLAY
```

There is no reverse transition and no edge from any state to callback execution.

## Retry and interruption semantics

| Durable cut | Exact retry may do | Competing or substituted retry must do |
| --- | --- | --- |
| Before issuance-authority consumption | Re-evaluate current source/expiry/revocation | Refuse mismatched target/source |
| Consumption exists, authority absent | Same issuer/source may finish deterministic authority + issuance evidence | Refuse different issuer/source/target |
| Authority exists, no authority consumption for claim | Resolve current authority and attempt one consumption | One claimant wins |
| Authority consumption exists, claim absent | Same consumer/source may finish deterministic claim | Refuse different claimant or lineage |
| Claim exists, not consumed | Re-evaluate policy and attempt first receipt use | One exact consumer wins |
| Claim consumption exists, receipt absent | Same consumer/source may finish the exact deterministic receipt | Refuse different receipt/lineage; never provider callback |
| Receipt exists | Read and verify the same receipt | Refuse any different receipt or mutation |

## Lock order for later proof

The implementation must use a single globally documented direction and prove
no reverse acquisition. The preparation candidate order is:

1. source/issuer identity scope;
2. issuance-authority consumption scope;
3. issued-authority immutable directory;
4. issuance-evidence immutable directory;
5. reconciliation-authority consumption scope;
6. claim immutable directory;
7. admission-continuation scope;
8. claim-consumption scope;
9. receipt immutable directory.

Batch 1 must turn this proposal into exact contracts before implementation.
Nested use of `AuthorityConsumptionStore` must be reviewed for self-locking and
all recovery cuts must be retry-convergent. No lock crosses external I/O; the
recovery graph contains no external I/O.

