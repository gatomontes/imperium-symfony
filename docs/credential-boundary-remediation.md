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
