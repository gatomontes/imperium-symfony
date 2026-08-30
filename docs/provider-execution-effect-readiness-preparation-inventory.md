# Provider Execution Effect Readiness — Preparation Batch 0 inventory

## Result

`PREPARATION_BATCH_0_COMPLETE_EFFECT_GATES_SEPARABLE_ASSURANCE_FIRST`

The corrected v2 pre-provider corridor is coherent, but provider effect is not
ready. Four stop conditions remain independently authoritative: the executor
principal is `ATTESTED_INERT`; the implementation binding is
`BOUND_INACTIVE`; no live-call contract joins the v2 winner to an adapter; and
provider assurance is fragmented or absent.

They may not be collapsed into one activation-and-call action. Provider
contract evidence must be admitted first as evidence, the executor principal
and operation-scoped binding must be activated through separate authorities,
and an authority-empty live-call contract must be proved before any sterile
provider observation. Live-consumer adoption remains a later campaign.

Preparation changed no runtime contract or behavior. It activated nothing,
admitted no provider fact, issued or consumed no authority, handled no
credential, invoked no provider, performed no external I/O, and kept Iron Gate
and Lazaretto closed.

## Stop-condition inventory

| Requirement | Classification | Producer / consumer | Crash and trust posture | Non-authorities |
| --- | --- | --- | --- | --- |
| Corrected combined v2 winner | `EXISTS_CANONICALLY` | Combined admission service / v2 stationary resolver | One activation-keyed winner on one trusted root; exact replay is read only | Winner proves pre-provider admission, not permission to call |
| Executor-principal attestation | `EXISTS_CANONICALLY` | Inert issuance service / admission validators | Durable `ATTESTED_INERT` evidence survives process loss | Attestation, class identity, OS process and same-process location are not activation |
| Executor-principal activation lifecycle | `ABSENT` | No competent producer / future live executor | Must be separately authorized, expiring, revocable and reconstructible before effect admission | Existing Imperator attribution and provider-execution authority cannot activate the principal |
| Immutable provider binding | `EXISTS_CANONICALLY` | Binding service / lineage validators | Exact `BOUND_INACTIVE` provider, adapter, credential family, encoder, decoder, destination and assurance identity | Selection and binding identity are not operation activation |
| Single-operation activation evidence | `EXISTS_CANONICALLY` | Activation issuance / combined winner | Exact activation is consumed with authority under the shared activation lock | It does not mutate or globally activate the underlying `BOUND_INACTIVE` binding |
| Live-capable binding posture | `EXISTS_FRAGMENTED` | Single-operation evidence exists; no live-call consumer exists | The operation activation is safe pre-provider, but no contract establishes that it is sufficient for first-byte execution | `ACTIVATED_UNCONSUMED` vocabulary and its combined winner do not themselves authorize I/O |
| Live-call runtime contract | `ABSENT` | None / future same-process executor | Must join only an already-winning v2 admission to exact encoder, adapter, request and outcome handling; contract existence grants no authority | Callback, transport, command and provider SDK are not the missing contract |
| AgentMail provider-contract evidence | `EXISTS_FRAGMENTED` | Official documentation and repository assessment / no canonical consumer | Exact direct-send semantics are cited but not immutable admitted evidence | Mutable documentation and an observation date are evidence inputs, not provider authority |
| Organization-wide idempotency registration | `ABSENT` | None / future first-byte gate | Must refuse key collision before I/O across exact organization, endpoint, inbox and payload identity | A local key inside one authority does not prove organization-wide uniqueness |
| In-progress duplicate semantics | `ABSENT` | Provider does not document them / recovery | Unknown; no automatic retry | Completed-duplicate behavior cannot be extended by analogy |
| Query-before-retry | `ABSENT` | No admitted provider lookup / recovery | Interrupted outcome cannot be resolved by lookup | Process restart and credential availability grant no retry |
| Completion-anchored retention | `EXISTS_FRAGMENTED` | Provider declares 24 hours after completion / no authoritative local clock | Local effect-start cannot establish provider completion | A locally calculated deadline is not provider retention truth |
| Response correlation | `EXISTS_FRAGMENTED` | Local key/request lineage and returned message/thread IDs / future assurance admission | Authenticated channel trust only | Callback lineage does not prove remote authorship |
| Remote cryptographic authorship | `ABSENT` | None | Channel trust is the declared ceiling unless provider evidence changes | HTTPS and unkeyed local digests are not provider signatures |
| Post-effect replay | `EXISTS_CANONICALLY` | Effect-start truth / recovery readers | `UNKNOWN_REPLAY_PROHIBITED` after possible effect | Idempotency documentation does not create retry authority |
| Secret exclusion | `EXISTS_CANONICALLY` pre-provider; `EXISTS_FRAGMENTED` live | V2 proof excludes secret/reference/capability / unmigrated transport and exception surfaces | Live logging and exception proof remains absent | Secret possession and environment names are not authority |
| Live AgentMail command and transport | `DEFERRED_BOUNDARY` | Existing retired/unmigrated surfaces | Must remain inert until a separate adoption campaign | Their ability to send does not make them governed consumers |
| Distributed and hostile-writer guarantees | `DEFERRED_BOUNDARY` | None | Outside `SINGLE_AUTHORITATIVE_ROOT_ONLY` and `TRUSTED_WRITER_CANONICAL_INTEGRITY` | Local locks and SHA-256 do not prove consensus or non-forgeability |

