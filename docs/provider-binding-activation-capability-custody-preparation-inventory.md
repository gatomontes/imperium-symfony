# Provider Binding Activation and Capability Custody — Preparation Batch 0 inventory

## Result

`PREPARATION_BATCH_0_COMPLETE_ACTIVATION_AND_DURABLE_CUSTODY_ABSENT`

The separated outbound corridor has canonical inactive provider bindings, provider-bound credential
eligibility, deterministic execution claims, effect-start journals, callback checkpoints and
read-only reconstruction. It does not have an authority or transition that activates a binding for
one execution. `BOUND_ACTIVE` is only a vocabulary value in
`ProviderImplementationBindingContract`; every produced binding is `BOUND_INACTIVE`, and eligibility
requires that inactive status.

The environment-backed broker cannot support truthful cross-process custody. It proves issuance by
PHP object identity in an issuer-local in-memory map. The same capability presented to another
broker instance is canonically refused as unissued. No durable custodian, delivery record, claim
token, transfer acknowledgement or atomic activation-and-capability consumption exists. Passing
capability metadata to a later process would reconstruct an object, not transfer the exact
already-issued authority.

Preparation changed no runtime file or behavior. No binding was activated; no capability was
issued, persisted, reconstructed, transferred, consumed or resolved; no credential reference or
secret was exposed; no provider or external I/O was invoked; Iron Gate and Lazaretto remained
closed. Provider Execution Assurance remains paused.

## Authority and custody classification

