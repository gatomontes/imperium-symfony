# Provider Execution Boundary Redesign — Preparation Batch 0 inventory

## Result

`PREPARATION_BATCH_0_COMPLETE_BOUNDARY_COHERENT_CONTRACTS_ABSENT`

The prior terminal refusal remains truthful about the selected cross-process PHP-object custody model,
but it is not a permanent architectural prerequisite. The model fused provider credential,
execution authority and the process-local `CredentialCapability` enforcement object. Only exact
execution authority needs durable identity and single-use consumption. Provider credential
possession may remain stationary inside one credential-owning execution boundary, while a
process-local capability object may be created and used only as an implementation mechanism inside
that already-authorized boundary.

The current repository does not yet implement that corrected boundary. It has exact inactive
provider binding evidence, credential eligibility, execution claims, effect-start journals,
same-root serialization, provider invocation admission, interruption checkpoints, replay
prohibition and read-only reconstruction. It does not canonically name the credential-owning
deployment boundary or exact executor principal, activate a binding for one operation, or atomically
consume one complete execution authority and commit effect-start before credential resolution and
the first outbound byte.

The smallest coherent candidate under the declared
`SINGLE_AUTHORITATIVE_ROOT_ONLY` / `TRUSTED_WRITER_CANONICAL_INTEGRITY` posture is a same-process
governed executor. This is a preparation finding, not an implementation selection or authority.

Preparation changed no runtime contract or behavior. No principal or binding was activated; no
authority was issued or consumed; no credential or capability was handled; no provider was invoked;
no external I/O occurred; and Iron Gate and Lazaretto remained closed.

## Credential, authority and boundary classification

