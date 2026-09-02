# Native Integration Remediation implementation v1

## Batch 2 native successor

`NATIVE_INTEGRATION_BATCH_2_NATIVE_SUCCESSOR_IMPLEMENTED`. Full suite: 1864 tests, 44326 assertions passed.
Native successor creation now resolves original activation, attestation, assurance, boundary, descriptor and activation-decision production records. It publishes the target, attributed creation decision, consumed creation authority, selected lifecycle successor and creation winner together. Exact successor and creation references are sealed into native transition issuance. No offline successor is imported. Assurance remains EVIDENCE_ADMITTED_NO_EXECUTION_AUTHORITY evidence; it is not promoted to execution authority.
Exact handoff: `docs/handoffs/executable-atomic-transition-native-integration-remediation-batch-2-complete.md`.
Additionally read: `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator.php`,
`src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceProductionContract.php`,
`src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicCreationWinnerBoundaryContract.php`,
`src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciledTargetContract.php`,
`src/Imperium/Runtime/Imperator/ProviderBindingActivationReconciledDecisionInputContract.php`,
`src/Imperium/Runtime/LaCortine/ProviderImplementationBindingContract.php`,
`src/Imperium/Runtime/LaCortine/ProviderImplementationBindingService.php` (binding writer),
`tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5CProductionTest.php` (production basis),
`tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch2Test.php` (fixture base header).

## Approved continuation and Batch 1

The operator explicitly approved the proposed signed Root-act trust route.
The dependency decision below is historical and resolved for implementation.
`NativeState`, `NativeRootActs`, `NativePrincipal` and `NativeAuthority` implement
public verification, native source loading, pending constitution, separate signed
activation/revocation and a principal -> decision -> value-shaped issuance target
-> custody -> single-use authority aggregate. No live identity is provisioned.

The trusted host operator owns `var/imperium/operator-root/transition-trust/identity.json`.
There is no application endpoint for installing or changing it. Its exact schema
binds anchor id, operator, instance, public key, validity and revocation. Rotation
is a trusted maintenance replacement under the `native-provider-transition` lock
and the `immutable:` hash lock of the trust directory; it invalidates outstanding
old-anchor acts. A revoked or missing identity never falls back to prior identity
or fixture keys. This conservative version cannot prove historical eligibility
after removing its original anchor; reconstruction must report unknown then.
Private signing stays outside runtime. Public verification requires ext-sodium.

The signed act includes exact purpose/action, physical storage identity,
operationalization seal, original native principal, preserved scopes,
source/next generation, transition scope, binding, operation, target and validity.
Native source records are reloaded; no scope is inferred from configured hashes.
Existing v2 lifecycle is checked with explicit expiry and generation checks.
Native v3 records with unsupported old lifecycle dispositions refuse migration;
the loader never treats an embedded projection as a native principal record.

The same global native lock encloses source-directory locks used by immutable
writers, including trust, principal, lifecycle, descriptor, activation, boundary
and attestation. Events use the retained fsync/pending/rename aggregate store.
Independent record publication is not a multi-record transaction; issuance keeps
decision, custody and authority in one aggregate. Constructor injection is the
existing opt-in service boundary; no default container activation is introduced.

Batch 1 focused proof: 4 tests, 18 assertions passed. Full suite: 1858 tests,
44285 assertions passed. The exact successor/admission refinement remains subsequent work. Batch 1
alone does not prove eligible-successor targeting or canonical transition success.

Additional sources followed for this continuation:

- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationService.php`
- `src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciliationContractValidator.php` (first 240 lines)
- `src/Imperium/Runtime/LaCortine/GovernedProviderExecutionSuccessorAdmissionV3ContractValidator.php`
- `src/Imperium/Runtime/Persistence/ImmutableRecordStore.php`
- `src/Imperium/Runtime/Persistence/AtomicTransition.php`
- `src/Imperium/Runtime/Imperator/ImperatorRuntimePrincipalVersionContract.php`
- `tests/Imperium/Runtime/ProviderBindingActivationStateReconciliationBatch2Test.php` (first 80 lines)
- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalCanonicalActivationService.php`
- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationCanonicalFixtureStore.php`
- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationContractValidator.php` (first 165 lines)
- `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceProductionService.php` (first 195 lines)
- `tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationResumptionBatch2Test.php` (fixture and boundary portions)
- `tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationResumptionBatch3Test.php` (first 125 lines)
- `tests/Imperium/Runtime/ProviderEffectPrincipalBindingActivationResumptionBatch4Test.php` (first 80 lines)
- `tests/Imperium/Runtime/ProviderExecutionEffectReadinessBatch7Test.php` (fixture source beginning at line 198)

