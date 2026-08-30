# Provider Activation-Consumption Remediation — Batch 5 revocation production

## Result

BATCH_5_LAWFUL_ATOMIC_ACTIVATION_REVOCATION_PRODUCTION_COMPLETE

A competent authorized decision can now issue one exact bounded single-use activation-revocation
authority. The issuance consumes only its own issuance permission; the resulting authority remains
unconsumed until revocation wins.

ProviderBindingActivationRevocationWinnerService consumes that exact authority into one immutable
activation-keyed record that is simultaneously the revocation fact and consumption evidence.

## Shared winner

Combined admission and authorized revocation use the same lock:
governed-provider-execution-admission:<activation-id>.

- If revocation wins first, combined admission observes the deterministic winner and refuses.
- If combined admission wins first, revocation refuses and cannot erase committed effect-start.
- Exact revocation replay reconstructs the same winner without re-consuming authority.
- Changed authority or reason refuses.
- Expired, already consumed, non-exercisable or revoked authority refuses.

The combined-admission service now recognizes only the corrected authorized revocation-winner
identity. The prohibited separate component records are not runtime inputs.

## Closed effects

Authority issuance and revocation production handle no credential or capability, activate no
principal or source provider binding, invoke no provider, perform no external I/O, send no byte,
authorize no retry, open neither Iron Gate nor Lazaretto, and claim no provider outcome.

## Next gate

Only remediation Batch 6 may next be considered: migrate stationary credential resolution to accept
only the v2 combined admission. The callback-local secret-free no-I/O proof must remain unchanged in
effect and v1 admission must not be accepted as corrected consumption evidence.
