# Provider Binding Activation and Capability Custody campaign terminal refusal

## Result

Campaign terminal refusal is sealed because cross-process capability custody is unprovable with the
selected environment-backed broker. The issuer recognizes only its exact process-local capability
object. Another broker refuses it as unissued. Persisting metadata or constructing another object
would reconstruct authority rather than transfer the already-issued capability.

Batch 4 records this refusal without creating custody or delivery, issuing or reconstructing a
capability, persisting a credential reference or secret, resolving credentials, invoking a provider
or performing external I/O. Provider Execution Assurance remains paused. Iron Gate and Lazaretto
remain closed.

## Closed continuation

No Batch 5 is authorized. Atomic execution admission, live-command migration and the remaining
proof sequence may not proceed on this credential substrate. No component may reinterpret the
activation lease, capability metadata or refusal record as custody, delivery, credential-use,
provider-invocation, retry or execution authority. Components may not reconstruct a capability.

A future campaign must be selected separately if Imperator chooses a credential platform or
custodian capable of preserving exact already-issued capability identity across processes without
persisting credential references, secrets, serialized capability material or provider authentication
material.
