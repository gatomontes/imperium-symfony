# Delegate mission flow

## Governing taxonomy

- `Officer` is the umbrella.
- `LEGATE` identifies a permanent Office-bound Officer.
- `DELEGATE` identifies a temporary examination-, proceeding-, commission-, or mission-bound Officer.
- Classification grants no authority.

Curia states mission capabilities but never chooses a profession or Persona. Guildhall resolves profession and Persona suitability. Garrison owns custody and availability facts. Conscription assembles and qualifies but does not select personnel. Imperator decides protected personnel, Profile, deployment, resource, perimeter, and action commitments.

## Implemented terminal flow through Step 69

### Demand and personnel resolution

1. Curia seals the exact mission-bound capability demand from the approved Mission Plan and governing source.
2. Guildhall accepts or refuses demand intake.
3. Guildhall resolves profession and exact Persona suitability against Garrison facts.
4. Curia presents the unchanged identity-bearing personnel-use request with its sealed institutional decision surface.
5. Imperator authorizes or records one of the existing non-authorizing dispositions for the exact personnel-use commitment; the existing judgment is accompanied by its sealed defensible decision record.
6. Guildhall accepts the authorization and requests exact Garrison reservation.
7. Garrison reserves or refuses the exact Persona while retaining custody.

### Profile scope, derivation, and examination preparation

8. Curia constructs the immutable Delegate Profile-scope request.
9. Imperator authorizes or refuses the exact Profile scope.
10. Conscription accepts and requests a custody-bound Laboratorium derivation commission.
11. Laboratorium accepts or refuses the commission.
12. Laboratorium derives and returns one sealed Profile candidate.
13. Conscription accepts or refuses candidate intake.
14. Conscription constructs the Senate examination-preparation handoff.
15. The Lord Speaker accepts or refuses examination preparation.
16. Conscription assembles and delivers an examination-only Delegate Manifestation.
17. The Bailiff admits or refuses the Manifestation at the secured Senate Stand.
18. The Lord Speaker opens one bounded hearing contract.

### First trust-question leg

19. The Lord Speaker issues one identity-bound trust-question commission.
20. The exact trust Senator accepts or refuses the commission.
21. The accepting Senator authors and seals one bounded trust question.
22. The Lord Speaker authorizes or refuses dispatch.
23. The exact Bailiff dispatches the sealed question unchanged.
24. The examination-only Manifestation seals one structured trust response.

### Security-question leg

25. The Lord Speaker issues one identity-bound security-question commission to the exact occupied security Senator.
26. The exact security Senator accepts or refuses the commission.
27. The accepting Senator authors and seals one bounded security question.
28. The Lord Speaker authorizes or refuses dispatch.
29. The exact Bailiff dispatches the sealed security question unchanged.
30. The examination-only Manifestation seals one structured security response.

### Usability-question leg

31. The Lord Speaker issues one identity-bound usability-question commission.
32. The exact usability Senator accepts or refuses the commission.
33. The accepting Senator authors and seals one bounded usability question.
34. The Lord Speaker authorizes or refuses dispatch.
35. The exact Bailiff dispatches the sealed usability question unchanged.
36. The examination-only Manifestation seals one structured usability response and three-jurisdiction testimony readiness.

### Independent findings leg

37. The Lord Speaker opens three separate identity- and jurisdiction-bound finding authorities.
38. Each exact Senator independently seals one finding; completion seals panel readiness without opening deliberation.

### Deliberation and reconciliation leg

39. The Lord Speaker admits the three findings unchanged and opens bounded reconciliation.
40. Tool-less cognition reconciles them without voting, aggregation, mutation, or disposition.

### Senate disposition leg

41. The Lord Speaker opens one bounded disposition authority without authoring a verdict.
42. The Lord Speaker seals one attributable disposition; the mandatory Security block mechanically prohibits approval.

### Imperator Profile decision

43. Imperator independently approves or records a non-approving disposition. Approval opens only one exact Conscription operational-qualification request.

### Operational construction

44. Conscription qualifies and installs the exact approved operational Profile.
45. Conscription assembles one operational Delegate Manifestation on generic Officer v0.
46. Conscription atomically binds it to the immutable mission Seat while leaving it inert.

### Deployment and custody

47. The Seneschal authorizes or refuses the exact bounded deployment.
48. The Constable independently transitions custody to deployed-and-bound while leaving the Delegate inactive.

