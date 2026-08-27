# Institutional informed consent

Status: proposed  
Scope: operator decision surfaces, Curia disclosure, authorization provenance, and reauthorization

## Objective

Make Imperium safe to operate without requiring the Imperator to remember every Office, Officer, capability, model, credential, profile, risk, or available course of action.

Imperium must remember and discover the relevant options. Curia must assemble those options into a bounded decision surface, explain their material consequences, and obtain an explicit decision from the Imperator.

The intended doctrine is:

> The Imperator is responsible for choosing among the disclosed options. Imperium is responsible for discovering, explaining, and faithfully recording those options.

## Required behavior

For every consequential decision, Curia must:

1. discover the materially relevant available options;
2. identify unavailable or prohibited options when their absence affects the decision;
3. explain each option in plain language;
4. disclose material consequences, risks, costs, external effects, and reversibility;
5. distinguish Curia's recommendation from the Imperator's decision;
6. allow the Imperator to select, reject, oppose, or request modification;
7. obtain explicit consent or authorization for the bounded choice;
8. record the complete decision surface and resulting disposition;
9. preserve everything that remains unauthorized;
10. require renewed authorization when material facts or consequences change.

## Decision record

The durable record must establish:

- which options were available;
- which options were presented;
- which relevant options were unavailable or prohibited;
- what each option meant at the time of decision;
- what Curia recommended and why;
- what the Imperator selected, rejected, opposed, or modified;
- what authority the decision granted;
- the scope, limits, and expiry of that authority;
- what remained unauthorized;
- the evidence used to construct the decision surface;
- the exact consent/authorization response;
- whether later changes rendered the consent stale.

## Accountability rule

"I did not know" must become testable rather than rhetorical.

- If an option and its material consequences were clearly disclosed and explicitly consented to, ignorance is not a valid defense.
- If a materially relevant option was omitted, buried, distorted, or ambiguously explained, Imperium failed its disclosure obligation.
- If material circumstances changed after consent, the prior consent is stale and cannot authorize continued or altered execution.
- Silence, inactivity, familiarity, or prior consent must never be treated as consent to a new or materially changed decision.

## Non-equivalences

Preserve the existing constitutional distinctions:

- presentation is not recommendation;
- recommendation is not selection;
- selection is not approval;
- approval is not authority;
- consent is not unbounded authority;
- possession of a capability is not permission to use it;
- prior authorization is not authorization under materially changed circumstances.

## Acceptance criteria

- The Imperator can make an informed decision without knowing Imperium's internal topology.
- No consequential choice is represented by a context-free prompt such as "Proceed?"
- Every requested decision states precisely what will and will not be authorized.
- The record can reconstruct the complete decision surface presented at that moment.
- Omitted material options are detectable as an Imperium disclosure failure.
- A material-change detector can invalidate stale consent and return the matter to Curia.
- Replay cannot widen authority or reinterpret the original consent.
- Tests cover explicit selection, rejection, opposition, modification, omitted-option failure, ambiguous disclosure failure, stale consent, silence, and replay.

## Campaign status

Selected as one half of the Institutional Decision Integrity campaign. The canonical sequence and exclusions are defined in `docs/next-campaign-institutional-decision-integrity.md`. This TODO remains the requirements source for decision-surface disclosure and stale-consent refusal.
