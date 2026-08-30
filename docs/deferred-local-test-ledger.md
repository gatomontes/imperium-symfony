# Deferred local test ledger

This ledger records local verification that could not be completed successfully
for the corresponding merged change. A pending entry is not evidence of a green
run.

## Cleared

### Provider Activation-Consumption Remediation Batch 7 terminal audit

- Original PR: `#595`
- Original merge commit: `cff19724e454af59e8841b7ac7a44b3cba2db128`
- Repair PR: `#597`
- Repair merge commit: `150da8756d1c12f49c9f53bd17b335ed3515f81c`
- First attempt: 11 tests, 107 assertions, 2 test-expectation failures.
- Repair: aligned corrupt-evidence refusal with
  `PEB702_EXECUTION_ADMISSION_INVALID` and normalized Markdown whitespace.
- Final status: `CLEAR_OPERATOR_REPORTED_AFTER_REPAIR`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderActivationConsumptionRemediationBatch7TerminalTest.php
```

- Counts: not supplied for the clear rerun and therefore not inferred.
- Runtime finding: no provider-boundary runtime behavior defect was observed.

## Cleared preparation follow-up

### Provider Execution Effect Readiness Preparation Batch 0

- Source PR: `#598`
- Source merge commit: `3963c87bd8138c4b488466d42e331ede37b75d22`
- First attempt: 3 tests, 22 assertions, 1 failure.
- Failure: the test joined Markdown headings with platform `PHP_EOL`; Windows
  supplied CRLF while the Git file retained LF.
- Runtime finding: no runtime or campaign-boundary defect was observed.
- Repair: use a line-ending-independent assertion scoped to the cleared Batch 7
  ledger entry.
- Final status: `CLEAR_OPERATOR_REPORTED_AFTER_LINE_ENDING_REPAIR`
- Clear rerun counts: not supplied and therefore not inferred.
- Required command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessPreparationBatch0Test.php
```

## Cleared Batch 1

### Provider Execution Effect Readiness Batch 1

- Source PR: `#600`
- Source merge commit: `7a41094a2b6f3991c1476bbfedd4851c7290a7ff`
- Final status: `CLEAR_OPERATOR_REPORTED`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch1Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared Batch 2

### Provider Execution Effect Readiness Batch 2

- Source PR: `#601`
- Source merge commit: `90392e2a4588ad87f15415cad9657771a0859bc3`
- Final status: `CLEAR_OPERATOR_REPORTED`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch2Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared Batch 3

### Provider Execution Effect Readiness Batch 3

- Source PR: `#602`
- Source merge commit: `2475e7fee2274996c4bda7c9053d1acb47112bd8`
- Final status: `CLEAR_OPERATOR_REPORTED`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch3Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared Batch 4

### Provider Execution Effect Readiness Batch 4

- Source PR: `#603`
- Source merge commit: `c169b3a60ae51d70674a2d3a11cf7d615657a5c8`
- Repair PR: `#604`
- Repair merge commit: `d5f1e8508ff03db32315a08f983bf39975689f9d`
- First attempt: 7 tests, 46 assertions, 1 classification-order failure.
- Repair: validate each existing source/profile/admission artifact before reading
  the next artifact so refused evidence is not masked by later absence.
- Final status: `CLEAR_OPERATOR_REPORTED_AFTER_CLASSIFICATION_ORDER_REPAIR`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch4Test.php
```

- Clear rerun counts: not supplied and therefore not inferred.

## Cleared Batch 5

### Provider Execution Effect Readiness Batch 5

- Source PR: `#605`
- Source merge commit: `389c6572c63e35617bd8d1a8bac2ca33d4f2b13a`
- Final status: `CLEAR_OPERATOR_REPORTED`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch5TerminalAuditTest.php
```

- Counts: not supplied and therefore not inferred.

## Cleared Batch 6

### Provider Execution Effect Readiness Batch 6

- Source PR: `#606`
- Source merge commit: `51145a56fa25a93e443b09ebd0c0fd6d71af62e4`
- Final status: `CLEAR_OPERATOR_REPORTED`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch6Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared Batch 7

### Provider Execution Effect Readiness Batch 7

- Source PR: `#607`
- Source merge commit: `cc4d5a08d6bd5347556ddbdcad753565b261a599`
- Final status: `CLEAR_OPERATOR_REPORTED`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch7Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared Batch 8

### Provider Execution Effect Readiness Batch 8

- Source PR: `#608`
- Source merge commit: `0f2ffcb7496eae2e15de562c42367d7482683484`
- Repair PR: `#609`
- Repair merge commit: `2ba5b4384e3d544e7935388419a120986db6c07a`
- First attempt: PHPUnit terminated before discovery with a fatal class-inheritance
  error; no test or assertion counts were produced.
- Repair: removed the accidental `final` modifier from the Batch 7 test class.
- Runtime finding: no production or provider-boundary defect was observed.
- Final status: `CLEAR_OPERATOR_REPORTED_AFTER_TEST_INHERITANCE_REPAIR`
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch8Test.php
```

- Clear rerun counts: not supplied and therefore not inferred.

## Cleared Batch 9

### Provider Execution Effect Readiness Batch 9

- Source PR: #610
- Source merge commit: 84b325577f643df080caa474b4736e086232b879
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch9Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared Batch 10

### Provider Execution Effect Readiness Batch 10 terminal audit

- Source PR: #611
- Source merge commit: b07b4f6e8988d979fa78234cdbabe0a1213ce38e
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch10TerminalAuditTest.php
```

- Counts: not supplied and therefore not inferred.

## Cleared principal-and-binding preparation

### Provider Effect Principal and Binding Activation Preparation Batch 0

- Source PR: #612
- Source merge commit: 77bdf540b87101cf350a1657992b1428429eb4e6
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationPreparationBatch0Test.php
```

- Counts: not supplied and therefore not inferred.

## Pending

None.

## Full-suite posture

No full-suite result was reported with this clear individual test. Any later
full-suite result must be recorded separately and identify the exact tested
commit.