| Requirement | Classification | Exact producer | Exact consumer | Trust boundary and crash posture | Non-authorities |
| --- | --- | --- | --- | --- | --- |
| Provider credential possession | `EXISTS_FRAGMENTED` | Deployment injects an `env:` secret; `EnvironmentCredentialBroker` can resolve it | Same broker process during `consume()` | Process environment is stationary local deployment state, but the credential-owning boundary and its admission policy are not canonical. Process death destroys broker issuance state, not the deployment secret. | Environment access, a credential reference or its digest does not authorize provider execution. |
| Execution authority distinct from credential possession | `EXISTS_FRAGMENTED` | Imperator-shaped outbound authorization, tool authority, execution claim and effect-start services produce separate facts | Existing validators and journal-bound broker consume or inspect only portions | Durable facts survive process loss, but no one record binds and atomically consumes the complete provider-operation permission. | Credential availability, capability identity, binding identity and eligibility are not execution authority. |
| Durable execution-authority identity | `ABSENT` | None | Future exact governed executor | Must be immutable, expiring, single-use and authoritative on the one root. Its absence requires refusal before credential resolution. | Existing claim, journal, activation analogy, disposition or receipt may not be renamed as the missing authority. |
| Process-local `CredentialCapability` identity | `EXISTS_CANONICALLY` | `EnvironmentCredentialBroker::issue()` | Exact issuing broker object through identity comparison | In-memory only; process exit destroys issuance recognition. This is acceptable only as an internal enforcement object after durable admission, not as cross-process authority. | Capability metadata and a reconstructed object prove neither issuance nor durable authority. |
| Durable authority versus process-local capability separation | `ABSENT` | None | Future boundary contract and executor | Current `CredentialBroker` comments describe the capability as authority to cause action, preserving the category error. Crash semantics therefore remain fused. | Neither durable metadata nor an in-memory object may substitute for the other. |
| Credential-owning execution boundary | `ABSENT` | None canonically; deployment currently supplies process environment | Future exact executor or broker boundary | A same-process boundary is coherent under one trusted root only if it alone admits authority, resolves credentials and invokes the provider. | La Cortine, Clavium, a CLI command, adapter or environment variable is not itself the named boundary. |
| Exact executor principal | `ABSENT` | None | Future atomic execution admission | Must bind principal ID, office/seat or infrastructure role, binding and generation to one operation. Process restart must re-prove current competence from durable state. | Class name, service identity, OS process, active disposition principal and operator invocation are not competent executor provenance. |
| Provider selection and inactive binding | `EXISTS_CANONICALLY` | `ProviderImplementationBindingService` consumes exact selection authority | Eligibility and downstream evidence validators | Immutable `BOUND_INACTIVE` record survives crashes and exact replay converges. | Selection and `BOUND_INACTIVE` do not activate credentials, execution or I/O. |
| Single-operation provider-binding activation | `ABSENT` | None | Future exact governed executor | Must be exact, expiring and non-continuing; crash before atomic admission leaves it unusable. | `BOUND_ACTIVE` vocabulary, eligibility, cognition activation and historical disposition are not outbound activation. |
| Complete authority tuple | `EXISTS_FRAGMENTED` | Armory, Imperator, La Cortine, Clavium and journal services separately | Future admission validator | Tool authority, effect authorization, provider binding, request identity, destination, payload digest, assurance profile, expiry and executor principal are not bound into one consumable identity. | Possession of any subset grants no continuation or execution. |
| Atomic authority consumption | `ABSENT` | None for the complete tuple | Future executor admission transition | `AuthorityConsumptionStore` and `AtomicTransition` are canonical primitives, but the provider lane has no complete authority to consume. Crash before a winner must preserve availability; after a winner it must prohibit competing use. | Provider-invocation admission and in-memory capability use are not consumption of the missing authority. |
| Effect-start ordering before credential resolution | `ABSENT` | Existing effect-start journal and broker provide separated checkpoints | Future same-process executor | Current journal, admission and attempt are durable, but `EnvironmentCredentialBroker::consume()` resolves the environment secret before incrementing its in-memory use count. No single atomic transition consumes full authority and commits effect-start before resolution. | A journal stating I/O may have started does not prove authority consumption, credential use or provider invocation. |
| First-outbound-byte ordering | `EXISTS_FRAGMENTED` | Journal-bound broker and adapter checkpoint admission and callback start | Provider callback lane | Existing checkpoints support unknown-outcome refusal, but they depend on the missing activation and executor authority. | Callback admission, HTTPS and idempotency identity do not grant permission to send. |
| Same-root contention | `EXISTS_CANONICALLY` | `AtomicTransition`, immutable winner records and authority-consumption primitives | Competing processes on one authoritative filesystem root | File locks and winner records can serialize a local admission. This proves no multi-root behavior. | PHP object identity and separate broker maps are not durable contention arbitration. |
| Distributed contention / split brain | `DEFERRED_BOUNDARY` | None | Future external custodian if selected | Outside the declared deployment posture. | Same-root `flock`, SHA-256 records and local process identity grant no distributed guarantee. |
| Expiry | `EXISTS_FRAGMENTED` | Binding, capability, authorization, claim and journal producers independently set expiries | Their respective validators | No canonical common effective expiry exists for the complete execution tuple; every stale constituent must fail closed. | A later constituent cannot extend an earlier expiry. |
| Revocation | `ABSENT` | None on the outbound provider lane | Future admission transition | No consumed revocation fact or activation revocation producer exists. Revocation observed after effect-start cannot erase possible provider effect. | Status vocabulary or terminal corridor disposition is not revocation authority. |
| Replay before effect-start | `EXISTS_FRAGMENTED` | Replay fingerprints, immutable records and claim services | Future exact executor | Same input can converge only before authority consumption; divergence must refuse. Missing complete authority prevents a truthful final rule today. | Convergent record replay does not permit provider replay. |
| Replay after effect-start | `EXISTS_CANONICALLY` | Effect-start journal, invocation admission and checkpoints | Recovery and reconstruction readers | `UNKNOWN_REPLAY_PROHIBITED` remains mandatory when provider outcome may be unknown. | Absence of a response, a fresh process or renewed credential availability grants no retry. |
| Crash recovery before atomic admission | `ABSENT` | Future executor admission transition | Future recovery coordinator | Correct posture is no credential resolution and no I/O; exact unused authority may remain available only if no winner exists. | An orphaned process-local capability cannot decide availability. |
| Crash after admission but before credential resolution | `EXISTS_FRAGMENTED` | Existing durable admission/checkpoint machinery | Read-only recovery | A future consumed authority plus effect-start winner must make automatic reinvocation prohibited even if the credential was never resolved. | “Credential probably unused” is not retry evidence. |
| Crash after credential resolution or callback start | `EXISTS_CANONICALLY` | Attempt/callback checkpoints and effect-start journal | Read-only forward recovery | Provider outcome may be unknown; preserve `UNKNOWN_REPLAY_PROHIBITED`. | Process restart, idempotency key or missing response does not authorize retry. |
| Read-only reconstruction | `EXISTS_CANONICALLY` | Existing receipt and governed-result reconstruction services | Audit and recovery readers | Rebuilds evidence only without provider access or reinvocation. | Reconstruction grants no authority and may reconstruct neither credential nor capability. |
| Credential/capability reconstruction | `ABSENT` | None | None | Correctly absent. The redesigned same-process posture removes the need for cross-process capability reconstruction. | Durable metadata, digests and logs may not manufacture credential access or authority. |
| Secret exclusion | `EXISTS_FRAGMENTED` | Existing durable corridor records use opaque IDs and digests; response capture rejects observed authentication material | Validators and reconstruction | Clear credential reference remains broker-local and secret remains process-local, but the future boundary still needs explicit log, exception, record and reconstruction exclusions. | A digest is evidence of equality only; it is neither a safe secret surrogate nor authority. |
| Provider outcome truth | `EXISTS_FRAGMENTED` | Local callback and response-envelope capture | Audit/recovery readers | Local effect-start truth is canonical; remote outcome is accepted observation, not cryptographic provider authorship. Interrupted outcome stays unknown. | HTTPS, callback lineage and a local envelope do not prove remote authorship. |
| Threat-model alignment | `EXISTS_CANONICALLY` | Runtime-principal threat-model document | All future boundary work | Claims are limited to canonical integrity and one-root locking against accidental/non-recomputed mutation. | No hostile-writer non-forgeability, signature authorship, multi-host consensus or split-brain resistance is claimed. |
| Retired live command | `EXISTS_CANONICALLY` | `AgentMailEmailSendCommand` refusal path | Operator receives refusal | Remains inert through this preparation. | It may not assemble, issue, activate, consume, resolve, invoke or migrate anything. |

