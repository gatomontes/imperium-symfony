# Canonical Native Effect Continuation and Exclusivity Remediation — corrected call graph v1

`PREPARATION_BATCH_0_CORRECTED_CALL_GRAPH_ONLY`
`BATCH_1_NOT_AUTHORIZED`

Baseline: `77d26f4c7f5655dcce67b5c3765714b5c0ede85e`.

## Current executable graph

```text
fixture-created sealed effect authority
  -> NativeEffectCredentialCapabilityIssuer::issue
       -> random capability object + issuer-local identity map
  -> NativeEffectAtomicAdmissionService::admit(authority, capability, at)
       -> replayIdentity(authority)                 [includes authority id/digest]
       -> admission-id = first 20 replay hex
       -> NativeBindingReader::legacy               [native lock]
       -> authority-id lock
       -> authority-specific replay lock
       -> scan admissions for same authority id
       -> NativeEffectAdmissionValidator::inspect
            -> recompute authority seal
            -> exact current native/root/receipt/provider joins
       -> issuer recognizes exact capability object
       -> ImmutableRecordStore::put(admission)       [admission-directory lock]
       -> returns durable array only                 [NO continuation object]

durable admission + caller authority/payload/key/time/callback
  -> NativeEffectDoubleExecutionService::execute
       -> admission-id continuation lock
       -> read admission
       -> existing receipt? return before input validation
       -> validate admission checkpoint, caller authority reference,
          payload digest, key digest and admission time window
       -> existing callback-start?
            -> response exists: bind receipt from response + caller authority
            -> response absent: UNKNOWN_REPLAY_PROHIBITED
       -> put callback-start                        [callback-directory lock]
       -> invoke provider double                    [no credential/network]
       -> put response                              [response-directory lock]
       -> bind receipt from admission + response + caller authority
                                                    [receipt-directory lock]
```

The continuation edge is entirely reconstructible. The capability dies with
the admission process, but this does not stop anything because `execute()` does
not receive it.

## Current process-loss states

```text
no final admission
  -> new admission attempt may proceed
  -> orphan .tmp.* is not classified by current code

admission present; callback-start absent
  -> FRESH PROCESS MAY START FIRST CALLBACK          [BQ-CNE-01]

callback-start present; response absent
  -> UNKNOWN_REPLAY_PROHIBITED; never callback again

response present; receipt absent
  -> fresh process may bind receipt, but only with caller authority
     and before admitted expiry                      [BQ-CNE-03 exposure]

receipt present
  -> execute returns it before caller validation
  -> reconstruct returns read-only proof
```

## Current cross-authority split

```text
authority A --id/digest A-->
  replay A -> authority-lock A -> effect-lock A -> admission A

same semantic effect tuple

authority B --id/digest B-->
  replay B -> authority-lock B -> effect-lock B -> admission B

result: no shared lock, id or winner record; A and B can both win
```

## Corrected target graph for later batches

```text
sealed exact authority
  -> validate canonical authority and native lineage
  -> derive semantic-effect-tuple-id from admitted source facts
     (never authority id/digest)
  -> acquire required ordered locks
  -> revalidate expiry/revocation/cancellation and native currentness
  -> if tuple winner exists:
       exact same winner -> read-only reconciliation, NO capability mint
       different authority -> explicit tuple-loser refusal;
                              losing authority remains unconsumed
  -> atomically publish tuple winner + exact authority consumption
  -> only on newly published winner:
       create issuer/registry-recognized process-local continuation object
       return {sealed admission, exact continuation object}

same uninterrupted process and custody registry
  -> executeFirst(admission-id, continuation-object, payload, key, at, double)
       -> recognize exact object identity and admission/tuple/digest binding
       -> consume object once in memory
       -> under continuation lock, publish callback-start before callback
       -> invoke provider double
       -> seal response
       -> bind receipt exclusively from admission + response

fresh process after admission, before callback-start
  -X-> no continuation object; first callback refused
  -> reconciliation/read-only status only

fresh process after sealed response
  -> forwardComplete(admission-id, response-id, at)
       -> no provider callback and no continuation mint
       -> derive provider, return contract, authority lineage and request
          meaning exclusively from sealed admission/response
```

## Immutable admitted provenance required

The corrected admission must contain or reference, without later caller
substitution: native root/transition/receipt; exact authority reference;
semantic tuple id; operation; destination; payload digest; request fingerprint;
provider id; adapter id/version; assurance admission; credential family;
expected-return contract; provider-idempotency-key digest; effective/expiry
facts; and tuple winner/authority-consumption disposition. Clear payload and
idempotency key may remain ephemeral inputs only if their digests are checked
against that admission before callback construction.

## Construction and bypass graph

```text
config/services.yaml
  -> excludes ProviderTransition namespace from direct discovery
  -> explicitly wires NativeState + NativeBindingReader
  -> auto-discovers CanonicalNativeEffectCorridor
       -> manually constructs validator / issuer / admission / double

production commands
  -> no canonical-native-effect command
  -> ImperiumNativeProviderTransitionCommand ends at pre-effect receipt

test CanonicalNativeEffectCorridorKernel
  -> production discovery with disposable storage + public facade

canonical_native_effect_worker.php
  -> admit / admit-and-exit
  -> callback-exit / callback-retry
  -> missing fresh-process first callback after admit-and-exit
```

No credential resolver, AgentMail implementation, transport, Iron Gate,
Lazaretto runtime, network call or live effect is reachable from this graph.
