---
inherits: [./doctrine.md]
---

# Conscription Mechanics

## accept-profile-derivation-authorization

The occupied ordinary Recruiter validates and accepts one exact Imperator-authorized Profile-derivation route. Validation binds the successful Garrison reservation, Curia request, immutable Mission Plan source, exact Persona and profession, Profile scope, jurisdiction assignments, and all record digests. The Recruiter then emits one derivation-only handoff request to the occupied Constable.

The acceptance stops at `PROFILE_DERIVATION_ACCEPTED_PENDING_CONSTABLE_HANDOFF_DISPOSITION`; the request stops at `PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION`. Neither record moves custody, hands off or retrieves the Persona, commissions Laboratorium, creates or installs a Profile, assembles a manifestation, invokes Senate, binds a Seat, deploys, or executes.

The Constable's later `APPROVED` disposition grants the Recruiter authority only to issue the next exact Laboratorium-commission request. It does not itself commission Laboratorium or transfer custody.

## commission-profile-derivation

The occupied ordinary Recruiter consumes one exact approved Constable derivation-lease disposition, revalidates its complete custody and authority chain, and issues one sealed `DERIVE_ONE_EXACT_MISSION_PROFILE` commission to `laboratorium.alchemist`. The immutable Profile scope and exact Persona are preserved without substitution or expansion.

The commission stops at `PENDING_ALCHEMIST_PROFILE_DERIVATION_COMMISSION_ACCEPTANCE`. Garrison retains custody, the Alchemist has not accepted, and no Profile artifact, approval, installation, manifestation, Seat binding, deployment, or execution exists.

## fulfill-canonical-authorship-resident

The ordinary Recruiter consumes one exact MasterMason construction commission, installs the admitted canonical Sanctographer or Chancellor Persona and current/active Profile on the generic officer substrate, verifies the declared qualification contract, and returns a sealed `QUALIFIED_UNBOUND` manifestation packet. This grants no authorship, subordinate-staff resolution, Seat-binding, acceptance, or execution authority.

These functions instantiate, preserve, package, and transport exact construction artifacts. They do not interpret Profiles, judge ordinary Officer qualification, manage spawning requests, bind occupants to Seats, or grant another Office's authority.

## register-construction-commission
Validate and preserve MasterMason's commission, originating requester when applicable, target Seat or mission destination, exact Profile version and digest, current/active designation, owning steward, structurally valid approval chain, requested cognition, correlation, and cited Charter basis.

## instantiate-generic-agent
Instantiate the authorized generic-agent substrate identified by a valid Officer, operative, or Recruiter-bootstrap case; output an immutable substrate-instance record.

## install-profile
Install the exact supplied Profile into the correlated generic-agent instance without rewriting, supplementation, or semantic transformation; output a Profile-installation record.

## register-examination-assembly-commission
Validate and preserve MasterMason's commission, Senate's originating request, exact pending-admission Persona version and digest, Foundry approval record, examination identity and contract, authorized destination, and correlation.

## commission-examination-profile
Transmit the exact pending-admission Persona and examination contract to Laboratorium with a commission limited to one `examination_only` Profile; preserve custody, identity, digest, and correlation.

## assemble-examination-packet
After receiving Laboratorium's exact returned Profile, bind it with the exact Persona, authorized generic-agent substrate, model and runtime configuration, applicable doctrine, permitted synthetic facilities, resource limits, Senate stand launch contract, expiry and disposal conditions, and a whole-packet integrity digest. Seal the result without activating a manifestation.

## deliver-examination-packet
Return the sealed examination assembly packet to MasterMason under the originating Senate correlation. Delivery transfers no live process, session, memory, tool connection, Seat, or operational authority.

## record-qualification-disposition
Preserve the occupied Recruiter Seat's attributable qualification disposition, findings, Profile version, substrate instance, and intended destination.

## seal-qualified-manifestation
Bind a successful qualification record to the constructed manifestation and its intended destination; output a manifestation construction record and sealed delivery packet. Packaging does not occupy a Seat or deploy an operative.

## deliver-qualified-manifestation
Transmit the qualified manifestation and exact construction record to MasterMason for verification and the separately authorized runtime transition; record delivery or bounded failure.

For a validated Guildhall summons, the ordinary Recruiter consumes exactly four MasterMason construction commissions, instantiates the shared immutable generic Officer substrate once per target, installs each exact canonical Guildhall Profile, verifies its Persona and lifecycle lineage, applies its qualification contract, and returns four sealed `QUALIFIED_UNBOUND` packets. These packets grant no Seat binding, Guildhall acceptance, mission work, or execution authority.

For a canonical Constable vacancy, the ordinary Recruiter consumes one exact MasterMason construction commission, instantiates the shared immutable generic Officer substrate, installs the exact Garrison-stewarded current/active Constable Profile, verifies the admitted Persona and lifecycle chain, applies the qualification contract, and returns one sealed `QUALIFIED_UNBOUND` packet. Qualification grants no Seat binding, inventory response, or execution authority.

For a canonical Artificer vacancy, the ordinary Recruiter consumes one exact MasterMason construction commission, instantiates the shared immutable generic Officer substrate, installs the exact current/active Artificer Profile, verifies its admitted Persona and lifecycle chain, applies the qualification contract, and returns one sealed `QUALIFIED_UNBOUND` packet. Qualification grants no Foundry construction authority, Seat binding, recipient acceptance, or execution authority.

## bootstrap-recruiter
When and only when MasterMason invokes the exact Charter-declared bootstrap transition and the resident Recruiter Seat is vacant, mechanically validate the exact Charter-recognized current/active Recruiter Profile and authorized generic substrate, instantiate the substrate, install that Profile, prepare the resulting Recruiter exclusively for the Recruiter Seat, and record its mechanical origin. MasterMason then verifies, binds, and activates Recruiter in the resident Recruiter Seat.

`bootstrap-recruiter` accepts no alternate Profile or Seat and contains no cognitive judgment. It cannot turn itself on. Any mismatch, ambiguity, missing version, invalid approval chain, invalid substrate, existing occupant, or attempted reuse fails closed.

Every function preserves exact identity, version, lineage, correlation, and disposition. Mechanical completion is not proof of ordinary cognitive qualification or authority outside valid occupancy or deployment.
