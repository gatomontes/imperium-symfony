# Canonical Native Effect Reconciliation Shared-Exclusion — mutation matrix v1

| Mutation | Accepted writer / storage | Excluded by `NativeState::locked()`? | Current reconciliation edge | Classification |
| --- | --- | --- | --- | --- |
| Operator Root `revoked`/identity replacement | no runtime writer; current untimestamped `transition-trust/identity.json` is externally provisioned | cooperative writers would share trust-directory lock, but hostile/external writer is not proved | issuer/resolve reads only | `DEFERRED_BOUNDARY` |
| Native principal constitution | `NativePrincipal::constitute()` -> `NativeState::locked()` -> `principals` event | yes | reconciliation source reads outside it | `ORDERING_HAZARD` |
| Native principal activation | `NativePrincipal::lifecycle()` -> `activations` event | yes | sequential refusal exists | `EXISTS_SEQUENTIAL_ONLY` |
| Native principal revocation | `NativePrincipal::lifecycle()` -> `revocations` event | yes | CU01 commits between resolve/use and stale claim publishes | `DISJOINT_LOCK_RACE_REPRODUCED` |
| Source generation advance | `FutureInstanceImperatorPrincipalConstitutionService` -> runtime principal-version immutable store | yes, by exact directory lock, when currentness is inside `NativeState::locked()` | current issuer/claim read outside shared exclusion | `ORDERING_HAZARD` |
| Source `SUSPEND` | `ImperatorPrincipalProvenanceFixtureStore::putLifecycleDisposition()` | yes, by exact lifecycle-directory lock | CU01 stale claim publishes | `DISJOINT_LOCK_RACE_REPRODUCED` |
| Source `SUPERSEDE` | same accepted lifecycle writer | yes | only sequential reconstruction/refusal evidence | `EXISTS_SEQUENTIAL_ONLY` |
| Source `REVOKE` | same | yes | only sequential reconstruction/refusal evidence | `EXISTS_SEQUENTIAL_ONLY` |
| Source `EXPIRE` | same | yes | ordinary expiry is partly transitively bounded; lifecycle event interleaving is unproved | `EXISTS_SEQUENTIAL_ONLY` |
| Source `RETIRE` | same | yes | only sequential reconstruction/refusal evidence | `EXISTS_SEQUENTIAL_ONLY` |
| v3 lifecycle/migration-required record | lifecycle immutable store; v3 reader explicitly refuses migration | yes for cooperative store writer | no deterministic interleaving harness on accepted base | `EXISTS_SEQUENTIAL_ONLY` |

DP01 applies to every row because source resolution precedes the issuer's
target lock, but exact stale decision publication is not operational on this
base. IU01 is not operational. CU01 directly proves native revocation and source
suspension; extrapolating that execution to every other row would be evidence
inflation, so the remaining rows retain their narrower classifications.
