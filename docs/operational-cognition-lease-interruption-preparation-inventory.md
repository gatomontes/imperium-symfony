# Operational Cognition Lease Interruption Preparation Batch 0 inventory

## Preparation boundary

This documentation-only inventory completes Preparation Batch 0 for one live, unconsumed
`imperium.clavium-operational-cognition-lease/v1` before durable operational invocation claim
creation. It changes no runtime behavior, defines no executable disposition, opens no enforcement
authority, and creates or consumes no operational claim.

The classifications mean:

- `EXISTS_CANONICALLY`: the exact operational fact or invariant is already enforced and tested;
- `EXISTS_FRAGMENTED`: the necessary evidence exists, but no single operational interruption
  contract proves the complete requirement;
- `ABSENT`: the operational interruption artifact, consumer, or proof does not exist; and
- `DEFERRED_BOUNDARY`: the requirement is intentionally outside this exact pre-claim slice.

## Competent actors

The competent interruption judge is the exact Seneschal identity sealed as `authorizer` by the
source `imperium.curia-bounded-execution-authorization/v1` and copied without reinterpretation into
the operational cognition request. Competence cannot be inferred from `curia.seneschal` alone. A
future disposition service must resolve the source authorization, require exact authorizer equality
across authorization and request, and prove that the same binding remains the sole active Seneschal
for the instance with the same binding digest, Manifestation, and occupancy generation. A replaced,
duplicate, stale, or merely same-Office occupant must fail stopped.

The exact mechanical enforcer is the sole current active `clavium.locksmith` for the lease instance,
proved from `imperium.clavium-locksmith-occupancy/v1`. The lease's `issuer` identifies the Locksmith
that issued it, but lease-issuance authority is not interruption-enforcement authority. A future
authority must separately appoint and bind the current Locksmith, including binding digest,
Manifestation, and occupancy generation.

## Requirement inventory

