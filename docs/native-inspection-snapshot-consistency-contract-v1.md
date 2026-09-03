# Native Inspection Snapshot Consistency contract v1

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_BATCH_1_COMPLETE`
`OPTIMISTIC_WHOLE_READ_SET_WITH_BOUNDED_REFUSAL_CONTRACT`

## Guarantee

An unlocked native inspection returns a result only when every declared local
semantic input has the same manifest before and after one complete derivation.
The accepted result describes that coherent attempt at the caller-supplied
integer `at`. It is not linearizable after return, is not monotonic across later
`at` values or publications, and is never transferable authority.

The implementation performs no more than two attempts. If neither attempt has
equal manifests, it applies the existing conservative mapping:

- `interpret()` returns its existing shape with classification `INCOMPLETE` and
  recovery `UNKNOWN_REPLAY_PROHIBITED`;
- `forClaim()`, `forJournal()` and `read()` throw
  `UNKNOWN_REPLAY_PROHIBITED`;
- direct `NativeReconstructor::reconstruct()` returns its existing
  `UNKNOWN_REPLAY_PROHIBITED` result.

An internal observation reread is not execution retry, transition recovery,
provider retry or continuing authority.

## Declared semantic manifest

Each manifest includes existence, directory membership, entry type and SHA-256
content for regular files beneath these project-root-relative bases:

1. `var/imperium/la-cortine/deterministic-execution-claims`;
2. `var/imperium/imperator/outbound-email-authorization-issuances`;
3. `var/imperium/la-cortine/deterministic-effect-start-journals`;
4. `var/imperium/runtime/native-provider-transition`;
5. every directory in `NativeState::SOURCES`;
6. `var/imperium/operator-root/transition-trust`;
7. `var/imperium/runtime/legacy-provider-transitions`.

Missing bases are explicit manifest entries. Directories and empty directories
are entries. Symlinks, unreadable entries, disappearing entries and unsupported
entry types refuse the attempt. Regular `.lock` files are excluded because they
are mutex mechanics rather than semantic evidence. Every other regular file is hashed,
so a pending, temporary, unexpected or replacement artifact cannot be silently ignored.

The manifest is local observation evidence only. It is never returned, signed,
cached, persisted or accepted as provenance, freshness, admission or authority.

## Attempt and nesting algorithm

For attempt 1 and, only if needed, attempt 2:

1. capture manifest A;
2. establish one process-local observation scope keyed by canonical native-state
   identity;
3. run the complete existing derivation with the original supplied `at`;
4. clear the scope in `finally`;
5. capture manifest B;
6. accept the existing result or propagate its existing error only when A equals
   B; otherwise discard both and start the next attempt.

Manifest capture failure counts as an unstable attempt. After attempt 2 the
boundary refuses as above. There is no unbounded loop, sleep, repair or fallback
to legacy state.

Nested `forJournal()` -> `forClaim()` -> `interpret()` -> `read()` ->
`reconstruct()` calls share the outer attempt. Direct entry through any one of
those methods creates the outer attempt. Scope state is removed after success or
exception and is isolated by canonical state identity.

## Existing public projections remain exact

No result classification or schema changes in this campaign:

- `interpret()`: `root`, `classification`, `descriptor`, `receipt`,
  `read_only=true`, `provider_effect_permitted=false`,
  `retry_authorized=false`, `recovery=UNKNOWN_REPLAY_PROHIBITED`;
- `forClaim()`: the `interpret()` projection plus `execution_claim`,
  `execution_id` and `replay_fingerprint`;
- `forJournal()`: the same claim projection after the existing journal/claim
  digest join;
- `read()`: `root`, `effective_status`, `descriptor`, `receipt`;
- `reconstruct()`: `classification`, `receipt`, `read_only=true`,
  `execution_authority=false`, `retry_authorized=false`,
  `provider_effect_started=false`.

The preserved classifications include `LEGACY_UNBOUND`, `BOUND_INACTIVE`,
`COMMITTED_CURRENT`, `COMMITTED_NOT_CURRENT`, `INCOMPLETE`, `CORRUPT`,
`UNRELATED_OPERATION`, `ABSENT`, `COMMITTED` and
`UNKNOWN_REPLAY_PROHIBITED`. Historical v3 `NOT_IMPLEMENTED` is untouched.

The lower-level `read()` projection does not acquire authority merely because it
does not contain the explicit false fields used by inspection projections.

## Authorizing and unlocked callers

`DeterministicJournalBoundCredentialBroker::invoke()`,
`DeterministicEffectStartJournalService::start()`, and
`AgentMailIdempotencyHeaderAdapter::invoke()` already hold
`native-provider-transition` across their pre-effect decision. Their nested
inspection uses the optimistic scope without reacquiring any production lock.
`NativeConsumer::execute()` likewise keeps its established exclusion.

Direct broker inspection and `agent:mail:send --inspect-claim` remain unlocked,
read-only and non-authorizing. They gain coherent accepted observations, not a
right to act on a result later.

## Side-effect and storage boundary

Inspection must not create a lock directory or lock file, write a snapshot,
publish native or legacy state, read a credential, invoke a provider, admit an
effect, open Iron Gate or Lazaretto, or grant execution/retry/recovery authority.
The accepted pre-effect consumer boundary remains unchanged.

The proof boundary is cooperative single-host local storage with canonical paths
and atomic rename publication. Windows and POSIX path identity must be tested.
Network filesystems, hostile ABA replacement, physical power loss and truth
outside the declared project root remain `DEFERRED_BOUNDARY`.

## Batch 2 implementation gate

Batch 2 may add one internal read-only manifest/scope component and route the
five public inspection entrypoints through it. A test-only checkpoint may be
constructor-injected for deterministic separate-process proof, but production
wiring must supply none. Batch 2 may not add a production lock, change a public
result projection, change a classification, or weaken any existing validator.