### Runtime activation

49. Conscription mechanically revalidates the exact generation-1 binding and live deployed custody, activates the runtime, and opens only one exact Seneschal mission-control intake authority.
50. The exact occupied Seneschal accepts, refuses, returns, or defers the unchanged active Delegate mission-control intake. Acceptance opens only one bounded-cognition commission-construction authority.
51. The exact occupied Seneschal constructs one sealed, single-iteration cognition-only commission directly from the unchanged mission use without invoking it or releasing resources.
52. Curia mechanically assesses exact resource and invocation readiness, preserves the resource requirements, detects the absent model binding, and opens only an Oracle model-requirement commission authority.
53. The Seneschal presents explicit proposed model-selection criteria to Imperator.
54. Imperator authorizes, amends-and-authorizes, refuses, returns, or defers the exact criteria.
55. The Seneschal issues the exact authorized commission against one pinned Oracle catalogue snapshot.
56. The exact occupied Augur accepts the unchanged commission against the still-current snapshot.
57. Oracle freezes the candidate universe and opens one evidence-bound eligibility authority per included model.
58. The Augur seals one independent evidence-bound eligibility finding per frozen candidate.
59. Oracle seals a comparative assessment without aggregate scoring, ranking, or a winner.
60. The Augur issues one attributable recommendation while retaining no selection authority.
61. The exact occupied Seneschal selects one frozen eligible model, rejects all candidates, or returns a new commission.
62. The ordinary Recruiter seals the exact selected model and configuration to the Delegate mission target and turn 1.
63. Clavium attests expiring access to the exact bound provider/model without releasing credentials.
64. Imperator authorizes only the attested model and frozen turn-one requirements.
65. Clavium creates one expiring, single-use credential lease and exact provider activation.

### Cognition and terminal return

66. Citadel invokes the exact sealed Symfony AI platform/runtime model once, consumes the lease and turn authority, and seals the bounded result.
67. The exact occupied Seneschal disposes the result consistently with its completed, stopped, or failed provider disposition and opens no continuation.
68. The Seneschal separately authorizes only the Profile's predeclared return, unbinding, custody-restoration, and retirement contract.
69. Garrison consumes the terminal authority, restores the Persona to available `ADMITTED_HELD` custody, unbinds the mission Seat, and retires the temporary Delegate Manifestation.

## Terminal checkpoint

`DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL`

The Persona is again held and available in Garrison. The mission Seat is unbound and the temporary Manifestation is retired. No cognition, provider, credential, tool, perimeter, external-action, execution, continuation, redeployment, or reuse authority survives.

## Terminal operational-evidence verification

The read-only operational-evidence audit verifies the persisted operational lineage and live terminal state without replaying side effects. It is not a comprehensive audit of all 69 lifecycle steps:

```bash
php bin/console imperium:delegate:audit-operational-evidence <terminal-id>
php bin/console imperium:delegate:audit-operational-evidence <terminal-id> --json
```

It validates exactly fourteen digest-bound records from terminal retirement back through return, result disposition, cognition, provider activation, model access and binding, bounded commission, runtime activation, custody transition, deployment, and the current terminal binding/custody state. Pre-deployment governance Steps 1–52 are explicitly outside this audit's completeness claim. The former command name remains only as a compatibility alias.

## Runtime-integrity hardening status

The separate runtime-integrity hardening lifecycle is complete through Hardening Step 35. It did not create Delegate Mission Step 70 or reopen the terminal Delegate.

The critical runtime corridors now enforce:

- broker-only credential resolution behind an exact consumed Clavium lease;
- durable, single-winner invocation claiming before provider I/O;
- stable provider idempotency identity and fail-stopped unknown outcomes;
- immutable response envelopes and provider-free forward recovery;
- shared atomic transition, immutable-record, mutable-state compare-and-swap, authority-consumption, replay-fingerprint, and reference-validation primitives;
- recoverable operational construction, deployment custody, and terminal retirement transitions;
- exact replay rejection when authoritative input changes;
- canonical validation across the Citadel, Clavium, deployment, and terminal-audit corridors;
- a strict DeepSeek-only runtime adapter and model-configuration contract; and
- an explicitly bounded fourteen-record terminal operational-evidence audit.

The operator reported the complete local PHPUnit suite clear after Hardening Step 34. Live provider-bypass, retained unknown-outcome recovery, repeated crash/concurrency, and production evidence capture remain operational evidence gates, not additional implementation steps.

