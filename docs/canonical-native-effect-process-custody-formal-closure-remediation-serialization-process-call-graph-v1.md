# Canonical Native Effect Process Custody and Formal Closure Remediation — serialization/process call graph v1

`PREPARATION_BATCH_0_SERIALIZATION_PROCESS_GRAPH_ONLY`
`BATCH_1_NOT_AUTHORIZED`

Baseline: `ef69362fd49252e15893af72ca71a3e2abb7a209`.

## Current construction and custody graph

```text
Symfony container (default shared services within one container)
  -> NativeState(root)                              [explicit service]
  -> CanonicalNativeEffectCorridor(state)           [auto-discovered facade]
       -> capabilityIssuer()      -> fresh credential issuer
       -> continuationIssuer()    -> fresh continuation issuer
       -> atomicAdmission(credential issuer, continuation issuer)
            -> NativeEffectAtomicAdmissionService
                 -> same continuation issuer
       -> providerDouble(continuation issuer)
            -> NativeEffectDoubleExecutionService
                 -> same continuation issuer only if caller carries it

NativeEffectAtomicAdmissionService::admit
  -> NativeEffectSemanticIdentity::{tupleId, authorityConsumptionId, admissionId}
  -> NativeState::locked
       -> native-provider-transition
       -> sorted immutable source/trust locks
       -> canonical-native-effect-authority:<sha256(authority id)>
       -> canonical-native-effect-tuple:<tuple id>
       -> validate exact authority/native lineage
       -> consume credential capability
       -> ImmutableRecordStore::put(admission)
  -> locks released
  -> continuationIssuer::issueForNewWinner(
       admission,
       authority.execution_boundary.id)             [LABEL, NOT PROCESS FACT]
  -> cache and return NativeEffectAdmissionOutcome(admission, continuation)
```

Every production effect-class constructor is confined to the facade and the
classes themselves. Tests construct the same services directly. The
ProviderTransition namespace is excluded from direct container discovery. No
production command calls this corridor.

## Current PHP transfer graph

```text
issuer.private issued[capability_id] ----same PHP object----> capability
outcome.continuation --------------------same PHP object----/

serialize([issuer, outcome])
  -> default serialization includes private issuer arrays and readonly fields
  -> constructor and validation are not run during unserialize
  -> PHP reference table preserves the shared capability node

unserialize(bytes)
  -> restored issuer.private issued[id] ------------> restored capability
  -> restored outcome.continuation -----------------/
  -> issuer::recognizes(outcome.continuation) == true

clone issuer
  -> shallow-copy registry array
  -> entries still point to original capability
  -> cloned issuer recognizes original capability

clone outcome
  -> continuation property still points to original capability
  -> original issuer recognizes cloned outcome.continuation

pcntl_fork on supported Unix
  -> child receives copy-on-write issuer, registry and object graph
  -> object identity relationships are preserved inside child memory
  -> no getmypid/process-incarnation comparison exists
  -> inherited child can recognize inherited custody
```

Cloning the capability by itself happens to create an unrecognized object, but
that is not a custody guarantee because cloning the issuer or outcome and
serializing the combined graph preserve a recognized edge.

## Current execute/recovery graph

```text
NativeEffectDoubleExecutionService::execute(admission, continuation, payload, key, at, double)
  -> continuation lock
  -> read admission
  -> receipt exists?
       YES -> RETURN RECEIPT                         [NO custody validation]
  -> callback-start exists?
       YES -> response exists?
                YES -> bindReceipt + RETURN          [NO custody validation]
                NO  -> UNKNOWN_REPLAY_PROHIBITED
  -> assertAndConsumeContinuation                    [registry identity only]
  -> put callback-start
  -> release continuation lock
  -> invoke provider double once
  -> put sealed response
  -> continuation lock
  -> bind receipt

reconstruct(receiptId)
  -> read receipt only
  -> read_only=true; provider_reinvoked=false; retry_authorized=false
```

The existing-receipt and sealed-response branches are forward/read-only acts
embedded in the callback method. They are safe from callback reinvocation but
do not prove governed forward mutation.

## Process-loss cuts

```text
before admission rename
  -> no durable winner; possible ignored .tmp.*; no provider action

after admission rename, before continuation issue/return
  -> durable winner; no caller-held custody; first callback must be stranded

after continuation return, before callback-start rename
  -> current serialize/clone/fork paths can transfer custody
  -> target semantics: process loss permanently removes first-callback right

after custody consumption, before callback-start rename
  -> no provider action; ambiguous/abandoned pre-callback; never reissue custody

after callback-start rename, before/during callback
  -> UNKNOWN_REPLAY_PROHIBITED; never invoke again

after provider observation, before response rename
  -> callback-start only; unknown; never invoke again

after response rename, before receipt rename
  -> governed forward completion only; never invoke provider

after receipt rename
  -> read-only reconstruction only
```

## Smallest target graph for later batches

```text
actual ProcessIncarnationSource
  -> initial_pid = getmypid()                        [runtime fact]
  -> nonce = random_bytes(...)                       [never exposed/persisted]
  -> assertCurrent: getmypid() === initial_pid

nonserializable/noncloneable issuer + capability + outcome
  -> issue only after newly published admission
  -> bind opaque incarnation + issuer + exact object
  -> recognize/consume only if current PID and nonce binding match

executeFirst(admission, capability, payload, key, at, providerDouble)
  -> custody validation/consumption FIRST
  -> callback-start publication
  -> release locks
  -> provider double at most once
  -> response/receipt

reconstruct(receiptId)
  -> read only; no claim; no mutation; no callback

governed reconciliation authority
  -> issues exact forward-completion-only claim for admission + response
  -> claim states provider=false, credential=false, retry=false

forwardComplete(admission, response, claim, at)
  -> validate claim and immutable joins
  -> continuation lock -> claim scope -> receipt store scope
  -> bind/return deterministic receipt
  -> no capability accepted; no callback parameter exists
```

`execution_boundary.id`, authority issuer names, container IDs and caller
labels may be recorded as governance provenance but can never substitute for
`getmypid()` plus the issuer-owned incarnation nonce.
