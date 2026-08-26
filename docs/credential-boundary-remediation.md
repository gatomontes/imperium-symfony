# Credential boundary remediation

This is a separately bounded runtime-remediation program. It is neither Crash Demonstration 5, Delegate Mission Step 70, nor Runtime Integrity Hardening Step 36.

## Exact boundary

Credential material may be resolved only inside a broker consumption callback for a capability issued by that exact broker instance. Cognitive callers receive neither the credential reference nor its value. Provider adapters receive the secret only for the duration of the exact authenticated operation.

The end-state must also remove `%env(DEEPSEEK_API_KEY)%` from every directly invokable Symfony AI platform definition. Until that migration is complete, the credential-boundary evidence gate remains open and no documentation may claim that direct provider invocation without a Clavium lease is impossible.

## Attack matrix

| Attack | Required disposition |
| --- | --- |
| Construct a capability without the broker | Reject before secret resolution |
| Replay an issued single-use capability | Reject before provider execution |
| Present a capability issued by another broker instance | Reject before secret resolution |
| Present an expired capability | Reject before secret resolution |
| Present a mismatched commission, operation, or credential reference | Reject before provider execution |
| Resolve a configured Symfony provider platform without a live Clavium grant | No credential-bearing platform may exist |
| Invoke the governed Delegate route with an exact live lease and claim | Permit one brokered adapter call only |

## Migration batches

1. Bind capability authenticity to the issuing broker and prove forged, foreign, expired, and replayed capabilities fail stopped. **Complete.**
2. Separate capability issuance from credential consumption and require an authoritative Clavium grant before issuance. **Complete for the Delegate provider route:** the claim-bound Clavium broker requires the exact intact persisted invocation claim, consumed lease and turn authority, exact DeepSeek binding, pre-I/O state, and live expiry before it delegates capability issuance.
3. Replace credential-bearing Symfony platform definitions with a brokered platform factory or equivalent gated runtime construction.
4. Migrate each cognition gateway by governance cluster while preserving its existing authority contract.
5. Run the credential-boundary bypass demonstration and retain private evidence with a sanitized external summary.

The migration must not place credentials, environment dumps, or credential-adjacent diagnostics in records, exceptions, test output, or Git.

## Migration status

The Citadel Legate gateway is the first direct Symfony-agent consumer removed from the credential-bearing platform. Its existing activation and at-most-once claim are now reread by a Legate-specific claim-bound Clavium broker before the DeepSeek adapter can receive a credential. The removed agent service can no longer be injected as a bypass.

Thirty-one direct platform-bound agent definitions remain across the main pre-Delegate governance clusters after the operational definition's removal. The isolated sortie runtime adds one more direct agent and a second `%env(DEEPSEEK_API_KEY)%` platform definition. The system-wide inventory is therefore 32 remaining agents across nine clusters and two credential-bearing platforms. The final evidence gate is not closed.

## Next bounded lifecycle: Operational Cognition Access

The first remaining migration is the operational Manifestation route. It is governed by the following exact six-boundary sequence:

1. Curia authorizes one bounded internal execution iteration.
2. Imperator separately authorizes or refuses the exact provider/model resource expenditure.
3. Clavium validates that decision and issues one opaque, expiring lease.
4. A durable invocation claim consumes that lease and the cognition authority atomically.
5. The broker constructs the provider adapter for that call only.
6. The Manifestation receives output, never credentials or network authority.

This sequence is the separately bounded **Operational Cognition Access lifecycle**. It is not Delegate Mission Step 70 and not Runtime Integrity Hardening Step 36. Curia's execution authorization continues to grant neither credential nor network authority. Imperator alone decides the exact provider/model expenditure; Clavium validates and activates that decision but does not make it.

Implementation proceeds in these batches:

