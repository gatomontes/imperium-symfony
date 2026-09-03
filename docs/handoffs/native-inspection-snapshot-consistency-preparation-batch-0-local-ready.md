# Native Inspection Snapshot Consistency — local entrypoint

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_CAMPAIGN_READY`
`PREPARATION_BATCH_0_AUTHORIZED_ONLY`
`NONAUTHORIZING_INSPECTION_CONSISTENCY_RESIDUAL_OPEN`

Use the following prompt in a new local Codex chat:

> Continue Imperium from clean synchronized `main` after the merge that selected
> the Native Inspection Snapshot Consistency campaign.
>
> Read `docs/next-campaign-native-inspection-snapshot-consistency.md`,
> `docs/handoffs/executable-atomic-transition-canonical-consumer-integration-correction-campaign-complete.md`,
> `docs/executable-atomic-transition-canonical-consumer-integration-correction-terminal-audit-v1.md`,
> `docs/executable-atomic-transition-canonical-consumer-integration-correction-inventory-v2.md`,
> all three canonical-consumer correction reading ledgers, the native transition
> contracts and implementation documents they identify, and every complete
> runtime/test source required to trace `NativeBindingReader::interpret`,
> `forClaim`, `forJournal`, `read`, `NativeReconstructor::reconstruct`,
> `NativeState::locked` and `AtomicTransition` callers and lock acquisition.
>
> Begin Native Inspection Snapshot Consistency Preparation Batch 0 only.
>
> Inventory every inspection caller, full read set, publication write order,
> mutation surface, lock scope/order, nested acquisition, interruption state,
> expiry/revocation race, migration race, cross-process observation race,
> time-of-check/time-of-use consumer and current documentary promise. Distinguish
> the authorizing journal-broker path that already holds native exclusion from
> unlocked read-only inspection paths.
>
> Classify each surface as EXISTS_CANONICALLY, EXISTS_FRAGMENTED, ABSENT or
> DEFERRED_BOUNDARY. Determine whether the smallest defensible contract should be
> lock-covered linearizable inspection, optimistic coherent snapshot,
> conservative retry/refusal, or explicitly bounded best-effort observation.
> Do not choose by taste: prove the lock graph, side effects, liveness and
> read-set implications. Propose the smallest implementation and adversarial
> separate-process proof sequence.
>
> Do not modify runtime behavior or production service wiring. Do not acquire a
> new production lock, change result classifications, execute a mission, invoke
> a provider, access a credential or capability, perform external I/O, publish
> non-disposable native state, authorize retry/recovery, or open Iron Gate or
> Lazaretto. Preserve BOUND_INACTIVE, historical v3 NOT_IMPLEMENTED,
> UNKNOWN_REPLAY_PROHIBITED and the bounded pre-effect acceptance.
>
> Produce the Preparation Batch 0 inventory, race matrix, reading ledger and
> completion handoff. Run only documentary/structural tests needed for those
> artifacts. Stop after Preparation Batch 0 and report the exact remaining
> stages and local PHPUnit command.

No later batch is authorized by this entrypoint.