The operator authorized completion of the seven remaining batches and PHPUnit
after each batch. Preparation-only restrictions in historical handoffs no longer
limit implementation. Live provisioning, credentials, provider effects, external
I/O and retry remain excluded. Tests use disposable local roots only.

## Batch 1 dependency decision

`NATIVE_INTEGRATION_BATCH_1_BLOCKED_ROOT_TRUST_POLICY_REQUIRED`

Batch 1 was begun but is not complete. Batches 2 through 7 have not started.
The campaign has seven unfinished planned stages. The prior terminal refusal,
`EXECUTABLE_ATOMIC_TRANSITION_TERMINAL_AUDIT_REFUSED_NATIVE_INTEGRATION_ABSENT`,
has not been removed. This is a dependency finding, not a terminal audit.

The new operator request authorizes implementation of the campaign. Historical
Preparation Batch 0 wording does not block that work. The unresolved decision is
which independent identity and authenticated ingress may speak as Operator Root
when an existing principal gains the exact transition competence. Code-writing
authorization supplies neither an instance-specific act nor a trust-anchor policy.
Missing live provisioning alone would not prevent implementing a defined route.

| Finding | Classification | Exact evidence |
| --- | --- | --- |
| Existing constitution routes | EXISTS_CANONICALLY | `ImperatorPrincipalConstitutionAuthorityContract::ROUTES` contains only `FUTURE_INSTANCE_ROOT_ESTABLISHMENT` and `EXISTING_INSTANCE_REMEDIATION`; transitions constitute an initial or remediate a missing principal. Neither widens an existing generation. |
| Root authenticity behind those routes | EXISTS_FRAGMENTED | `ImperatorPrincipalProvenanceFixtureStore::operatorRoot()` checks caller-supplied identifier and digest shapes. The producers read that evidence store. It does not independently establish who authorized the scope grant. |
| Ordinary lifecycle scope change | ABSENT | `assertLifecycleDisposition()` requires `authority_scope_changed=false`. Its activation or supersession route cannot silently become a scope-grant issuer. |
| Post-operationalization founding installation | DEFERRED_BOUNDARY | `OperatorRootPersonnelInstallationService::install()` refuses after the seal with `B212_OPERATOR_ROOT_WINDOW_CLOSED`; its output grants neither execution nor external authority. Reopening it is outside the correction. |
| Upgrade planning | EXISTS_CANONICALLY | `OperatorRootUpgradePlanningService::prepare()` requires the operationalization seal and produces `PREPARED_NOT_STARTED`, with `execution_authority=false`. A plan is not a constituting act. |
| Exact native transition constitution ingress | ABSENT | No authenticated issuer/loader for the exact transition scope was found in the inspected Bootstrap and Imperator source corridor. Neither v3's different competence nor the isolated configured grant supplies it. |
| Proposed independent signing identity | DEFERRED_BOUNDARY | A new purpose-bound signed Root-act ingress would require an explicit trust-anchor ownership and provisioning rule. No such identity or grant was installed. |

## Concrete proposal for the policy decision

Recommended: authorize a new, narrowly scoped post-operationalization Operator
Root act route with a separately operator-provisioned public signing identity.
Bind that identity to the exact operator and instance outside caller input.
The trusted ingress must verify a signed act over the exact scope, original
native principal reference, preserved scopes, source/next generation, operation,
binding, storage identity and validity. A signature under an arbitrary key passed
by the caller is insufficient; a configured digest is not the act's provenance.
Do not reuse a proof-verification identity as a scope-granting identity by analogy.

The route would authorize mechanical constitution of one pending next generation,
followed by a separately authorized activation and explicit predecessor treatment.
The existing founding window stays closed. The runtime would verify public
evidence only; private signing and live identity provisioning remain outside this
campaign. Missing, revoked or mismatched trust identity must fail closed. There
would be no default key, automatic trust-on-first-use, fixture import or test-key
fallback. Key rotation, anchor revocation, signer-to-instance binding and the
authorized provisioning owner must be explicit parts of the route contract.

The decision requested is approval of this new trust route for implementation,
or identification of the existing authoritative Root ingress to use instead.
Approval would not authorize live signing, provisioning, constitution or effects.
Until that decision, selecting an arbitrary application-configured key as Root
would change institutional authority by implementation choice.

The `senior-symfony-backend-engineer` skill's Escalation Rules require pausing when
a change needs a product/policy decision or conflicting authority sources lack a
sanctioned rule. That rule causes this pause; the old preparation handoff does not.

## Disposition and continuation

