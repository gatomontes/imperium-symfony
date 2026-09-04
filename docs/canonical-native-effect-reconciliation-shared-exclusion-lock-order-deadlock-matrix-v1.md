# Canonical Native Effect Reconciliation Shared-Exclusion — lock order/deadlock matrix v1

| From | To | Rule | Finding | Classification |
| --- | --- | --- | --- | --- |
| none | native shared exclusion | permitted first acquisition | outer native scope then sorted source/trust scopes | `SHARED_EXCLUSION_PROVED` |
| native shared exclusion | semantic issuance target | proposed forward order only | permits currentness and publication in one cut | `ORDERING_HAZARD` |
| native shared exclusion | semantic claim target | proposed forward order only | permits currentness and claim use in one cut | `ORDERING_HAZARD` |
| issuance target | native shared exclusion | prohibited | reverse path can deadlock a mutation holding shared then awaiting target | `ORDERING_HAZARD` |
| claim target | native shared exclusion | prohibited | current accepted correction cannot simply call `locked()` from inside `derive()` | `ORDERING_HAZARD` |
| native shared exclusion | same NativeState object | prohibited | `NIR_NESTED_LOCK` | `SHARED_EXCLUSION_PROVED` |
| TransitionStore domain lock | same store | prohibited | `EAT_NESTED_LOCK_REFUSED` | `SHARED_EXCLUSION_PROVED` |
| AtomicTransition scope | identical scope via another handle | prohibited by future contract | no reentrancy detector; may block | `ORDERING_HAZARD` |
| issuance target A | issuance target B | prohibit nesting; serialize only one semantic target | target-wide serialization is not mutation exclusion | `ORDERING_HAZARD` |
| shared/target lock | provider, credential, transport or external callback | prohibited | no external I/O under governed locks | `DEFERRED_BOUNDARY` |
| local `flock` | another host/distributed filesystem/hostile writer | no proof | cooperative single-host boundary only | `DEFERRED_BOUNDARY` |

Smallest later rule: expose one canonical shared-exclusion entrypoint capable of
covering currentness plus the downstream deterministic target operation without
reentering `NativeState`. Refactor callers to enter shared first and target
second. Do not patch by nesting shared exclusion beneath today's target locks.
