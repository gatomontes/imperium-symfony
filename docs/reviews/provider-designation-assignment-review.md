# PPC6 receiving source review

Disposition: **PARTIAL_DESIGNATION_ASSIGNMENT_EVIDENCE**. Bounded designation and owner-routing components are accepted offline after source review and fresh hosted CI. **R3 remains open; ASSIGNMENT_EVIDENCE_IMPLEMENTED_OFFLINE is not claimed.** Countdown **3–5 → 3–5, decrement zero**. The native verifier remains dormant and unaccepted for production use.

## Exact packet and approval

Outer packet SHA-256: `7c4dffe860ba5195c277001ac794018754fb3f5fbadc1127e8e2727bf9ae83a4`. The unmodified supplied verifier passed all 13 manifested payloads, exact outer/nested membership and modes, **3,608 source files / 1,428 evidence files**, byte/SHA-256/Git blob identities, exact bounded prerequisite, all three full-index diffs and **34 protected files**. The standalone report equals its packet and committed copies; the independently reproduced verification JSON equals the separately uploaded JSON. Tested-to-final changes are Markdown documentation only.

| Identity | Commit | Tree |
| --- | --- | --- |
| Exact approved start | `32198cb7bffdd98fff258bd8c73d483b71222ccb` | `18aa63134ec5c84e8ea6927a7b498b8aa6447c2e` |
| Producer tested executable | `50fb5d32d50b515700435dd942db7cf3abc25138` | `ad1ed9019342cd68cd842d129bfdde874d236fdc` |
| Producer final public source | `bef00bf4f76223f95d9a64bf3ab86c97939ee453` | `011d2fedbbe5808979f9a89504840218200d1d51` |
| First receiving candidate | `e1c8d1586b29a1ee2cb28bf2185a18c8dfbf5cfc` | `011d2fedbbe5808979f9a89504840218200d1d51` |
| Receiving PR #829 candidate | `c92eb0d082d334bbee99446b51ba75251f6acf80` | `d65b8fdb44214240003f610939189fb84832a509` |
| Hosted PR checkout | `119fa69680805a0ef75c63db62e5bf175f2e6b2e` | `d65b8fdb44214240003f610939189fb84832a509` |

The retained [PPC2 A–F approval](../handoffs/provider-profile-designation-approval.md), original proposal SHA-256 `c24939fd808cd5917c8057efe518787cc2448d3da60be089511e7abd6dee2984`, and [PPC6 scope](../handoffs/provider-designation-assignment-scope.json) authorize this bounded continuation. Their historical bytes remain intact. No new FRESH exception, owner model jurisdiction or operational authority follows from this receiving result.

The receiving object store reconstructed the published prerequisite from public GitHub commit metadata and verified its exact SHA-1 before using it as a shallow bundle boundary. The first metadata request had a mistyped SHA and returned 404; the corrected request succeeded. This transport correction changed no source, assertion or gate.

## Component review and remaining gaps

