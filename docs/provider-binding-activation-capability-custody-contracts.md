# Provider Binding Activation and Capability Custody contracts

## Status

`BATCH_1_CONTRACTS_COMPLETE_NO_IMPLEMENTATION`

Five separately versioned contracts define the minimum lawful vocabulary without producing or
consuming any runtime record:

1. `ProviderBindingActivationAuthorityContract` — Imperator's exact, single-use authority for one
   execution; it selects nothing and performs no activation.
2. `SingleExecutionProviderBindingActivationContract` — La Cortine's immutable activation lease
   binding the source authority, tool authority, effect authorization, execution claim, inactive
   provider binding, assurance profile, destination policy and common expiry.
3. `OpaqueCapabilityCustodyContract` — Clavium's durable identity and custody posture for the exact
   already-issued capability, excluding the credential reference, secret, serialized capability and
   provider authentication material.
4. `OneTimeCapabilityDeliveryContract` — one exact runtime principal wins delivery under one
   generation, with delivery, acknowledgement, abandonment and pre-I/O consumption states.
5. `AtomicProviderExecutionAdmissionContract` — one-root admission requires activation, custody and
   delivery consumption in one committed transaction before resolution or external I/O.

## Separation rules

- Provider selection is not activation.
- Activation authority is not the activation lease.
- Capability identity is not the credential reference or secret.
- Custody is not issuance, delivery or credential resolution.
- Delivery is not consumption and grants no redelivery or retry.
- Atomic admission is not credential resolution, effect start or provider success.
- Reconstruction may read identities and states but may not recreate capability authority.
- Contract existence grants no authority and creates no runtime producer or consumer.

## Producer and consumer posture

| Contract | Exact producer posture | Exact consumers | Non-authority invariant |
| --- | --- | --- | --- |
| Activation authority | `imperator.exact-provider-binding-activation-decision` | La Cortine activation transition | Cannot select, activate, issue, deliver, resolve or start I/O. |
| Activation lease | `la-cortine.provider-binding-activation-transition` | Clavium custody intake, atomic admission, reconstruction | Cannot mutate binding, prove custody, resolve, start I/O or retry. |
| Capability custody | `clavium.exact-issued-capability-custody-intake` | One-time delivery, atomic admission, reconstruction | Cannot issue, reconstruct, reissue, deliver without claim, resolve or invoke. |
| One-time delivery | `clavium.atomic-capability-delivery-claim` | Acknowledgement, atomic admission, reconstruction | Cannot change winner, double-deliver, redeliver, resolve or retry. |
| Atomic admission | `la-cortine.atomic-activation-custody-consumption-transition` | Credential-resolution boundary, effect journal, reconstruction | Cannot issue, activate, reconstruct capability, resolve, start I/O or assert success. |

## Preserved perimeter

No producer, custodian, delivery mechanism or consumer is implemented. No existing service or
command changes. No binding is activated and no capability is issued, persisted, reconstructed,
transferred, consumed or resolved. No credential reference or secret is exposed. No provider is
invoked, no external I/O occurs, and Iron Gate and Lazaretto remain closed.
Provider Execution Assurance remains paused.