## Authority and ordering matrix

| Order | Gate | Competent authority posture | Last lawful refusal point |
| --- | --- | --- | --- |
| 1 | Admit exact provider-contract evidence | Evidence-admission authority is absent and must not be execution authority | Refuse if source, version, operation, scope, unknowns or retention basis is incomplete |
| 2 | Activate exact executor-principal generation | Competent lifecycle authority and consumer are absent | Refuse while principal remains inert, expired, revoked or generation-conflicted |
| 3 | Establish operation-scoped live binding sufficiency | Separate binding-activation authority must bind the admitted assurance profile and v2 operation | Refuse while source binding remains merely selected or operation activation lacks live semantics |
| 4 | Define and validate authority-empty live-call contract | Contract producer may define shape only; the v2 winner remains the sole execution admission | Refuse on any provider, adapter, request, destination, encoder, decoder or assurance mismatch |
| 5 | Commit first-byte gate | Exact active principal, live-capable operation binding, admitted assurance and combined v2 winner must all validate | This is the final pre-I/O refusal point; credential resolution alone cannot cross it |
| 6 | Sterile provider conformance, if separately authorized | A sterile observation authority must be distinct from adoption and retry authority | Refuse any sensitive destination, unbounded duplicate, missing containment or ambiguous outcome |
| 7 | Live-consumer adoption | Separate future campaign only | No adoption authority exists in this campaign |

The first-byte gate must not consume a second provider-execution authority or
create a second effect-start fact. It may only continue the exact combined v2
winner after all later evidence and lifecycle gates validate.

## Crash, replay and unknown-outcome matrix

| Last durable fact | Lawful recovery |
| --- | --- |
| Provider evidence not admitted | Refuse activation and live-call consideration |
| Evidence admitted; principal inert | Reconstruct evidence only; do not activate by replay |
| Principal activation authority consumed, activation record absent | Recover only under an explicitly proved consume-to-commit transition |
| Active principal; binding not live-capable | Refuse before credential resolution and I/O |
| V2 winner exists; later live gates incomplete | Preserve winner as pre-provider evidence; do not infer permission |
| First-byte gate absent | No outbound effect is authorized |
| First-byte/effect may have started; no response | `UNKNOWN_REPLAY_PROHIBITED`; no reinvocation |
| Accepted provider response exists | Reconstruct forward from admitted evidence without reinvocation |
| Expired or revoked constituent before first byte | Refuse permanently for that exact operation |
| Competing same-root callers | Only the activation-keyed combined winner may continue; every loser refuses |
| Corrupt or conflicted evidence | Refuse; reconstruction creates neither authority nor credential access |

## Candidate boundary sequence

The smallest lawful continuation is deliberately divided:

1. **Provider Assurance Evidence Admission** — authority-empty contracts,
   validators and immutable admitted evidence for AgentMail direct send,
   including explicit unknowns. No provider call.
2. **Provider Effect Principal and Binding Activation** — separately authorized
   lifecycle and operation-scoped activation, still no provider call.
3. **Provider Live-Call Contract** — authority-empty exact join from the
   combined v2 winner to adapter invocation and truthful outcome states.
4. **Sterile Provider Conformance** — only if admitted evidence cannot resolve
   required provider behavior; isolated non-sensitive observation, never live
   adoption.
5. **Adversarial terminal audit** — crash, contention, expiry, revocation,
   unknown outcome, correlation and secret exclusion.
6. **Live-consumer adoption** — separate future campaign, if and only if the
   preceding campaigns close without refusal.

## Batch 1 gate

Only Batch 1 may next be considered: authority-empty Provider Assurance
Evidence Admission contracts for one exact AgentMail direct-send operation and
its evidence source, scope, request-equivalence fields, idempotency syntax,
completed-duplicate behavior, completion-anchored retention and explicit
unknowns.

Batch 1 may not admit evidence into live runtime authority, activate a principal
or binding, define a live-call runtime contract, issue or consume execution
authority, handle a credential, invoke AgentMail or any provider, perform
external I/O, authorize retry, migrate a command or transport, or open Iron Gate
or Lazaretto.
