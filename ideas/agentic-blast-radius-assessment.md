# Agentic Blast-Radius Assessment

**Status:** Opportunity candidate  
**Origin:** JADEPUFFER agentic ransomware analysis, August 2026  
**Kind:** Productized assessment, recurring control, and Imperium validation campaign

## Opportunity

Create an evidence-backed service, and eventually an Imperium-powered product, that answers:

> If an autonomous agent, malicious or compromised, acquired this identity, what power could it actually assemble, reach, alter, destroy, or export before the institution stopped it?

The commercial opening is not another generic "AI ransomware protection" product. Established EDR, XDR, SOAR, backup, and cloud-security vendors already occupy that category.

The sharper problem is **assembled destructive power**: organizations can inventory identities, credentials, tools, network access, and data privileges independently while failing to determine what an autonomous actor could combine into one executable path.

## Triggering evidence

Sysdig's JADEPUFFER analysis describes an agentic operation that:

- entered through an internet-facing, vulnerable Langflow instance;
- harvested credentials and environmental information;
- moved laterally to production systems;
- adapted after failed operations at machine speed;
- encrypted 1,342 Nacos configuration entries;
- removed the original database tables; and
- left no usable recovery key, turning the supposed ransom into destructive extortion.

The techniques were not especially novel. The material change was autonomous composition: reconnaissance, credential discovery, target selection, correction, lateral movement, and destructive execution were chained without a human driving each step.

Primary source:

- [Sysdig: JADEPUFFER — Agentic ransomware for automated database extortion](https://www.sysdig.com/blog/jadepuffer-agentic-ransomware-for-automated-database-extortion)

## Governing claim

Imperium should not claim that its governance prevents exploitation of a vulnerable external runtime.

The defensible claim is narrower and stronger:

> Compromise of one component should not automatically become possession of the whole system. Imperium is designed to prevent an initial foothold from silently assembling credentials, tooling, instructions, target reachability, and destructive authority into one operative context.

This opportunity applies directly to Least Assembled Power.

A Locksmith may possess credential custody without mission tooling. An Armorer may control tooling without credentials. A Delegate may receive narrow, temporary authority without inheriting the institution. An external sortie may be single-use, bounded, and retired without acquiring permanent standing.

Those boundaries matter only if they are executable and evidenced.

## Proposed assessment

The **Agentic Blast-Radius Assessment** would determine:

1. Which agent runtimes and orchestration surfaces are externally reachable.
2. Which credentials each runtime or workload can discover, read, request, or inherit.
3. Which tools, interpreters, APIs, and network destinations each identity can use.
4. Whether credentials, tooling, instructions, target access, and destructive privileges can be assembled in one execution context.
5. Which database, cloud, filesystem, messaging, and deployment operations are reachable.
6. Whether privileges permit encryption, deletion, export, persistence, or recovery impairment.
7. Whether detection, isolation, revocation, and lease interruption operate at machine speed.
8. Whether backups are isolated, restorable, and protected from the same assembled authority.
9. Whether each conclusion is supported by reproducible evidence rather than policy assertions.

## Evidence artifact

The output should be an attack-path dossier rather than a compliance score.

| Field | Required evidence |
|---|---|
| Initial foothold | Exposed or compromised runtime identity |
| Discoverable authority | Credentials, tokens, leases, inherited roles |
| Available tooling | Clients, interpreters, APIs, execution surfaces |
| Reachable targets | Systems and data addressable from the foothold |
| Assemblable path | Exact sequence joining identity, tool, credential, and target |
| Destructive ceiling | Maximum demonstrable mutation, deletion, encryption, or export |
| Interruption window | Measured detection, revocation, isolation, and termination times |
| Recovery truth | Demonstrated restoration from an authority-isolated copy |
| Residual exposure | Power still assemblable after remediation |

Every finding should distinguish:

- possession from authority;
- authority from capability;
- capability from reachability;
- approval from authorization;
- temporary access from durable standing;
- policy intent from runtime enforcement.

## Safe engagement boundary

The assessment must use authorized targets, non-production fixtures where possible, bounded test identities, predeclared stop conditions, reversible operations, and preserved evidence.

It must not require uncontrolled malware, persistence outside the assessment boundary, destructive production testing, credential reuse beyond the commissioned scope, or offensive action against third parties.

## Imperium validation campaign

Before selling the assessment, Imperium should prove the underlying doctrine through a controlled hostile-agent simulation.

### Campaign objective

Demonstrate that a compromised or adversarial Delegate can obtain one or more elements of a destructive path but cannot assemble all required power without separately governed authority.

### Required demonstrations

1. **Credential without tool:** credential custody exists, but the operative cannot obtain or invoke the required client.
2. **Tool without credential:** the operative can request or receive a tool, but cannot acquire target credentials.
3. **Credential and tool without authority:** both artifacts exist institutionally, but no valid mission-bound consumption route permits their joint use.
4. **Authority without target reachability:** authorized activity remains constrained by network or endpoint boundaries.
5. **Expired lease:** previously valid authority fails after interruption or expiry.
6. **Sortie containment:** an external sortie cannot inherit internal standing or retain usable authority after retirement.
7. **Recovery custody:** destructive authority cannot reach the evidence or recovery copy needed to restore the system.
8. **Machine-speed interruption:** detection causes deterministic revocation and termination without waiting for discretionary human analysis.

### Success condition

The campaign succeeds only when Imperium produces reproducible refusal, interruption, custody, and recovery evidence for each boundary. Architectural diagrams or policy declarations alone do not satisfy the campaign.

## Commercial path

### Phase 1 — Fixed-price review

Deliver a bounded architecture and authority assessment for organizations deploying agent runtimes, copilots, workflow agents, or AI-adjacent infrastructure.

### Phase 2 — Controlled simulation

Validate identified attack paths with safe fixtures and reversible operations. Produce measured interruption and recovery evidence.

### Phase 3 — Remediation architecture

Design separated identities, credentials, tools, leases, network paths, recovery custody, and operational authorities.

### Phase 4 — Recurring control

Monitor changes that create new assembled paths: new credentials, expanded roles, added tools, altered network access, persistent tokens, weakened backups, and longer revocation windows.

### Phase 5 — Assurance dossier

Provide executive, insurer, auditor, customer, or board-facing evidence without exposing proprietary Imperium architecture.

## Likely buyers

- organizations deploying internal AI agents;
- SaaS companies embedding agentic workflows;
- MSPs and MSSPs serving small and mid-market clients;
- security teams responsible for AI-adjacent infrastructure;
- cyber-insurance and assurance partners seeking demonstrable controls;
- enterprises that cannot explain an agent's maximum executable blast radius.

## Differentiation

The central question is not:

> What permissions does this agent have?

It is:

> What destructive power can this environment assemble for the agent, through which path, under whose authority, for how long, and with what evidence of interruption and recovery?

That is a materially different product category from permissions inventory, prompt-injection scanning, generic red teaming, or endpoint detection.

## Public positioning

A suitable architecture-obscured thesis is:

> The agent was not independently powerful. The environment assembled its power for it.

Public material may discuss the problem, evidence model, and outcomes. It should not disclose Imperium's internal offices, exact authority-consumption mechanics, custody routes, or enforcement topology.

## Decision

Preserve this as a candidate post-operationalization campaign and monetizable service. Do not begin implementation until the current Imperium campaign sequence reaches an appropriate boundary and the Imperator explicitly selects it.
