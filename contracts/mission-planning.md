# Mission Planning and Authorization Contract

## Purpose

This contract governs the conversion of authenticated Operator intent into two separately authorized phases: bounded resource-bearing planning, followed by bounded mission execution.

```text
Intent → internal clarification → disclosed Planning Charter → valid approval
       → Planning Authorization → planning commissions → approval-ready Mission Plan
       → valid approval → Mission Authorization → execution commissions → execution
```

No earlier state implies a later one.

## Constitutional distinctions

- **Intent** requests that Imperium begin mission formation.
- **Authority** is legitimate power already held within an exact jurisdiction.
- **Approval** is an attributable decision concerning an exact authorization-object version.
- **Planning Charter** is the disclosed, versioned object that bounds resource-bearing investigation needed to formulate a Mission Plan.
- **Planning Authorization** is the bounded permission resulting when competent authority validly approves that exact Planning Charter version.
- **Mission Authorization** is the separate bounded permission resulting when competent authority validly approves the exact Mission Plan version.
- **Commission** is a single-purpose, least-necessary delegation derived from that authorization.
- **Execution** is action under a valid commission; it is not produced by planning, disclosure, or technical capability alone.

Therefore:

```text
approval ≠ authority
authority ≠ approval
internal clarification ≠ resource-bearing planning ≠ execution
Planning Authorization ≠ Mission Authorization
authorization ≠ commission ≠ action
```

## Planning proceeding

The first Operator input opens a planning proceeding but authorizes no resource use and no mission execution. Castellan, through Secretariat, may clarify intent one question at a time and reason over the Operator's supplied material, admitted doctrine, and information already lawfully present in the proceeding. It preserves every accepted value against exact Operator evidence.

Before Imperium commissions another Office, invokes a restricted tool, releases a credential, accesses protected data, queries an external system, consumes money or metered capacity, performs outbound contact, or creates any other external effect for planning, it must disclose an exact Planning Charter and obtain valid approval from competent Operator authority.

Planning continues until Castellan can either produce an approval-ready plan, identify an exact unresolved blocker, or return a bounded refusal or impossibility disposition.

## Approval-ready Planning Charter

Every proposed Planning Charter must have a stable identity, version, digest, status, author, governing doctrine, and complete lineage. It must disclose:

- the questions or uncertainties the investigation is intended to resolve;
- the Offices, roles, and planning sorties that may participate;
- the tools, credentials, data, sources, operation surfaces, and external systems that may be used;
- any permitted outbound contact, disclosure, storage, or other external effect;
- cost, time, retention, and resource ceilings;
- the evidence and planning payloads that must return;
- stop conditions, amendment triggers, expiry, revocation, and closure conditions; and
- the express prohibition against using Planning Authorization for mission execution.

For every planned external operation, the Charter must classify the work as either deterministic boundary execution or external-cognition sortie under `/contracts/la-cortine-boundary.md`. A tool call alone does not justify a sortie. When cognition must occur in the untrusted environment, the Charter must disclose the sortie's bounded purpose, minimum context, tools/capabilities, destinations, expected raw return payload, and termination conditions.

Proportional detail is permitted, but no undisclosed resource or effect acquires permission through approval of the visible Charter.

## Planning Authorization and commissions

Valid approval of the exact Planning Charter produces a Planning Authorization record. It is bound to the Charter and approval evidence and must identify its authority source, holder, object, scope, duration, conditions, revocation path, and permitted delegation path.

Resource-bearing planning may proceed only through exact, least-necessary planning commissions derived from that record. Each commission must satisfy the commission requirements below and must be labeled planning-only. It may investigate, retrieve evidence, or estimate execution requirements within its exact bounds; it may not perform, rehearse through real effects, or silently begin the proposed mission.

Armory possession does not authorize tool use. Locksmith custody does not authorize credential release. Guildhall, Hagiography, or another Office's institutional jurisdiction does not authorize its participation in a particular planning proceeding. Each requires a valid derived planning commission and Runtime enforcement at the relevant boundary.

Any external planning operation must cross La Cortine through Iron Gate and return through Lazaretto. Internal cognition does not continue inside a sortie. No raw external payload may be delivered directly into Curia or another internal cognitive proceeding.

When personnel requirements are material, Curia commissions Guildhall to determine the required professions and reconcile them against exact Garrison inventory facts. Guildhall returns a versioned Personnel Disposition identifying suitable admitted Personas available or unavailable, personnel gaps requiring Foundry construction, and the estimated cost, effort, dependencies, and uncertainty of filling those gaps. Garrison reports inventory facts; Guildhall determines suitability. The disposition informs planning and disclosure but authorizes neither construction nor deployment.

Planning Authorization closes upon completion, failure, revocation, expiry, Operator termination, or issuance of the terminal planning disposition declared by its Charter. It does not merge into, survive as, or supplement Mission Authorization.

## Approval-ready Mission Plan

