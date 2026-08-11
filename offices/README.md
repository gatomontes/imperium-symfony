# Offices

This directory is the Office-first doctrinal view of Imperium.

Every Office and Office-owned constituent inherits the constitutional canons
in [`imperium-doctrine.md`](../imperium-doctrine.md). Office doctrine may
specify or narrow those canons within its jurisdiction; it may not silently
contradict, waive, or enlarge them.

Each Office defines:

- `doctrine.md` — institutional purpose, jurisdiction, cognitive responsibilities, and boundaries
- `mechanics.md` — non-cognitive operations that move, preserve, correlate, version, structurally verify, and expose the Office's work
- `seat-*.md` — where Office authority is localized
- `profile-*.md` — cognition and qualifications required to occupy a Seat

Profile definitions are architectural source material. Runtime Profile
artifacts exchanged by Offices must conform to the shared
[`Profile Artifact Contract`](../contracts/profile-artifact.md).

Mechanics do not reason, interpret evidence, decide relevance, approve substance, or create authority. Those acts belong to an occupied Seat operating through its Profile. A mechanic may execute an already-made disposition; it may not make the disposition.

The Charter is Imperium's declarative authority, and MasterMason is its non-cognizant executable form. MasterMason bootstraps the primordial Offices, manages every spawning request, commissions qualified manifestations through Conscription, binds them to exact Seats, and manages declared routes. Imperator is the exceptional SuperAdmin principal and does not participate in ordinary operation. Offices steward their Profiles; Conscription verifies the complete Profile approval chain before installation; Garrison admits and lodges Personas only.

## Mechanical capability index

| Mechanical capability | Owning Office | Mechanic |
|---|---|---|
| Register a MasterMason construction commission | Conscription | `register-construction-commission` |
| Instantiate the generic-agent substrate and install an exact approved Profile | Conscription | `instantiate-generic-agent`, `install-profile` |
| Preserve qualification and seal a successful construction | Conscription | `record-qualification-disposition`, `seal-qualified-manifestation` |
| Deliver a qualified manifestation for MasterMason binding under Charter authority | Conscription | `deliver-qualified-manifestation` |
| Bootstrap the resident Recruiter through the sole mechanical spawning exception | Conscription | `bootstrap-recruiter` |
| Open and preserve a Persona-transformation case | Laboratorium | `open-transformation-case` |
| Bind requirements, target contract, and exact admitted Persona source | Laboratorium | `bind-transformation-inputs` |
| Version a derived artifact without mutating its source | Laboratorium | `version-derived-artifact` |
| Record Alchemist disposition and issue the derived-artifact packet | Laboratorium | `record-transformation-disposition`, `issue-derived-artifact-packet` |
| Open and version a mission dossier | Curia | `open-planning-proceeding`, `version-mission-dossier` |
| Present one authorized question and preserve its answer | Curia | `register-active-question`, `record-operator-answer` |
| Register Curial submissions and Seneschal dispositions | Curia | `register-curial-submission`, `record-seneschal-disposition` |
| Track mission dependencies and continuity | Curia | `track-dependency`, `assemble-succession-packet` |
| Bind an authorized Seneschal Seat transfer | Curia | `bind-seat-transfer` |
| Package and deliver an authorized Curial artifact | Curia | `package-authorized-delivery` |
| Open a profession-resolution case | Guildhall | `open-resolution-case` |
| Dispatch and correlate committee work | Guildhall | `dispatch-committee-assignment`, `record-committee-return` |
| Assemble attributed committee material | Guildhall | `assemble-committee-record` |
| Record and issue Guildmaster disposition | Guildhall | `record-guildmaster-disposition`, `issue-profession-packet` |
| Open and preserve a Seneschal suitability case | Guildhall | `open-executive-suitability-case` |
| Preserve findings and issue executive-suitability disposition | Guildhall | `record-executive-suitability-return`, `issue-executive-suitability-disposition` |
| Register, retrieve, and release held artifacts | Garrison | `register-custody`, `retrieve-held-artifact`, `release-held-artifact` |
| Query inventory and record custodial state | Garrison | `query-inventory`, `record-custodial-state` |
| Verify custody-record integrity | Garrison | `verify-custody-integrity` |
| Open and preserve a Persona production case | Foundry | `open-production-case` |
| Dispatch and correlate specialized commissions | Foundry | `dispatch-commission`, `record-specialized-return` |
| Bind and version candidate sections | Foundry | `bind-candidate-sections`, `version-candidate` |
| Dispatch for adversarial artifact review and correlate its return | Foundry | `dispatch-for-adversarial-review`, `record-adversarial-return`, `route-adversarial-return` |
| Record production disposition and issue release | Foundry | `record-production-disposition`, `issue-release-packet` |
| Open and preserve an evidentiary inquiry | Hagiography | `open-inquiry` |
| Register Chronicler assignments and returns | Hagiography | `register-assignment`, `dispatch-research-task`, `record-research-return` |
| Preserve provenance and bind attributed research | Hagiography | `preserve-evidence-record`, `bind-research-packet` |
| Submit and close an inquiry | Hagiography | `submit-research-packet`, `record-inquiry-disposition`, `close-inquiry` |
| Open and preserve a doctrine case | Studium | `open-doctrine-case` |
| Register, dispatch, and correlate specialized Notary work | Studium | `register-notary-assignment`, `dispatch-notary-assignment`, `record-notary-return` |
| Bind and version attributable doctrine sections | Studium | `bind-doctrine-sections`, `version-doctrine` |
| Record doctrine disposition and issue its packet | Studium | `record-doctrine-disposition`, `issue-doctrine-packet` |
| Correlate semantic amendments with revalidation duties | Studium | `register-revalidation-impact` |
| Open and preserve a manifestation-bound confirmation case | Senate | `open-confirmation-case` |
| Register the confirmation plan and Senator assignments | Senate | `register-confirmation-plan`, `register-senator-assignment` |
| Create and close sterile witness instances | Senate | `instantiate-sterile-witness`, `close-witness-instance` |
| Dispatch questions and preserve exact testimony | Senate | `dispatch-question`, `record-testimony` |
| Preserve findings and assemble the confirmation record | Senate | `record-senator-finding`, `assemble-confirmation-record` |
| Record disposition and issue the confirmation record | Senate | `record-senate-disposition`, `issue-confirmation-record` |

## Authority boundary

```text
authenticated Charter and compatible MasterMason
    ↓
Charter-bound MasterMason runtime authority
    ↓
Office doctrine
    ↓
Seat authority + Profile cognition
    ↓
Cognitive disposition
    ↓
Office mechanics
    ↓
Runtime implementation
```

Mechanical success proves only that an operation completed; it does not prove that the underlying judgment was correct or authorized.
