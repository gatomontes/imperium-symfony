# Blackquill review of the Codex runtime-source audit

Date: 2026-08-25  
Reviewed audit: `docs/handoffs/runtime-source-audit-codex.md`

## Claim

Codex claims that the runtime cleanup should proceed through eighteen severe compression targets, then through the remaining 146 files with physical lines longer than 240 characters, before a final global guard and closeout.

## Weak points

### 1. The 240-character threshold is a house rule wearing a borrowed uniform

PSR-12 does not define a 240-character hard limit. Calling zero 240-character violations the condition for “PSR-12/readability cleanup” conflates a local readability preference with an external standard. Keep the guard if it is useful, but name it honestly: an Imperium bounded-readability rule.

### 2. The audit counts ugliness, not risk

A 241-character line and a 6,886-character class compressed into six lines are both counted as one violation. That arithmetic is tidy and operationally stupid. The eighteen severe files are credible cleanup targets. The other 128 oversized-line files vary radically in urgency and must not become an undifferentiated gate.

### 3. The method that caused the defect is insufficiently constrained

PR #329 exists because whitespace expansion damaged `<=>`. “Token-preserving changes” is an intention, not a control. Every formatting batch needs an actual parse check before the behavioral suite. PHPUnit discovering a parse error eventually is not the same as linting every changed PHP file deliberately.

### 4. Several alleged token risks are valid PHP written badly

Forms such as tightly adjacent declarations or namespace-qualified construction may violate the intended style without necessarily being parse failures. The audit correctly separates the confirmed split operator from the remaining debt, but its language still flirts with treating aesthetic offenses as executable hazards. Stop doing that.

### 5. The proposed sequence lets housekeeping obstruct evidence

The project needs crash demonstrations because its strongest claims concern recovery, custody, and effectively once-only behavior. Formatting 146 files before producing that evidence would be bureaucratic self-harm: polishing the witness box while postponing the testimony.

## Verdict

Codex’s inventory is useful. Its finish line is not.

The eighteen severe compression targets should be cleaned in two bounded batches, with PHP lint and the complete PHPUnit suite after each. Once those eighteen are clean and guarded, the cleanup gate is sufficient for crash demonstrations. The remaining long-line debt should continue later by coherent lifecycle cluster; it must not block operational evidence.

## Stronger execution order

1. Cleanup Batch A: the Delegate control-plane severe targets.
2. PHP lint every changed PHP file, then run the complete PHPUnit suite.
3. Cleanup Batch B: the cognition, Foundry, Authorship, and legacy Senate severe targets.
4. PHP lint every changed PHP file, then run the complete PHPUnit suite.
5. Rerun the severe-compression audit and extend the explicit guard to those cleaned files.
6. Begin crash demonstrations: operational construction, deployment custody, unknown provider outcome, and terminal retirement.
7. Continue secondary long-line normalization later, by coherent lifecycle cluster, without pretending that 240 characters came down from Mount PSR.

## Exit criterion for the cleanup gate

- Zero runtime files larger than 500 bytes remain at ten physical lines or fewer.
- Every changed PHP file passes explicit lint.
- The complete PHPUnit suite is green.
- The formatting regression guard covers every severe target that was expanded.
- Secondary long-line debt remains recorded and does not block crash evidence.
