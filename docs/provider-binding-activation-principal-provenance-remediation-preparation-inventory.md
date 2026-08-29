# Provider Binding Activation Principal Provenance Remediation — Preparation Batch 0 inventory

## Result

`PREPARATION_BATCH_0_COMPLETE_CONSTITUTING_AUTHORITY_AND_PRINCIPAL_LIFECYCLE_ABSENT`

The activation-capable Imperator runtime principal has no canonical producer, source authority or
lifecycle. Tests and one offline demonstration create ad hoc records using the recognized
`imperium.imperator-runtime-principal/v1` schema, but runtime recognition is not constitution.
Adding `provider_binding_activation_authority` to another fixture would repeat the defect.

Operator Root is the only existing posture that can plausibly constitute the Imperator identity.
MasterMason may eventually be its mechanical producer, but cannot own or infer the authority. The
existing operator-root personnel installer cannot be reused: Imperator is not an Office seat, and
the founding-personnel installation window closes permanently at operationalization. Existing
instances therefore require a separately governed, single-use remediation authority; future
instances may use an initial root-establishment route. Neither route exists today.

Preparation changes no runtime behavior and grants no authority.

## Exact classification

| Requirement | Classification | Existing evidence | Exact gap and stop condition |
| --- | --- | --- | --- |
| Competent constituting authority | `ABSENT` | Operator Root installs founding personnel and seals operationalization. Imperator is the external authority owner, not a required Office seat. | No record authorizes constitution of the Imperator's runtime identity. Do not infer it from SuperAdmin status or repository access. |
| Mechanical principal producer | `ABSENT` | MasterMason drives bootstrap and v0 activation but produces no Imperator runtime principal. | MasterMason cannot choose identity or grant authority without one exact operator-originated constitution authority. |
| Runtime-principal schema | `EXISTS_FRAGMENTED` | Caller-authority services recognize `imperium.imperator-runtime-principal/v1`; tests and the offline interruption demonstration write compatible ad hoc records. | No separately versioned contract defines required fields, lifecycle, source authority or non-authorities. Recognition authenticates nothing. |
| Source installation authority | `ABSENT` | Operator-root personnel packages carry package digests and installation provenance. | Those packages install Officers or Operatives, not Imperator. Their window is permanently closed after operationalization. |
| Initial-instance constitution | `ABSENT` | Operator Root and MasterMason already establish one instance and close the root window. | No one-time Imperator identity-binding transition runs before the seal. It must remain separate from personnel installation. |
| Existing-instance remediation authority | `ABSENT` | Governed post-operational upgrades exist for personnel. | No one-time operator-originated authority permits adding the missing root principal without reopening founding installation. |
| Instance and operator-root binding | `EXISTS_FRAGMENTED` | Fixtures carry `instance_id`; operator-root records bind the instance and package digest. | Principal records do not bind the operationalization seal, operator-root identity, constitution decision or source digest. |
| Imperator identity and binding | `EXISTS_FRAGMENTED` | Recognized records carry `principal_id`, `binding_id` and `principal_generation`. | No canonical meaning, identity proof, uniqueness rule or relationship between principal and authenticated operator exists. |
| Activation-authority field | `EXISTS_FRAGMENTED` | Caller-authority issuance requires `provider_binding_activation_authority=true`. | No competent transition may set, narrow, renew or remove the field. Boolean presence is not provenance. |
| Lifecycle state machine | `ABSENT` | Issuer and consumer accept only `status=ACTIVE`. | Installation, pending activation, suspension, supersession, revocation, expiry and terminal retirement are undefined. |
| Freshness and expiry | `ABSENT` | Caller authorities expire within fifteen minutes. | The source principal itself has no `issued_at`, `effective_at` or `expires_at`. Short-lived downstream authority cannot repair an immortal source. |
| Generation semantics | `EXISTS_FRAGMENTED` | Issuer copies `principal_generation`; consumer requires the current generation and exact source digest. | No transition increments generation or explains supersession, rollover or historical validity. |
| Principal issuance replay | `ABSENT` | Immutable stores reject divergent writes at one path. | No principal identity derivation, one-winner constitution lock or semantic replay rule exists. |
| Caller-authority issuance replay | `EXISTS_FRAGMENTED` | Authority IDs bind source, transition, target and timestamps; immutable writes converge for one identical ID. | Different timestamps can create multiple live authorities for the same principal/transition/target. No issuance-winner index exists. |
| Caller-authority consumption | `EXISTS_CANONICALLY` | `AuthorityConsumptionStore` gives one consumer winner and same-consumer convergence. | This proves downstream consumption only; it does not validate how the principal was constituted. |
| Contention | `EXISTS_FRAGMENTED` | Caller-authority writes and consumption use filesystem locks. Batch 2 proves transition-cut contention. | No competing principal installers, renewers, suspenders or revokers exist to test. |
| Crash recovery | `EXISTS_FRAGMENTED` | Decision and issuance recovery converge after caller-authority consumption. | No constitution or lifecycle transition has interruption cuts, recovery ownership or conflict classifications. |
| Read-only reconstruction | `EXISTS_FRAGMENTED` | Caller consumption rereads the current principal by source ID and requires exact digest, generation, instance, binding and `ACTIVE` status. | No canonical reconstruction joins constitution authority, principal versions, lifecycle dispositions and downstream authorities. |
| Suspension and revocation | `ABSENT` | Replacing the current source with a changed digest makes old caller authority stale. | No competent suspension/revocation record, reason, effective time, recovery rule or historical index exists. File replacement is not governance. |
| Supersession and retirement | `ABSENT` | None. | No single-active-generation rule, successor binding or terminal state exists. Retirement cannot be inferred from inactivity. |
| Historical-principal interpretation | `ABSENT` | Downstream caller authorities retain source ID/digest and copied generation. | No rule distinguishes valid historical attribution from current exercisability after expiry, suspension, revocation or supersession. |
| Secret and credential exclusion | `EXISTS_FRAGMENTED` | Existing fixtures and caller-authority records contain authority flags and digests, not credential material. | No principal contract explicitly forbids credential references, secrets, provider authentication or serialized capabilities. |
| Hostile-writer authenticity | `DEFERRED_BOUNDARY` | Canonical JSON, unkeyed digest verification and immutable conflict checks provide trusted-writer integrity. | They do not prove authorship against a hostile filesystem writer. Signatures/MACs and independent custody remain separate work. |
| Multi-host principal consensus | `DEFERRED_BOUNDARY` | Deployment remains single-authoritative-root. | No distributed principal registry, quorum, fencing token or split-brain proof exists. |

