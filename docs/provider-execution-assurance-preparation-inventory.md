# Provider Execution Assurance — Preparation Batch 0 inventory

## Result

`PREPARATION_BATCH_0_COMPLETE`

The exact first operation is AgentMail direct `email.send` at
`POST /v0/inboxes/{inbox_id}/messages/send`. Official provider documentation inspected on
2026-08-28 states that the `Idempotency-Key` is scoped to one organization, binds the sending inbox,
endpoint and message content, returns the original `message_id` and `thread_id` for an exact retry,
returns `409 Conflict` for changed input, rejects an empty key, and expires 24 hours after the send
completes.

That evidence makes provider-side deduplication credible for this exact operation. It does not make
an interrupted request immediately replayable. The documentation exposes no query by idempotency
key and does not define an authoritative upper bound for a request still in progress. Imperium must
therefore preserve `UNKNOWN_REPLAY_PROHIBITED` until an original response is observed or a separately
proved provider lookup resolves the outcome. Expiry measured after completion must not be guessed
from the local send-start time.

Preparation performed no provider call, credential resolution, external I/O or runtime mutation.

## Provider-contract evidence

| Evidence | Admitted fact | Limit |
| --- | --- | --- |
| `https://docs.agentmail.to/idempotency` | Send operations accept a 1–256 character `Idempotency-Key`; first use sends and records the result; exact retry returns the original message identity; changed inbox, endpoint or content returns `409`; the key is organization-scoped and expires 24 hours after completion. | Repository prose cites a mutable remote page. No immutable, versioned provider-contract evidence record exists yet. |
| `https://docs.agentmail.to/knowledge-base/preventing-duplicate-sends` | Direct sends use the header rather than `client_id`; one key must represent one logical send. | Guidance does not define query-before-retry or an in-progress timeout bound. |
| `https://www.agentmail.to/docs/api-reference/inboxes/messages/send` | The exact operation and successful `message_id` / `thread_id` response shape exist. | The response is not remotely signed and no idempotency-key lookup operation is specified. |
| `docs/iron-gate-agentmail-idempotent-send-assessment.md` | The repository already records the provider guarantee and selects direct send as the smallest candidate. | It is an assessment, not a canonical provider-contract admission consumed by the corridor. |

Provider documentation is declarative contract evidence, not observed conformance evidence. No
claim is made about undocumented implementation behavior.

## Requirement classification and exact consumer posture

| Requirement | Classification | Exact consumer posture | Evidence and stop condition |
| --- | --- | --- | --- |
| Exact provider operation and endpoint | `EXISTS_CANONICALLY` | `AGENTMAIL_DIRECT_SEND_ONLY` | Authorization, adapter and return contract restrict the lane to `email.send` and the exact HTTPS AgentMail inbox-send endpoint. Other send operations are not adopted by analogy. |
| Local logical request identity | `EXISTS_CANONICALLY` | `EXACT_LOCAL_REQUEST_BOUND` | Authorization, claim, journal, admission and envelope bind operation, destination, payload digest, request fingerprint and provider key. |
| Provider-recognized request identity | `EXISTS_FRAGMENTED` | `PROVIDER_CONTRACT_ADMISSION_REQUIRED` | The adapter sends the exact `Idempotency-Key`, but provider semantics exist only in cited prose and are not a separately versioned admitted contract. |
| Idempotency collision domain | `EXISTS_FRAGMENTED` | `ORGANIZATION_ENDPOINT_INBOX_CONTENT_SCOPE` | Official documentation names organization scope and rejects changed inbox, endpoint or content. Imperium does not durably register keys across all local authorizations or prove organization identity. |
| Key syntax | `EXISTS_FRAGMENTED` | `STRICT_PROVIDER_KEY_VALIDATION_REQUIRED` | Provider permits 1–256 characters from `A-Z a-z 0-9 - . _ ~`; current adapter checks only non-empty text. |
| Retention interval | `EXISTS_FRAGMENTED` | `PROVIDER_COMPLETION_ANCHORED_RETENTION` | Provider says 24 hours after completion. Current authorization carries a local `provider_key_expires_at`, but no provider completion time proves the start of that interval. |
| Exact duplicate after completion | `EXISTS_FRAGMENTED` | `ORIGINAL_RECEIPT_EXPECTED` | Provider declares no second send and return of the original `message_id` and `thread_id`; no sterile conformance observation is admitted. |
| Changed request under the same key | `EXISTS_FRAGMENTED` | `PROVIDER_409_EXPECTED_LOCAL_COLLISION_REFUSED` | Provider declares `409`; local request binding exists, but no durable global key registry refuses cross-authorization collision before I/O. |
| Duplicate while first request is in progress | `ABSENT` | `IN_FLIGHT_REPLAY_PROHIBITED` | Provider documentation does not define wait, conflict or duplicate behavior before completion. |
| Durable pre-I/O registration | `EXISTS_FRAGMENTED` | `SINGLE_ROOT_JOURNAL_BOUND` | Claim and effect-start journal persist the key and fingerprint before callback admission. They do not provide a provider-contract registry or organization-wide collision index. |
| Local concurrency | `EXISTS_CANONICALLY` | `ONE_AUTHORITATIVE_ROOT_SERIALIZED` | Atomic transitions and immutable winner records serialize the bounded corridor on one filesystem root. |
| Distributed concurrency | `DEFERRED_BOUNDARY` | `MULTI_HOST_UNPROVED` | No split-brain, distributed lock or cross-root collision guarantee is claimed. |
| Effect-start truth | `EXISTS_CANONICALLY` | `JOURNAL_BEFORE_CREDENTIAL_AND_CALLBACK` | The journal is durable before credential resolution; admission, credential-attempt and callback-start checkpoints remain distinct. |
| Timeout/disconnect outcome | `EXISTS_CANONICALLY` | `UNKNOWN_REPLAY_PROHIBITED` | Once effect may have started, absence of a response is never rewritten as success or failure. |
| Query before retry | `ABSENT` | `NO_QUERY_NO_RETRY` | No provider operation that resolves a send by idempotency key is documented or implemented. |
| Replay after an observed accepted duplicate | `ABSENT` | `NO_LIVE_RETRY_ADOPTION` | The unused corridor refuses a second callback admission. Any recovery retry would require a new, exact transition that consumes the same durable registration and proves the provider window remains open. |
| Response correlation | `EXISTS_FRAGMENTED` | `KEY_REQUEST_AND_MESSAGE_ID_CORRELATED` | Callback envelope binds key/fingerprint and accepted content yields `message_id`/`thread_id`; the provider response does not echo a separately authenticated operation ID or the idempotency key. |
| Remote response authorship | `ABSENT` | `AUTHENTICATED_CHANNEL_TRUST_ONLY` | Callback lineage and HTTPS transport are not remote cryptographic authorship. No response signature or provider lookup proof is admitted. |
| Raw evidence and accepted receipt recovery | `EXISTS_CANONICALLY` | `READ_ONLY_FORWARD_RECOVERY` | Envelope, raw result, Lazaretto binding and reconstruction preserve an observed accepted response without reinvocation. |
| Rejected and unknown recovery | `EXISTS_CANONICALLY` | `UNADMITTED_TRUTHFUL_OUTCOME` | Rejected evidence is retained; unknown has no fabricated receipt and cannot automatically replay. |
| Tamper detection | `EXISTS_CANONICALLY` | `TRUSTED_WRITER_FAIL_CLOSED` | Canonical digests reject unsealed mutation under the trusted-writer model. |
| Hostile-writer non-forgeability | `DEFERRED_BOUNDARY` | `HOSTILE_WRITER_UNPROVED` | Unkeyed digests do not prove non-forgeability against a writer able to replace records and recompute hashes. |
| Secret exclusion | `EXISTS_CANONICALLY` | `OPAQUE_CREDENTIAL_ONLY` | Durable records contain credential references/digests; tests prove callback secrets are not persisted. Live command exceptions and transport logs remain unmigrated. |
| Existing live command and transport | `DEFERRED_BOUNDARY` | `UNMIGRATED_LIVE_CONSUMER` | `AgentMailEmailTransport` does not send the governed key and collapses transport failure into an exception. It is not authorized to consume this corridor. |

