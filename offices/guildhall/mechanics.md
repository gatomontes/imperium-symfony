---
inherits: [./doctrine.md]
---
# Guildhall Mechanics

These functions support Guildhall cognition. They do not determine professions, compose the queue, challenge boundaries, or adjudicate recommendations.

## open-resolution-case
Preserve exact Castellan inputs, versions, provenance, and correlation in a resolution-case record.

## dispatch-committee-assignment
Route a Guildmaster-authorized bounded assignment to the named Committee Seat and record attribution.

## record-committee-return
Preserve the exact return with Seat, Profile version, author, sources, and case identity.

## assemble-committee-record
Bind attributable returns without reconciling their meaning; output a committee record for Guildmaster review.

## record-guildmaster-disposition
Preserve the exact Guildmaster disposition, rationale, contributions, version, and authority.

## issue-profession-packet
Package and transmit the exact admitted Profession Determination Packet to Castellan.

Every function fails closed on missing authority, attribution, identity, version, integrity, or correlation.
