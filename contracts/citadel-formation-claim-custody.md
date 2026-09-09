# Formation claim custody — FC0–FC3 contract

This adds an offline-testable, default-dormant consumer of genuine Citadel formation authority. It does not select a provider, amend B1, enroll native or formation trust, confer ProtectedMission competence, or authorize commissioning. Owner disposition remains DEFER_ENROLLMENT. Existing [formation semantics](citadel-formation-runtime.md) and CF01/CF02/IR01 remain controlling.

## Producer and consumer boundaries

All paths below are relative to `src/Imperium/Runtime/`.

| Fact | Authoritative producer / retained source | Custody consumer |
| --- | --- | --- |
| Institutional actor and parent instance | `Citadel/Formation/FormationInstitution`; actual root installation and current occupancy; public-only delegated evidence | `FormationPersonnel` revalidates candidate lineage and current Castellan/Locksmith; no imported claim establishes an actor |
| Owner effect, exact terms, revocation | `FormationSignatures::verify`, aggregate trust and revoked decisions | Shared `FormationSessionAuthority::validateSession`; exact signature, phase, source, currentness, refusal history and understanding closure |
| Holder and phase source | `FormationCognition::authorizationSource`; intake/opening exchange, separate exact drafting request, or receiving handoff | `FormationSessionAuthority::source/request`; exact retained request, cognitive artifact, holder, visible context and registry generation |
| Provider/resource and planning decision | `Imperator/GovernanceProviderResourceDecisionService::formationSession` at grant | Recomputed authentic decision equals retained session decision; drafting retains separate Planning Authorization |
| Session and attempt | Aggregate `sessions[session_id].attempts[attempt_id]`, under `FormationJournal` | Supplied claim/request/terms must equal retained claim/request/terms; session ID recomputed from intake/phase/terms/decision |
| Reservation | `SessionExposure::reserve` inside aggregate change | Exact maximum, signed per-call/total equality and aggregate re-reservation check; unsettled exposure stays maximum |
| Consumed authority and lease | `Clavium/FormationSessionLeaseService::derive`, reservation transaction | Rebuild complete authority/lease against current holder and current Locksmith issuer; supplied consumed flags or digests alone are insufficient |
| Exact operation | Deployment-selected `FormationWireAdapter::prepare`, checked by `FormationPreparedOperation` before reservation | Recomputed operation must equal retained v2 claim at delivery; prepare checked again immediately before durable dispatch |
| Credential delivery | `Clavium/FormationClaimCustodyBroker` commits one-use custody in the genuine aggregate | `LaCortine/CredentialBroker::issue/consume`, exact approved reference, claim ID, operation, expiry and maxUses=1 |
| Actual return | Fixed adapter dispatch inside private credential callback | Broker validates result scope, fixture/deployment adapter provenance, ID, exact bytes and integer usage before real `ProviderResponseEnvelopeService::seal` |
| Settlement and admission | `FormationCognition::call/recover` | Exact retained envelope; existing current admission checks, understanding closure, separate drafting and receiving semantics |

The shared session interpreter was extracted without changing old phase/control semantics. `FormationCognition` retains its constructor and v1 paths. No legacy broker is repurposed. No ordinary command accepts a root, clock, verifier, broker, adapter or arbitrary callback override.

## Versioned additions

New custody requires signed session terms `transport` with exactly:

```json
{
  "schema": "imperium.formation-transport-authorization/v1",
  "adapter": "<deployment-approved-adapter-identity>",
  "credential_reference": "<approved-public-custody-reference>",
  "operation": "<approved-credential-operation>"
}
```

These are public names, never secrets. `FormationPreparation` can serialize this exact addition for independent signing. Signing is not approval of an unsupported adapter. The deployment-selected adapter must independently accept the approved tuple and all provider/bounds terms; callers cannot select an implementation by changing this name.

`PreparedFormationTransport` causes `FormationCognition` to retain `imperium.citadel-session-call-claim/v2`, adding `prepared_operation`. The authority/lease scope adds its digest. The operation schema `imperium.formation-prepared-operation/v1` binds canonical request bytes and digest; exact wire bytes (base64) and SHA-256; provider/model/destination; authority-source and pricing digests; approved transport tuple; maximum/per-call/total; and session expiry. Exact claim expiry is separately retained and bounded by both session expiry and the existing local duration calculation. Validation rebuilds the operation, bounds byte size, validates integer fields/order and compares its complete digest.

The prepared wire representation must include every non-secret dispatch input that could change the authorized effect: body, method/protocol and any relevant public headers/options. Authentication is the only separately injected infrastructure context. A future adapter must transmit exactly that representation to the single approved destination, reject redirects/implicit alternate endpoints, recheck its serialization immediately before dispatch, validate actual usage/provenance, and refuse unsupported remote bounds during pure prepare. There is no live implementation in this change.

