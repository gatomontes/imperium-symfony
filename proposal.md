# Proposal: Absorb the Secretariat into Curia

## Status

Proposed.

## Decision

Abolish the Secretariat as an independent Office.

The Operator shall hold the **Imperator Seat in Curia** and participate directly in the Situation Room. Curia may maintain a Secretary Seat when administrative support is required, but the Secretary shall be a Curial officer rather than an institutional gateway between the Operator and Curia.

## Rationale

The Secretariat was originally conceived as the Operator's point of entry into Imperium. Its Secretary received the Operator's words, clarified them, and relayed them to Castellan or Curia.

That separation no longer contributes a distinct authority or judgment. Curia now owns:

- intent clarification;
- planning;
- decision support;
- mission orchestration; and
- consultation with the Operator when authorization is required.

Maintaining a separate Office solely to relay the Operator's words creates an additional institutional hop without adding governance. The mediation belongs inside the Situation Room.

## Curia composition

Curia shall contain, at minimum:

- the **Imperator Seat**, occupied directly by the human Operator;
- the **Rector Seat**, occupied by Curia's mission orchestrator; and
- any mission-specific Curial Seats required for planning or deliberation.

Curia may additionally declare a **Secretary Seat** when clerical or conversational support is useful.

Isolde may remain an admitted persona and be manifested as a Curial Secretary. Her existing interaction discipline remains useful:

- ask one question at a time;
- reject answers that do not address the question;
- deliver each relevant answer immediately to Curia; and
- consult the Rector when uncertain.

These are qualifications of a Curial Secretary, not justification for a separate Office.

## Seat distinction

The Imperator Seat is not an ordinary agent-occupied Seat.

It represents the human authority attached directly to Curia and is not populated, qualified, or manifested by Conscription. Agent-occupied Seats remain subject to the normal lifecycle:

1. an Office declares a Seat and its qualification requirements;
2. Conscription obtains the required admitted persona;
3. Conscription installs the signed profile into a generic agent manifestation; and
4. the qualified manifestation occupies the Seat.

The Operator enters Curia by authority, not by manifestation.

## Revised command path

Previous path:

`Operator → Secretariat → Curia`

Proposed path:

`Operator / Imperator Seat ↔ Curia`

If present, a Curial Secretary assists within Curia. The Secretary does not control access to Curia, reinterpret Curia's authority, or become a mandatory relay.

## Bootstrap consequences

The removal of Secretariat simplifies initialization:

1. the Operator activates Imperium and assumes the Imperator Seat in Curia;
2. bootstrap provides the initial Recruiter;
3. the Recruiter recycles its bootstrap manifestation under authorized provenance;
4. Conscription assembles the Rector;
5. the Rector occupies Curia's Rector Seat; and
6. Curia summons a Secretary only when one is required.

The system therefore no longer needs to manifest a Secretary merely to gain access to its own command room.

## Architectural consequences

- Remove Secretariat from the Office map.
- Move the Secretary Seat, Secretary profile, and Isolde-specific behavior under Curia.
- Replace all `Operator → Secretariat → Curia` flows with direct Operator participation in Curia.
- Preserve Curia as the owner of intent, planning, authorization consultation, and mission orchestration.
- Preserve Conscription's authority over all agent manifestations while explicitly excluding the human-held Imperator Seat from that lifecycle.

## Summary

Curia is the command room. The Operator belongs in it.

A Secretary may assist the command room, but should not stand outside it as a separate institution through which command must pass.
