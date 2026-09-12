# O4-B0 completion source review — HOLD

One blocking defect is independently reproduced: **O4-R1, missing binding between persisted settings and the actual formation consumer's aggregate and executing Profile.** The completion implements the O4 migration, application, replacement and persistent resolver. This is a targeted correction of that implementation, not a restart of the campaign.

## Reviewed identity
- Uploaded final: d3fe18700188970bf41117d4cbc6d9a00d802e1d.
- Uploaded final tree: 3ec05806baf1f379c7f29322816671dda2e93bb2.
- Uploaded tested commit: cbeb218fbdd02254816517b44fd6e87398be0616; all 1,875 PHP files match final.
- Exact remote candidate: c150c459feba3629263d3f7c950f062d2af0a0c2, same final tree.
- Draft candidate PR: https://github.com/gatomontes/imperium-symfony/pull/798.
- Main at review entry: 6fbc625b50e411b654ffcc7423502d1ec90cc3cb.

## O4-R1 — settings labels do not establish execution identity
The application contract requires exact original-backed Profile/model/configuration identity and an actual persistent-settings consumer boundary, while retaining separate personnel, session and lease authority (contracts/provider-onboarding-assignment-application.md, application, consumer and acceptance sections).

SettingsBoundTransport::check resolves its configured PersistentSettings instance and compares provider/model and the copied model_settings tuple in terms. It does not establish that the actual request's holder/Profile belongs to that settings history or that the formation is the same authoritative aggregate. SettingsPreparedTransport uses the same check before preparation. FormationCognition validates an optional settings shape; FormationSessionAuthority supplies the genuine holder and its Profile, but there is no typed bridge from those execution originals to the admitted O4 tuple.

The independent test creates O4 history using real synthetic O1/O2/O3 producers and the application owner. Separately it creates an actual temporary formation with appointed officers, intake and legitimately signed session. Its terms copy the valid O4 tuple. The production SettingsBoundTransport, composed into actual FormationCognition, then invokes an offline recording sink **once**. The formation's own authority is valid, but no original-backed link establishes eligibility to use these settings.

Expected: refusal before the underlying transport is invoked. Actual: one invocation, then the sink deliberately throws; runtime reports CMF059_OUTCOME_UNKNOWN_NO_RETRY. No HTTP/provider call occurs. The O4 producer request count and onboarding journal remain unchanged.

This combined negative case establishes acceptance of an incompatible aggregate/Profile composition. It does not isolate each dimension independently. Also, the logged O4 profile_ref digest and executing Profile content_digest identify different record schemas; their raw inequality is not itself the identity proof. Correction must use an explicit typed mapping to retained originals, not equate unlike digest fields.

## Independent runtime evidence
- Reviewer test: probes/AssignmentReviewerConsumerBindingTest.php.
- Diagnostic-only commit: 61fdcf17b0201bcc5c0421ad9278e579ceac8168.
- Diagnostic checkout: 775a3613c02f66e6410b27ac59421f73478f6d6f.
- Verified diagnostic tree: c84bbb77eeb5c5dbaea2bfdddb5ed66fd7ae213a.
- Diagnostic PR: https://github.com/gatomontes/imperium-symfony/pull/799 — DO NOT MERGE.
- Hosted run: https://github.com/gatomontes/imperium-symfony/actions/runs/34725680344.
- Job: 103639261639; PHP 8.4.25, PHPUnit 13.3.0.
- Result: 1 test, 5 assertions, 1 failure; 02:25.807.
- Actual transport_calls: 1; expected: 0.
- Diagnostic production source is identical to the candidate. Only the additive reviewer test and diagnostic branch's selected-test workflow differ. Do not import that workflow into the correction.

## Integrity and submitted evidence
Independently verified all 2,035 manifest payloads, inner ZIP integrity, bundle identities, all three patch reconstructions to the final tree, original comparisons, all 1,875 tested/final PHP files, all 16 final before/after source manifests, diff check and 86 static specification checks. The included integrity.json retains submitted status fields; those are provenance, not this review's final disposition.

Recorded local focused tests pass; selections overlap and must not be summed. local-evidence.json preserves each result. The recorded full local gate timed out with exit 124 after 1,800.607 seconds; it is not a pass. The local-evidence independent_php_run=false field describes that submitted-evidence audit; the separate independent hosted regression above was subsequently executed.

The unchanged candidate full gate, vendor/bin/phpunit tests, remains in progress as of this review snapshot:
https://github.com/gatomontes/imperium-symfony/actions/runs/34725449745
No full hosted pass is claimed. A later green existing suite does not invalidate the independently reproduced missing negative case.

## Disposition
Keep #798 draft and unmerged. Keep diagnostic #799 unmerged. Correct O4-R1, demonstrate compatible real consumer success and independent incompatible-identity refusals, then resubmit for source review and fresh full CI. No O5 work or operational activation is authorized by this review.
