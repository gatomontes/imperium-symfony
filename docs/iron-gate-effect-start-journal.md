# Iron Gate deterministic effect-start journal

## Status

`BATCH_7_EFFECT_START_UNKNOWN_OUTCOME_BOUNDARY_DURABLE`

Batch 7 adds the durable checkpoint that must exist before the deterministic lane may approach
external I/O. One intact execution claim opens one immutable effect-start journal under a
claim-scoped lock.

The journal binds the claim and authorization digests, execution and replay identities, exact
credential-capability ID and credential-reference digest, provider idempotency key, exact request
fingerprint, provider contract, start time and expiry.

## Conservative effect rule

Opening the journal records the point after which external I/O may have begun. Therefore its first
truthful unresolved state is:

- `checkpoint=EFFECT_STARTED`;
- `external_io_may_have_started=true`;
- `outcome=UNKNOWN_REPLAY_PROHIBITED`; and
- `automatic_replay_permitted=false`.

This deliberately permits a safe false positive: if the process dies after journal persistence but
before provider invocation, automatic replay still remains prohibited. It never permits the unsafe
false negative in which a provider may have accepted the effect while the durable state says no
attempt occurred.

Exact replay returns the first journal. No later timestamp creates another attempt. Claim tamper,
expiry, non-idempotent strategy or a second journal identity fails closed.

## Credential and provider boundary

The journal binds one required credential use but does not perform it. It explicitly seals
`consumed_by_journal=false`, `credential_resolved=false` and `provider_invoked_by_transition=false`.
No credential reference or secret material is persisted beyond the existing digest.

No command, broker or transport was migrated. No provider header was emitted, network call made,
provider outcome claimed, raw receipt created or Lazaretto admission performed. Sortie remains
separate.

## Smallest safe continuation

Only a separately authorized Batch 8 may put one claim-bound credential broker and AgentMail
idempotency-header adapter behind this journal. It must prove that no provider callback is reachable
without the exact journal and that the exact key and request fingerprint reach the adapter. Live
external I/O, raw receipt persistence and Lazaretto admission remain closed.