The speculative verifier, registry and repository drafts were removed before
delivery. No runtime or service configuration change remains. No executable
contract was retained; no live principal, successor, grant, journal, transition
lock, consumption, winner or receipt was installed or exercised. `BOUND_INACTIVE`, native v3
`NOT_IMPLEMENTED` and `UNKNOWN_REPLAY_PROHIBITED` remain unchanged. Credentials,
capabilities, providers, external I/O, effects, retry, Iron Gate and Lazaretto stay
closed. Test fixtures are disposable local test state, not live instance actions.

After the policy decision, finish Batch 1's native producer/store/loader and
lifecycle checks, then principal -> decision -> issuance target -> custody ->
single-use authority. Retain backward sealed references and forward value-shaped
targets. Reconcile existing v2/v3 source loading and current-generation ownership;
a parallel scope registry that native consumers never read is insufficient.
Then execute Batches 2 through 6 with PHPUnit after each, followed by the separately
sequenced Batch 7 audit from clean locally merged Batch 6 main. Do not decrement
the stage count for this blocked investigation or report documentary tests as
native executable proof.

Validation: `php vendor/bin/phpunit tests` passed **1854 tests, 44250 assertions**
on PHP 8.4.14 / PHPUnit 13.3.0. Focused dependency checks, lint and whitespace
validation also passed. No failures required runtime fixes. The new tests invoke
existing pure validators and inspect documentary boundaries; passing them does
not complete native competence, successor, admission or receipt proof.

## Additional reading

- `C:/Users/gatom/.codex/skills/senior-symfony-backend-engineer/SKILL.md`
- `C:/Users/gatom/.codex/skills/blackquill/SKILL.md`
- `src/Imperium/Runtime/Bootstrap/OperatorRootOperationalizationService.php`
- `tests/Imperium/Runtime/TransactionalAuthorityConsumptionBatch12CoverageTest.php` (first 130 lines)
- `src/Imperium/Runtime/ProviderTransition/TransitionStore.php`
- `tests/Imperium/Runtime/ExecutableTransitionBatch1Test.php`
- `docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv` (final entries)
- `src/Imperium/Runtime/LaCortine/ProviderExecutionBoundaryContract.php` (first 90 lines)
- `src/Imperium/Runtime/Bootstrap/OperatorRootPersonnelInstallationService.php` (installation and normalization, first 180 lines)
- `src/Imperium/Runtime/Bootstrap/OperatorRootUpgradePlanningService.php` (prepare/read/write path, first 170 lines)
- `src/Imperium/Runtime/Imperator/ImperatorPrincipalConstitutionAuthorityContract.php`
- `src/Imperium/Runtime/Imperator/ImperatorPrincipalProvenanceFixtureStore.php` (constitution, principal, lifecycle and identity validation, first 155 lines)
- `src/Imperium/Runtime/Imperator/ImperatorRuntimePrincipalVersionV3Contract.php`
- `src/Imperium/Runtime/Imperator/ImperatorPrincipalLifecycleReconstructionService.php`
- `src/Imperium/Runtime/Imperator/FutureInstanceImperatorPrincipalConstitutionService.php`
- `src/Imperium/Runtime/LaCortine/ProviderAssuranceEvidenceFixtureStore.php`
- `src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciledLifecycleSuccessorContract.php`
- `src/Imperium/Runtime/LaCortine/GovernedProviderExecutionSuccessorAdmissionV3Contract.php`
- `tests/Imperium/Runtime/PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5BTest.php` (source fixture around lines 155-205)
- `tests/Imperium/Runtime/ProviderBindingActivationPrincipalProvenanceBatch4Test.php`
- `tests/Imperium/Runtime/ProviderBindingActivationPrincipalProvenanceBatch5Test.php` (remediation authority fixture)
- `tests/Imperium/Runtime/ExecutableAtomicTransitionNativeIntegrationRemediationPreparationBatch0Test.php`
- `docs/handoffs/executable-atomic-transition-native-integration-remediation-preparation-batch-0-complete.md`
- `docs/handoffs/README.md` (current entries and final historical entries)
- `composer.json`
- `.github/workflows/phpunit.yml`

Preparation's existing reading ledger remains authoritative for its 22 required
and 48 additional sources. This continuation rechecked its inventory, campaign
selection/handoff, retained TransitionContract/TransitionStore, and the current
flow/Blackquill sections. Symbol searches additionally covered `src/Imperium/Runtime`
for Root/operator identity, signature/trust-anchor, constitution and scope routes.
Search hits alone are not recorded as full-file reads. New runtime draft files
were inspected during development and then removed as described above:
`src/Imperium/Runtime/ProviderTransition/NativeRootActVerifier.php`,
`src/Imperium/Runtime/ProviderTransition/NativePrincipalRegistry.php`, and
`src/Imperium/Runtime/ProviderTransition/NativeTransitionRepository.php`.
These removed drafts are not available runtime sources or completed deliverables.