Every proposed Mission Plan must have a stable identity, version, digest, status, author, governing doctrine, and complete lineage. Proportional detail is permitted, but the plan must disclose every execution-relevant boundary, including:

- requested outcome and success, failure, and completion conditions;
- included and excluded scope;
- material facts, assumptions, unknowns, and dependencies;
- Offices, roles, planned sorties, suitable personnel already available, and personnel gaps requiring construction;
- tools, credentials, data, and other resources;
- recipients, operation surfaces, ingress and egress points, and external effects;
- classification of each external operation as deterministic boundary execution or external-cognition sortie;
- cost, time, retention, and resource limits;
- risks, stop conditions, contingencies, and amendment triggers;
- required raw return payloads, Lazaretto admission requirements, evidence, provenance, and reporting; and
- expiry, revocation, interruption, and reauthorization conditions.

Disclosure must be understandable enough for the Operator to know what is being approved. Hidden execution-relevant terms cannot acquire authorization through approval of the visible plan.

## Valid approval

An approval has authorizing effect only when Runtime can verify:

1. the approving Operator's identity;
2. the Operator's competent authority over the proposed mission and affected resources;
3. the exact Planning Charter or Mission Plan identity, version, and digest presented;
4. the approval's explicit affirmative disposition;
5. the approval's scope, conditions, timing, and authenticity; and
6. the absence of supersession, revocation, expiry, or unresolved authority conflict.

Silence, continued conversation, submission of intent, correction of a draft, approval of a different version, or possession of SuperAdmin capability is not valid approval of either authorization object.

## Authorization record

Valid approval of an exact Mission Plan produces a Mission Authorization record bound to that plan version and approval evidence. The record must identify its authority source, holder, object, scope, duration, conditions, revocation path, and permitted delegation path.

Authorization grants no unlisted tool, credential, data, recipient, destination, or external effect. Runtime enforces the record; no cognitive manifestation may reinterpret it to enlarge permission.

## Derived commissions

Any authorized work may begin only through exact commissions issued under the applicable authorization object. Each commission must:

- cite the Planning Authorization and Charter digest or the Mission Authorization and plan digest, never an ambiguous combination;
- name one authorized manifestation, deterministic boundary executor, or exact qualification target;
- define one bounded task and purpose;
- expose only the necessary resources and destinations;
- define whether external work is deterministic execution or requires an external-cognition sortie;
- define its expected payload, evidence, Lazaretto return path, and provenance obligations;
- state start, stop, expiry, consumption, and failure conditions; and
- prohibit delegation unless an exact delegation path is itself authorized.

A commission cannot delegate more authority than its source, and receipt of its payload does not transfer its authority.

A sortie commission must bind the exact sortie manifestation identity once created and must terminate with that sortie. A deterministic external-execution commission must bind the exact boundary executor and operation attempt. Neither may bypass Iron Gate outbound enforcement or Lazaretto inbound admission.

## Credential-use rule

A commission may authorize use of a credential without exposing or transferring the secret itself. Locksmith retains custody. La Cortine should prefer credential brokering or short-lived scoped capabilities; direct injection of long-lived secrets into cognition is prohibited.

An authenticated external API operation may still be a single external request. Internal credential retrieval, brokering, or attachment at the boundary does not create a second external authorization step unless the provider protocol itself requires one.

## Amendment and deviation

A proposed change must be classified as either non-material clarification or material amendment under the governing plan.

A material amendment creates a new immutable Planning Charter or Mission Plan version and invalidates authorization for every affected portion until competent authority validly approves that version. Planning or execution encountering an unplanned material need must stop at the smallest dependent boundary and request amendment; it may not treat necessity, convenience, lower cost, or likely Operator preference as authorization.

Declared contingencies may proceed without amendment only within their own phase and only when their triggers, bounds, and consequences were disclosed and authorized in the exact approved version.

A disclosed suspicion contingency may authorize Curia to suspend the smallest affected commission and place an exact operative before Senate for independent examination. Senate's hearing record informs competent action but does not itself authorize restriction, repair, replacement, retirement, or resumed deployment. If no applicable authorized contingency exists, Curia must stop at the affected boundary and seek the authorization required for the hearing and any consequential response.

## Closure

Completion, failure, revocation, expiry, or Operator termination closes the applicable authorization and every dependent unconsumed commission. Returned evidence remains evidence; it does not preserve dormant planning or execution authority.

External-cognition sorties are retired at the boundary on completion, failure, revocation, expiry, or consumption. Their raw payloads may remain preserved as evidence, but neither the sortie nor its authority may enter or persist inside Imperium Runtime.

## Governing maxim

> **Intent opens deliberation. Planning Authorization permits bounded investigation. Mission Authorization permits bounded execution. Both arise only from valid approval exercising competent authority over an exact disclosed version, and both act only through exact derived commissions. External work crosses La Cortine: deterministic execution when the operation is fully specified, or a disposable sortie only when cognition must occur outside the trust boundary.**
