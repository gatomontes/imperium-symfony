# Deferred local test ledger

This ledger records local verification that could not be completed successfully
for the corresponding merged change. A pending entry is not evidence of a green
run.

## Pending repair rerun

### Provider Activation-Consumption Remediation Batch 7 terminal audit

- Original PR: `#595`
- Original merge commit: `cff19724e454af59e8841b7ac7a44b3cba2db128`
- Tested working-copy frontier: `d3b9d7ce40c396bcd60016fce2ca2dc1446d1c8c`
- Attempt status: `FAILED_TEST_EXPECTATIONS_RUNTIME_REFUSAL_INTACT`
- Reported result: 11 tests, 107 assertions, 2 failures.
- Failure 1: corrupt combined evidence was correctly refused as
  `PEB702_EXECUTION_ADMISSION_INVALID`; the test incorrectly required the
  lower persistence error `PST113_IMMUTABLE_RECORD_TAMPERED`.
- Failure 2: terminal documentation contained every required semantic claim,
  but one phrase crossed a legitimate Markdown line wrap and the assertion did
  not normalize whitespace.
- Runtime finding: no provider-boundary runtime behavior defect was observed.
- Repair posture: correct the exact refusal expectation and normalize
  documentation whitespace only.
- Rerun status: `PENDING_OPERATOR_LOCAL_RUN_AFTER_REPAIR_MERGE`
- Required command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderActivationConsumptionRemediationBatch7TerminalTest.php
```

- Scope: adversarial expiry, corrupt reconstruction, missing credential,
  intact-v1 refusal, exact v2 replay, recursive secret exclusion, shared
  activation winner, provider-path exclusion, and repeated documentation audit.
- Rule: do not mark Batch 7 locally verified or use it as local-suite evidence
  until the operator reports a clear rerun against the repair merge.
- On failure: preserve the exact output and open only the demonstrated
  remediation boundary.
- On success: replace the pending status with the reported test count,
  assertion count, execution date and tested commit.

## Full-suite posture

No full-suite run is required solely by this test-only repair. Any later
full-suite result must be recorded separately and identify the exact tested
commit.
