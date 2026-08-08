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

## retrieve-held-artifact
Fetch the exact authorized immutable version and bind its custody and provenance.

## record-custodial-state
Append an authorized versioned custodial-state transition.

## verify-custody-integrity
Compare recorded identity, digest, lineage, and authority fields; output the structural result and exact defects.

## release-held-artifact
Transmit the exact retrieved package and append a release receipt.

Every function fails closed on absent authority, ambiguity, prohibited state, stale identity, or integrity failure.
