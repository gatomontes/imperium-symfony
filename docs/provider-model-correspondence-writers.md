# PPC5 issuer, consumer and writer inventory

The approved [v1 provision](../contracts/formation-model-preparation-v1.md)
adds no command, service wiring, provider integration or assignment authority.
The configured Formation journal, signatures, clock and native institution are
fixed constructor dependencies; ingress cannot choose a verifier, root or key.

| Actual boundary | Publication / competence |
| --- | --- |
| `FormationModelPreparation::initialize` | One signed expected-head transition initializes the optional v1 extension. Existing affected work refuses. No model authority is initialized implicitly. |
| `prepareBinding` | Reads native lineage and publishes a factual immutable candidate, exact H originals and producer-calculated generation. It cannot seal or approve a Profile. |
| `sourceOriginal` | Factual read of complete retained signed source evidence under Formation; constructs its deterministic typed original without publication or continuing-authority claim. |
| `delegate` | Separately verifies `DELEGATE_FORMATION_MODEL_SEALING`, exact current Recruiter incarnation/key/context/binding and finite time. |
| `authorize` | Separately verifies `AUTHORIZE_FORMATION_MODEL_PREPARATION`, the actual retained Laboratorium source, exact specification/line/binding/delegation and one-use terms. |
| `sealingPayload` | Read-only signing preparation under Formation; checks current originals. The returned bytes confer no authority and are fully rechecked on publication. |
| `seal` | Actual Conscription Ed25519 act, domain/effect separated from owner and personnel evidence. Checks current native tenure, signatures, originals, nonces, head and immutable Profile version. Commits seal and consumption in one journal frame. |
| `verifyInOwner` | Fixed internal current correspondence verifier, reading fresh same-root journal custody through a live owner frame. No lock acquisition or detached currentness certificate. |
| `FormationPersonnel::recordAuthorizedModelBoundProfile` | Real strict v2 Laboratorium ingress; verifies its own current institutional signature and calls the correspondence verifier before writing evidence. |
| `authorizedModelCandidateInOwner` | Real strict v2 lifecycle consumer; requires the new schema, exact own four findings/reconciliation/approval/qualification and current correspondence. PPC2 v1 evidence refuses here. |
| Existing `candidateInOwner` | Preserves v1 meaning; if encountering v2, independently performs the same stronger verification. The explicit strict method never falls back to v1. |

All new state writers are the five `FormationModelPreparation` transitions above
and the existing personnel journal transition for new v2 evidence. Each enters
Formation once through `FormationJournal::changeAtHead` or `change`. Verification
within those transitions passes the actual `FormationOwnerFrame`; it never calls
an upward acquisition from a lower storage callback. Current native observation
uses `FormationInstitution::actorInOwner`, including exact complete native
installation packages and unsupported-successor refusal. Accepted R2 order stays
**Formation → native → bootstrap/target storage**.

There are no new generic storage calls or file writers in production PPC5 code.
Complete H ancestry is resolved from bounded retained originals in dependency
order, never through an external resolver or callback. Authorization retains the
complete typed source Profile evidence and verifies its exact journal custody;
the Conscription seal references that same typed original.
The shared journal is the only publication point. AtomicTransition, all three
generic storage APIs and their approved successor, FormationJournal/OwnerFrame,
old snapshot ledgers and the old model-bound contract remain byte-identical.
Process tests exercise the accepted owner-aware generic CAS retirement path as a
competing native occupancy writer; it is not an alternate PPC5 authority issuer.

Historical replay returns only an exact retained original. Changed bytes conflict;
current consumers still check expiry, decision revocation, trust, generation and
native tenure. Retained receipts never imply continuing permission. The test-only
clock and rename barriers control timing in disposable processes, supply no
owner/authority and call the real publication operation.

| Compatibility surface | Result |
| --- | --- |
| Generic Profile and `model_binding` fields | Unchanged; source version and SUPERSEDES are mechanically derived in the new specialization. |
| PPC2 v1 model-bound evidence | Same deliberately structural meaning and original tests. Cannot satisfy strict v2 ingress/lifecycle. |
| Mission/delegate/tool seals | Existing code and scope unchanged. Wrong new-purpose or old-schema originals refuse strict correspondence. |
| O4 H/Policy/Rules/effects/configuration/provider/fee policy | Unchanged. Complete candidate/configuration H originals are preserved; no admission or selected tuple is treated as native authority. |
| Profile approval and qualification | Existing exact jurisdiction and separate full evidence chain; no generic owner model approval. |
| Appointment, designation, settings/application/use | No PPC5 implementation or wiring. v2 is a preparation/lifecycle prerequisite only. |
| FRESH and production ports | Root/schema fences and MissingAssignmentEvidence remain; no provider, account, base or cognition production readiness. |

Scope remains cooperating current binaries, configured canonical roots and fixed
filesystem topology, as accepted in R2. No installed state or real credentials
were read. Ephemeral fixture keys sign actual implemented acts; external model
facts remain separately synthetic/unverified.
