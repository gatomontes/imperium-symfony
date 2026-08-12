---
inherits: [./doctrine.md]
---
# Garrison Mechanics

These functions maintain custody. They do not decide admission, qualification, suitability, selection, or permission to use.

## register-custody
Store exact identity, version, digest, disposition, owner, lineage, and custodial state.

## record-custody-refusal
Preserve the Constable's exact defects and return destination.

## query-inventory
Match recorded criteria without ranking or suitability inference.

Guildhall may route one sealed Profession Determination containing exact inventory questions and its attributable Guildmaster occupancy. Mechanics preserve that inquiry even while the Constable Seat is vacant, but status it `CONSTABLE_ACTIVATION_REQUIRED` and issue no authoritative inventory response. Only an exact active Constable occupancy may answer; neither an empty roster nor a filesystem scan may impersonate the vacant Seat.

## retrieve-held-artifact
Fetch the exact authorized immutable version and bind its custody and provenance.

## record-custodial-state
Append an authorized versioned custodial-state transition.

## verify-custody-integrity
Compare recorded identity, digest, lineage, and authority fields; output the structural result and exact defects.

## release-held-artifact
Transmit the exact retrieved package and append a release receipt.

Every function fails closed on absent authority, ambiguity, prohibited state, stale identity, or integrity failure.
