# Mission Planning and Authorization Contract

## Purpose and current phase allocation

This contract governs bounded planning and separately authorized execution.
For new missions, [Citadel mission intake](citadel-mission-intake.md) governs
ownership: Citadel receives, discusses overlap, interviews, requests drafting
permission, drafts and presents. A mission-specific Curia receives the approved
mission only after competent constitution and handoff. Curia retains planning
and amendment responsibilities within its existing mission mandate.

The default order is: Citadel request and discussion → attributable understanding
→ disclosed request to draft → Imperator approval to draft → authorized proposal
elaboration → exact proposal approval → Mission Authorization and competent Curia
constitution/handoff → receiving Seneschal acceptance → governed execution.

No earlier state implies a later one. Interview cognition requires legitimate
authority before use; drafting approval cannot retroactively authorize it.

## Constitutional distinctions

- Intent opens deliberation; it grants no resource or execution authority.
- Understanding is a cognitive judgment about intent, outcome and constraints.
- Readiness to draft is a separate judgment, not proposal approval-readiness.
- Approval to draft is the Imperator's exact affirmative decision permitting
  bounded proposal preparation under the applicable planning authority.
- Planning Charter and Planning Authorization bound resource-bearing planning.
- Mission Plan approval and Mission Authorization concern the eventual mission.
- Commissions derive bounded work from competent authority; they do not enlarge it.

Understanding, agreement, readiness, drafting permission, mission approval,
appointment, handoff acceptance and execution are distinct.

## New-request proceeding

Citadel preserves the exact Imperator request and conducts the direct interview
through a competent cognitive holder. It consults shared mission records to
identify overlap and asks other Seneschals only when their judgment is needed.
A similarity result does not reject the request or approve duplicate work.
The holder's title, appointment and resource policy must be grounded in the
implementation preparation; this contract does not invent them.

### Interview completion: “I understand”

Understanding is the interview's single completion criterion. Preserve the
attributable declaration, exact exchange, understood intent, uncertainties and
objections. Disagreement can coexist with understanding. Recording a declaration
does not make its author competent, prove semantic understanding or grant authority.

Without that declaration the interview remains open or records its impediment.
With it the interview is complete, but actual proposal elaboration remains
blocked until the distinct drafting decision. Changed intent requiring renewed
understanding preserves the original record and invalidates affected continuation.

### Separate request and permission to draft

The responsible cognitive officer may state:
“I understand. I am ready to draft a proposal. Do you approve?”

Present the exact drafting scope, information/disclosure, resources, limits and
expiry with that request. Understanding plus silence or continued conversation
does not authorize drafting. The drafting request is not itself the elaborated
mission proposal and may not hide research, recruitment or external operations.

Only a valid, exact-version Imperator approval permits actual drafting, within
the applicable planning authority. Use the existing Planning Charter/authorization
machinery where competent; preparation must specify the concrete object mapping.
Even a draft using only material already present requires the explicit permission
to draft. Resource-bearing investigation requires the Charter and derived
commissions appropriate to its disclosed bounds; no redundant permission is
required for unchanged work already covered by valid authority.

Decline, deferral, expiry and resumption preserve information without preserving
spend permission indefinitely. Citadel presents an approval-ready proposal or
a specific unresolved requirement. Understanding, drafting readiness and
proposal approval-readiness must remain separately attributable.

## Existing mission proceedings

After an approved handoff, the Seneschal governs the assigned mission. Its Isolde
coordinates and preserves mission communication; the Chamberlain maintains its
dossier. Clarification, proposed changes and additional planning remain subject
to their applicable scope and authorization. Receiving the original exchange
avoids automatically repeating the initial interview. Handoff gaps and material
changes are resolved without silently rewriting the approved mission.

## Implementation compatibility

Existing Curia-named classes, schemas and source records describe the legacy
implementation. New Citadel work must establish competent issuer/holder,
proceeding and authority mappings before reusing them. Renaming a caller,
copying records or giving it a Curia-shaped fixture is not migration evidence.
The underlying version, approval, commission and boundary rules below remain.

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

Armory possession does not authorize tool use. Clavium custody does not authorize credential release. Guildhall, Hagiography, or another Office's institutional jurisdiction does not authorize its participation in a particular planning proceeding. Each requires a valid derived planning commission and Runtime enforcement at the relevant boundary.

Any external planning operation must cross La Cortine through Iron Gate and return through Lazaretto. Internal cognition does not continue inside a sortie. No raw external payload may be delivered directly into Citadel's planning proceeding, Curia or another internal cognitive proceeding.