| Requirement | Classification | Exact proof or gap |
| --- | --- | --- |
| Source bounded-execution authorization is immutable and digest-bound | `EXISTS_CANONICALLY` | `OperationalCognitionRequestService` resolves and validates `imperium.curia-bounded-execution-authorization/v1`; `OperationalCognitionAccessRequestDecisionTest` proves exact replay/conflict behavior. |
| Competent judge is identified by the source authorization's `authorizer` | `EXISTS_CANONICALLY` | `BoundedExecutionAuthorizationService` seals seat, binding, Manifestation, and generation; `OperationalCognitionRequestService` copies that actor into the request. |
| Authorizer binding digest is present in the source authorization | `ABSENT` | The authorization's `authorizer` omits `binding_digest`; the digest must be recovered from and matched to the current occupancy record rather than invented or inferred. |
| Exact authorizer equality across authorization and request | `EXISTS_FRAGMENTED` | Both records carry `authorizer`, but current claim admission does not compare them and no interruption validator reconstructs the authorization. |
| Same authorizer is the unique current active Seneschal | `EXISTS_FRAGMENTED` | Curia occupancy and the governance interruption precedent provide the native uniqueness check; no operational-lease interruption service currently performs the full binding/digest/Manifestation/generation proof. |
| Office name alone confers interruption competence | `ABSENT` | No contract grants omnibus Curia or Seneschal revocation power; same-Office substitution must fail stopped. |
| Lease issuer is exactly attributable | `EXISTS_CANONICALLY` | `OperationalCognitionLeaseService` seals the current Locksmith's binding digest, Manifestation, and generation into `issuer`; its test rejects unauthorized or credential-capable occupancy. |
| Current Locksmith can be selected mechanically | `EXISTS_FRAGMENTED` | Clavium occupancy plus completed governance-lease authority services prove the pattern; no operational interruption authority performs the selection. |
| Lease issuance authority is interruption enforcement authority | `ABSENT` | The current occupancy grants only operational lease issuance. Separate, expiring, single-use interruption authority is required. |
| Request → Imperator decision reference and digest | `EXISTS_CANONICALLY` | `OperationalProviderResourceDecisionService` resolves the request and seals `source_cognition_request`; request/decision tests prove exactness and conflict rejection. |
| Decision → lease reference and digest | `EXISTS_CANONICALLY` | `OperationalCognitionLeaseService` resolves the decision and request, seals both references, and rejects divergent decisions. |
| Request → decision → lease target, input, model, resource, and iteration equality | `EXISTS_CANONICALLY` | Lease issuance and claim validation compare the exact target, provider, model, configuration, resource ceiling, input digest, Profile-model digest, and iteration. |
| Source authorization and custody/binding lineage survive into the request | `EXISTS_CANONICALLY` | The request seals the authorization reference, custody transition, binding, authorizer, target Manifestation/Seat/binding/custody, and bounded input digest. |
| Full authorization → request → decision → lease lineage is reconstructed in one proof | `ABSENT` | Existing services validate adjacent links only; no read-only operational interruption reconstruction reaches the source authorization and occupancy evidence. |
| Native claim lock order is cognition authority then lease | `EXISTS_CANONICALLY` | `OperationalCognitionInvocationClaimService::claim()` acquires `oca-cognition-authority:<sha256 authorityId>` then `oca-lease:<sha256 leaseId>`. |
| Same lease/same authority concurrent claims converge | `EXISTS_CANONICALLY` | `OperationalCognitionInvocationClaimServiceTest::testTwoProcessesConvergeOnOneClaim()` and `tests/fixtures/operational-cognition-claim-contender.php` prove one durable claim. |
| Same lease or same authority with divergent counterpart fails stopped | `EXISTS_CANONICALLY` | Claim scanning rejects divergent or partial consumption with `OCA405`/`OCA406`; focused tests cover partial/divergent authority. |
| Interruption and claim creation serialize on the native lease lock | `ABSENT` | No operational interruption consumer acquires `oca-lease:<sha256 leaseId>` and no claim-admission guard reads an interruption result while that lock is held. |
| Claim wins race before interruption | `EXISTS_FRAGMENTED` | Native claim locking and mechanical claim search exist; a future disposition/authority/enforcement path must reject an already claimed lease and prove the losing interruption creates no result. |
| Interruption wins race before claim | `ABSENT` | No operational interruption result exists for claim admission to consume and deny claim creation. |
| Request expiry is at most fifteen minutes | `EXISTS_CANONICALLY` | `OperationalCognitionRequestService` rejects a later bound. |
| Decision expiry is at most ten minutes and no later than request expiry | `EXISTS_CANONICALLY` | `OperationalProviderResourceDecisionService` enforces both bounds. |
| Lease expiry is at most five minutes and no later than decision expiry | `EXISTS_CANONICALLY` | `OperationalCognitionLeaseService` enforces both bounds. |
| Future enforcement-authority expiry is bounded by the earliest live source expiry | `ABSENT` | The safe rule is `min(request.expires_at, decision.expires_at, lease.expires_at)` and at most five minutes after issuance. The lease is transitively earliest today, but every source must be read and compared so malformed or future-version lineage fails stopped. |
| Interruption effective time is within request, decision, and lease validity | `ABSENT` | A future disposition must require issuance ≤ effective time < every source expiry and reject consumed leases or existing claims. |
| Immutable operational interruption result exists | `ABSENT` | Governance-scope result contracts cannot be reused by changing identifiers. |
| Result proves authority consumed and no operational claim created | `ABSENT` | Required exact flags: `authority_consumed=true`, `claim_created=false`, `cognition_authority_consumed=false`, and `lease_consumed=false`. |
| Result proves no credential or provider activity | `ABSENT` | Required exact flags: `credential_resolved=false`, `credential_reference_disclosed=false`, `credential_material_present=false`, `credential_mutated=false`, `provider_invoked=false`, `provider_journal_created=false`, and `network_access_performed=false`. |
| Result proves source immutability and no widening | `ABSENT` | Required exact flags: `lease_mutated=false`, `lease_closed=false`, `request_mutated=false`, `decision_mutated=false`, `propagation_performed=false`, `continuation_authority=false`, and `external_action_authority=false`. |
| Mechanical durable operational-claim absence | `EXISTS_FRAGMENTED` | The claims directory and exact `lease_consumption.lease_id` relation exist; no interruption reconstruction scans every intact claim and fails on a match. |
| Read-only interruption reconstruction | `ABSENT` | Must prove authorization, current Seneschal occupancy, request, decision, lease, disposition, authority, current Locksmith occupancy, result, and claim absence without writing state. |
| Missing, substituted, duplicate, or contradictory evidence fails stopped | `ABSENT` | Adjacent services and the governance precedent prove local rules, but no operational interruption reconstruction enforces exactly one coherent chain and exactly one result. |
| Credential resolution, provider journal, cognition, and external I/O | `DEFERRED_BOUNDARY` | The selected boundary ends before durable claim creation; these operations remain forbidden. |
| Lease mutation, closure, expiry rewriting, or in-flight cancellation | `DEFERRED_BOUNDARY` | Interruption may deny only the future exact claim; the immutable source lease remains untouched. |
| Cross-principal propagation, telemetry, containment, and incidents | `DEFERRED_BOUNDARY` | No instance, mission, Profile, Manifestation, Seat, or credential fan-out is authorized. |
| Iron Gate, Lazaretto, sorties, and credential-platform changes | `DEFERRED_BOUNDARY` | These perimeter and credential-platform campaigns remain closed. |

