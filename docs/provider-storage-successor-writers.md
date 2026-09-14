# PPC4 writer, target, alias and lock-order inventory

This is the successor to the historical [PPC3 writer map](provider-native-tenure-writers.md), which remains unchanged as evidence of the previously open gap. Scope is cooperating current binaries and fixed filesystem topology. Arbitrary PHP execution, old binaries, administrator replacement and concurrent external topology changes are outside this contract.

## Actual generic entries and migration

`ImmutableRecordStore::put`, `MutableStateStore::compareAndSwap` and `compareAndSwapGuarded` now validate their original grammar and run a read-only reserved-path preflight before the primitive lock, current-state read, guard or filesystem publication. Reserved bare writes throw `PPC401_RESERVED_STORAGE_OWNER_REQUIRED`. Ambiguous descendant aliases throw `PPC402_STORAGE_TOPOLOGY_AMBIGUOUS`. No generic callback acquires Formation.

Explicit `putInOwner`, `compareAndSwapInOwner` and `compareAndSwapGuardedInOwner` consume a live `FormationOwnerFrame`. Its existing same-canonical-root assertion runs before the original primitive operation. The original target lock still encloses comparison, guard and commit. Formation remains continuously held by the caller; these methods neither create a frame nor renew authority.

| Reachable writer family | Actual target / owner treatment |
| --- | --- |
| All three generic entries, including variable-path callers | Reserved destinations refuse at the primitive itself. Ordinary callers cannot bypass this by supplying a different schema or payload. |
| SourceReview fixture's Curia and Clavium occupancy producers | Both reserved seed publications now share an explicit `FormationOwnerFrame::run` boundary and call `putInOwner`. No installed incumbents are copied. |
| PPC3 unsupported-successor and retirement regression | Consumes the live journal owner; bare guarded retirement first proves refusal with untouched original and no guard invocation. Historical PPC3 gap documentation remains unchanged. |
| PPC4 process writer | Receives the owner from the real Formation entry, publishes through each explicit generic counterpart and races actual model-bound Courtthane/Locksmith current consumers. Unsupported successor publication invalidates currentness; it grants no appointment. |
| Root installer, required installation, V0, StateStore, MasterMason, operationalization | Existing PPC3 bespoke publications and owner seams unchanged. Formation precedes native and bootstrap/target storage. Root installation already held Formation indirectly before PPC3. |
| Constable and Guildhall; native trust/protocol/journal enrollment, succession, retirement, revocations | Existing native owner boundary unchanged, including registry changes with untouched raw occupancy. Generic reserved mutation now cannot run outside the outer Formation fence. |
| Formation journal/trust, institutional observations, independent target appointments | Existing journal owner and real `currentCourtthaneInOwner` / `currentLocksmithInOwner` consumers unchanged. No new model approval or designation authority. |

The retained `generic-target-inventory.json` and `generic-callers.txt` inventory the source references and call expressions. No production typed generic writer to the reserved classes was found: production institutional publishers use the already-owned bespoke PPC3 paths. The legitimate reserved generic call sites found were fixture/regression producers above. A new wrapper alone would have been insufficient; the old entries themselves now enforce refusal.

## Ordinary callers and indirect callbacks

| Variable-path family | Resolution and exclusion |
| --- | --- |
| DeterministicJournalBoundCredentialBroker checkpoint | Private calls select `var/imperium/la-cortine/deterministic-*` checkpoint directories. Ordinary evidence. |
| ProviderInvocationJournalService | `var/imperium/runtime/provider-invocation-journal/<claim>.json`; original interruption guard stays under the ordinary CAS lock. |
| BrokeredSortieCognitionProviderInvoker | `var/imperium/runtime/sortie-cognition-invocations/<digest>.json`; invocation callbacks update this same ordinary journal. |
| Conscription/Curia model binding/governance and Senate authority transitions; OperationalAdoptionAuthorityTransition | Derive root and relative result directory from their result-directory argument. Normal service directories are binding/governance/decision/adoption results, not Office occupancy. If supplied a reserved destination, the old primitive now refuses; no upward acquisition or caller-controlled owner assertion is introduced. Historical exact-read replay branches do not write or confer current authority. |
| Curia commission-readiness/result-return record mechanics | Private directory parameters select bounded commissions, resource readiness, result dispositions and return authorizations. |
| Garrison deployment-custody and terminal coordinators | Runtime transition journals, `offices/garrison/custody`, terminal returns and **mission** occupancy. Mission occupancy is distinct from reserved **Office** occupancy. |
| Conscription operational transition coordinator | Qualification/assembly evidence and mission occupancy. Existing native owner wrappers remain where present. |
| Imperator inert issuance | Fixed boundary/principal decision and issuance directories and `offices/la-cortine/provider-*` records. None is Office occupancy. |
| CodexImperiiStore; ChildCuriaFormationService | `var/imperium/codex-imperii.json`; child-root `var/imperium/curia/handoffs`. Ordinary semantics and service construction retained. |
| Remaining constant/literal generic targets | Office custody, testimony, commissions, admissions, provider transitions, mission and terminal evidence. Being under `offices` alone does not reserve a target. |

## Compatibility and aliases

| Destination / validator behavior | PPC4 behavior |
| --- | --- |
| `var/imperium/bootstrap-state.json` | Reserved exact effective file, including immutable `bootstrap-state` ID plus `.json`. |
| `operator-root/`, `native-authority/`, `citadel/formation/` under `var/imperium/` | Entire subtrees reserved, including absent descendants and package directories. |
| `var/imperium/offices/<any-office>/occupancy/` | Entire subtree reserved, including unknown Offices and nested paths. |
| Repeated separators and immutable trailing directory separator | Classified by effective segments; original grammar retained. |
| Single-dot segments | Mutable validator accepts them and classifier removes them. Immutable directory grammar does not accept dots. |
| Case | Windows classification is case-insensitive; Linux remains case-sensitive. Immutable directory grammar is lowercase but IDs admit uppercase. |
| Windows trailing-dot segments accepted by mutable grammar | Trimmed for classification to match Win32 aliases. `..` remains rejected by the old validator. |
| Siblings such as `native-authority-sibling`, Office custody, mission occupancy | Ordinary when topology is unambiguous. |
| Existing symlink/junction descendant, dangling link or unresolved alias | Deliberate compatibility change: refuse ambiguous topology, even for an otherwise ordinary lexical path. Live and dangling Windows junction probes refused all three entries. The symlink test cannot create links on this Windows host; suitable-host proof remains required. |
| Canonical root alias / absent ordinary root | Resolve the root consistently with Formation; inspect nearest existing ancestor without creating it. Ordinary absent-root creation still works. |
| Absolute paths and traversal | Original validator refuses; no grammar broadening. |

Ordinary lock keys, sealing, replay/conflict errors, CAS comparison, single guard invocation, temporary write/rename and historical reads remain the original primitive implementation. This is not package atomicity or power-loss durability. PPC3 completion-marker validation still refuses interrupted multi-file packages.

The lock order remains **Formation → native → bootstrap or target storage**. A bare reserved write from an ordinary lower-lock guard, or while its exact primitive lock is already held, refuses before reacquisition. Owner-aware entry does not make non-reentrant Formation reentrant. Wrong-root and escaped frames refuse, and exception/process-interruption tests prove lock release.
