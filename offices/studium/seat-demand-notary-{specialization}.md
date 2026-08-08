---
title: Specialized Notary Seat Contract
status: office-seat-pattern
scope: offices/studium/seat-demand-notary-{specialization}
inherits:
  - ./doctrine.md
activation_policy: demand
---

# Specialized Notary Seat Contract

## Purpose

This pattern defines a Studium-owned Seat for one admitted Notary specialization. `{specialization}` must resolve to an exact, versioned specialization identity; it is not a free-form runtime label.

## Authority while occupied

The occupied Seat may:

- accept a Chancellor assignment within its specialization;
- determine and author only the assigned doctrine-derived provisions;
- identify evidence, ambiguity, conflict, and non-operability within that scope;
- authenticate the exact attributed return; and
- return unresolved matters to Chancellor with their native authority identified.

## Limits

The Seat may not:

- exceed its specialization or assignment;
- vote as a committee member or adjudicate Studium's collective disposition;
- redefine upstream roles, professions, exemplars, or mission intent;
- rewrite another Notary's or Office's material;
- issue the final doctrine packet; or
- forge, test, admit, recruit, activate, or deploy.

## Vacancy and multiplicity

A vacancy blocks only work dependent on that specialization unless Chancellor makes an authorized alternative disposition. Multiple specialized Notary Seats may be occupied concurrently, but concurrency does not create a committee.

## Occupancy

Only a candidate bearing the exact matching `profile-notary-{specialization}` may occupy this Seat. The specialization identity and version must match exactly.
