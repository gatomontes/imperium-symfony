# Provider Binding capability-custody feasibility refusal

## Status

`BATCH_4_CROSS_PROCESS_CUSTODY_REFUSED`

Clavium now records the mandatory feasibility decision before any custody or delivery producer can
exist. The exact capability is recognized by its issuing `EnvironmentCredentialBroker` instance
through PHP object identity. A distinct broker instance does not recognize that same object, and
the broker explicitly reports that it cannot support cross-process custody.

The refusal record binds the exact Batch 3 activation and the claim's non-secret capability
identity. It persists only the opaque capability ID, an identity digest, the existing credential
reference digest and issuer class. It does not persist the credential reference, secret, serialized
capability or provider authentication material.

Creating an `OpaqueCapabilityCustodyContract` or `OneTimeCapabilityDeliveryContract` record from
those digests would falsely convert metadata into possession. Batch 4 therefore seals
`REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` and creates neither custody nor delivery. Capability
issuance, reconstruction, resolution and external I/O remain absent.

## Terminal consequence

The campaign cannot lawfully proceed to atomic admission or command migration with the selected
environment-backed broker. A future separately selected campaign may replace the credential
platform or introduce a custodian whose issuer can attest and transfer the exact capability without
persisting secret material. Completion of that future work would not retroactively authorize this
route.
