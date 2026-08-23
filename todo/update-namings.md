# Update Institutional Namings

## Purpose

Normalize Imperium terminology so institutional Offices are not named after the cognition or Officer that occupies them.

## Confirmed naming

- [x] Rename the credential-custody Office from `Locksmith` to `Clavium`.
- [x] Retain `Locksmith` as the cognition/Officer belonging to the Clavium.
- [x] Preserve the Clavium domain: credentials, secrets, keys, access grants, rotation, revocation, and capability provisioning.
- [x] Retain `Armory` as the tool-custody Office.
- [x] Name `Armorer` as the cognition/Officer belonging to the Armory.
- [x] Preserve the Armory domain: tool custody, cataloging, validation, provisioning, restriction, and retirement.

## Repository-wide normalization

- [ ] Inventory every institutional name currently used interchangeably for an Office, Seat, cognition, Persona, or Officer.
- [ ] Classify each term before renaming it: Office, Seat, cognition, Persona, Profile, Officer, Manifestation, service, or runtime mechanism.
- [ ] Apply the same Office-to-Officer distinction consistently across doctrine, schemas, services, fixtures, tests, commands, UI text, diagrams, and TODOs.
- [ ] Update identifiers and artifact contracts without rewriting sealed historical records.
- [ ] Retain deprecated names only in historical artifacts or explicit migration mappings.
- [ ] Add compatibility readers or migrations wherever persisted artifacts still use the former names.

## Verification

- [x] Prove current artifacts emit `Clavium` as the competent Office and `Locksmith` as its cognition/Officer.
- [x] Prove current artifacts emit `Armory` as the competent Office and `Armorer` as its cognition/Officer.
- [ ] Search the repository for remaining ambiguous institutional names and classify every intentional exception.
- [ ] Run the relevant contract, workflow, and semantic-integrity tests after normalization.

The governing distinction is: the Office persists; its cognition, Persona, Profile, Officer, and Manifestation may change.
