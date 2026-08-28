# Iron Gate AgentMail idempotent-send reassessment

## Status

`BATCH_3_PROVIDER_IDEMPOTENCY_PROVED_NOT_ADOPTED`

This assessment supersedes the current provider-safety conclusion in
`docs/iron-gate-agentmail-provider-safety-assessment.md`; that Batch 2 document remains historical
evidence of the documentation available when it was written.

## Exact provider contract

Official AgentMail documentation checked on 2026-08-28 now states that `messages.send`, replies,
forwards and `drafts.send` accept an `Idempotency-Key` HTTP header:

- [Idempotent Requests](https://docs.agentmail.to/idempotency) states that the first request sends
  and records the result; an identical-key retry returns the original message and does not send a
  duplicate; a changed request returns `409 Conflict`; an empty key returns `400`; and the key
  expires 24 hours after completion.
- [Preventing duplicate sends](https://docs.agentmail.to/knowledge-base/preventing-duplicate-sends)
  repeats the direct-send rule and requires a unique key per logical send.
- [Send Message](https://www.agentmail.to/docs/api-reference/inboxes/messages/send) defines the
  exact endpoint and successful `message_id`/`thread_id` response.

For the exact AgentMail direct `email.send` operation, the Batch 1
`PROVIDER_IDEMPOTENCY_KEY` prerequisite is therefore `EXISTS_CANONICALLY` at the provider boundary.
The safer candidate is direct send with one stable key bound to the exact request fingerprint; the
draft workflow adds no necessary safety property for this first slice.

## Remaining Imperium gaps

| Requirement | Classification | Consumer posture |
| --- | --- | --- |
| Official duplicate-send semantics | `EXISTS_CANONICALLY` | `PROVIDER_PREREQUISITE_SATISFIED` |
| Stable key bound to one local authorization and request fingerprint | `ABSENT` | `NOT_ADOPTED` |
| Competent native source decision and issuer | `ABSENT` | `BLOCKED_NATIVE_ISSUER_AND_DURABLE_CLAIM` |
| Durable pre-I/O winner, consumption and provider journal | `ABSENT` | `BLOCKED_NATIVE_ISSUER_AND_DURABLE_CLAIM` |
| Transport propagation of the governed key | `ABSENT` | `NOT_ADOPTED` |
| Durable receipt binding and reconstruction | `ABSENT` | `NO_FORWARD_RECOVERY` |

Provider safety alone does not authorize a send. The current command's random identities cannot
produce a stable replay identity across process death, and the current transport does not carry a
governed `Idempotency-Key`. No retry posture changes until a durable claim binds the key, exact
request fingerprint, authorization consumption and provider-key expiry before I/O.

No provider call, credential resolution, authority transition, runtime mutation or external I/O
was performed for this assessment.
