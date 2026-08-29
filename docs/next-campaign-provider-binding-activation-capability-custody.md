# Next campaign: Provider Binding Activation and Capability Custody

## Status

`CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY`

Governed Tool and Provider Separation is terminal through Batch 9. It proves that tool authority,
provider selection, credential eligibility, provider request/response translation and normalized
admission can remain separate. It also proves that the live command cannot lawfully resume: every
produced provider binding remains `BOUND_INACTIVE`, and `EnvironmentCredentialBroker` retains an
issued opaque capability only inside the issuing process.

This campaign addresses exactly those two missing execution facts. Only Preparation Batch 0 is
authorized. Preparation is read-only inventory and classification; it defines no contract and
changes no runtime behavior.

## Preparation questions

Preparation must answer:

1. Which competent office and exact source decision may authorize activation of one provider
   binding for one execution?
2. Is activation a new immutable record, a status transition, or a separately consumed lease?
3. What exact tool authority, effect authorization, execution claim, provider binding, assurance
   profile, destination policy and expiry must activation bind?
4. Which component may mint an opaque capability, and which component may take custody of it?
5. How can the same already-issued capability cross a process boundary without persisting secret
   material, reconstructing authority or issuing a replacement?
6. What durable identifier and digest can prove capability identity while keeping the credential
   reference and secret outside ordinary records, logs and exceptions?
7. What record must commit before capability delivery, credential resolution or the first outbound
   byte?
8. Can activation and capability custody be consumed atomically under the existing one-root
   persistence model?
9. What happens on crash before custody transfer, after transfer, after credential resolution and
   after provider callback start?
10. How are replay, double delivery, competing processes, expiry, revocation and stale activation
    refused?
11. Which recovery facts can be reconstructed read only without reissuing or re-resolving anything?
12. Which guarantees remain impossible under environment-backed credentials, one-root locks or
    multi-host execution?

## Required classifications

Every relevant record, service and boundary must be classified as `EXISTS_CANONICALLY`,
`EXISTS_FRAGMENTED`, `ABSENT`, or `DEFERRED_BOUNDARY`, with its exact producer, consumer and
non-authorities. Preparation must distinguish:

- provider selection from provider-binding activation;
- credential eligibility from capability issuance and custody;
- opaque capability identity from credential reference and secret material;
- custody transfer from credential resolution;
- execution admission from network effect start;
- crash recovery from authorization to retry;
- one-process object identity from cross-process durable authority;
- local atomicity from distributed execution guarantees.

## Sequence to assess, not authorize

Preparation must determine whether the smallest safe campaign resembles:

1. activation and custody contracts;
2. separately governed provider-binding activation authority;
3. immutable single-execution activation;
4. opaque capability custody and one-time delivery;
5. atomic execution admission consuming activation and custody;
6. live-command migration to exact pre-existing records;
7. crash, contention, replay, expiry, revocation and secret-exclusion proof;
8. terminal audit and Provider Execution Assurance resumption decision.

The inventory may split, narrow, reorder or refuse this sequence. No numbered implementation batch
is authorized by this proposal.

## Closed boundaries

Preparation Batch 0 may not define a runtime contract, activate a provider binding, issue, persist,
reconstruct, transfer, consume or resolve a credential capability, expose a credential reference or
secret, migrate the command, invoke AgentMail or another provider, perform external I/O, open Iron
Gate or Lazaretto, or change inbound webhook, sortie, credential-platform, revocation, propagation,
telemetry, reassessment, containment or incident behavior.

Provider Execution Assurance remains paused. Refusal is a valid preparation result if exact
cross-process custody cannot preserve capability identity without manufacturing authority.
