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

Thirty-two direct platform-bound agent definitions remain across the pre-Delegate governance clusters. The shared `%env(DEEPSEEK_API_KEY)%` platform definition therefore remains explicitly classified as an open bypass until those clusters are migrated; the final evidence gate is not closed.

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

The current `SymfonyAiOperationalExecutionCognitionGateway` remains an open bypass until batches 1–5 are merged. Completion of that first migration does not close the system-wide gate while any other directly configured agent or `%env(DEEPSEEK_API_KEY)%` platform definition remains.

Operational Cognition Access Batch 1 is implemented after `70e4dcd`. Curia now seals the exact single-iteration cognition request without credential or network authority, and Imperator independently authorizes or refuses the exact DeepSeek model/configuration and resource ceiling. An authorized decision opens only a single-use Clavium lease-activation authority; it does not issue a lease, resolve a credential, or permit provider invocation. Batch 2 must implement that opaque lease boundary.
