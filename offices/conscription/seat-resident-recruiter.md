---
inherits:
  - ./doctrine.md
activation_policy: resident
---

# Recruiter Seat

## Status

Current theoretical definition.

## Ownership

The Recruiter Seat belongs to Conscription. It is the sole institutional position through which a Recruiter exercises Conscription's Officer-construction and qualification authority.

The Seat is not the Recruiter, Recruiter Profile, generic Officer substrate, or bootstrap mechanic.

## Purpose

The Seat binds a qualified occupant to Conscription's jurisdiction. Occupancy permits the Recruiter to install Office-provided Profiles, examine constructed agents, and issue Conscription qualification dispositions.

## Vacancy and bootstrap

When the Seat is vacant:

- ordinary Officer construction and qualification cannot proceed
- valid activation requests remain pending
- Conscription mechanics may invoke only `bootstrap-recruiter`
- the mechanic must use the exact current Recruiter Profile, authorized generic substrate, and this resident Seat
- any attempt to use the bootstrap for another Profile or Seat fails closed

The resulting bootstrap origin must remain explicit for the full life of that Recruiter instance.

## Admission to occupancy

The primordial Recruiter may occupy this Seat only through the exact mechanical bootstrap declared by Conscription doctrine.

A later replacement Recruiter may be constructed and qualified by an already occupied Recruiter Seat, then installed after controlled vacation or succession. No Officer may occupy this Seat merely because a generic substrate was instantiated or a Profile was installed.

## Authority while occupied

The occupant may:

- perform the responsibilities in `doctrine.md`
- install exact Office-provided Profiles into authorized generic substrates
- conduct declared qualification checks
- issue the sole Conscription qualification disposition
- bind successful qualification to one intended Seat
- return attributable failures without repairing the supplied Profile

The occupant may not:

- rewrite another Office's Profile or requirements
- grant another Office's authority
- occupy another Office's Seat
- authorize mechanics to bootstrap a non-Recruiter
- treat installation or qualification as occupancy
- retain Seat authority after removal, suspension, replacement, or Profile invalidation

## Loss or suspension of occupancy

Occupancy ends or is suspended when:

- the Recruiter is removed or replaced
- the Recruiter Profile is withdrawn, superseded, invalidated, or mismatched
- required cognition becomes unavailable
- the Officer/Seat binding fails validation
- Conscription is stopped by competent authority

Pending construction cases remain Conscription-held. They do not authorize mechanics or another Office to complete cognitive qualification.

## Invariants

```text
The bootstrap exists only to occupy the first Recruiter Seat.
The occupied Recruiter qualifies every other Officer.
Qualification is not occupancy.
Occupancy elsewhere remains the requesting Office's act.
```