The automated PR review identified a **P2 missing-owner defect** in the submitted tree: enabling v2 Courtthane appointment left `FormationCognition::authorizationSource`, `CuriaFormationService::currentDossier` and `ChildCuriaFormationService::publish` calling detached current-holder validation. Those paths would refuse a valid v2 holder with PPC303_CURRENT_OWNER_STATE_REQUIRED. The receiving review confirmed the omitted call paths and held integration despite a green initial suite. [Original finding](https://github.com/gatomontes/imperium-symfony/pull/829#discussion_r4010573621).

Receiving correction `c92eb0d082d334bbee99446b51ba75251f6acf80` changes four paths: the three runtime classes and FormationProfileDesignationTest. Existing owners now reach authorization queries, Curia presentation/review/reservation/delivery/Step One checks and child publication. Schema-less compatibility remains explicit. The regression follows an actual native v2 appointment through the existing synthetic personnel/session/Curia flow, then verifies current refusal after model-authorization revocation. It uses no O4 application, provider access or FRESH exception and cannot close R3. No source contract, generic interface or pinned writer is changed. All 34 protected files match the submitted packet at the corrected executable candidate. This later receiving documentation updates the countdown separately. No remaining blocker was identified within this corrected partial integration scope. This is the receiving assistant's source review plus the cited automated PR finding; no separate manual reviewer is claimed.

| Reviewed component | Finding and limit |
| --- | --- |
| Signed ingress and competence | Closed designation/revocation payloads use separate purpose-limited owner delegations and actual current Laboratorium actor under Formation. Initialization remains explicit and grants no current Profile. |
| History and originals | Signed originals, complete findings and approval, strict v2 Profile/native correspondence, bounded retained records and cumulative reconstruction remain required. Signed head ordering reconstructs the exact index; no pruning or generation recycling entry point. Historical cryptographic checks do not renew expired authority. |
| Terminal transitions | Exact predecessor/generation and immutable Profile lineage; atomic superseded/current-active attestations for a live predecessor; revoked/expired facts retained. Revocation after expiry records distinct expiry/revocation attestations. Exact replay returns historical evidence before current-time/head checks; changed scoped nonce refuses. |
| Currentness and appointment | Current designation rechecks strict authorized v2 lifecycle and native current actor. Appointment remains separate; v2 appointments use the existing owner seam, and current v2 Locksmith reconstruction checks the exact holder/occupancy. All counters retain separate meanings. |
| Assignment specialization | Fixed AuthorityStore and live same-root owner, exact whole pair, native originals and full admitted Profile mapping are required by the dormant specialization. Only three directly proved Profile predicates are recognized. Committed tests do not directly instantiate this specialization; its positive native consumer behavior is not accepted. |
| Owner propagation | Initial application/replacement and whole-pair settings checks carry the existing owner. Role resolution verifies both tuples before projection. Formation settings/session/lease/custody callers propagate ownership without moving external invocation under Formation. The receiving correction also covers the omitted Courtthane authorization/Curia consumers. Generic routing proof and historical compatibility tests have their limited meanings. |
| Process proof | Both Seats' current designation versus native Laboratorium retirement and designation revocation in both orders; actual lock probes; both Seats' successor rename exceptions/termination before and after publication; Courtthane competing signed heads and fresh expiry. This is the enumerated component matrix, not full application/use concurrency coverage. |
| Protected behavior | AtomicTransition, Formation journal/owner, accepted storage and PPC5 preparation, generic Profile/AssignmentEvidence, O4 rules, dependencies, default Composition and CI remain exact protected bytes. Default missing production evidence still refuses. |

Complete R3 has the following remaining acceptance gaps:

- Supported same-root FRESH establishment remains unavailable (R4): institutions first fail vacancy; founding first fences installation; installer rejects onboarding v4.
- NativeAssignmentEvidence recognizes only directly verified profile.current_active, profile.exact_model_binding and profile.independent_appointment. Broader substantive predicate production and verification remain open; profile.fits refuses.
- No direct NativeAssignmentEvidence invocation is covered by the committed tests. Generic owner-routing tests and native designation histories do not establish a positive native assignment consumer.
- Full initial application, replacement, actual settings delivery and their mutation races remain unproven through the same supported native owner sequence.
- Corruption/overflow and incumbent/appointment/competition permutations remain open beyond the specifically enumerated cases.

R4 is a prerequisite for a supported combined positive path, but resolving it alone does not complete R3. The next internal work must establish the actual FRESH/native owner sequence and then complete substantive predicates, native assignment application/use and their race/refusal proof. The old synthetic-parent application fixture cannot supply that sequence. Existing approval does not authorize a new establishment contract decision.

## Independent evidence and validation

The three new submitted test classes contribute **48 cases / 401 assertions** in the retained Windows gate. The receiving correction adds one native personnel/Formation compatibility and revocation case with **10 assertions**. These remain separate from NativeAssignmentEvidence, which neither test set directly invokes.

Public Python Ed25519 verification authenticated **6 native histories / 320 linked frames / 320 signature verifications unique within each history / 14 designation events**, including retained event/index and attestation digest reconstruction. This is public fixture integrity, not 320 necessarily globally distinct signatures or current/external authority. The separate final-candidate diagnostic retains **54 checks: 52 independently verified signed malformed inputs and two fresh-process expiry refusals**. Diagnostic totals are not added to the full gate.

The unchanged aggregate independently reproduced the Windows result: **3,951 tests / 65,748 assertions / 1 explicit symlink skip**, zero errors/failures, four reported warnings, all eight native workers and aggregate exit 0. Its source digest is `4f5eaf84c3ae46b872b5fa7f251687211c58511667736b621ceb5a749ea665f2`. The complete Windows gate was not green: ten guards passed and one errored with WinError 1314. Longest worker wall time was 1,961.710 seconds and JUnit time 1,861.135 seconds; the local result did not establish the hosted 30-minute limit. Earlier failed/interrupted/superseded runs, four linked-worktree warning diagnostics and exact generated-reference changes remain in the packet, with no reused partitions or relabelled outcomes. The receiving scratch environment has no PHP; its unchanged guards passed nine cases and explicitly skipped two PHP-dependent cases. No local receiving PHPUnit pass is claimed.

The first hosted run [34909615209](https://github.com/gatomontes/imperium-symfony/actions/runs/34909615209) passed the exact submitted tree: **3,950 tests / 65,735 assertions / four Windows-specific skips**, all 622 files/cases and all 11 guards. It did not cover the omitted v2 callers. Its original artifacts remain separately recorded and are not reused for the correction gate.

Fresh corrected hosted [run 34911209375](https://github.com/gatomontes/imperium-symfony/actions/runs/34911209375) passed **3,951 tests / 65,745 assertions / 4 explicit skips**, all **622 files** and every enumerated case exactly once. All eight workers and all **11 guards passed, zero guard skips**. Longest JUnit partition: 806.005862 seconds; longest entire worker: 822.000 seconds, inside the unchanged 30-minute limit. Source digest: `348469e9d5ee079b5d10452b120a974c6f56e5b038fd62a56ee49a0ad255ae06`. PHP 8.4.25, PHPUnit 13.3.0; interval 2026-09-14T23:58:40Z through 2026-09-15T00:12:33Z. The Linux symlink case executed successfully. The [integration record](../provider-designation-assignment-reviewed-integration.json) retains artifact SHA-256s, checkout/tree, exact coverage, partition counts/exits/times, explicit skips and guards. These results validate the corrected component tree; they do not close the missing acceptance paths.

## Integration and countdown

**Partial components integrated** through [PR #829](https://github.com/gatomontes/imperium-symfony/pull/829), merge `1722c3645acb6c0413ce2a7223c2f7a45503246e`, verified tree `d65b8fdb44214240003f610939189fb84832a509`. The PR contains the 23 submitted paths plus two additional runtime paths in the four-path receiving correction: 25 paths total. Its corrected tree differs explicitly from the producer packet. This receiving documentation follows the tested source with no executable change.

Countdown **3–5 → 3–5, decrement 0**. R1/R2 stay accepted and receive no second credit. R3 stays open; R4 remains required, R5 account/access/base/cognition ports remain open and R6 separately authorized commissioning/interview remains deferred. The estimate concerns the first bounded live Courtyard interview, not full mission execution. No new scope or guaranteed batch count is inferred from partial integration.

All five operational flags remain false, `DEFER_ENROLLMENT` remains and the actual retry allowlist is empty. No real account/provider access, installed private state, enrollment, appointment, commissioning, deployment, activation or execution is authorized by this offline result.
