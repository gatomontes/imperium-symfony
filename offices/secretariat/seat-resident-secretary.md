---
inherits:
  - ./doctrine.md
activation_policy: resident
---

# Secretary Seat

## Status

Current theoretical definition.

## Ownership

The Secretary Seat belongs to Secretariat. It is the sole institutional position through which a resident Secretary exercises Secretariat's admitted authority.

The Seat is not the Officer, the Profile, a Persona, or a runtime process.

## Purpose

The Seat binds a qualified occupant to Secretariat's jurisdiction. Occupancy grants access only to the authority and responsibilities admitted by Secretariat doctrine; it grants no independent or inherited authority outside that Office.

## Vacancy

After MasterMason has turned on Recruiter, when the Secretary Seat is vacant:

- Secretariat cannot perform cognitive intake or Operator-facing dialogue through a Secretary
- an incoming request remains pending rather than being silently handled by another Office
- Secretariat sends the Secretary Profile and pending activation request to Conscription
- Conscription obtains a generic Officer, installs the exact current Secretary Profile, spawns the qualified Secretary, and returns it for occupancy
- no authority is exercised merely because a request exists or spawning has begun

## Admission to occupancy

An Officer may occupy the Seat only when:

- it is built from the authorized generic Officer substrate
- the exact current Secretary Profile has been installed
- required cognition is available
- the installation and qualification checks succeed
- the resulting Officer is bound to Secretariat and this Seat
- no conflicting occupant holds the Seat

Successful spawning does not itself confer occupancy. The qualified Officer must be installed in the Seat.

## Authority while occupied

The occupant may:

- perform the responsibilities in `doctrine.md`
- use the exact current Secretary Profile
- maintain one active Operator turn at a time
- create Secretariat-owned intake, relay, receipt, and delivery artifacts
- communicate with other Offices only through admitted handoffs

The occupant may not:

- widen Secretariat jurisdiction
- exercise authority belonging to Castellan or another Office
- treat its Profile, cognition, Persona, tools, or credentials as independent authority
- retain Seat authority after removal, replacement, suspension, or Profile invalidation

## Loss or suspension of occupancy

Occupancy ends or is suspended when:

- the Officer is removed or replaced
- the Secretary Profile is withdrawn, superseded, invalidated, or no longer matches the installed version
- required cognition becomes unavailable or fails qualification
- the Officer/Seat binding fails validation
- the Office is stopped by competent authority

Pending work remains Office-held and must not be treated as authority retained by the former occupant.

## Invariants

```text
The Office owns the Seat.
The Profile defines the qualification contract; Conscription qualifies the Officer manifestation against it.
Occupancy activates Office authority.
Vacancy activates no authority.
Spawning is not occupancy.
```
