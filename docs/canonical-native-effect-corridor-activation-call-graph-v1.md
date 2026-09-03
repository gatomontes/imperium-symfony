# Canonical Native Effect Corridor Activation — call graph v1

`PREPARATION_BATCH_0_CALL_GRAPH_ONLY`

## Canonical native chain

```text
signed Operator Root act
  -> NativeRootActs::verify
  -> NativePrincipal::{constitute,lifecycle,load}
  -> NativeSuccessor::{create,load}
  -> NativeAuthority::{issue,load}
  -> ImperiumNativeProviderTransitionCommand
  -> NativeConsumer::execute(authority-id)
     -> NativeState::locked
     -> NativeMigration::locked
     -> NativeAdmission::records
     -> native journal + legacy retirement
     -> seven-record transition commit
  -> NativeBindingReader::{read,interpret,forClaim}
     -> NativeInspectionSnapshot::observe
     -> NativeReconstructor::reconstruct
     -> COMMITTED_CURRENT / COMMITTED_NOT_CURRENT / refusal
```

The seven-record commit is authority consumption, selected v3 admission,
adoption join, source-binding transition, successor-binding activation, winner
and pre-effect receipt. Every effect flag remains false. This chain terminates at
read-only interpretation.

## Established historical effect chain and native refusal

```text
stored deterministic claim
  -> DeterministicEffectStartJournalService::start
     -> NativeBindingReader::forClaim
     -> requires LEGACY_UNBOUND                 [native refusal cut 1]
  -> DeterministicJournalBoundCredentialBroker::invoke
     -> NativeBindingReader::legacy
     -> stored journal + claim + capability checks
     -> NativeBindingReader::forClaim
     -> requires LEGACY_UNBOUND                 [native refusal cut 2]
     -> immutable invocation admission
     -> credential-attempt checkpoint
     -> CredentialBroker::consume
     -> AgentMailIdempotencyHeaderAdapter::invoke
        -> NativeBindingReader::forJournal
        -> requires LEGACY_UNBOUND              [native refusal cut 3]
     -> callback-start checkpoint
     -> provider callback
     -> response content + callback-bound envelope
  -> DeterministicRawProviderResultService::seal
  -> DeterministicLazarettoReceiptAdmissionService::admit
  -> DeterministicReceiptReconstructionService::reconstruct
```

This is executable only with a caller-supplied callback in the historical proof
corridor. The production email command supplies none and remains retired. The
chain has no native-effect authority join.

## Competing and archival paths

```text
OutboundRequest(email.send)
  -> DeterministicBoundaryExecutor::execute
  -> CCI_EMAIL_REQUEST_HAS_NO_BINDING_ROOT      [cut 4, pre-Iron-Gate]

AgentMailEmailTransport::execute(email.send)
  -> CCI_EMAIL_TRANSPORT_HAS_NO_BINDING_ROOT    [cut 5, no network body]

AgentMailEmailSendCommand
  -> default: GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE
  -> --inspect-claim: broker::inspectClaim only [cut 6, read-only]

legacy descriptor/admission/credential readers D01-D10, A01-A02
  -> NativeBindingReader::legacy/assertLegacyRecord
  -> CCI_NATIVE_STATE_PRECLUDES_LEGACY          [cut 7]

provider-neutral raw evidence
  -> AgentMailProviderEvidenceDecoder
  -> ProviderBoundEvidenceNormalizationService
  -> NormalizedToolResultAdmissionService
  -> GovernedToolResultReconstructionService   [archive, no outgoing edge]

AgentMail webhook
  -> AgentMailWebhookVerifier
  -> InboundLazaretto
  -> InboundArtifactStore::persistOnce         [separate inbound meaning]
```

## Missing activation edge

```text
COMMITTED_CURRENT native root + read-only receipt
  -X-> competent one-effect decision/issuance       ABSENT
  -X-> atomic effect-authority consumption/start    ABSENT
  -X-> stationary credential/capability join        ABSENT
  -X-> provider callback                            ABSENT
  -X-> native-bound raw result/Lazaretto receipt    ABSENT
```

## Current exact lock order

Native writers acquire:

1. `native-provider-transition`;
2. `immutable:<sha256(directory)>` for every `NativeState::SOURCES` directory
   and Root trust directory, lexicographically sorted;
3. migration legacy `TransitionStore` locks, sorted by physical storage identity;
4. per-event `TransitionStore` publication lock during immutable put.

Guarded legacy consumers acquire `native-provider-transition` before their old
winner/immutable scopes. Read-only inspection takes no transition lock and uses
manifest comparison; it is non-authorizing.

## Proposed effect lock order

The minimum deadlock-safe order is:

1. `native-provider-transition` (exclude publication/migration and revalidate
   the exact current native root);
2. the existing sorted immutable source/trust locks if the implementation reads
   them through `NativeState::locked`;
3. `canonical-native-effect-authority:<sha256(authority-id)>` (mutually exclude
   first consumption and revocation/cancellation);
4. `canonical-native-effect:<effect-replay-identity>` (one winner/start
   aggregate);
5. immutable aggregate publication lock.

No provider callback, credential resolution or outbound I/O may occur while a
filesystem lock is held. No code may acquire the native lock after acquiring an
effect/legacy/source lock. The aggregate must be revalidated immediately before
rename; after rename, its winner identity—not a held lock—prevents duplicates.

## Earliest irreversible cut

The earliest irreversible cut is the successful atomic rename of the single
effect aggregate that records both effect-authority consumption and
`EFFECT_STARTED / UNKNOWN_REPLAY_PROHIBITED`. It must occur before credential
resolution, capability consumption, callback construction or any outbound byte.
Nothing after this cut may automatically retry or create another winner.

Batch 0 created no such edge, lock or record.
