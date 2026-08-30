# Provider Activation-Consumption Remediation — Batch 3 revocation authority contracts

## Result

BATCH_3_REVOCATION_AUTHORITY_ISSUANCE_AND_CONSUMPTION_CONTRACTS_DEFINED

Batch 3 supplies the authority layer deliberately refused in Batch 2. It defines:

1. an exact single-use provider-binding activation revocation authority;
2. its separately authorized decision and issuance contract; and
3. an exact consumption record binding that authority to one revocation fact.

No authority or revocation fact is produced.

## Exact scope

The authority binds one immutable activation, execution boundary, executor principal and provider
binding. It carries an allowlist of revocation reason codes and a bounded effective/expiry window
with its own revocation reference.

The issuance decision must bind the same four references in its basis. Its issuance permission is
single-use, exercisable, expiring, non-continuing and target-digest bound.

The revocation-authority consumption binds:

- the exact revocation authority reference;
- the exact provider-binding activation reference;
- the exact revocation-fact reference;
- single-use consumed truth;
- non-continuing authority;
- the activation-keyed winner scope; and
- consumption time.

## Ordering with admission

A future lawful revocation producer and the v2 combined-admission producer must use the same
activation-keyed transition. If revocation wins, admission observes the fact and refuses. If combined
admission already won, revocation may record future governance state but cannot erase committed
effect-start or imply provider retry.

No mutable activation status is introduced.

## Non-authorities

The issuance contract does not itself revoke anything. The authority cannot revoke without exact
consumption. The consumption contract does not consume the provider execution activation or durable
execution authority, activate a principal or binding, handle credentials, invoke a provider,
perform external I/O, authorize retry, or open Iron Gate or Lazaretto.

## Batch 4 gate

Only Batch 4 may next be considered: lawfully issue the exact revocation authority and atomically
produce its append-only revocation fact plus authority-consumption evidence under the shared
activation lock. Stationary-resolution migration remains closed.
