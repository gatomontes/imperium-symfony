# Curia Migration Plan

## Status

Approved for implementation.

## Objective

Replace the Secretariat, Castellan, and Rector architecture with a mission-specific Curia composed of:

- the human-held Imperator Seat;
- a Seneschal Seat;
- a Chamberlain Seat;
- a Secretary Seat, occupied provisionally by Isolde; and
- mission-specific Curial Seats.

Imperium runs one instance per mission. Curia is therefore the government of exactly one mission-specific Imperium instance.

> **Imperator authorizes. Seneschal decides. Chamberlain coordinates. Isolde records and communicates. Curiales deliberate.**

## Target ownership

| Matter | Competent owner |
|---|---|
| Mission decisions | Seneschal Seat acting through Curia |
| Planning proceeding and mission dossier | Curia |
| Coordination and mission-state continuity | Chamberlain Seat |
| Operator exchange and Curial record | Secretary Seat |
| Protected authorization | Imperator |
| Mission-domain judgment | Competent Curialis |
| Persona suitability | Guildhall informed by Garrison inventory |
| Persona construction | Foundry |
| Persona admission and custody | Garrison |
| Manifestation and qualification | Conscription |
| Seat binding and transfer | MasterMason under exact authority |

## Implementation increments

### 1. Constitute Curia

- Create Curia doctrine, mechanics, Seats, and Profiles.
- Define the Imperator Seat as human-held and outside Conscription.
- Define standard Seneschal and Chamberlain qualifications.
- Reassign the Secretary function to Curia and preserve Isolde's interaction discipline.
- Define mission-specific Curial demand and Profile templates.

This increment adds the approved target architecture without changing the pinned executable bootstrap corpus.

### 2. Redistribute abolished responsibilities

- Assign intent clarification, mission formation, planning, and mission orchestration to Curia.
- Assign executive dispositions to the Seneschal.
- Assign dossier mechanics, dependency tracking, and continuity to the Chamberlain.
- Assign exact presentation, recording, and delivery to the Secretary.
- Replace Castellan and Secretariat handoffs in dependent Office doctrine.

### 3. Add Seneschal suitability and succession contracts

- Define a versioned Seneschal Suitability Demand.
- Extend Guildhall personnel suitability to executive temperament.
- Define succession directive, succession packet, suspension, interim appointment, and atomic Seat-transfer contracts.
- Preserve mission state independently from the outgoing Seneschal manifestation.

### 4. Replace the bootstrap corpus

Replace the primordial Secretary/Rector pair with:

```text
Imperator activates the instance
→ provisional Recruiter
→ ordinary Recruiter
→ standard Seneschal + standard Chamberlain
→ Curian governing pair bound inactive
→ Isolde attached provisionally
→ Curia activated
→ mission-specific Curiales summoned later
```

The Seneschal and Chamberlain form the required governing pair. Isolde is attached separately so Curia remains directly addressable by the Imperator when the Secretary Seat is vacant.

### 5. Replace executable bootstrap semantics

- Replace triad-specific bootstrap states with Curian-core states.
- Update `MasterMason`, `ManifestValidator`, `ValidationReceipt`, state and recovery contracts, routes, compatibility declarations, status output, and service configuration.
- Create and pin Seneschal, Chamberlain, and Curial Secretary artifacts.
- Regenerate and sign the bootstrap manifest.

### 6. Implement Seneschal transfer

- Support normal replacement and emergency suspension.
- Remove outgoing Seat authority before or atomically with new occupancy.
- Prevent two active Seneschals.
- Prevent the Chamberlain from inheriting executive authority by vacancy.
- Preserve authorizations, commitments, evidence, and mission state across the transfer.

### 7. Retire obsolete architecture

- Remove Secretariat and Castellan Office directories.
- Remove Rector and Secretariat bootstrap artifacts.
- Remove active references to Secretariat, Castellan, and Rector from doctrine, contracts, configuration, and Runtime.
- Retain historical references only where explicitly marked as migration history.
- Mark the governing proposal implemented.

## Validation gates

Each increment must preserve an internally coherent repository. Final cutover requires:

- bootstrap-manifest construction and signature verification;
- clean activation reaching `CURIA_READY`;
- direct Imperator access to Curia without Isolde;
- distinct Seneschal and Chamberlain manifestation identities;
- no mission-specific Curialis bootstrapped without mission need;
- successful normal succession with preserved mission state;
- immediate authority removal during emergency suspension;
- closed failure when a candidate, Profile, commission, or succession packet is stale or invalid;
- no simultaneous active Seneschals;
- no automatic Chamberlain executive inheritance;
- complete automated-test success; and
- no active obsolete architectural references.

## Migration rule

The current bootstrap corpus remains executable until its Curian replacement is complete. New Curia artifacts remain **approved target architecture** and acquire no Runtime authority merely by existing in the repository.
