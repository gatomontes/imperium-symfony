# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — issuance/custody/consumption matrix v1

`PREPARATION_BATCH_0_MATRIX_ONLY`
`NO_AUTHORITY_CREATED_OR_CONSUMED`

| Stage / object | Durable source | Authorization input | Currentness cut | Consumption/publication | Retry/recovery | Classification |
| --- | --- | --- | --- | --- | --- | --- |
| Native transition decision | Native authority chain | Signed Root/native principal exact transition | `NativeAuthority::load()` | Used by native consumer | Read-only replay refuses committed authority | `EXISTS_CANONICALLY` |
| Native transition authority | Native state `authorities` | Decision/custody exact target | Before native transition commit | Consumption embedded at `transition.records.authority_consumption` | Commit is durable; no continuing use | `EXISTS_CANONICALLY` |
| Reconciliation issuance decision | none | none | none | none | none | `ABSENT` |
| Reconciliation issuance authority | none | none | none | none | none | `ABSENT` |
| Typed issuance capability | none | none | none | none | none | `ABSENT` |
| Reconciliation issuer service | public class/corridor factory | admission ID + timestamps only | SourceResolver at method entry | authority then issuance evidence; no issuance-authority consume | deterministic exact retry completes orphan issuance | `EXISTS_FRAGMENTED` |
| Reconciliation authority | immutable v2 record | presently implied by source provenance | checked at issue/resolve | publication is not an authorized consumption | deterministic ID converges | `EXISTS_FRAGMENTED` |
| Reconciliation issuance evidence | immutable v1 record | no source decision/consumed issuance authority | source references validated at resolve | records result only | exact retry converges | `EXISTS_FRAGMENTED` |
| Reconciliation resolver capability | resolver-private registry + object | canonical authority ID/time | full currentness at resolution | in-memory one use; claim is durable embodiment | fresh process must resolve again | `EXISTS_CANONICALLY` |
| Capability at claim-use cut | exact resolver object | existing capability | **source currentness absent** | deterministic claim publication under authority lock | cut before claim leaves no durable consumption | `EXISTS_FRAGMENTED` |
| Forward-recovery claim | immutable v2 record | embedded authority consumption | full source inspection occurs later at forward use | generic consumption binds claim digest to receipt | cut after consumption resumes same receipt | `EXISTS_CANONICALLY` |
| Receipt | immutable deterministic record | consumed exact claim | source re-inspected before first mutation | receipt publication | exact return/reconstruction only | `EXISTS_CANONICALLY` |
| Reconstruction | receipt/claim/authority/Root chain | receipt ID only | historical admitted-time inspection | no writes or authority actions | read-only | `EXISTS_CANONICALLY` |

## Custody distinctions

| Custody property | Existing reconciliation capability | Required issuance capability |
| --- | --- | --- |
| Durable evidence is not capability | yes | must be yes |
| Resolver-issued exact object | yes | required |
| PID/process-incarnation bound | yes | required |
| Clone/serialization refused | yes | required |
| Exact target/issuer/decision bound | authority + issuance digests only | decision, issuance authority, issuer, target and validity required |
| Present-tense source validation at delivery | yes | required |
| Present-tense validation at use | **no** | required in same governed cut |
| Durable single-use consumption | claim embodies consumption | must be atomically recorded with authority/issuance publication |
| Fresh-process recovery | resolve fresh capability if still valid/unconsumed | reconstruct decision/evidence, then resolve fresh typed custody |

## Lock and publication order

| Flow | Current order | Finding / target |
| --- | --- | --- |
| Native transition | global native transition -> sorted source immutable scopes -> migration/transition store | Canonical local-host pattern; consumption is part of commit. |
| Reconciliation issuance | issuance authority-ID scope -> authority immutable directory -> issuance immutable directory | Deterministic and acyclic, but has no upstream decision/authority consumption. |
| Authority-to-claim | reconciliation authority scope -> claim immutable directory | Exact local winner; insert source currentness revalidation before consume/publication without upstream lock inversion. |
| Claim-to-receipt | admission-continuation scope -> exact claim scope -> generic authority-consumption scope/directory -> receipt immutable directory | Accepted no-provider recovery pattern. |
| Target issuance | one exact issuance-root scope -> currentness reads -> issuance-authority consumption -> authority -> issuance evidence | Must select one deterministic source/consumer winner and permit only exact retry completion. |

Nested use of `AuthorityConsumptionStore` inside a caller-held scope is already
used by canonical issuers. The later design must define a single global order;
it must not acquire the downstream reconciliation-authority lock before an
upstream issuance-authority lock.

## Deterministic retry rules

| Cut | Current behavior | Required later disposition |
| --- | --- | --- |
| Before issuance authority consumption | no such cut | no output; retry revalidates decision/currentness |
| After issuance consumption, before authority publication | no such cut | exact decision/issuer/target retry alone may finish |
| After authority, before issuance evidence | orphan is unresolvable; exact `issue()` finishes | preserve, now tied to consumed issuance authority |
| After capability resolution, before use | source may revoke and stale capability may publish claim | revalidate and refuse if no longer current |
| After in-memory capability consume, before claim | no durable claim/consumption; fresh process retries | preserve bounded exact retry behavior |
| After claim consumption, before receipt | durable exact source/consumer consumption exists | exact retry finishes/returns receipt; different consumer refuses |
| After receipt | immutable result | read-only reconstruction only |

## Boundaries

- Cooperative single-host filesystem locking: `EXISTS_CANONICALLY`.
- Windows path identities are case-normalized; Linux identities preserve case:
  `EXISTS_CANONICALLY`.
- Cross-host/distributed locks and hostile storage: `DEFERRED_BOUNDARY`.
- Provider invocation, credential resolution, callback reinvocation, mission,
  email, Iron Gate, Lazaretto and Batch 7: `DEFERRED_BOUNDARY` and unauthorized.
