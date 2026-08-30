# Provider Activation-Consumption Remediation — Batch 2 production

## Result

BATCH_2_ACTIVATION_KEYED_COMBINED_ADMISSION_PRODUCED_REVOCATION_WRITER_REFUSED

GovernedProviderExecutionCombinedAdmissionService now produces one immutable v2 admission keyed and
locked by the exact activation identity.

The record is the one combined winner for activation consumption, durable execution-authority
consumption and local effect-start. No separate activation-consumption record is written.

## Winner behavior

The producer resolves the exact activation referenced by durable authority before deriving the
winner. The admission ID derives from activation ID and activation digest. The atomic transition
scope is governed-provider-execution-admission:<activation-id>.

Inside that scope:

1. an existing exact activation-and-authority winner reconstructs;
2. an existing winner for a different authority refuses;
3. an existing deterministic revocation fact refuses;
4. fresh authority, boundary, principal, activation and binding lineage is validated;
5. both consumption statements and local effect-start are committed in one immutable record; and
6. credential resolution and provider effects remain false.

A single authority cannot legally name another activation. Every separate authority that names the
same activation reaches the same lock and winner ID.

## Revocation read boundary

The producer fails closed when an intact or tampered record exists at the deterministic activation
revocation identity. This establishes the read side of revocation arbitration.

Batch 2 deliberately does not implement a revocation writer. The Batch 1 revocation-fact contract
names source_revocation_authority but no contract defines that authority's exact scope, issuance,
expiry, single use or consumption. A writer built now would either trust caller-supplied metadata or
silently invent authority rules inside a service. Both would be self-authorizing.

This refusal narrows the next gate; it does not weaken admission's fail-closed read behavior.

## Preserved perimeter

The v1 producer and records remain unchanged. The v2 producer handles no credential or capability,
does not activate a principal or source binding, invokes no provider, performs no external I/O,
sends no outbound byte, authorizes no retry, migrates no command, opens neither Iron Gate nor
Lazaretto, and claims no provider outcome.

## Next gate

Only the next remediation batch may define the exact activation-revocation authority and its
issuance/consumption contract. Runtime revocation production and stationary-resolution migration
remain unauthorized until that contract exists.