When personnel requirements are material, the competent planning authority (Citadel for new mission formation; Curia within an existing mission mandate) commissions Guildhall to determine the required professions and reconcile them against exact Garrison inventory facts. Guildhall returns a versioned Personnel Disposition identifying suitable admitted Personas available or unavailable, personnel gaps requiring Foundry construction, and the estimated cost, effort, dependencies, and uncertainty of filling those gaps. Garrison reports inventory facts; Guildhall determines suitability. The disposition informs planning and disclosure but authorizes neither construction nor deployment.

When the mission may require a non-standard executive disposition, the competent planning holder prepares an exact Seneschal Suitability Demand under [`seneschal-suitability.md`](seneschal-suitability.md). Guildhall evaluates the standard Seneschal first unless the demand validly states otherwise, compares admitted Persona versions against the demand, and returns `standard_suitable`, `admitted_candidate_suitable`, `construction_required`, or `unresolved`. The existing suitability contract's Curia-specific issuer assumptions require an explicit competent mapping before Citadel can use this route. Merely formatting a demand from lawfully present material grants no evaluation or appointment authority. Guildhall evaluation, protected inventory access, candidate examination, research, or Persona construction requires the applicable Planning Authorization and exact derived commissions.

When the demand arises from an Imperator succession directive, its executive requirements must remain bound to that directive. The incumbent Seneschal may contribute attributable evidence but may not authoritatively narrow, delay, veto, or rewrite the demand or Guildhall commission.

Planning Authorization closes upon completion, failure, revocation, expiry, Operator termination, or issuance of the terminal planning disposition declared by its Charter. It does not merge into, survive as, or supplement Mission Authorization.

## Approval-ready Mission Plan

Every proposed Mission Plan must have a stable identity, version, digest, status, author, governing doctrine, and complete lineage. Proportional detail is permitted, but the plan must disclose every execution-relevant boundary, including:

- requested outcome and success, failure, and completion conditions;
- included and excluded scope;
- material facts, assumptions, unknowns, and dependencies;
- Offices, roles, planned sorties, suitable personnel already available, and personnel gaps requiring construction;
- every proposed exact provider/model/version binding, its Oracle evidence and recommendation lineage, attributable planning selection rationale, expected cost and limits, fallback policy, and unresolved objection;
- tools, credentials, data, and other resources;
- recipients, operation surfaces, ingress and egress points, and external effects;
- classification of each external operation as deterministic boundary execution or external-cognition sortie;
- cost, time, retention, and resource limits;
- risks, stop conditions, contingencies, and amendment triggers;
- required raw return payloads, Lazaretto admission requirements, evidence, provenance, and reporting; and
- expiry, revocation, interruption, and reauthorization conditions.

Disclosure must be understandable enough for the Operator to know what is being approved. Hidden execution-relevant terms cannot acquire authorization through approval of the visible plan.

The complete versioned planning dossier is presented to Imperator as one authorization object. Imperator may affirmatively approve that exact version, object to identified terms, or return it for revision. A competent planning model-selection decision is evidence inside the dossier; it is not separately approved and cannot become an operational binding by itself.

## Valid approval

An approval has authorizing effect only when Runtime can verify:

1. the approving Operator's identity;
2. the Operator's competent authority over the proposed mission and affected resources;
3. the exact drafting request, Planning Charter or Mission Plan identity, version, and digest presented;
4. the approval's explicit affirmative disposition;
5. the approval's scope, conditions, timing, and authenticity; and
6. the absence of supersession, revocation, expiry, or unresolved authority conflict.

Silence, continued conversation, submission of intent, correction of a draft, approval of a different version, or possession of SuperAdmin capability is not valid approval of any of these authorization objects.

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

A commission may authorize use of a credential without exposing or transferring the secret itself. Clavium retains custody through its Locksmith. La Cortine should prefer credential brokering or short-lived scoped capabilities; direct injection of long-lived secrets into cognition is prohibited.

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

> **Intent opens deliberation. Understanding closes the interview only. Distinct Imperator approval permits bounded proposal drafting. Planning Authorization permits bounded investigation. Mission Authorization permits bounded execution. Both arise only from valid approval exercising competent authority over an exact disclosed version, and both act only through exact derived commissions. External work crosses La Cortine: deterministic execution when the operation is fully specified, or a disposable sortie only when cognition must occur outside the trust boundary.**