Canonical references:

- authority consumption: `docs/delegate-mission-authority-consumption-matrix.md`;
- record schemas: `docs/delegate-mission-record-schema-catalogue.md`;
- terminal audit: `docs/delegate-mission-terminal-operational-evidence-audit.md`;
- hardening closeout: `docs/handoffs/runtime-integrity-hardening-leg-complete.md`; and
- residual evidence backlog: `todo/blackquill-todos.md`.

## Cleanup closure and next evidence program

The severe-source cleanup gate is closed at merged commit `20208f177df576b863340ee397730b455b2965df`. The final audit reread all 376 runtime PHP files and found zero files larger than 500 bytes at ten physical lines or fewer. Cleanup Batches A and B passed explicit local PHP lint and the complete PHPUnit suite. Secondary long-line and adjacent-declaration style debt remains recorded without being misrepresented as a runtime-integrity failure or a PSR-12 claim.

The next work is a separate operational-evidence program. It does not create Delegate Mission Step 70, Hardening Step 36, new mission authority, or surviving Delegate authority.

1. **Crash Demonstration 1 — operational construction recovery:** inject interruption around the Steps 44–46 Codex/Folia transition, resume, prove one ordered generation-one Codex, immutable Folia, exact replay, and no deployment or cognition authority.
2. **Crash Demonstration 2 — deployment custody recovery:** inject interruption around deployment authorization, custody compare-and-swap, transition Folium, and runtime activation boundary; resume to one deployed-and-bound inactive state without duplicate mutation or leaked authority.
3. **Crash Demonstration 3 — unknown provider-outcome recovery:** preserve an in-flight unknown outcome, prove automatic reinvocation is prohibited, then recover only from a sealed response envelope under one exact consumed recovery authorization with `provider_reinvoked=false`.
4. **Crash Demonstration 4 — terminal retirement recovery:** inject interruption after each terminal checkpoint and resume to one terminal record, restored Persona custody, retired binding, and no continuing authority.

Crash Demonstration 1 is implemented by the repeatable local command and evidence contract in `docs/crash-demonstration-1-operational-construction-recovery.md`. Private retained evidence remains local and uncommitted; only its sanitized summary shape is documented.

Crash Demonstration 1 has operator-retained proof against source commit `8cfcef92b5d5cf7396ad147ee2ea4191d7354159`. Crash Demonstration 2 is implemented by the repeatable command and evidence contract in `docs/crash-demonstration-2-deployment-custody-recovery.md`; it stops before runtime activation.

Crash Demonstration 2 has operator-retained proof against source commit `9633ef0239c0dc7fbaf122753f76ffe35c47875d`. Crash Demonstration 3 is implemented by the repeatable command and evidence contract in `docs/crash-demonstration-3-unknown-provider-outcome-recovery.md`; unknown outcomes remain non-replayable and sealed-response recovery has no provider dependency.

Crash Demonstration 3 has operator-retained proof against source commit `bd3620ccd32e1511c96d53caacb60806348cf995`. Crash Demonstration 4 is implemented by the repeatable command and evidence contract in `docs/crash-demonstration-4-terminal-retirement-recovery.md`; it converges every existing terminal checkpoint on restored custody, retired binding, one terminal Folium, and zero surviving authority.

Crash Demonstration 4 has operator-retained proof against source commit `598cbcdf749fc804b979a2ddfb310bf025b385b2`. The bounded four-demonstration crash-evidence program is complete. This closure creates neither Delegate Mission Step 70 nor Runtime Integrity Hardening Step 36. The next separate operational-evidence target is proof that direct provider invocation without a valid Clavium lease is impossible.

Each demonstration must produce a repeatable local command, machine-readable retained evidence, explicit assertions, and a sanitized external summary that reveals the property proved without disclosing proprietary runtime topology.

## Separate next lifecycle: Operational Cognition Access

The terminal Delegate flow above remains closed at Step 69. The following sequence belongs to a new, separately bounded lifecycle and must not be numbered as Delegate Mission Step 70 or Runtime Integrity Hardening Step 36:

1. Curia authorizes one bounded internal execution iteration.
2. Imperator separately authorizes or refuses the exact provider/model resource expenditure.
3. Clavium validates that decision and issues one opaque, expiring lease.
4. A durable invocation claim consumes that lease and the cognition authority atomically.
5. The broker constructs the provider adapter for that call only.
6. The Manifestation receives output, never credentials or network authority.

