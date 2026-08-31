# Provider Binding Activation State Reconciliation — Preparation Batch 0

## Result

PREPARATION_BATCH_0_COMPLETE_IMMUTABLE_BINDING_SUCCESSOR_REQUIRED

The existing operation-scoped activation evidence and the immutable provider
implementation binding do not form one current binding lifecycle.

`SingleOperationProviderBindingActivationIssuanceService` accepts only an
`ATTESTED_INERT` principal attestation, emits `ACTIVATED_UNCONSUMED`, and
records `provider_binding_activated=false`. The canonical principal truth is a
separate immutable ACTIVE activation. The legacy operation activation
cannot be promoted, replayed or reinterpreted as proof that the durable binding
is active.

The smallest safe posture is an immutable operation-scoped binding-lifecycle
successor that binds the exact active principal activation, original
`BOUND_INACTIVE` implementation descriptor, admitted assurance and operation.
The original binding remains immutable. Global `BOUND_ACTIVE` mutation is
rejected.

## Inventory and classification

| Requirement | Classification | Exact finding | Non-authority or stop |
| --- | --- | --- | --- |
| Binding lifecycle vocabulary | EXISTS_FRAGMENTED | The v1 descriptor lists BOUND_INACTIVE, BOUND_ACTIVE, EXPIRED and REVOKED, but only BOUND_INACTIVE has a canonical producer | Vocabulary is not lifecycle truth |
| Owner of BOUND_ACTIVE | ABSENT | No competent producer owns a durable transition to BOUND_ACTIVE | Existing services may not infer or assign ownership |
| Immutable implementation descriptor | EXISTS_CANONICALLY | It binds provider, adapter, assurance, credential family, encoder, decoder, destination, scope and validity at BOUND_INACTIVE | The descriptor grants no tool, credential, provider or I/O authority |
| Canonical active executor principal | EXISTS_CANONICALLY | One immutable principal-activation winner binds the exact generation, process boundary, provider, operation and assurance | Principal activation is prerequisite evidence only |
| Legacy operation activation evidence | EXISTS_CANONICALLY_BUT_INCOMPATIBLE | It requires ATTESTED_INERT and emits ACTIVATED_UNCONSUMED while declaring provider_binding_activated=false | It cannot prove current active-principal binding sufficiency |
| Legacy activation authority consumption | EXISTS_CANONICALLY_BUT_INCOMPATIBLE | One issuance authority is consumed under an authority-keyed lock | Consumption cannot repair the inert-principal basis |
| Binding activation successor target | ABSENT | No versioned record binds the ACTIVE principal activation to the immutable binding descriptor and one operation | A mutable status flip would erase lineage |
| Competent successor activation decision owner | ABSENT | No current authority is scoped to create the reconciled successor | Principal activation, selection, assurance and execution admission are non-issuers |
| Legacy activation revocation | EXISTS_CANONICALLY_BUT_INCOMPATIBLE | Revocation creates an immutable winner for the legacy activation and competes with combined admission | It does not revoke or mutate the binding descriptor |
| Reconciled activation/revocation lock | ABSENT | No shared target-wide root exists for successor activation versus revocation | Separate authority and activation locks do not prove mutual exclusion for the new lifecycle |
| Provider assurance prerequisite | EXISTS_CANONICALLY | Admitted assurance is digest-bound in the principal and binding corridors | Assurance is documentary evidence, not activation or provider truth |
| Activation-to-effect ordering | EXISTS_FRAGMENTED | Combined v2 admission proves atomic activation, authority consumption and effect-start for its own corridor | It does not consume a reconciled active-principal binding successor |
| Expiry | EXISTS_FRAGMENTED | Principal, descriptor, legacy activation and assurance expiries exist separately | The reconciled successor minimum expiry is undefined |
| Revocation observation | EXISTS_FRAGMENTED | Principal and legacy activation revocation evidence exist, but no successor-wide decision rule exists | Absence cannot be interpreted as continuing authority |
| Exact replay | EXISTS_FRAGMENTED | Immutable component replay exists | No exact replay identity exists for the reconciled successor tuple |
| Contention | EXISTS_FRAGMENTED | Authority-keyed activation and activation-keyed revocation locks exist | No one target-wide successor winner is proved |
| Crash recovery | EXISTS_FRAGMENTED | Principal and legacy activation records reconstruct separately | No cut matrix exists for successor authority consumption and commit |
| Reconstruction | EXISTS_FRAGMENTED | Active principal and existing records reconstruct read only | No aggregate classifies successor basis as eligible, incomplete, conflicted or refused |
| Secret exclusion | EXISTS_CANONICALLY | Durable corridors exclude credential bytes, references, environment names and capability identity | Activation must remain credential-independent |
| Process-local capability | DEFERRED_BOUNDARY | Capability identity remains issuer-process-local and the cross-process custody refusal remains authoritative | Binding reconciliation may not issue, transfer, reconstruct, consume or resolve capability authority |
| Live-call contract | DEFERRED_BOUNDARY | Still absent and separately selected | An activation successor would not authorize first byte |
| Live consumer and command | DEFERRED_BOUNDARY | Existing command and transport remain unmigrated | Code presence is not adoption authority |
| Threat ceiling | EXISTS_CANONICALLY | TRUSTED_WRITER_CANONICAL_INTEGRITY on SINGLE_AUTHORITATIVE_ROOT_ONLY | No hostile-writer, multi-root or distributed guarantee is implied |

