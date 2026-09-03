# Canonical Native Effect Corridor Activation — Batch 1 contracts v1

`BATCH_1_CANONICAL_NATIVE_EFFECT_CONTRACTS_COMPLETE_NO_PRODUCER_NO_CONSUMER`

Batch 1 adds three declarative contracts: the exact Imperator-issued one-effect
authority, the La Cortine atomic authority-consumption/effect-start winner, and
provider-neutral response/receipt vocabulary. Contract existence grants no
authority and creates no issuer, consumer, credential path, callback or provider
I/O.

The effect authority binds the exact native root, transition and pre-effect
receipt; successor and selected v3 admission; executor principal/boundary;
provider binding, adapter and assurance; operation, destination, payload and
request fingerprint; stationary credential family; expected return contract;
idempotency-key digest; holder, issuer, validity, revocation/cancellation and
single-use state.

The admission contract requires one aggregate to record authority consumption
and `EFFECT_STARTED_UNKNOWN_REPLAY_PROHIBITED`. Automatic replay and continuing
authority are always false. A result may be accepted, rejected or unknown; no
outcome grants retry.

No production service wiring or runtime state changes in this batch. Batch 2 is
not authorized by this document alone.