| Requirement | Classification | Exact producer | Exact consumer | Non-authorities and stop condition |
| --- | --- | --- | --- | --- |
| Provider selection authority | `EXISTS_CANONICALLY` | Imperator-shaped `ProviderBindingAuthorizationContract` record supplied to La Cortine | `ProviderImplementationBindingService::bind()` | Selection produces only `BOUND_INACTIVE`; provider registry, command, adapter and binding cannot activate themselves. The present runtime has no production issuer for this contract; tests construct fixtures directly. |
| Inactive provider binding | `EXISTS_CANONICALLY` | `ProviderImplementationBindingService` after single-use authority consumption | Curia authorization, Clavium eligibility, encoder, decoder and reconstruction postures | Status does not grant execution, credential use or I/O. Exact replay converges; changed or expired authority refuses. |
| Binding activation source decision | `ABSENT` | None | A future exact activation transition | Existing selection authority, outbound authorization, execution claim and eligibility may not be reinterpreted as activation authority. |
| Single-execution activation record or lease | `ABSENT` | None | Future atomic execution admission | `BOUND_ACTIVE` vocabulary is not a record, transition or lease. No existing component may mutate the immutable inactive binding. |
| Activation binding set: tool, effect authorization, claim, binding, assurance, destination and expiry | `EXISTS_FRAGMENTED` | Armory, Imperator, La Cortine and the claim/journal services separately | Future activation validator | The facts exist in separate sealed records but no canonical activation identity binds and consumes the complete tuple. |
| Credential capability issuer | `EXISTS_CANONICALLY` | `EnvironmentCredentialBroker::issue()` in the issuing process | The same broker object only | The command, provider binding, eligibility service, claim and journal are non-issuers. Issuance is random, in-memory and not source-authority-backed. |
| Opaque capability identity and scope | `EXISTS_CANONICALLY` | `CredentialCapability` plus broker issuance | Eligibility, execution claim, journal and journal-bound broker | Durable records retain capability ID and credential-reference digest, but the object also contains the clear credential reference. Metadata is not transferable custody authority. |
| Capability custody owner | `EXISTS_FRAGMENTED` | Issuing `EnvironmentCredentialBroker` instance holds an in-memory object map | Same-process `consume()` | Clavium is doctrinal custody but no durable custodian service owns the issued capability. Process exit destroys the only issuance proof. |
| Cross-process delivery of the exact already-issued capability | `ABSENT` | None | Later command process | Serializing metadata, constructing a new object, reissuing, or rereading an environment reference would manufacture or replace authority rather than transfer it. |
| Durable non-secret capability identity proof | `EXISTS_FRAGMENTED` | Eligibility, claim and journal seal capability ID plus credential-reference digest | Validators and reconstruction | A digest proves record consistency, not possession of the issuer-recognized capability; no unforgeable claim token or custody-generation record exists. |
| Custody transfer and delivery acknowledgement | `ABSENT` | None | Future custodian and exact process principal | No offered, claimed, delivered, acknowledged, abandoned or recovered custody checkpoints exist. |
| Credential resolution | `EXISTS_CANONICALLY` | Same-process `EnvironmentCredentialBroker::consume()` resolves `env:` only after issuer-object and expiry checks | Callback-local provider operation | Custody transfer is not resolution. Eligibility, claim, journal, adapter, command and reconstruction may not resolve credentials. |
| Atomic pre-I/O capability consumption | `EXISTS_FRAGMENTED` | Broker increments its in-memory use count before callback; journal-bound broker commits admission and attempt checkpoints | Same process and callback | Capability use is not durably consumed in the authoritative root, and activation plus custody are not one atomic transaction. Crash after the memory increment loses consumption truth. |
| Atomic activation-and-custody consumption | `ABSENT` | None | Future single-root transition | Existing nested file locks serialize individual scopes but no record atomically wins activation, custody delivery and use before I/O. |
| One-root contention serialization | `EXISTS_CANONICALLY` | `AtomicTransition`, immutable winner records and `AuthorityConsumptionStore` | Same authoritative filesystem root | `flock` and filesystem records do not establish multi-root or multi-host ownership. |
| Cross-process contention for capability delivery | `ABSENT` | None | Competing local processes | There is no durable delivery winner. Separate broker instances each lack the issuer's object identity and both refuse. |
| Distributed contention and delivery | `DEFERRED_BOUNDARY` | None in this campaign | Future distributed custodian, if selected | Multi-host locks, split brain and distributed transactions remain unproved and are not smuggled into the one-root claim. |
| Capability expiry | `EXISTS_CANONICALLY` | Capability issuer sets `expiresAt`; eligibility and claims narrow/use it | Eligibility, claim, journal and broker | Expiry refuses future use but does not recover abandoned custody or prove prior consumption. |
| Binding expiry | `EXISTS_CANONICALLY` | Selection authority supplies validity; binding preserves it | Binding and eligibility validation | No active lease exists to expire independently, and no process consumes a stale-activation record because none exists. |
| Revocation before capability use | `ABSENT` | None on this lane | Future custodian/admission transition | Status vocabulary includes `REVOKED`, but immutable bindings have no revocation producer or consumed revocation fact. Revocation behavior remains a closed boundary. |
| Replay refusal after effect start | `EXISTS_CANONICALLY` | Execution claim, effect-start journal and invocation admission/checkpoints | Journal-bound broker and recovery services | `UNKNOWN_REPLAY_PROHIBITED` prevents automatic reinvocation; it does not create activation or custody authority. |
| Capability replay refusal | `EXISTS_FRAGMENTED` | Same broker's in-memory use counter and single-use metadata | Same broker process | Replay refusal does not survive process loss. A later process refuses as unissued rather than proving consumed, available or abandoned. |
| Crash recovery before custody transfer | `ABSENT` | No durable custody facts exist | None | The capability disappears with the issuing process; recovery cannot distinguish never delivered, abandoned or consumed. |
| Crash recovery after delivery but before resolution | `ABSENT` | No delivery checkpoint exists | None | No safe reclaim, redelivery or terminal refusal can be derived. |
| Crash recovery after resolution/callback start | `EXISTS_FRAGMENTED` | Attempt and callback-start checkpoints plus effect journal | Read-only forward recovery | Provider outcome is truthfully unknown and replay prohibited, but durable capability-consumption truth remains absent. |
| Read-only evidence reconstruction | `EXISTS_CANONICALLY` | Receipt and governed-result reconstruction services | Audit/recovery readers | Reconstruction explicitly performs no credential resolution, provider reinvocation or I/O and grants no retry authority. |
| Capability reconstruction | `ABSENT` | None | None | Correctly absent: reconstructing a `CredentialCapability` from durable metadata would manufacture authority. |
| Secret exclusion from durable corridor | `EXISTS_CANONICALLY` | Eligibility, claim, journal, response and reconstruction records use digests/opaque IDs | Validators, admission and audit readers | The clear `credentialRef` remains process-local in the capability object; capability metadata must not be logged, persisted or placed in exceptions because it includes that reference. |
| Retired live command | `EXISTS_CANONICALLY` | `AgentMailEmailSendCommand` | Operator receives fail-closed refusal only | It may not assemble authority, select or activate a provider, issue/receive a capability, resolve a credential or invoke I/O. |
| Delegate cognition activation analogy | `EXISTS_FRAGMENTED` | `DelegateMissionProviderInvocationActivationService` produces a mission-specific activation and credential-lease record | One bounded cognition turn | It is a different authority lane, uses non-atomic file persistence, does not transfer credential possession and cannot authorize or template outbound `email.send` activation by analogy. |

