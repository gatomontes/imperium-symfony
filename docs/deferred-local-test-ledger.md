# Deferred local test ledger

This ledger records local verification that could not be run when the
corresponding change was merged. A pending entry is not a failure, but it is
also not evidence of a green run.

## Pending

### Provider Activation-Consumption Remediation Batch 7 terminal audit

- PR: `#595`
- Merge commit: `cff19724e454af59e8841b7ac7a44b3cba2db128`
- Status: `PENDING_OPERATOR_LOCAL_RUN_POWER_OUTAGE`
- Required command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderActivationConsumptionRemediationBatch7TerminalTest.php
```

- Scope: adversarial expiry, corrupt reconstruction, missing credential,
  intact-v1 refusal, exact v2 replay, recursive secret exclusion, shared
  activation winner, provider-path exclusion, and repeated documentation audit.
- Rule: do not mark Batch 7 locally verified or use it as local-suite evidence
  until the operator reports the result.
- On failure: stop dependent terminal closure claims, preserve the failure
  output, and open an exact remediation batch.
- On success: replace the pending status with the reported test count,
  assertion count, execution date and tested commit.

## Full-suite posture

No full-suite run is required solely by this documentation transition. Any
later full-suite result must be recorded separately and must identify the exact
tested commit.
