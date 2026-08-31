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

## Cleared principal activation Batch 1

### Provider Effect Principal and Binding Activation Batch 1

- Source PR: #613
- Source merge commit: 43544c2514d6bf3fd5877cc57aa3091da5fa4945
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationBatch1Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared principal-production terminal audit

### Provider Effect Principal and Binding Activation Batch 2

- Source PR: #614
- Source merge commit: 8dd7df83ea527362d8c95a983bc225925169e81c
- Final status: CLEAR_OPERATOR_REPORTED
- Audit disposition: BATCH_2_TERMINAL_AUDIT_REFUSED_UNPROVEN_DECISION_AUTHORITY_PROVENANCE
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationBatch2TerminalAuditTest.php
```

- Counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance preparation

### Principal Activation Decision Authority Provenance Remediation Preparation Batch 0

- Source PR: #615
- Source merge commit: 88e9fce4be7549deb146f81e8d96aed570631772
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationPreparationBatch0Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 1

### Principal Activation Decision Authority Provenance Remediation Batch 1

- Source PR: #616
- Source merge commit: 288376b417927687600ece5a33b4414c39177d89
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch1Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 2

### Principal Activation Decision Authority Provenance Remediation Batch 2

- Source PR: #617
- Source merge commit: e69e7fa5c180c0fdc46b8413a203c630872ad0ad
- Repair PR: #618
- Repair merge commit: f5b481aff4b91d7c336121b49d3b33f445962a09
- First attempt: 4 tests, 27 assertions, 1 documentation-expectation failure.
- Repair: stated the already-binding authority prohibition as the contiguous
  phrase `may not issue or consume authority`.
- Runtime finding: no validator, fixture-store, or provider-boundary behavior
  defect was observed.
- Final status: CLEAR_OPERATOR_REPORTED_AFTER_DOCUMENTATION_REPAIR
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch2Test.php
```

- Clear rerun counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 3

### Principal Activation Decision Authority Provenance Remediation Batch 3

- Source PR: #619
- Source merge commit: 2ad07dfc82a27f38375ad67954b7b1bba681bb14
- Repair PR: #620
- Repair merge commit: 0861a6c2667676349fda4cd41049d2124f9b3fee
- First attempt: 4 tests, 112 assertions, 1 documentation-expectation
  failure.
- Repair: normalized `two same-root contenders` to the asserted term
  `same-root contention`.
- Runtime finding: all interruption, replay, refusal, contention and recovery
  assertions completed; no proof or provider-boundary behavior defect was
  observed.
- Final status: CLEAR_OPERATOR_REPORTED_AFTER_DOCUMENTATION_REPAIR
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch3Test.php
```

- Clear rerun counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 4

### Principal Activation Decision Authority Provenance Remediation Batch 4

- Source PR: #621
- Source merge commit: fe0050729e71f26e9daefeb5054044fb87b03e44
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch4Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 5 production refusal

### Principal Activation Decision Authority Provenance Remediation Batch 5 readiness refusal

- Source PR: #622
- Source merge commit: dce0d8b4988b2e3d4d85826cef3b64f832547013
- Final status: CLEAR_OPERATOR_REPORTED
- Refusal disposition: BATCH_5_PRODUCTION_REFUSED_SUCCESSOR_PRINCIPAL_AND_DECISION_LINEAGE_CONTRACTS_ABSENT
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5ReadinessRefusalTest.php
```

- Counts: not supplied and therefore not inferred.
- Runtime finding: production did not begin; no principal, authority, decision,
  credential, capability or provider-boundary effect was created.

## Cleared decision-authority provenance Batch 5A

### Principal Activation Decision Authority Provenance Remediation Batch 5A

- Source PR: #623
- Source merge commit: 2ef3741c8e6e85e7c5f63010af5d5d925a36d3d4
- Repair PR: #624
- Repair merge commit: a3d5888d62734b542caff2ff9187a8b14e4f2b2e
- First attempt: 3 tests, 28 assertions, 2 test-API errors.
- Repair: removed obsolete strictness booleans passed as the PHPUnit
  `assertNotContains()` message argument.
- Runtime finding: no contract or provider-boundary behavior defect was observed.
- Final status: CLEAR_OPERATOR_REPORTED_AFTER_PHPUNIT_COMPATIBILITY_REPAIR
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5AContractTest.php
```

- Clear rerun counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 5B

### Principal Activation Decision Authority Provenance Remediation Batch 5B

- Source PR: #625
- Source merge commit: 4a1331c7c8bef6f5fc7eeb379a2ec3490a60f6b2
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5BTest.php
```

- Counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 5C

### Principal Activation Decision Authority Provenance Remediation Batch 5C

- Source PR: #626
- Source merge commit: 22a5e14b8002aceb27a2ef647ab17171a2f73cc5
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5CProductionTest.php
```

- Counts: not supplied and therefore not inferred.

## Cleared decision-authority provenance Batch 6

### Principal Activation Decision Authority Provenance Remediation Batch 6 adversarial audit

- Source PR: #627
- Source merge commit: 30c2c1038dd90f6cd81bec7cf3afd687085093ab
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch6AdversarialAuditTest.php
```

- Counts: not supplied and therefore not inferred.
- Runtime finding: the audit path remained read-only and found no provenance,
  secret-exclusion, or non-authority-perimeter defect.

## Cleared decision-authority provenance Batch 7 terminal audit

### Principal Activation Decision Authority Provenance Remediation Batch 7

- Source PR: #628
- Source merge commit: ef078a261d2ce40ac02d38ffc98b3a3122801c70
- Repair PR: #629
- Repair merge commit: 286746a04a57a51955b49de331199331c8e4cf6a
- First attempt: 5 tests, 10 assertions, 4 undefined-helper errors.
- Repair: added the missing test-local Markdown `document()` helper.
- Runtime finding: no terminal doctrine, production, or provider-boundary defect
  was observed.
- Final status: CLEAR_OPERATOR_REPORTED_AFTER_TEST_HELPER_REPAIR
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch7TerminalAuditTest.php
```

- Clear rerun counts: not supplied and therefore not inferred.

## Cleared principal-and-binding resumption preparation

### Provider Effect Principal and Binding Activation Resumption Preparation Batch 0

- Source PR: #630
- Source merge commit: 23bd89d47c6c431f34acdc8a52427f41ea5bd902
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationResumptionPreparationBatch0Test.php
```

- Counts: not supplied and therefore not inferred.

## Cleared principal-and-binding resumption Batch 1

### Provider Effect Principal and Binding Activation Resumption Batch 1

- Source PR: #631
- Source merge commit: f04bf4a1791827facb03eac9b82666cf1cd9f7a2
- Final status: CLEAR_OPERATOR_REPORTED
- Clear command:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationResumptionBatch1Test.php
```

- Counts: not supplied and therefore not inferred.

## Pending

None.

## Full-suite posture

No full-suite result was reported with this clear individual test. Any later
full-suite result must be recorded separately and identify the exact tested
commit.
