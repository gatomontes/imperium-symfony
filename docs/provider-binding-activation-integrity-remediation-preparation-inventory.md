# Provider Binding Activation Integrity Remediation — Preparation Batch 0 inventory

## Result

`PREPARATION_BATCH_0_COMPLETE_ACTIVATION_CORRIDOR_UNPROVED_AND_QUARANTINED`

The terminal custody refusal remains correct and authoritative. The activation corridor is not yet
eligible for preservation, retirement or future credential-platform adoption. Its runtime-principal
source is absent, its new transition recovery is inherited but not demonstrated, its activation
artifacts have no terminal disposition, its clear credential-reference access boundary is broader
than the durable secret-exclusion claim, and its cross-process evidence combines an observed
distinct-instance refusal with a declarative unsupported flag rather than a restart demonstration.

Preparation changes no runtime behavior. Existing Batch 2–3 records, if any, remain immutable,
expiring and non-operational. They are classified as quarantined by campaign policy only; this
document does not mutate, revoke, consume or replace them.

## Exact classification

| Requirement | Classification | Exact producer or evidence | Exact consumer or recovery owner | Non-authority and stop condition |
| --- | --- | --- | --- | --- |
| Imperator runtime-principal schema | `EXISTS_FRAGMENTED` | Tests write ad hoc `imperium.imperator-runtime-principal/v1` fixtures; runtime validators recognize the schema. | Caller-authority issuance and consumption read the record. | Recognition is not installation, provenance, renewal or revocation authority. |
| Producer of `provider_binding_activation_authority` | `ABSENT` | No runtime producer, transition, contract or installation service was found; only the issuer checks the field. | None can lawfully receive the field today. | Do not add the flag to a fixture, file or generic principal producer and call that governance. |
| Activation caller-authority issuance vocabulary | `EXISTS_CANONICALLY` | `DeterministicTransitionCallerAuthorityIssuanceService` recognizes decision and issuance transitions. | Batch 2 decision and issuance services. | It remains unreachable without a competent principal source. |
| Caller-authority durable single consumption | `EXISTS_CANONICALLY` | `AuthorityConsumptionStore` records one authority/consumer winner and returns the same record to the same consumer. | `DeterministicTransitionCallerAuthorityConsumer`. | Consumption is not the target decision or issuance commit. |
| Decision consume-to-commit atomicity | `EXISTS_FRAGMENTED` | Caller consumption commits first; `ImmutableRecordStore` commits the decision afterward under another lock. | Same decision service may retry with identical semantic and temporal inputs. | No Batch 2 crash demonstration proves the post-consumption/pre-decision cut. |
| Issuance consume-to-commit atomicity | `EXISTS_FRAGMENTED` | Caller consumption precedes lineage validation and issuance lock/write. | Same issuance service may recover only if retry inputs reproduce the immutable record. | Expiry between interruption and retry, changed `issuedAt`, lineage change and conflicting recovery are unproved. |
| Embedded decision issuance-authority consumption | `EXISTS_FRAGMENTED` | Issuance record embeds a consumed posture and scans prior issuances under one authority lock. | Activation-authority issuer. | The source decision remains immutable with `consumed: false`; consumers must not read that field alone as current truth. |
| Batch 3 activation-authority consumption | `EXISTS_FRAGMENTED` | Activation record embeds consumption under an authority-specific atomic lock. | Future custody or admission consumers, presently forbidden. | Source nested authority remains immutable; no canonical terminal index or reconstruction policy resolves apparent state. |
| Stranded activation authorities | `EXISTS_CANONICALLY` | Batch 2 issuance may contain one exact expiring authority. | Batch 3 only, but the campaign is terminally refused downstream. | No new activation is justified while custody remains impossible. Expiry grants no successor authority. |
| Stranded `ACTIVATED_UNCONSUMED` leases | `EXISTS_CANONICALLY` | Batch 3 produces an immutable execution-scoped lease. | Custody/admission postures are prohibited by terminal refusal. | Status text must not override expiry or refusal; the lease may not be reinterpreted as provider, credential or retry authority. |
| Terminal artifact disposition | `ABSENT` | No quarantine, supersession, retirement or refusal-binding record references the exact authorities and leases. | Future read-only disposition/reconstruction posture. | Do not mutate immutable artifacts or manufacture revocation retrospectively. |
| Clear credential-reference object boundary | `EXISTS_FRAGMENTED` | `CredentialCapability` stores a public `credentialRef`; its `metadata()` returns the clear reference. Eligibility, claim, broker and feasibility services read it. | Same-process validators and broker resolution. | Durable records normally retain digests, but public access and metadata copying are broader than “not persisted.” |
| Credential-reference durable exclusion in Batch 4 | `EXISTS_CANONICALLY` | Feasibility record persists the existing digest and explicit false secret/reference flags. | Audit readers. | Durable exclusion does not prove memory, log, dump or exception exclusion. |
| Credential-reference memory lifetime, logging and crash policy | `ABSENT` | No exact authorized-reader list, zeroization/lifetime rule, dump exclusion or negative logging proof exists. | Future credential-boundary hardening campaign or remediation batch. | Do not claim secret exclusion from absence in one record shape. |
| Distinct-broker refusal | `EXISTS_CANONICALLY` | Batch 4 test proves issuer recognition and refusal by another broker instance for the same object. | Feasibility assessment. | Different instances in one process are not a second-process crash/restart demonstration. |
| Declared cross-process support posture | `EXISTS_CANONICALLY` | `EnvironmentCredentialBroker::supportsCrossProcessCustody()` returns `false`. | Feasibility assessment. | Declaration is honest but not experimental evidence. |
| Real process-loss demonstration | `ABSENT` | No subprocess issues a capability, exits, and proves that a successor cannot recover possession without reconstruction. | Future offline evidence harness. | It may not resolve credentials, invoke a callback or persist the clear reference to stage the proof. |
| Multi-host custody | `DEFERRED_BOUNDARY` | No shared custodian, distributed lock or issuer attestation exists. | Future credential-platform campaign, if separately selected. | Same-root filesystem evidence cannot prove distributed custody. |
| Future credential-platform prerequisites | `ABSENT` | No selected issuer/custodian contract proves transferable possession, process principal binding, secret exclusion and atomic consumption. | Future selection campaign only after remediation disposition. | Platform selection before disposition would repeat the sequencing error. |

