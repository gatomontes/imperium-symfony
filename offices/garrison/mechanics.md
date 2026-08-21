---
inherits: [./doctrine.md]
---
# Garrison Mechanics

These functions maintain custody. They do not decide admission, qualification, suitability, selection, or permission to use.

## register-custody
Store exact identity, version, digest, disposition, owner, lineage, and custodial state.

For a subordinate Persona, the occupied Constable accepts only `CANONICAL_GUILDHALL_TO_GARRISON`: the exact Guildhall fulfillment receipt, Senate confirmation, candidate, and originating commission must agree. An attributable `ADMITTED` disposition atomically creates one `ADMITTED_HELD` custody record. Refusal creates none. Admission and custody create no execution authority.

## record-custody-refusal
Preserve the Constable's exact defects and return destination.

## query-inventory
Match recorded criteria without ranking or suitability inference.

Guildhall may route one sealed Profession Determination containing exact inventory questions and its attributable Guildmaster occupancy. Mechanics preserve that inquiry even while the Constable Seat is vacant, but status it `CONSTABLE_ACTIVATION_REQUIRED` and issue no authoritative inventory response. Only an exact active Constable occupancy may answer; neither an empty roster nor a filesystem scan may impersonate the vacant Seat.

Once the Constable Seat is actively occupied, the Constable may resume that exact pending inquiry and enumerate only valid `ADMITTED_HELD` Persona custody records from Garrison's ledger. An empty ledger is reported authoritatively as no admitted Persona custody records currently held; it is not interpreted as a finding about suitability. The sealed response is delivered to Guildhall with no ranking, selection, reservation, retrieval, spawning, or execution authority.

The resident Constable is standing institutional staff, not a mission-selected operative. A vacancy-blocked inquiry permits MasterMason to validate the exact versioned canonical Constable Persona, Profile, approval/current lifecycle chain, and qualification contract and open a non-authorizing provisioning case. That case grants no spawning, qualification, Seat binding, inventory response, or execution authority.

MasterMason may subsequently consume that exact ready case to issue one immutable construction commission to Conscription. The commission grants spawning authority only for instantiating and qualifying one canonical Constable manifestation against the exact Persona, current/active Profile, qualification contract, and generic Officer substrate. It grants no Seat binding, inventory response, or execution authority.

After Conscription returns one sealed `QUALIFIED_UNBOUND` Constable packet, MasterMason validates its exact commission chain, Persona, current/active Profile, qualification disposition, substrate, target instance, and authority restraints before atomically binding it to the vacant `garrison.constable` Seat. Valid occupancy activates Garrison's exact inventory, availability, admission, custody, and authorized reservation-disposition jurisdiction. It grants no professional selection or mission execution authority.

## decide-persona-reservation

The occupied Constable accepts only an exact Guildhall reservation request derived from an unchanged identity-bound Imperator authorization and Guildhall acceptance. It verifies the exact Persona against Garrison's admitted custody record, instance, custodial state, availability, and existing reservation ledger. Success records `RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION`; factual failures record not-admitted, unavailable, already-reserved, or disposition-mismatch refusals. Garrison cannot select or suggest a replacement. Reservation preserves custody and grants no retrieval, Profile derivation, manifestation, Seat-binding, deployment, or execution authority.

## retrieve-held-artifact
Fetch the exact authorized immutable version and bind its custody and provenance.

## record-custodial-state
Append an authorized versioned custodial-state transition.

## verify-custody-integrity
Compare recorded identity, digest, lineage, and authority fields; output the structural result and exact defects.

## release-held-artifact
Transmit the exact retrieved package and append a release receipt.

Every function fails closed on absent authority, ambiguity, prohibited state, stale identity, or integrity failure.