## Credential possession versus authority

The corrected boundary keeps four statements non-interchangeable:

1. The deployment may possess a provider credential.
2. A durable record may authorize one exact provider operation.
3. A process may be the exact competent executor of that authority.
4. A process-local object may gate access to the stationary credential during that admitted operation.

Only the conjunction of an intact, current and atomically consumed durable authority; the exact
active executor principal; the exact activated binding; and the exact request permits entry into
credential resolution. Credential possession alone is inert. A `CredentialCapability` object is
not the durable permission and need not survive process death.

## Candidate boundary postures

| Posture | Preparation classification | Required trust expansion | Decision |
| --- | --- | --- | --- |
| Same-process governed executor | `COHERENT_SMALLEST_CANDIDATE` | None beyond the declared one authoritative root and trusted code/process boundary; exact principal and admission remain to be defined | Carry forward as the smallest candidate, not selected or authorized. |
| Local credential-owning broker | `COHERENT_IF_THREAT_MODEL_EXPANDS` | Authenticated local IPC, broker principal, request integrity, broker availability and broker-side atomic consumption | Defer unless separate-process isolation is required. |
| External custodian / dynamic-secret platform | `COHERENT_EXTERNAL_BOUNDARY` | External identity, lease, revocation, availability, audit and failure semantics | Defer until deployment or hostile-process threats justify relocated custody. |
| Permanent refusal | `ALWAYS_VALID` | None | Preserve if the candidate boundary cannot prove exact principal, atomic ordering or secret exclusion. |

A local broker or external custodian becomes justified if provider credentials must be isolated from
the application process, independently revoked, used across hosts, protected from a compromised
application principal, or made available under a separately audited custody service. Those threats
are not solved by the current one-root filesystem model and are not silently claimed here.

## Crash and replay matrix

| Last durable fact | Credential posture | Execution posture |
| --- | --- | --- |
| Inactive binding only | Never resolve | No activation or authority exists. |
| Complete future authority issued, no atomic winner | Never resolve | Exact authority may remain available subject to expiry/revocation; reconstruction is read only. |
| Atomic winner/effect-start absent after process loss | Never infer consumption | Treat as not started only if the authoritative root proves no winner. |
| Atomic authority winner and effect-start committed | Resolution may or may not have occurred | `UNKNOWN_REPLAY_PROHIBITED`; no automatic reinvocation. |
| Credential-attempt checkpoint | Secret must remain unrecorded | `UNKNOWN_REPLAY_PROHIBITED`. |
| Callback-start checkpoint, no accepted response | Secret must remain unrecorded | Provider may have acted; no retry. |
| Accepted response envelope | No credential reconstruction | Reconstruct accepted evidence forward; never reinvoke to recreate it. |
| Expired or revoked before atomic winner | Never resolve | Refuse permanently for that authority. |
| Competing same-root processes | Only the durable winner may enter the future boundary | Losers refuse without credential resolution or I/O. |

## Explicit non-authorities

None of the following is provider execution authority: credential possession; an environment
variable; a credential reference or digest; a `CredentialCapability` object or metadata; capability
eligibility; provider selection; an inactive binding; `BOUND_ACTIVE` vocabulary; an execution
claim; an effect-start journal; provider-invocation admission; an idempotency key; HTTPS; a callback
checkpoint; a response envelope; an accepted historical receipt; a corridor disposition; a
disposition principal or caller authority; Delegate cognition activation; read-only reconstruction;
same-input replay; or this preparation inventory.

## Smallest proposed campaign sequence

No step is authorized merely because it appears here.

1. Define separately versioned, authority-empty boundary, executor-principal, execution-authority
   and single-operation binding-activation contracts with explicit non-authorities.
2. Define the competent issuer and exact executor; bind tool authority, effect authorization,
   binding, request, destination, payload digest, assurance profile, common expiry and principal.
3. Implement one same-root atomic admission that consumes exact execution authority and commits
   effect-start before credential resolution or the first outbound byte.
4. Resolve the stationary credential and invoke the provider only inside the winning same-process
   executor; keep any capability object process-local and non-durable.
5. Prove crash cuts, replay, contention, expiry, revocation, reconstruction and secret exclusion.
6. Resume Provider Execution Assurance against the redesigned boundary.
7. Perform a terminal audit before any live-command adoption.

## Batch 1 gate

Preparation finds the same-process boundary coherent enough to consider Batch 1: contract definition
only for the authority-empty boundary, exact executor principal, durable execution authority and
single-operation provider-binding activation. Batch 1 is not begun or authorized by this result.
Permanent refusal remains valid if later work cannot preserve the stated ordering or threat model.

## Preserved perimeter

No runtime contract was defined and runtime behavior is unchanged. No principal or binding was
activated; no authority was issued or consumed; no credential or capability was issued, transferred,
resolved, persisted, reconstructed or otherwise handled; no provider was invoked; no external I/O
occurred; no live command was migrated; and Iron Gate and Lazaretto remained closed.
