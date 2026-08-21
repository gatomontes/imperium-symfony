---
inherits: [./doctrine.md]
---
# Guildhall Mechanics

These functions support Guildhall cognition. They do not determine professions, compose the queue, challenge boundaries, or adjudicate recommendations.

## demand-runtime-activation

When an exact planning commission has reached Guildhall's inbox but the four resident Seats are unavailable, preserve a deterministic activation demand to MasterMason. The demand names the Guildmaster, Disciplinary-Fit, Composition, and Boundary-Challenge Seats and their Guildhall-owned Profile Definitions. Guildhall's profile-registry mechanic verifies each exact immutable definition version, source digest, Imperator approval, and current designation. Definitions remain non-installable inputs: missing admitted Persona selections, Laboratorium-derived Profile artifacts and lifecycle attestations, Conscription qualification packets, or Seat bindings remain explicit blockers. The demand grants no spawning, qualification, binding, acceptance, or execution authority.

## open-provisioning-case

MasterMason may mechanically validate that exact activation demand and open one deterministic provisioning lane per required Seat. Guildhall is the standing Office; its Guildmaster and committee members are canonical institutional staff whose work does not vary by mission. Their admitted Personas, current Profiles, and qualification chains are prepared and versioned institutionally rather than selected or derived anew for each mission.

When the personnel question arises in Curia, the Seneschal requests Guildhall activation and the Chamberlain records and routes that summons. MasterMason validates the request, commissions Conscription to instantiate the exact current qualified Guildmaster and committee manifestations, and binds them to the four Guildhall Seats. The Guildmaster may accept the waiting commission only after valid occupancy. Neither the Seneschal nor Chamberlain selects Guildhall occupants, constructs manifestations, or acquires Guildhall jurisdiction; MasterMason performs no cognition and Conscription performs no personnel selection.

Opening a provisioning case records this summoning rule and any missing canonical staff artifacts. It grants no spawning, acceptance, or execution authority.

The canonical staff package binds four exact Garrison-admitted Persona versions, their Laboratorium-derived Officer Profiles, competent approval attestations, Guildhall current/active designations, and immutable file digests. The package is versioned institutional material, not a live staff body. A summons still requires fresh Conscription manifestations, qualification, and MasterMason Seat bindings for the current Imperium instance.

After Conscription returns the four sealed `QUALIFIED_UNBOUND` packets, MasterMason validates them as one exact cohort against the summons, canonical staff package, immutable generic Officer substrate, qualification dispositions, target Seats, and vacant generation. It commits all four Seat bindings in one atomic occupancy record or commits none. The resulting occupants are bound pending Guildmaster acceptance of the waiting planning commission; binding does not itself record recipient acceptance or grant mission execution authority.

The bound Guildmaster may accept only the exact delivered planning commission whose digest and proceeding lineage agree with the activation demand, summons, and atomic occupancy cohort. Acceptance is attributable to the Guildmaster manifestation and occupancy generation. It admits the commission for Guildhall's institutional deliberation and authorizes the bounded Personnel Disposition requested by that commission; it grants no spawning, Seat binding, tool activation, target access, or mission execution authority.

After acceptance, Guildhall validates the `CAPABILITY_TO_PROFESSION` boundary before cognition begins. The source must contain functional `capability_requirements`, identify `guildhall.guildmaster` as the exclusive translation authority, and withhold profession-selection and Persona-selection authority from Curia. Missing or contradictory boundary claims fail closed.

The Disciplinary-Fit, Composition, and Boundary-Challenge committee occupants then deliberate independently over the exact commissioned Mission Plan. Guildmaster translates the source skills, attributes, capabilities, constraints, and expected outcomes into a sealed Profession Determination containing required professions, exemplar criteria, team composition, boundary controls, and exact Garrison inventory questions. From this boundary downward, the lifecycle speaks in professions and Personas. Because suitability is a claim about available admitted Personas and personnel, this determination explicitly remains short of a final Personnel Disposition until Garrison returns exact inventory facts.

When the active Constable returns an authoritative empty admitted-Persona custody ledger, Guildmaster may issue a final Personnel Disposition identifying every required profession as an unresolved personnel gap. Guildhall may route one exact Persona-construction demand per gap to Foundry, but the demands remain `PENDING_CURIA_CONSTRUCTION_AUTHORIZATION`: they grant no Persona or exemplar selection, construction, spawning, Seat binding, or execution authority.

Each committee disposition is durably checkpointed before the next Seat is invoked. The proceeding exposes Seat-level progress, applies bounded provider request durations, and resumes from the last valid checkpoint after interruption without repeating completed cognition. Guildmaster synthesis begins only after all three exact committee records are sealed.

## summon-canonical-staff

The exact Seneschal-issued Guildhall planning commission constitutes the bounded activation request when it reaches a dormant Guildhall and the personnel question is present. Chamberlain records and routes the derived summons. MasterMason validates current Seneschal and Chamberlain occupancies, the immutable commission and delivery chain, the ready provisioning case, and the exact canonical staff package. It may then issue four single-purpose Conscription construction commissions. Each permits one fresh manifestation to be instantiated and qualified for one exact Guildhall Seat; it grants no Seat binding, Guildhall acceptance, mission work, or execution authority.

## open-resolution-case
Preserve exact Curia inputs, versions, provenance, authority, and correlation in a resolution-case record.

## commission-subordinate-construction

Accept the exact Curia-delivered personnel authorization and issue one immutable construction commission to `foundry.artificer`, bound to the complete ordered resolution set and authorization digest. This is the sole canonical subordinate-construction route into Foundry: Curia cannot bypass Guildhall. Issuance makes construction authority available for attributable Foundry acceptance but grants no Persona selection, admission, spawning, Seat binding, or execution authority.

## accept-persona-fulfillment

The occupied Guildmaster accepts only the exact Senate-confirmed candidate returned by Foundry as fulfillment of Guildhall's originating construction commission. Acceptance binds the Foundry fulfillment, Senate record, candidate, and commission identities without reinterpretation or substitution and grants no admission or execution authority.

## forward-persona-to-garrison

Guildhall forwards the accepted fulfillment unchanged to `garrison.constable` under `CANONICAL_GUILDHALL_TO_GARRISON`. A missing Guildhall receipt, Senate record, candidate match, or originating commission match fails closed. Forwarding requests a Constable disposition but grants Guildhall no admission or custody authority.

## dispatch-committee-assignment
Route a Guildmaster-authorized bounded assignment to the named Committee Seat and record attribution.

## record-committee-return
Preserve the exact return with Seat, Profile version, author, sources, and case identity.

## assemble-committee-record
Bind attributable returns without reconciling their meaning; output a committee record for Guildmaster review.

## record-guildmaster-disposition
Preserve the exact Guildmaster disposition, rationale, contributions, version, and authority.

## issue-profession-packet
Package and transmit the exact admitted Profession Determination Packet and Personnel Disposition to Curia.

## open-executive-suitability-case
Preserve the exact Seneschal Suitability Demand, mission mandate, Planning Authorization, commission, Garrison inventory facts, and candidate references without interpreting them.

## record-executive-suitability-return
Preserve the exact attributable axis findings returned by the qualified Executive-Suitability Committee occupant.

## issue-executive-suitability-disposition
Package and transmit the exact Guildmaster disposition, evaluated candidates, evidence, mismatches, uncertainty, construction estimate, and revision conditions to Curia.

Every function fails closed on missing authority, attribution, identity, version, integrity, or correlation.
