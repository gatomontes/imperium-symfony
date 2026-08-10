# La Cortine Boundary Contract

## Purpose

La Cortine is Imperium's border-security runtime domain. It separates trusted internal cognition from untrusted external cognition and external systems.

La Cortine contains two asymmetric boundaries:

- **Iron Gate** governs what Imperium is permitted to expose or cause outside the trusted core.
- **Lazaretto** governs what external material is permitted to become admitted internal evidence.

La Cortine does not own mission cognition, mission planning, evidence interpretation, credential custody, or institutional judgment. It realizes exact authorized boundary contracts.

## Trust-domain separation

Imperium Runtime, La Cortine Runtime, and Sortie Runtime are distinct trust domains.

```text
Imperium Runtime
  internal cognition, deliberation, mission state
        |
        | exact authorized outbound request
        v
================ Iron Gate ================
        |
        | deterministic external execution
        | OR disposable external-cognition sortie
        v
External systems / untrusted environment
        |
        | raw return material
        v
================ Lazaretto =================
        |
        | admitted provenance-bound artifact only
        v
Imperium Runtime
```

No external operative manifestation may enter or resume inside Imperium Runtime. No raw external material may bypass Lazaretto into internal cognition.

## Deterministic execution versus sortie

A tool call does not itself justify a sortie.

Iron Gate must classify an authorized outbound task before external execution:

1. **Deterministic execution** — the target, operation, payload, constraints, and expected response contract are already fully determined. La Cortine executes the external operation without deploying cognitive agency.
2. **External-cognition sortie** — accomplishing the authorized task requires observation, interpretation, adaptive choice, or iterative reasoning in the untrusted external environment. Iron Gate creates a disposable sortie outside Imperium Runtime.

Examples of deterministic execution include sending an exact approved email, uploading an exact artifact to a known endpoint, or performing a known API operation with a fixed payload.

Examples requiring sorties include open-ended web research, navigating an unknown portal path, investigating a changing external system, or deciding which external source or interaction is needed next.

When the distinction is unresolved, the task fails closed until competent planning resolves it. Convenience or agent availability does not justify a sortie.

## Iron Gate

Iron Gate accepts only an exact outbound commission derived from valid Planning Authorization or Mission Authorization.

Before allowing an external effect, Iron Gate must verify at minimum:

- authorization object identity, version, and digest;
- commission identity and phase;
- exact task and purpose;
- permitted disclosure and outbound payload;
- permitted destination, recipient, or operation surface;
- tools and capabilities permitted;
- credential-use authorization, if any;
- cost, time, attempt, and resource ceilings;
- expected return payload and evidence contract;
- expiry, revocation, consumption, and stop conditions; and
- whether the execution is deterministic or requires a sortie.

Iron Gate may narrow an authorized commission for technical enforcement. It may not enlarge, reinterpret, or substitute its authorized meaning.

## Sortie lifecycle

A sortie is disposable cognition deployed beyond Imperium's trust boundary because cognition must occur in an untrusted environment.

Every sortie must:

- be created outside Imperium Runtime;
- have a cryptographically verifiable manifestation identity;
- bind to exactly one derived commission;
- receive only the minimum context necessary for that task;
- receive only the exact tools, destinations, capabilities, data, and limits authorized;
- have no general access to Imperium state, doctrine, deliberations, identities, or mission context beyond what is necessary;
- have no authority to create or enlarge its own permissions;
- return only through Lazaretto;
- terminate on completion, failure, revocation, expiry, or consumption; and
- never be admitted, promoted, resumed, or transferred into Imperium Runtime.

A sortie's compromise must not create a path into trusted cognition or persistent authority.

## Credential use

Credentials are infrastructure secrets, not cognitive possessions.

Locksmith retains credential custody and credential policy. An operative, Officer, Curia, Iron Gate, or sortie does not acquire ownership merely because an authenticated action is authorized.

For an authorized external operation, Runtime should prefer one of these patterns in descending order of isolation:

1. a boundary service uses the Locksmith-controlled credential on behalf of the caller without exposing the secret;
2. Locksmith issues or brokers a short-lived, scoped capability bound to the exact commission; or
3. where the provider offers no stronger mechanism, La Cortine injects the minimum required credential into the disposable execution environment for the shortest possible lifetime.

Long-lived credentials must not be placed in prompts, mission artifacts, deliberation records, Persona/Profile artifacts, or returned payloads.

Authentication attached to an external request does not imply a separate external "unlock" operation. A single authenticated API request may remain one external operation while credential retrieval, brokering, or attachment occurs internally at the boundary.

## Lazaretto

Lazaretto treats every returning byte, claim, document, URL, tool response, instruction, and structured object as untrusted external material.

Lazaretto must preserve the raw return artifact and bind it to:

- sortie or deterministic-execution identity;
- source commission and authorization;
- external source or provider identifiers when observable;
- tools and capabilities used;
- timestamps and attempt identifiers;
- content digest or equivalent integrity evidence;
- transformation history; and
- any validation failures, uncertainty, or rejected fields.

Raw return material is evidence but is not yet admitted internal state.

Lazaretto may validate schemas, enforce size and type limits, normalize representations, strip or isolate executable content, detect malformed or unexpected structure, and separate embedded instructions from data. Sanitization may not silently alter substantive meaning.

Lazaretto outputs an **admitted provenance-bound artifact** or an explicit rejection/quarantine disposition. Internal cognition may reason over the admitted artifact and its preserved lineage; it may not consume the raw external payload directly.

## Provenance and lineage

The minimum external lineage is:

```text
external source or interaction
→ deterministic execution or sortie manifestation
→ raw payload
→ Lazaretto validation/transformation record
→ admitted provenance-bound artifact
→ internal deliberation
→ finding or decision
```

Returned evidence carries no operative authority. Admission of an artifact does not validate its truth; it validates only that the artifact may enter the trusted evidentiary domain under its declared provenance and transformation record.

## Non-authority

La Cortine must not:

- originate mission intent or planning;
- decide what evidence means;
- decide whether a consequential claim is true;
- create mission authorization;
- grant itself tool, credential, data, destination, or disclosure authority;
- treat technical reachability as permission;
- allow a sortie to become a persistent internal operative; or
- allow raw external material to bypass Lazaretto.

## Governing invariants

> **Internal cognition does not cross outward. External cognition does not cross inward. Only exact authorized requests leave through Iron Gate; only admitted provenance-bound artifacts return through Lazaretto.**

> **A tool call does not justify a sortie. External cognition does.**

> **Secrets belong to infrastructure, not cognition.**