Older claims retain v1 recovery and original behavior, but cannot acquire custody from the new broker. Existing terms without transport cannot authorize this path. An arbitrary self-sealed v2 copy, a consumed flag or an operation digest does not create retained authority.

## Lock order, currentness and interruption

`FormationJournal::change` uses `Persistence/AtomicTransition` scope `citadel-formation`, immutable hash-chain generations, flushed/fsynced temporary frame and rename before return. Hashes detect byte changes; trusted root custody, not an unkeyed hash, establishes retained authority. Every process must share that root and lock protocol. Administrative rollback, malicious filesystem writers, old binaries bypassing the protocol, power-loss/directory-fsync guarantees and malicious adapters are outside this proof.

| Boundary, in order | Locked work | Work after releasing lock | Interrupted result |
| --- | --- | --- | --- |
| Inspect/prepare | None | Pure operation construction only | No credential/transport effect |
| Reserve | Revalidate session/request; reserve; derive consumed exact authority/lease; retain claim | None | Full reserved exposure; original attempt only recovers |
| Formation start | Current session and Locksmith; set STARTED_OUTCOME_UNCERTAIN | Enter fixed transport | No automatic retry |
| Custody delivery | Revalidate complete authentic claim and current session/issuer/accounting; require no custody marker; commit DELIVERY_COMMITTED_OUTCOME_UNCERTAIN | Issue one scoped capability | Marker is never reset, even if no capability was actually issued |
| Consumption | Repeat currentness/binding validation; move only DELIVERY → CONSUMPTION_COMMITTED_OUTCOME_UNCERTAIN | Consume capability through private callback | Possible unused capability; never issue again |
| Dispatch | Recompute prepared operation; repeat currentness; move only CONSUMPTION → DISPATCH_COMMITTED_OUTCOME_UNCERTAIN | Fixed adapter dispatch once | A replayed callback cannot pass this transition; possible/unknown effect keeps full exposure |
| Response | Validate actual result and usage outside aggregate lock; seal immutable envelope under separate immutable-store lock | Commit RESPONSE_RETAINED with envelope digest, ID, usage and provenance | Envelope publication and custody receipt are separate; recovery may recognize the exact trusted-root envelope under the retained dispatch fence |
| Settle/admit | Caller reads the already-sealed envelope (never re-seals at a later timestamp), settles exact usage, then applies original admission checks | Receiving consumer if applicable | Missing settlement retains full maximum, including after recoverable envelope publication |

No aggregate lock spans credential issue/consume, adapter work or response-store locking. Final fresh-start authorization linearizes at the dispatch checkpoint: refusal, expiry, revocation, changed holder/issuer, changed source and interview completion observed before that checkpoint prevent dispatch. A later change cannot retract an effect already authorized at that checkpoint. This is not a guarantee that physical network activity precedes an expiry/revocation instant; B1 must be resolved separately. Response admission still applies original currentness checks, while already-admitted completion remains recognizable without fresh authority.

The guarantees are at-most-once entry to credential issue and adapter dispatch per retained attempt, not exactly-once remote delivery. Crash after a checkpoint but before its effect may permanently forfeit that attempt. Two processes and repeated infrastructure callbacks cannot obtain a second issue/dispatch; no timeout reset, automatic replay, refund or record deletion exists. A fresh attempt still needs valid unexhausted signed session authority and is never a retry of an unknown attempt.

## Accounting and confidentiality limits

Usage preserves the original ordered fields `calls,input_tokens,output_tokens,cost_microusd,milliseconds`, integer bounds and calls=1. Missing, reordered, non-integer, excessive or contradictory returns cannot settle. Provenance is supplied by trusted adapter code; a response ID or label is not independent provider authentication. Synthetic measurements establish only fixture behavior. A crash after envelope sealing but before caller settlement can admit the retained response under existing rules while preserving full maximum; recovery performs no credential or transport activity.

Credentials remain inside the infrastructure callback. No authentication context is stored in an operation, claim, envelope or receipt. The callback parameter is sensitive; exact raw context echoes are refused. Credential/adapter exception messages and prior exceptions are discarded at the public broker/cognition boundary. These measures do not constitute general redaction of transformed secrets or protection from a malicious trusted adapter; adapter review remains mandatory.

`UnavailableFormationTransport` remains the production alias. The new `FormationWireAdapter` alias is `UnavailableFormationWireAdapter`, which refuses before credential access or provider I/O. Registering the broker creates no usable live path. No environment activation flag or installation command was added. B1 still requires actual enforceable remote cost/time/cancellation policy and owner approval; output tokens or a local HTTP deadline are not substitutes.