These boundaries preserve the existing constitutional split. Curia determines that one internal iteration is permitted but grants no credential or network authority. Imperator makes the independent resource-expenditure decision. Clavium validates and leases access without selecting or approving the expenditure. The durable claim is the sole pre-I/O consumption point. The broker alone resolves credentials and creates the short-lived provider adapter. The Manifestation receives only the sealed result.

The implementation order, record bindings, failure matrix, and continuation prompt are canonicalized in `docs/handoffs/operational-cognition-access-lifecycle-ready.md`. System-wide credential-boundary proof remains blocked until every direct platform-bound agent is migrated and the shared environment-backed platform is removed.

## Current campaign frontier

This flow remains terminal at Delegate Mission Step 69. Runtime Integrity Hardening is terminal at
Step 35, credential-boundary remediation at Batch 17, Institutional Decision Integrity at Batch 6,
and Continuous Agent Governance Controls at Batch 16. None created Delegate Mission Step 70 or
reopened authority inside this closed route.

The separate Operational Cognition Lease Interruption campaign is terminal through Batch 6, as
defined in `docs/next-campaign-operational-cognition-lease-interruption.md` and the active handoff
`docs/handoffs/operational-cognition-lease-interruption-campaign-complete.md`. Preparation, the exact
source-authorizer `INTERRUPT` disposition, one single-use Locksmith authority, and native
admission-result enforcement and rotation-safe read-only nine-artifact reconstruction exist. Batch
6 additionally proves validate-before-select admission, strict timestamps, and complete canonical
replay equivalence.

The next separately selected campaign is Transactional Authority Consumption Adoption, governed by
`docs/next-campaign-transactional-authority-consumption.md` and
`docs/handoffs/transactional-authority-consumption-batch-12-complete.md`. Preparation Batch 0, the
separately versioned Batch 1 contracts, and the first operational cognition claim adoption in Batch
2 are complete. Batch 3 proves the adopted claim at every internal recovery observation without
adding a second transaction record or moving provider I/O. Batch 4 adopts the structurally parallel
governance cognition claim without weakening resolver identity, changing lock order, or moving the
provider journal. Batch 5 adopts the Delegate provider claim under its unchanged composite lock
and keeps provider I/O and recovery outside the transaction. Batch 6 adopts the eight deterministic
Delegate Senate consumers from Steps 19–42 under exact authority locks without merging
jurisdictions, actors, or result schemas. Five cognition-bearing consumers remain
`RECOVERY_INCOMPLETE` because their model-call outcome cannot truthfully be reconstructed by a post-I/O
transaction envelope.
Batch 7 adopts only the three model-bound Profile Senate opening consumers with an existing exact
single-use authority ID, Lord Speaker, immutable source, `opened_at`, and one-result commit. Legacy
boolean-authority records, deterministic multi-write or missing-timestamp paths, and all Profile
Senate cognition remain outside adoption. Batch 8 adopts only operational-adoption reconciliation
and final disposition under exact authority locks. Governing intake has no canonical single-use
authority identity, while independent assessment still has an uncheckpointed assessment-to-panel-
completion write boundary. Batch 9 adopts only the Delegate model-criteria request and model-
selection decision authorities whose native results preserve exact instance, source, actor and
timestamp identity. Boolean, multi-write, missing-instance, external research, construction,
credential and provider-admission paths remain outside adoption.
Batch 10 adopts only the exact Delegate model-binding sealing authority. Older construction and
admission powers are boolean, missing-time, or multi-write; Clavium access/activation remains a
deferred credential-platform boundary, and the intervening resource decision is unchanged.
Batch 11 adopts Oracle eligibility finding consumption plus deterministic forward recovery of the
dependent phase closure under one evaluation-case lock. Other recovery-incomplete paths remain
explicit exclusions. Batch 12 mechanically reconstructs all runtime coverage, freezes 231 strong
authority candidates, verifies the exact 26 canonical and 3 locked-fragmented consumers, and
records the adversarial limits without changing runtime behavior. Batch 13 remains unopened pending explicit authorization.

The campaign does not alter this route or any credential, provider-journal, external-I/O,
propagation, telemetry, containment, incident, Iron Gate, Lazaretto, sortie, or credential-platform
boundary.
