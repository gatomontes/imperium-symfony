# Codex runtime-source audit

Date: 2026-08-25  
Audited commit: `7bafa498a18f10d122771c65b13b7250c8be9f51`

## Verdict

The runtime is behaviorally green under the user's PHP 8.4.14 suite after PR #329, but the source-formatting cleanup is not complete. The existing formatting guard must remain scoped. Widening it across the runtime now would either fail honestly or require an allowlist that merely hides known debt.

## Scope and method

Codex read all 376 PHP files under `src/Imperium/Runtime` and measured physical line count and maximum physical-line length. The scan separately checked literal high-risk formatter artifacts: adjacent strict-types/namespace declarations, malformed namespace-qualified construction, missing token separation, and the split spaceship operator that caused PR #329.

This is a source-integrity/readability audit. It does not replace PHP linting or PHPUnit execution.

## Quantitative findings

- Runtime PHP files audited: **376**
- Severely compressed files (more than 500 bytes in ten physical lines or fewer): **18**
- Files with at least one physical line longer than 240 characters: **146**
- Files with a line longer than 500 characters: **92**
- Files with a line longer than 1,000 characters: **63**
- Files with a line longer than 2,000 characters: **24**
- Files with a line longer than 4,000 characters: **12**
- Additional literal `<= >` occurrences after PR #329: **0**

The 146 oversized-line files are concentrated in Curia (29), Senate (29), Foundry (16), Conscription (16), Garrison (9), Authorship (8), Oracle (8), Imperator (7), Laboratorium (7), Guildhall (6), Clavium (5), Citadel (4), and Mission (2).

## Eighteen severe compression targets

1. `Authorship/SymfonyAiSubordinatePersonaSectionAuthorshipGateway.php`
2. `Citadel/DelegateMissionBoundedCognitionTurnService.php`
3. `Clavium/DelegateMissionModelAccessAttestationService.php`
4. `Clavium/DelegateMissionProviderInvocationActivationService.php`
5. `Curia/DelegateMissionBoundedCognitionCommissionService.php`
6. `Curia/DelegateMissionCognitionResultDispositionService.php`
7. `Curia/DelegateMissionControlIntakeDispositionService.php`
8. `Curia/DelegateMissionModelCriteriaRequestService.php`
9. `Curia/DelegateMissionOracleCommissionIssuanceService.php`
10. `Curia/DelegateMissionResourceInvocationReadinessAssessmentService.php`
11. `Curia/DelegateMissionReturnAuthorizationService.php`
12. `Foundry/SubordinatePersonaSpecificationService.php`
13. `Foundry/SymfonyAiSubordinatePersonaSpecificationCognitionGateway.php`
14. `Garrison/DelegateMissionTerminalReturnService.php`
15. `Imperator/DelegateMissionModelCriteriaDecisionService.php`
16. `Imperator/DelegateMissionResourceInvocationDecisionService.php`
17. `Senate/ProfileExaminationDispositionService.php`
18. `Senate/SymfonyAiProfileExaminationDispositionCognitionGateway.php`

All paths are relative to `src/Imperium/Runtime`.

## Risk distinction

The audit distinguishes executable defects from ugly-but-valid legacy formatting. PR #329 was an executable defect: automated whitespace expansion changed `<=>` into `<= >`. The remaining severe candidates are not declared defective merely because they are compressed. Each must be expanded with token-preserving changes and verified by PHP lint plus the complete PHPUnit suite.

## Recommended cleanup sequence

1. **Batch A — Delegate control plane:** the two Clavium, six Curia, two Imperator, Citadel bounded-turn, and Garrison terminal-return targets.
2. **Batch B — cognition and legacy profile examination:** the two Foundry targets, Authorship gateway, and two Senate disposition targets.
3. **Batch C — long-line reduction:** work outward from lines longer than 2,000 characters, then 1,000, then 500; expand by coherent lifecycle cluster rather than arbitrary file count.
4. **Closeout:** rerun the 376-file audit, widen the guard to every compliant cluster, reconcile this report and the backlog, run PHP lint and PHPUnit locally, then submit the cleaned runtime to Blackquill.

## Guard decision

Do not install a runtime-wide 240-character guard yet. Keep the current migrated-cluster guard and expand its explicit coverage with every verified cleanup batch. The final global guard is justified only when the runtime-wide scan reaches zero violations.

## Closeout addendum — 2026-08-25

Blackquill corrected the original proposed finish line: the 240-character threshold is an Imperium readability rule, not a PSR-12 hard limit, and secondary long-line debt must not block operational evidence.

Cleanup Batch A expanded thirteen severe Delegate control-plane targets. Cleanup Batch B expanded the remaining five Authorship, Foundry, and Senate targets. Both batches passed explicit local PHP lint followed by the complete PHPUnit suite.

Codex then reread all 376 runtime PHP files at commit `15227cf8cf6ca467c7cf71f64182073ea1a7ba7a`. Result: **zero files larger than 500 bytes remain at ten physical lines or fewer**, and no literal split `<= >` occurrence remains.

The severe-source cleanup gate is closed. Thirteen valid but tightly adjacent declaration/namespace style artifacts and the previously measured long-line population remain secondary formatting debt. Neither is represented as PSR-12 compliance, and neither blocks crash demonstrations.
