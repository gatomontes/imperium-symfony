# TODO: Sanitized Custody/Recombination Runtime Trace

## Purpose

Prepare the smallest faithful runtime artifact that can be shared externally to demonstrate Imperium's custody/recombination boundary without disclosing proprietary architecture.

The artifact must prove this narrow claim:

> Successful capability recombination demonstrates that execution is technically possible; it does not grant, imply, inherit, or renew permission to operate.

This is an evidence deliverable, not a marketing summary or architectural specification.

## Source requirements

- Derive the trace from an actual completed runtime/test path.
- Do not invent events, statuses, custody transitions, denials, digests, or conclusions.
- Prefer one bounded end-to-end case over a composite of several cases.
- Preserve the order in which the runtime produced the evidence.
- Identify any design assumption that failed or became stricter under runtime pressure.
- If the available runtime evidence does not yet prove the intended claim, record the gap instead of polishing around it.

## Shareable output format

Produce one compact Markdown or PDF artifact, ideally no more than 2–4 pages, with these sections:

### 1. Scope and claim

A short statement defining exactly what the trace demonstrates and, equally importantly, what it does not demonstrate.

### 2. Sanitized subject legend

Use neutral labels rather than Imperium names, for example:

- Identity Artifact
- Bounded Role/Profile
- Model Binding
- Capability Eligibility
- Custodian A / Custodian B
- Assembly/Recombination Request
- Assembled Runtime Subject
- Closed Operational Boundary
- Accountable Authority

Do not publish Office, Officer, Seat, Persona, Profile, Manifestation, Curia, Senate, Conscription, Garrison, Oracle, Armory, Iron Gate, Lazaretto, or other internal names unless separately approved for disclosure.

### 3. Minimal chronological trace

Use a table with exactly these columns:

| Seq. | Runtime event | Input/custody before | Output/custody after | Authority effect | Evidence reference |
|---|---|---|---|---|---|

The trace should contain only the minimum events required to show:

1. relevant components existed separately and under bounded custody;
2. a bounded purpose requested recombination;
3. required identity/purpose/capability conditions were checked;
4. recombination or assembly succeeded;
5. successful assembly did not create operational authority;
6. the next authorization boundary remained closed;
7. an attempted or hypothetical inference from “assembled” to “authorized” was mechanically unavailable or rejected.

### 4. Boundary result

State the result in plain language:

- what became technically possible;
- what remained prohibited;
- what further decision or authorization would have been required;
- whether any authority was consumed, inherited, expanded, or created.

### 5. What survived runtime contact

List only the original design principles that the trace actually supports.

### 6. What changed under runtime pressure

Record the smallest honest design-change note. Candidate, subject to confirmation from actual history:

> Successful assembly originally appeared to be the principal milestone. Runtime implementation forced assembly, qualification, approval, installation, binding, and operational authority to become distinct states because each answers a different governance question.

Do not use this wording unless repository history and runtime evidence support it.

### 7. Disclosure note

End with a short statement that:

- identifiers and institutional names were generalized;
- irrelevant events and internal implementation details were omitted;
- event order and authority effects were preserved;
- the artifact is illustrative evidence from one bounded trace, not a complete system specification.

## Evidence handling

- Replace live identifiers, repository paths, provider names, model identifiers, digests, credentials, endpoints, and internal class/service names with stable neutral aliases.
- If hashes are useful to demonstrate binding, show truncated or synthetic display aliases only; retain a private mapping for internal verification.
- Do not expose source code, schemas, prompts, policies, decision rubrics, command names, complete state enums, internal directory structure, or the full lifecycle graph.
- Do not include secrets, credentials, personal data, infrastructure details, or vendor-specific account information.
- Do not combine enough individually harmless details to make the proprietary architecture reconstructable.
- Preserve semantic truth: sanitization may rename and omit, but must not alter event order, custody, authority effect, or outcome.

## Review gates before sharing

The artifact must pass all of the following:

- **Fidelity:** every displayed event maps to actual runtime evidence.
- **Minimality:** removing any remaining event would make the claim materially weaker or ambiguous.
- **Non-disclosure:** the artifact cannot be used to reconstruct Imperium's institutional topology, lifecycle, prompts, policies, or implementation.
- **Authority accuracy:** capability, qualification, approval, custody, assembly, binding, and authority are not conflated.
- **Adversarial reading:** no reasonable reader can interpret successful recombination as permission to execute.
- **Operator approval:** the final sanitized artifact requires explicit review and approval before external sharing.

## Deliverables

1. Private source worksheet mapping sanitized events to exact runtime evidence.
2. External 2–4 page sanitized trace in Markdown or PDF.
3. One-paragraph cover message explaining that the trace shows what survived runtime contact and what had to change.
4. A short withheld-information register listing categories intentionally excluded from the external artifact.

## Non-goals

This TODO does not authorize:

- publication or external transmission;
- disclosure of Imperium terminology or architecture;
- creation of a public white paper;
- release of source code, prompts, schemas, or tests;
- claims beyond what one verified trace demonstrates;
- operational execution or any new runtime authority.