## Crash and recovery matrix

| Last durable fact | Provider fact available | Posture |
| --- | --- | --- |
| Claim only | No effect-start journal | No provider invocation; a separately authorized transition may continue from the claim. |
| Effect-start journal, no admission | Provider execution is not provably absent | `UNKNOWN_REPLAY_PROHIBITED`; no retry. |
| Admission or credential attempt, no callback start | Provider callback is not recorded as started, but the journal deliberately refuses absence fiction | `UNKNOWN_REPLAY_PROHIBITED`; no retry. |
| Callback start, no response envelope | Provider may have accepted the request | `UNKNOWN_REPLAY_PROHIBITED`; query is unavailable, so no retry. |
| Response envelope/raw rejected result | Exact rejection evidence exists | Preserve rejection; do not retry by implication. |
| Accepted raw result, no Lazaretto binding | Provider identity and bytes are durable | Forward-admit and reconstruct without provider reinvocation. |
| Accepted receipt binding | Complete local evidence chain exists | Reconstruct read only; never resend to recreate evidence. |

## Smallest safe proposed sequence

No step is authorized merely because it appears here.

1. **Provider-contract evidence admission** — define a separately versioned AgentMail direct-send
   assurance contract and immutable evidence record containing exact source, observation date,
   operation, organization collision scope, request equivalence fields, key syntax, duplicate
   results, completion-anchored retention and explicit unknowns.
2. **Durable provider-key registration** — add a single-root registry that refuses key collisions
   across authorizations and binds organization, endpoint, inbox, content fingerprint and contract
   version before effect start.
3. **Exact request/response correlation** — define accepted duplicate semantics and prove that the
   same durable key/request tuple yields the original provider identity without allowing caller-
   supplied provider truth.
4. **Unknown-outcome rule** — preserve permanent replay prohibition unless a separately admitted
   provider lookup or a proved still-valid retry transition resolves the outcome. Completion-based
   retention may not be inferred from local time.
5. **Sterile provider conformance harness** — only if separately authorized, observe first send,
   exact duplicate, changed-body collision, empty/invalid key, timeout/disconnect and retention-edge
   behavior with non-sensitive sterile content. Observation must remain evidence, not authority.
6. **Adversarial proof** — test collision, concurrency, partial write, forged response, timeout,
   tamper and secret-exclusion cases without opening the live command.
7. **Terminal audit** — decide whether direct send is safely adoptable, adoptable only with replay
   prohibition, or refused. Live-consumer migration remains a separate campaign.

## Superseded continuation boundary

The initial proposal to proceed directly to provider-contract evidence admission is superseded before
Batch 1 begins. AgentMail-specific facts cross tool authority, credential-bound invocation, decoding
and Lazaretto admission. Provider Execution Assurance is paused; the next separately selected
campaign is Governed Tool and Provider Separation, whose Preparation Batch 0 is recorded in
`docs/governed-tool-provider-separation-preparation-inventory.md`.
