# Native Inspection Snapshot Consistency race matrix v1

This is the Preparation Batch 0 adversarial plan. All proof state must be under a
disposable temporary root. All competing actors must be separate PHP processes.
No live provider, credential, capability, mission, Iron Gate,
Lazaretto or external I/O is in scope.

| ID | Competing mutation / cut | Affected read set | Current possible observation | Required later proof | Classification |
| --- | --- | --- | --- | --- | --- |
| X00 | No writer; repeat direct `interpret`, `forClaim`, `forJournal`, `read` and reconstruction | Entire read set | Stable classifications normally repeat | Identical stable inputs produce identical existing result shapes and zero file delta | EXISTS_FRAGMENTED |
| X01 | Claim file rename between outer reads | Claim | `forClaim()` already detects exact claim change | Shared snapshot refuses; never returns a mixed claim/native result | EXISTS_CANONICALLY |
| X02 | New matching authorization issuance appears after unique-match scan | Issuance membership | Undetected by current outer checks; can combine old uniqueness with later native state | Barrier after manifest A, canonical immutable publication, then retry/refusal | ABSENT |
| X03 | New/changed binding descriptor during candidate scan | Binding membership/content | Current binding snapshot detects it | Preserve refusal and prove no alternate binding/root is selected | EXISTS_CANONICALLY |
| X04 | Native journal before-open / pending / after-rename | Native journal | Incomplete or conservative corrupt/refusal; direct inspection can expose a transient | Each cut either belongs to one stable snapshot or triggers bounded reread/refusal | EXISTS_FRAGMENTED |
| X05 | Each registered legacy retirement before-open / pending / after-rename | Migration/legacy | Orphan/partial retirement yields unknown/incomplete | No inactive/current success across partial retirement; no retry grant | EXISTS_FRAGMENTED |
| X06 | Final transition commit before-open / pending / after-rename | Transition commit and embedded seven records | Before commit may be inactive/incomplete; after commit current/noncurrent | Accepted result is wholly pre-commit or wholly post-commit; pending is conservative | EXISTS_FRAGMENTED |
| X07 | Process death after journal and before final commit | Journal/retirement/transition | Stable `INCOMPLETE`; re-entry prohibited | Repeated inspection remains coherent incomplete and byte-stable; execution remains `UNKNOWN_REPLAY_PROHIBITED` | EXISTS_CANONICALLY |
| X08 | Signed native revocation published during inspection | Principal/revocation/trust | Inner reconstruction can be stable while outer claim reads belong to another epoch | Crossing attempt retries/refuses; next stable read is `COMMITTED_NOT_CURRENT` | EXISTS_FRAGMENTED |
| X09 | Expiry boundary crosses while the call runs | Caller-supplied `at` and source validity | Supplied integer `at` is stable, so wall time alone must not change result | Same `at` repeats; later `at` yields noncurrent without retry/authority | EXISTS_CANONICALLY |
| X10 | Higher original principal generation publication | Principal directory | Reconstructor snapshot usually detects change | Force publication between manifests; accept no mixed current generation | EXISTS_FRAGMENTED |
| X11 | Higher executor activation generation publication | Activation directory | Reconstructor snapshot usually detects change | Force crossing and prove refusal/noncurrent behavior with unchanged classifications | EXISTS_FRAGMENTED |
| X12 | Production, boundary, attestation or assurance publication | Source memberships and references | Inner snapshot detects semantic file change but enclosing reads are not joined | One representative per directory plus combined churn; bounded refusal | EXISTS_FRAGMENTED |
| X13 | Trust identity or operationalization seal maintenance | Trust | Reconstruction refuses changed/invalid trust | Crossing attempt cannot return current from pre-maintenance trust | EXISTS_FRAGMENTED |
| X14 | Migration inventory replacement or registered store-set change | Trust/legacy | Inner snapshot can detect files but the outer call may straddle it | Set/order/identity must be from one accepted manifest; aliases refuse | EXISTS_FRAGMENTED |
| X15 | Unregistered legacy directory appears | Legacy base membership | Existing snapshot includes visible base directory entries; inventory completeness remains operator-declared | Visible semantic entry changes cause retry/refusal; truth outside declared local root remains deferred | EXISTS_FRAGMENTED |
| X16 | Canonical writer holds `native-provider-transition` while unlocked CLI inspection runs | Native publication | Existing test observes `INCOMPLETE` instead of waiting | New optimistic inspection must not acquire/create a lock; it retries/refuses or returns one stable side | EXISTS_FRAGMENTED |
| X17 | Authorizing journal broker competes with native publication | Existing outer lock | Broker waits, then evaluates final native state and refuses before credentials/callback | Preserve existing separate-process exclusion test unchanged | EXISTS_CANONICALLY |
| X18 | Inspector process is terminated mid-read | No writer | No state mutation expected | Abrupt inspector loss leaves no lock, pending file, publication or repair artifact | ABSENT |
| X19 | Continuous cooperative churn exceeds retry bound | Whole manifest | No defined bound today | At most two attempts, then conservative unavailable/unknown; no busy loop or starvation claim | ABSENT |
| X20 | Result cached/displayed/signed/admitted after writer commits | TOCTOU after return | Returned “current” can become stale immediately | Contract states non-transferability; downstream must re-inspect and separately authorize | ABSENT |
| X21 | Same-root copy, symlink, manual deletion, A->B->A replacement, network filesystem or physical power loss | Host/storage boundary | Not covered by cooperative snapshots | Refuse detected alias/corruption; explicitly retain as deferred and make no linearizability claim | DEFERRED_BOUNDARY |
| X22 | Windows and POSIX local runs | `realpath`, path identity, `flock`, rename | Windows path identity lowercases; both rely on cooperative local semantics | Run focused proof on available host and CI peer OS; document any filesystem prerequisite, without distributed claim | DEFERRED_BOUNDARY |

## Separate-process proof sequence

1. Build a complete disposable fixture without credentials and fingerprint every
   file. Start an inspector worker with a trusted test-only barrier immediately
   after manifest A.
2. Start a canonical writer worker for the selected X02-X15 case. Hold it at its
   real before-publish cut, release the inspector to begin derivation, then release
   the writer before manifest B. The first attempt must not be accepted.
3. Assert either one bounded internal reread returns the stable post-publication
   classification or the existing conservative unknown/refusal is returned. No
   mixed receipt, descriptor, claim identity or migration set may escape.
4. Repeat at pending and after-publish cuts, and kill the writer at every existing
   interruption family. Stable partial state must remain `INCOMPLETE` or
   `UNKNOWN_REPLAY_PROHIBITED`; it may never become `BOUND_INACTIVE` or authorize
   retry/recovery.
5. Run the unlocked inspector during a held native transition and prove it neither
   waits on nor creates `transition-locks`; then run the existing broker worker and
   prove that authorizing use still waits under native exclusion and refuses before
   admission, credential access and callback.
6. Terminate the inspector itself between manifests and compare the filesystem
   fingerprint. Then drive continuous canonical additions past the attempt bound
   and prove bounded liveness/refusal.
7. After writers stop, perform two separate-process inspections with the same
   `at`; require byte-identical non-authorizing output. Repeat with a later `at`
   for expiry/revocation and require `COMMITTED_NOT_CURRENT` where applicable.

The trusted pause hook is proof instrumentation only. It must not be bound from a
request, persisted, or registered in the production container.