## Interruption matrix

| Cut | Current truthful posture | Required proof |
| --- | --- | --- |
| Before caller-authority consumption | No transition won. | Retry may consume once if authority and principal remain valid. |
| After consumption, before decision commit | Consumption may exist with no decision. | Same-consumer recovery must converge with original immutable decision inputs or seal terminal refusal. |
| After decision commit, before issuance caller consumption | Decision exists; no activation authority exists. | Reconstruction must distinguish authorized decision from issued authority. |
| After issuance caller consumption, before lineage validation/write | Caller authority may be consumed with no issuance. | Retry, expiry and changed-lineage outcomes require explicit proof. |
| After issuance write, before activation | One activation authority exists; no activation lease exists. | Read-only reconstruction must use issuance winner, not the source decision's stale nested flag. |
| After activation write | Lease exists but custody refusal blocks every successor. | Terminal disposition must preserve expiry and non-authority without mutating the lease. |

## Smallest safe remediation sequence

No step is authorized merely because it appears here.

1. **Batch 1 — remediation evidence and disposition contracts.** Define separately versioned,
   authority-empty contracts for activation-principal provenance, transition interruption evidence,
   stranded-artifact terminal disposition, credential-reference exposure observation and
   process-loss custody evidence. Contract existence grants no authority.
2. **Batch 2 — offline interruption demonstrations.** Exercise the decision and issuance crash cuts
   without provider I/O; prove same-consumer convergence, expiry refusal and conflict behavior.
3. **Batch 3 — exact stranded-artifact disposition.** Seal immutable `QUARANTINED_EXPIRED_UNUSED`,
   `QUARANTINED_PENDING_REMEDIATION` or `RETIRE_CORRIDOR` decisions without mutating source records.
4. **Batch 4 — credential-reference boundary hardening.** Minimize clear-reference readers, remove
   clear references from generic metadata, and prove logs, exceptions and durable records exclude
   them. This batch must not redesign credential custody.
5. **Batch 5 — process-loss evidence.** Use an offline subprocess/restart harness to distinguish
   observed loss from declared unsupported custody without credential resolution or external I/O.
6. **Batch 6 — corridor disposition.** Imperator may then decide whether to retire the activation
   corridor, preserve it quarantined, or authorize a separate principal-provenance remediation.
7. **Future campaign selection only.** Credential-platform research may begin only if the corridor
   disposition names exact prerequisites and does not reinterpret old artifacts as authority.

## Preparation gate

Only Batch 1 is authorized: define the five remediation evidence and terminal-disposition contracts
without implementing a producer, consumer, principal installer, recovery service, quarantine
transition, credential hardening change or subprocess harness.

Provider Execution Assurance remains paused. The custody refusal, Iron Gate, Lazaretto, credential
resolution, provider invocation and external I/O boundaries remain closed.
