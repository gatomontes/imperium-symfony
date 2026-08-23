# Handoff: governed operational-adoption lifecycle complete

## Repository checkpoint

- Repository: `gatomontes/imperium-symfony`
- Branch: `main`
- Merged implementation commit: `3317329639088e3f6ebe3406662e0b6dfe9c1c79`
- Pull requests: `#252`, `#253`, and `#254`
- Terminal checkpoints:
  - `OPERATIONAL_ADOPTION_DISPOSITION_ADOPTED_LIFECYCLE_CLOSED_NO_ACTION_AUTHORITY`
  - `OPERATIONAL_ADOPTION_DISPOSITION_ADOPTED_WITH_LIMITATIONS_LIFECYCLE_CLOSED_NO_ACTION_AUTHORITY`
  - `OPERATIONAL_ADOPTION_DISPOSITION_NOT_ADOPTED_LIFECYCLE_CLOSED_NO_AUTHORITY`
  - `OPERATIONAL_ADOPTION_DISPOSITION_UNRESOLVED_LIFECYCLE_CLOSED_NO_AUTHORITY`

## Starting boundary

This is a separate lifecycle from the terminally closed governed Legate commission. Commissioner acceptance at governed-commission Step 43 meant only that the result satisfied its exact commission. It granted no operational adoption or authority to act.

The operational-adoption lifecycle begins only from one exact terminally `ACCEPTED` Step 43 review. A rejected result cannot enter it.

## Canonical ten-step flow

1. **Original commissioner presents the accepted result.** The same exact current commissioner binding presents the unchanged result and lineage to `curia.seneschal`. The route stops pending governing intake.
2. **Seneschal decides governing intake.** The exact current sole Seneschal accepts or refuses intake. Refusal closes the lifecycle. Acceptance grants no evaluation authority.
3. **Seneschal opens bounded evaluation.** Curia declares three required independent judgments: evidence sufficiency; mission and operational fit; and risk, authority, and reversibility. No Curialis is selected or commissioned.
4. **Curia resolves composition and issues commissions.** Three distinct qualified, active Curiales—explicitly classified as `LEGATE` or `DELEGATE`—are each bound to one jurisdiction. Three independently sealed commissions remain non-exercisable pending recipient acceptance.
5. **Each Curialis independently accepts or refuses.** Partial acceptance grants nothing. Only unanimous acceptance opens three exact recipient- and jurisdiction-bound single-use assessment authorities. Any refusal closes the evaluation without assessment authority.
6. **Each Curialis independently seals one assessment.** Each consumes only its own authority and cannot see sibling assessments. After all three exist, machinery exposes only their jurisdiction-bound IDs and digests pending reconciliation.
7. **Runtime admits the assessments unchanged.** The three complete assessments enter one sealed reconciliation opening. Exactly one recipient-bound Seneschal reconciliation authority becomes exercisable.
8. **Seneschal reconciles without voting or aggregation.** The Seneschal explains agreement, disagreement, limitations, risk, and uncertainty while preserving all assessment references and content unchanged. No adoption disposition exists.
9. **Runtime opens adoption-decision authority.** Exactly one current Seneschal receives a single-use authority limited to `ADOPTED`, `ADOPTED_WITH_LIMITATIONS`, `NOT_ADOPTED`, or `UNRESOLVED`.
10. **Seneschal seals the terminal adoption disposition.** All four branches close this lifecycle. Conditional adoption requires explicit limitations. No branch grants authority to amend, use, commission, act, or execute.

## Preserved personnel taxonomy

`Officer` remains the umbrella and `generic-officer` remains the neutral substrate.

- `LEGATE`: permanent, Office-bound Officer.
- `DELEGATE`: temporary examination-, proceeding-, commission-, or mission-bound Officer.

Classification grants no authority. Operational-adoption Curiales may be Legates or Delegates only when their exact qualification, occupancy, commission, acceptance, and assessment authority records independently permit participation.

## Authority separations preserved

- Commissioner acceptance is not operational adoption.
- Presentation is not Curia intake.
- Intake acceptance is not evaluation authority.
- Evaluation opening is not Curial composition.
- Curial composition is not commission acceptance.
- One Curialis acceptance is not panel readiness.
- Panel readiness is not assessment.
- Assessment is not reconciliation.
- Reconciliation is not an adoption disposition.
- Adoption-decision authority is not adoption.
- Adoption is not Mission Plan amendment.
- Adoption is not operational-use permission.
- Adoption is not follow-up commissioning.
- Adoption is not tool, credential, external-action, or execution authority.

No authority from the terminally closed source commission is revived or reused.

## Verification

Local verification on PHP `8.4.14` with PHPUnit `13.3.0` passed:

- `298` tests;
- `3,990` assertions;
- `0` failures;
- `9.019` seconds;
- `40.00 MB` peak reported memory.

## Next separate lifecycle

The recommended next leg is the **Delegate mission route**. Do not append Step 11 to operational adoption.

The Delegate route must begin from a new exact mission-bound capability demand and preserve the distinction between an adopted result and authority to amend a plan or deploy an operative.

Read `docs/next-lifecycle-delegate-mission-route.md` before implementation.

## Copyable continuation prompt

```text
Continue Imperium from `main` at or after merged commit `3317329639088e3f6ebe3406662e0b6dfe9c1c79`.

Read `docs/handoffs/operational-adoption-lifecycle-complete.md`, `docs/next-lifecycle-delegate-mission-route.md`, `docs/officer-taxonomy.md`, and `docs/next-lifecycle-persona-retrieval-and-deployment.md` before changing code.

The permanent model-bound Legate route, one bounded governed commission through its terminal Step 43, and the separate ten-step operational-adoption lifecycle are complete. Local PHPUnit verification is green: 298 tests and 3,990 assertions on PHP 8.4.14.

Preserve the taxonomy: Officer is the umbrella; Legates are permanent Office-bound Officers; Delegates are temporary examination-, proceeding-, commission-, or mission-bound Officers. Classification grants no authority.

All operational-adoption branches are terminal. Even ADOPTED and ADOPTED_WITH_LIMITATIONS grant no Mission Plan amendment, operational-use, follow-up-commission, tool, credential, external-action, or execution authority. Do not invent Step 11 inside that closed lifecycle.

Begin the separately named Delegate mission route. First confirm and implement only its first bounded transition: Curia seals one mission-bound capability demand from an exact approved Mission Plan and governing authority source. The demand may state capabilities, mission Seat, duration, data/tool/perimeter needs, and stop/return/retirement conditions, but Curia must not select a profession or Persona. State the exact input record, decision-maker, consumer, output checkpoint, and every authority that remains false before coding.
```