## Exact producer, consumer, recovery and non-authority postures

| Posture | Exact responsibility | Must not do |
| --- | --- | --- |
| Operator Root | Own one exact, instance-bound Imperator-principal constitution decision and any one-time remediation authority. | Must not act as the runtime principal, issue activation caller authority, decide provider binding, handle credentials or invoke providers. |
| MasterMason | Mechanically validate and commit the exact authorized principal version. | Must not select the Imperator identity, infer authority from bootstrap, reopen personnel installation or grant broader authority. |
| Imperator principal lifecycle owner | Later exercise separately scoped renewal, suspension, revocation, supersession or retirement authority after constitution. | Must not rewrite history, self-widen scope, mint its own source authority or activate a provider. |
| Caller-authority issuer | Read one intact active principal generation and issue one exact expiring transition authority. | Must not install or repair the principal, infer missing provenance, or issue credentials. |
| Activation decision and issuance services | Consume exact caller authority for their existing targets. | Must not treat principal recognition as constitution or bypass lifecycle state. |
| Reconstruction posture | Join immutable constitution, principal version, lifecycle disposition and downstream consumption records read-only. | Must not reactivate, renew, revoke, issue caller authority or create successor authority. |
| Recovery owner | Recover only the exact interrupted principal transition under the original authority and semantic inputs. | Must not change identity, generation, scope, lifecycle outcome or timestamps to force convergence. |

## Two non-interchangeable constitution routes

1. **Future-instance root establishment.** One operator-originated constitution authority may permit
   MasterMason to bind the initial Imperator principal before operationalization seals. This is not
   personnel installation and grants no provider or credential authority by itself.
2. **Existing-instance remediation.** One separately governed operator-originated remediation
   authority may permit exactly one missing-principal constitution against the intact existing
   operationalization seal. It must not reopen the founding-personnel window or alter installed
   personnel.

The routes require the same canonical principal and lifecycle vocabulary but different source
authorities and replay domains. One may not substitute for the other.

## Smallest safe remediation sequence

No step is authorized merely because it appears here.

1. **Batch 1 — authority-empty constitution and lifecycle contracts.** Define separate versioned
   contracts for operator-originated constitution authority, Imperator runtime-principal versions
   and lifecycle dispositions. Define future-instance and existing-instance source-authority
   variants without implementing either.
2. **Batch 2 — validators and immutable stores.** Validate exact fields, source seals, scope,
   identity, instance, generation, lifecycle state, expiry and non-authorities. No producer yet.
3. **Batch 3 — offline transition interruption evidence.** Prove constitution, renewal,
   suspension, supersession, revocation and retirement cuts, replay, contention and reconstruction
   without installing a live principal.
4. **Batch 4 — future-instance root-establishment producer.** Only after separate authorization,
   adopt the initial route before operationalization sealing.
5. **Batch 5 — existing-instance remediation producer.** Only after separate authorization and
   operator evidence, adopt the one-time route without reopening personnel installation.
6. **Batch 6 — caller-authority issuer hardening.** Require the canonical principal contract and
   one issuance winner per principal generation, transition and target.
7. **Batch 7 — read-only reconstruction and lifecycle enforcement.** Make suspension, revocation,
   supersession, expiry and retirement mechanically visible to downstream consumers.
8. **Future campaign selection only.** Reconsider activation-corridor disposition only after real
   principal provenance and lifecycle evidence exist. Credential-platform work remains separate.

## Preparation gate

Only Batch 1 is authorized: define the three authority-empty constitution and lifecycle contracts
without implementing an authority producer, principal producer, installer, lifecycle transition,
validator, store, recovery service or runtime consumer.

The activation corridor remains policy-quarantined and operationally unusable. The custody refusal,
credential resolution, provider invocation, external I/O, Iron Gate and Lazaretto boundaries remain
closed. Provider Execution Assurance remains paused.