## Crash, replay and recovery matrix

| Last provable fact | Truthful posture |
| --- | --- |
| Inactive provider binding only | No activation and no execution authority. |
| Eligibility record only | Capability metadata matched in one process; no issuance possession or custody transfer is proved. |
| Capability issued in memory, issuer process exits | Capability authority is lost. Do not reconstruct or reissue it by implication. |
| Capability offered to another current broker | `CREDENTIAL_CAPABILITY_UNISSUED`; object metadata is not cross-process custody. |
| Future activation without durable custody winner | Refuse before credential resolution and I/O. |
| Invocation admission or credential-attempt checkpoint | Preserve `UNKNOWN_REPLAY_PROHIBITED`; absence of callback proof grants no retry. |
| Callback-start checkpoint, no response | Provider may have acted; no capability redelivery, reconsumption or provider reinvocation. |
| Sealed accepted evidence | Reconstruct forward read only; never resolve credentials or resend to recreate evidence. |

## Smallest safe proposed sequence

No step is authorized merely because it appears here.

1. **Activation and custody contracts** — separately version the exact activation authority,
   immutable single-execution activation lease, custody identity, delivery state and consumption
   boundary, all with explicit non-authorities and no credential reference or secret.
2. **Governed activation decision and issuance** — name the competent decision owner and issuer;
   bind one tool authority, outbound effect authorization, execution claim, inactive provider
   binding, assurance profile, destination policy and common expiry.
3. **Durable custodian feasibility gate** — require a custodian able to recognize the exact issued
   capability across processes without persisting secret material, reissuing or reconstructing
   authority. If no such mechanism is selected, terminate the campaign with execution refused.
4. **One-time claim and delivery** — durably serialize one process principal as winner, record
   offered/claimed/delivered/acknowledged or abandoned custody states and exclude capability-bearing
   material from ordinary records, logs and exceptions.
5. **Atomic pre-I/O admission** — on one authoritative root, atomically consume the activation and
   custody claim before credential resolution or any outbound byte; preserve the existing effect
   journal and unknown-outcome rules.
6. **Crash, contention, expiry and revocation proof** — prove every pre-delivery, post-delivery,
   pre-resolution and post-callback crash cut; same-root contention; stale activation; revocation
   observation; replay refusal; and secret exclusion. Multi-host guarantees remain deferred.
7. **Live-command migration** — only after the prior proofs, make the command consume exact
   pre-existing records. It may issue, select or reconstruct nothing.
8. **Terminal audit** — decide whether Provider Execution Assurance may resume. No live execution
   follows automatically.

## Batch 1 gate

Only Batch 1 may next be considered: define and test activation and capability-custody contracts
without implementing a producer, custodian, delivery mechanism or consumer. Batch 1 must preserve
the possibility of a terminal refusal if no cross-process custodian can preserve exact capability
identity without manufacturing authority.

## Preserved perimeter

Runtime behavior is unchanged. Provider Execution Assurance remains paused. No binding activation,
capability issuance, persistence, reconstruction, transfer, consumption or resolution, credential
reference or secret exposure, live-command migration, provider invocation, external I/O, Iron Gate
or Lazaretto opening occurred. Inbound webhook, sortie, credential-platform, revocation,
propagation, telemetry, reassessment, containment and incident behavior remain unchanged.
