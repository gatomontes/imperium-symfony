# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — adversarial proof matrix v1

`PREPARATION_BATCH_0_ADVERSARIAL_MATRIX_ONLY`
`NO_CASE_EXECUTION_AUTHORIZED`
`NO_PROVIDER_NO_NETWORK_NO_CREDENTIAL`

| ID | Later case | Present condition | Required result | Classification |
| --- | --- | --- | --- | --- |
| AUTH01 | Call `issue(admissionId, at, expiresAt)` without decision | succeeds if source/time valid | refuse before write | `ABSENT` |
| AUTH02 | Possess issuer service from corridor | sufficient construction access | construction never satisfies authority | `ABSENT` |
| AUTH03 | Copy issuer-service identity | public constant | identity alone refuses | `ABSENT` |
| AUTH04 | Present consumed native transition authority | accepted as source provenance | refuse as derivation grant | `ABSENT` |
| AUTH05 | Present historical transition commit/consumption | accepted evidence | refuse as continuing power | `ABSENT` |
| AUTH06 | Counterfeit issuance decision | no decision type exists | exact canonical resolution refuses | `ABSENT` |
| AUTH07 | Missing/counterfeit typed issuance capability | no type exists | refuse before publication | `ABSENT` |
| AUTH08 | Replayed issuance authority, identical target | no consumption exists | exact retry converges only to established output | `ABSENT` |
| AUTH09 | Replayed issuance authority, changed target/expiry/issuer | no consumption exists | conflict/refuse | `ABSENT` |
| AUTH10 | Two issuers race same semantic target | deterministic IDs converge accidentally | one authorized consumption winner | `EXISTS_FRAGMENTED` |
| CUR01 | Resolve -> Root revoke -> consume | succeeds through consume | refuse | `ABSENT` |
| CUR02 | Resolve -> native principal revoke -> consume | succeeds through consume | refuse | `ABSENT` |
| CUR03 | Resolve -> source generation advance -> consume | succeeds through consume | refuse | `ABSENT` |
| CUR04 | Resolve -> source lifecycle suspend/revoke/supersede -> consume | succeeds through consume | refuse | `ABSENT` |
| CUR05 | Resolve -> authority expiry -> consume | refuses capability | preserve | `EXISTS_CANONICALLY` |
| CUR06 | Resolve -> authority/issuance byte substitution -> consume | digest mismatch refuses | preserve | `EXISTS_CANONICALLY` |
| CUR07 | Revoke after claim before receipt | forward inspection refuses | preserve and distinguish cut | `EXISTS_CANONICALLY` |
| CUR08 | Revoke after receipt then reconstruct | historical read-only reconstruction | preserve; no new power | `EXISTS_CANONICALLY` |
| CUST01 | Serialize/clone reconciliation capability | refused | preserve | `EXISTS_CANONICALLY` |
| CUST02 | Recreate capability from copied fields | exact object registry refuses | preserve | `EXISTS_CANONICALLY` |
| CUST03 | Fresh process reuses old capability metadata | PID/incarnation refuses | preserve | `EXISTS_CANONICALLY` |
| CUST04 | Fresh process resolves current unconsumed authority | permitted | preserve after at-use revalidation | `EXISTS_CANONICALLY` |
| CONS01 | Two capabilities compete for one claim | one local winner | preserve | `EXISTS_CANONICALLY` |
| CONS02 | Exact claim/receipt retry | generic consumption converges | preserve, no new authorization | `EXISTS_CANONICALLY` |
| CONS03 | Substitute claim digest or receipt consumer | conflict/refuse | preserve | `EXISTS_CANONICALLY` |
| EXP01 | Issuance decision expired | no decision exists | refuse before consumption | `ABSENT` |
| EXP02 | Issuance authority expired | no authority exists | refuse at use cut | `ABSENT` |
| EXP03 | Reconciliation expiry beyond source | issuer rejects | preserve | `EXISTS_CANONICALLY` |
| EXP04 | Boundary equality at every expiry | partially tested | exact `at >= expires` refusal | `EXISTS_FRAGMENTED` |
| SUB01 | Substitute admission/callback/response lineage | resolver refuses | preserve | `EXISTS_CANONICALLY` |
| SUB02 | Substitute Root/native/source references | resolver refuses | preserve at issue/resolve/use | `EXISTS_FRAGMENTED` |
| SUB03 | Change deterministic authority bytes after publication | immutable/digest checks refuse | preserve | `EXISTS_CANONICALLY` |
| CUT01 | Exit before issuance-authority consumption | no such cut | no output; retry revalidates | `ABSENT` |
| CUT02 | Exit after issuance consumption before authority | no such cut | exact retry alone finishes | `ABSENT` |
| CUT03 | Exit after authority before issuance evidence | orphan unresolvable; retry finishes | bind retry to consumed authority | `EXISTS_FRAGMENTED` |
| CUT04 | Exit after resolve before claim, then revoke | stale capability survives | retry/use refuses | `ABSENT` |
| CUT05 | Exit after in-memory capability consume before claim | no durable consumption | preserve fresh exact retry | `EXISTS_CANONICALLY` |
| CUT06 | Exit after claim consumption before receipt | exact retry finishes | preserve | `EXISTS_CANONICALLY` |
| CUT07 | Exit after receipt publication | read-only replay | preserve | `EXISTS_CANONICALLY` |
| APP01 | Real container obtains corridor and issuer | prior test proves | corrected issuer requires typed authority | `EXISTS_FRAGMENTED` |
| APP02 | Directly instantiate issuer | public constructor works | authority input still mandatory | `EXISTS_FRAGMENTED` |
| APP03 | Reconciliation worker issues directly | support worker works | migrate to authorized fixture path | `EXISTS_FRAGMENTED` |
| APP04 | Production command invokes issuer | no call site | preserve absence unless separately selected | `ABSENT` |
| APP05 | Stale closure consumer says zero stages | historical documents exist | controlling refusal/countdown wins | `EXISTS_FRAGMENTED` |
| OS01 | Windows case-insensitive path spelling | identity lowercases path | prove same root/lock semantics | `EXISTS_CANONICALLY` |
| OS02 | Linux case-sensitive path spelling | identity preserves case | CI proves supported semantics | `EXISTS_CANONICALLY` |
| OS03 | `flock` across unsupported/distributed filesystem | unproved | no claim | `DEFERRED_BOUNDARY` |
| GIT01 | Batch 1 begins in same workspace/instruction | prohibited | refuse | `ABSENT` |
| GIT02 | Later stages separately commit/merge | future | retain exact chain | `ABSENT` |
| CI01 | Workflow file treated as result | possible rhetoric | exact run/job/SHA/conclusion only | `ABSENT` |
| CI02 | Terminal audit before clean merged Batch 4 | prohibited | refuse | `ABSENT` |
| BND01 | Issuance/recovery resolves credential/provider | absent now | preserve source scan/reflection guard | `EXISTS_CANONICALLY` |
| BND02 | Multi-host competing issuers/claimants | unproved | no claim | `DEFERRED_BOUNDARY` |
| BND03 | Hostile same-process/filesystem writer | outside cooperative model | no claim | `DEFERRED_BOUNDARY` |

Batch 4 must execute these cases only after Batches 1–3 are separately
authorized, committed and merged. Preparation Batch 0 authorizes the matrix,
not its execution.
