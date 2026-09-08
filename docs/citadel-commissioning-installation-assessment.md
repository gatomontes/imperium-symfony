# CP0 — installation assessment

Observed 2026-09-08T22:08:22+00:00 through Git metadata only, using no optional locks, disabled fsmonitor, and no external diff/text conversion. Exact commands/native exits/UTC timestamps are in external `observation-commands.json`; sanitized machine input is `assessment.json`. No application was booted in the installation and no runtime/private/credential/.env contents were read.

| Identity | Value / attribution |
| --- | --- |
| Identified root | E:\htdocs\imperium; owner launch |
| Observed installed commit | 8244afa5630b06973cc4a52dce6ed96f42a444f3 |
| Observed installed tree | 8329f745c548b0b672fc8b9dee61055774d1a24b |
| Observed installed branch | codex/bounded-source-review |
| Tracked drift | None at observation; untracked contents intentionally not examined |
| Historical supplied commit | 8244afa5630b06973cc4a52dce6ed96f42a444f3; equals today's observation but remains separately attributed |
| Proposed runtime target | f35a0b33ddab9e8815470a697d8593984e050023 |
| Proposed runtime tree | e4140d95cbb83b3dbf9125849953cec0c7eda989 |
| Preparation entry | 170902f4c609c25776bc53a70950866a542c3d92 / 7f5715bea7b4bde2057077f0a0b17a977bca2f8d; clean tracked entry |
| Preparation branch | codex/citadel-native-authority-commissioning |
| Deployment approval / performed | false / false |

The accepted runtime merge was verified as an ancestor of the campaign entry. The target is fixed to the accepted runtime tree, not moving main or the later preparation helper commit. Main integration does not update the installation.

## Exact committed-source comparison

There are 213 changed paths from the observed installed commit to the proposed runtime target: README.md: 1, contracts: 7, docs: 55, imperium-doctrine.md: 1, offices: 7, src: 89, tests: 38, tools: 15. The complete status/path comparison is [citadel-commissioning-source-impact.tsv](citadel-commissioning-source-impact.tsv), obtained from committed objects in the preparation repository. It contains no installed file contents.

`composer.json`, `composer.lock`, `symfony.lock` and `config/services.yaml` have identical Git blob identities across this comparison. Source changes include Citadel formation/authority commands and runtime, legacy Conscription/Garrison and StateStore boundaries, native journal/trust/admission, planning consumers, contracts, tools and tests. The changed-source list is wider than native authority: deployment imports the accepted formation and authority campaigns too. This package makes no production changes to that target. No database migration or package update is introduced by the preparation helper.

Source requirements: Composer requires PHP >=8.4, ext-ctype and ext-iconv; native signing verification additionally calls Sodium Ed25519 functions; JSON, hash, flock, fsync and atomic local rename are used. Existing dependency platform requirements must also be satisfied. Those are source requirements, not an observation of the installed process/extensions/vendor tree. Offline tests reuse the accepted worktree's vendor bytes; they do not validate installed dependencies.

Native operations need protected read access to public inputs/original occupancy/delivery/inquiry/retained custody and write access to transition locks and `var/imperium/native-authority` for fresh effects. StateStore operations additionally use bootstrap locking; other application cache/log requirements remain operational prerequisites. Source-declared modes are not proof of Windows ACL protection. Enrollment fences unsupported cooperating legacy paths immediately. Old binaries, unrelated writers, direct edits and administrator rollback are outside the guarantee.

## Unknown operational facts and owner consequences

| Fact | Evidence status | Consequence / required later observation |
| --- | --- | --- |
| Running executable/process source | UNVERIFIED; Git does not identify a running process | Owner inventory of services/processes and active code required before source deployment |
| Runtime enrollment, registry/pending state | NOT READ | No inference of absence, currentness or readiness; separately authorized review before ceremony |
| Custody, deliveries and live original occupancy | NOT READ; retained public history only | Existing data and exact current candidates/inquiries require separate authorized evidence |
| Private Recruiter projection | MISSING | Separately authorize bounded exporter; no raw private-state export |
| Keys, issuer identity, clock and ACLs | UNVERIFIED / real values null | Independent custodian, fingerprint, validity and protection checks |
| Quiescence and unmanaged writers | UNVERIFIED | Owner must identify and control every writer, including old binaries/admin paths |
| Backup and restore feasibility | UNVERIFIED | Demonstrate protected data/source recovery before approving deployment; no safe post-enrollment source rollback claim |
| Formation trust/competence, other Seats and B1 | OPEN | Native protocol does not supply live formation readiness |

Recommendation: prepare for review and defer enrollment. The owner must decide whether disabling the [mapped workflows](citadel-commissioning-compatibility.md) is acceptable. No process stop, package installation, source switch, enrollment, signing, provider or mission activity occurred on the installation.