## Candidate boundary postures

### Selected: immutable operation-scoped successor

Create a new versioned lifecycle artifact later, under separate authorization,
that references rather than mutates:

1. the exact immutable ACTIVE principal activation;
2. the exact immutable BOUND_INACTIVE implementation descriptor;
3. the exact admitted assurance;
4. one provider and operation;
5. one single-use activation authority;
6. one target-wide replay/contention root; and
7. explicit expiry, revocation, reconstruction and non-authority fields.

The successor may express operation-scoped binding sufficiency. It must not
change the original binding record to BOUND_ACTIVE and must not imply provider
invocation, credential access, effect start, retry or continuing authority.

### Rejected: global BOUND_ACTIVE mutation

A global status mutation would over-assemble power, discard immutable selection
lineage, blur operation scope and make unrelated consumers appear authorized.
It is rejected.

### Rejected: promote legacy ACTIVATED_UNCONSUMED

The legacy artifact was admitted against an inert principal and explicitly says
the provider binding was not activated. Reinterpretation would manufacture
current authority from incompatible historical evidence. It is rejected.

### Lawful refusal

If no competent decision owner and exact successor scope can be established,
the campaign must refuse before production. The provider binding remains
BOUND_INACTIVE.

## Ordering and crash posture

| Order or cut | Required truth |
| --- | --- |
| Reconstruct basis | Read-only; ACTIVE principal, immutable binding, assurance and operation must agree exactly |
| Before successor authority consumption | No successor; authority remains unconsumed |
| Consumption begins before commit | Recovery may return only one exact consume-to-commit outcome; absence grants no retry |
| Successor commit | One immutable operation-scoped winner under the target-wide root |
| Competing activation or revocation | Mutually exclusive winner under the same target-wide lock |
| Exact replay | Return the same successor; changed evidence conflicts |
| Expiry or revocation before commit | Refuse without successor or consumption-only state |
| Process restart | Reconstruct durable truth only; never capability identity |
| Before first byte | Stop: live-call contract, capability authority and live adoption remain absent |
| Possible effect ambiguity | UNKNOWN_REPLAY_PROHIBITED; never infer reinvocation |

## Smallest safe batch sequence

No later batch is authorized merely because it appears here.

1. **Authority-empty successor contracts** — define the reconciled target,
   decision input and immutable operation-scoped lifecycle successor with exact
   non-authorities.
2. **Pure validation and offline fixtures** — fail closed on active-principal,
   binding, assurance, operation, expiry, revocation, substitution and secrets.
3. **Read-only aggregate reconstruction** — classify complete, absent, corrupt,
   conflicted, expired, revoked and legacy-incompatible chains.
4. **Competent authority gate** — identify or refuse the exact decision owner,
   issuance authority and custody path before production.
5. **Atomic successor and revocation winner** — only if the authority gate
   passes, consume one authority into one target-wide immutable winner.
6. **Adversarial and terminal audit** — prove interruption, replay, contention,
   revocation, secret exclusion, non-authority and campaign closure.

## Preserved perimeter

Preparation changed no runtime contract or behavior. It activated no provider
binding, issued or consumed no authority, handled or resolved no credential or
capability, invoked no provider, performed no external I/O, started no provider
effect, authorized no retry, migrated no consumer or command, and opened neither
Iron Gate nor Lazaretto.

The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.