1. define the cognition request, independent Imperator resource decision, and their exact bindings;
2. issue an opaque Clavium lease bound to the authorized provider/model, Manifestation, input, authority, and expiry;
3. atomically consume the lease and cognition authority into one durable, idempotent invocation claim before provider I/O;
4. replace the operational Manifestation's directly injected Symfony agent with a claim-bound broker that constructs a per-call adapter;
5. prove refusal, missing/mismatched/expired/consumed/superseded authority, concurrency, crash, and secret-exclusion behavior; and
6. migrate the remaining governance clusters, remove the global credential-bearing platform, and only then run the final bypass demonstration.

The operational route's direct-agent bypass is removed and its hostile proof matrix is complete through Batch 5. Completion of this first migration does not close the system-wide gate while any other directly configured agent or `%env(DEEPSEEK_API_KEY)%` platform definition remains.

Operational Cognition Access Batch 1 is implemented after `70e4dcd`. Curia now seals the exact single-iteration cognition request without credential or network authority, and Imperator independently authorizes or refuses the exact DeepSeek model/configuration and resource ceiling. An authorized decision opens only a single-use Clavium lease-activation authority; it does not itself issue a lease, resolve a credential, or permit provider invocation.

Operational Cognition Access Batch 2 now issues the exact opaque Clavium lease. The lease is single-use, unconsumed, expiring, fully lineage-bound, and contains no credential reference, credential material, endpoint secret, network authority, or provider-invocation authority. The operational gateway remains a direct-agent bypass, and the lease remains unusable, until Batch 3 atomically consumes it with the cognition authority into a durable pre-I/O claim and Batch 4 installs the claim-bound broker.

Operational Cognition Access Batch 3 now atomically consumes the explicit cognition-authority identity and opaque lease into one durable, single-winner pre-I/O invocation claim. The claim carries the stable provider idempotency identity but no credential material or reference; external I/O remains unstarted. The operational gateway remains a direct-agent bypass until Batch 4 removes its configured Symfony agent and installs the claim-bound broker.

Operational Cognition Access Batch 4 removes that operational agent and direct `AgentInterface` injection. The gateway now resolves exactly one authorization-bound durable claim, validates its live consumed lease and cognition authority, resolves the credential only inside the claim-bound broker callback, invokes the strict DeepSeek adapter with the stable idempotency identity, and reuses the sealed response-envelope and provider-outcome journal contracts. This closes the operational direct-agent route, not the system-wide gate; Batch 5 must complete the hostile proof matrix and the remaining governance clusters still require migration.

Operational Cognition Access Batch 5 completes that hostile proof matrix and closes a replay-ordering weakness found by the proof: the operational gateway now atomically reserves its invocation journal before broker consumption. Exact replay, concurrent reuse, or recovery after an interrupted reservation is rejected before a second credential can be issued or resolved. The journal transitions to external-I/O-started only inside the broker callback immediately before adapter invocation. Missing, malformed, mismatched, expired, consumed, superseded, refused, interrupted, unknown-outcome, and secret-exclusion cases are covered across the Batch 1–5 suites. The next remediation boundary is inventory and ordered migration of the remaining governance clusters; the global gate remains open.

Credential-boundary Batch 6 seals the exhaustive inventory in `docs/credential-boundary-agent-inventory.json` and guards it with an automated configuration/source-injection completeness test. It classifies 31 main-runtime definitions plus the separately configured sortie agent. The ordered migration is: common governance invocation substrate; Foundry; resident requirements; section authorship; Laboratorium; Senate Profile examination; Senate Persona confirmation; Guildhall; Curia; La Cortine sortie; then global platform removal and the final bypass proof. Each cluster retains its own existing authority semantics and receives one claim per exact stage/Seat operation; no omnibus governance credential grant is permitted.

Credential-boundary Batch 7 implements the common internal-governance cognition substrate without migrating any gateway. A cluster-specific resolver must reread one exact native authority and normalize its immutable identity/digest, cluster/type, Seat, purpose, input digest, single-use state, and expiry. The common layer then seals the request, independent Imperator decision/refusal, opaque Clavium lease, atomic lease-plus-governance-authority invocation claim, and the existing journal/response-envelope transitions for the governance claim family. The registry fails stopped unless exactly one resolver owns the cluster/type. No credential is resolved, no agent is removed, and the inventory remains 32.