## Race and lock proof

The only safe implementation lock for the selected lease transition is the existing
`oca-lease:<sha256 leaseId>` lock. Future enforcement must acquire that lock and, while holding it,
re-read the lease and scan intact operational claims before sealing a result. Claim creation already
acquires `oca-cognition-authority:<sha256 authorityId>` before that lease lock.

An interruption path must not acquire the cognition-authority lock: it does not consume or mutate
that authority. It must acquire only the lease lock and must not call back into a path that later
tries to acquire cognition-authority. This preserves a single lock direction and avoids inversion.
Consequently, exactly one contender can establish the pre-claim fact:

1. if claim creation holds the lease lock first, it seals the claim and later interruption fails as
   no longer an unclaimed-lease case; or
2. if enforcement holds the lease lock first, it seals the denial result, and claim admission must
   observe that result under the same lock and fail before writing a claim.

The existing two-process test proves claim/claim convergence only. The two opposing outcomes above,
including zero partial artifacts for the loser, require new process-level tests in the enforcement
batch.

## Exact future result shape

Preparation does not define an executable schema. It fixes the minimum evidence a later separately
authorized schema must carry: an exact source authority reference; the source disposition; the full
lease scope; current Locksmith identity; performed transition
`DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE`; consumption time; the absence flags
listed above; no continuing, external-action, perimeter, or propagation authority; and an immutable
record digest.

The result is denial evidence, not a replacement lease state. `lease_consumed=false` and
`cognition_authority_consumed=false` describe untouched sources; `authority_consumed=true` describes
only the separately opened interruption authority.

## Smallest safe implementation sequence

No step is authorized by this inventory. Each requires a separately opened batch.

1. **Disposition contract and service.** Seal one operational-lease `INTERRUPT` judgment by the
   exact source authorizer, revalidated as the unique current Seneschal. Bind the complete
   authorization/request/decision/lease lineage and create no authority or mutation.
2. **Enforcement authority.** From that disposition, open one at-most-five-minute, single-use
   authority for the unique current Locksmith. Cap expiry at the earliest request, decision, and
   lease expiry. Grant only denial of the exact durable operational claim.
3. **Native admission enforcement and result.** Under `oca-lease:<sha256 leaseId>`, consume only the
   interruption authority, seal the immutable absence result, and add an exact result guard inside
   `OperationalCognitionInvocationClaimService` before claim validation/write. Prove both race
   outcomes with two processes.
4. **Read-only reconstruction.** Resolve the complete lineage and both current occupancies, require
   exactly one disposition/authority/result chain, mechanically scan intact claims for the lease,
   and report claim/credential/journal/I/O/propagation absence without writes.
5. **Documentation-only closeout.** Mark only this pre-claim operational lease slice complete and
   retain every deferred boundary.

## Preserved stop conditions

Preparation opens no executable disposition or enforcement authority. It does not change
`OperationalCognitionInvocationClaimService`, create or consume a claim, invoke cognition, resolve
credentials, create a journal, perform external I/O, mutate or close a lease, or propagate beyond
the exact lease. Generalized revocation, `RESTRICT`, `REAUTHORIZE`, `RETIRE`, telemetry,
containment, incidents, Iron Gate, Lazaretto, sorties, and credential-platform work remain closed.
