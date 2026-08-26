# Runtime cleanup Batch A complete

Cleanup Batch A expands the thirteen severely compressed Delegate control-plane services identified by the Codex audit and prioritized by Blackquill.

## Expanded cluster

- Clavium access attestation and provider-invocation activation
- Curia bounded cognition commissioning, result disposition, control intake, model criteria, Oracle commissioning, resource readiness, and return authorization
- Imperator model-criteria and resource-invocation decisions
- Citadel bounded cognition turn execution
- Garrison terminal return

## Change discipline

The expansion is insert-only: original source bytes remain in their original order and only whitespace is inserted outside quoted strings and comments. The transformer never rewrites operator tokens. This specifically prevents recurrence of the PR #329 `<=>` regression.

All thirteen files now contain 147–218 physical lines and their maximum physical-line lengths range from 126–185 characters. The formatting regression guard now covers the complete Batch A cluster.

## Required verification

Run explicit PHP lint on every changed runtime file before PHPUnit. On PowerShell from the repository root:

```powershell
$files = @(
    'src/Imperium/Runtime/Clavium/DelegateMissionModelAccessAttestationService.php',
    'src/Imperium/Runtime/Clavium/DelegateMissionProviderInvocationActivationService.php',
    'src/Imperium/Runtime/Curia/DelegateMissionBoundedCognitionCommissionService.php',
    'src/Imperium/Runtime/Curia/DelegateMissionCognitionResultDispositionService.php',
    'src/Imperium/Runtime/Curia/DelegateMissionControlIntakeDispositionService.php',
    'src/Imperium/Runtime/Curia/DelegateMissionModelCriteriaRequestService.php',
    'src/Imperium/Runtime/Curia/DelegateMissionOracleCommissionIssuanceService.php',
    'src/Imperium/Runtime/Curia/DelegateMissionResourceInvocationReadinessAssessmentService.php',
    'src/Imperium/Runtime/Curia/DelegateMissionReturnAuthorizationService.php',
    'src/Imperium/Runtime/Imperator/DelegateMissionModelCriteriaDecisionService.php',
    'src/Imperium/Runtime/Imperator/DelegateMissionResourceInvocationDecisionService.php',
    'src/Imperium/Runtime/Citadel/DelegateMissionBoundedCognitionTurnService.php',
    'src/Imperium/Runtime/Garrison/DelegateMissionTerminalReturnService.php'
)
$files | ForEach-Object { php -l $_ }
php bin/phpunit
```

Cleanup Batch B must not begin until lint and the complete PHPUnit suite are green.
