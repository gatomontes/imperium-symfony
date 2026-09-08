# Citadel native authority protocol — accepted integration

**ACCEPTED_LOCAL_OFFLINE_PROTOCOL; NA-IR01 AND NA-IR02 CLOSED.** P0–P3 and its corrections are complete within their local/offline scope. [PR #774](https://github.com/gatomontes/imperium-symfony/pull/774) merged after [GitHub CI passed](https://github.com/gatomontes/imperium-symfony/actions/runs/34281808594). Main integration was independently verified to have the exact accepted final tree. No deployment, enrollment or activation occurred.

| Identity | Value |
| --- | --- |
| Accepted local head | `0624079f220d28dc725d39ba310c96a4a8467e82` |
| Accepted tree | `e4140d95cbb83b3dbf9125849953cec0c7eda989` |
| Tested executable head | `132a0b01e8e4ee8d7a83de494248de8425b73fcd` |
| Tested executable tree | `4d1e9f32c2b8e48f86f0e67ef8fa79485eb21bb3` |
| Merge | `f35a0b33ddab9e8815470a697d8593984e050023` |
| Correction ZIP SHA-256 | `6f9fb59b30f48ea7a6f738b9b6ba53b526460f5d4d4a6abe3817938659577783` |

Independent review verified the complete 79-payload manifest, retained original packet, Git bundle and both 3,106-file source archives against Git blobs/modes/byte hashes. The exact post-test diff changes seven Markdown files only. Only NativeProtocol changed in correction production code.

NA-IR01 now uses one combined native/legacy custody policy under the shared lock. Same-Persona admission refuses without a new frame or changed original occupancy/custody/disposition, including a second delivery identity. A distinct Persona coexists and remains visible to supported inventory. No automatic migration or new legacy recovery is introduced.

NA-IR02 samples one acceptance instant inside the locked operation and uses it for fresh authority/currentness/interval checks and retained acceptance timestamps. Historical signature, interval and revocation ordering remain checked on exact completed replay. Acceptance precedes physical publication and is not a trusted timestamp or guaranteed publication-before-expiry deadline.

Supplied tests inspected by the independent reviewer: before correction, 13 cases exposed two duplicate-admission failures and five retained-recovery errors with six passing controls. All 19 corrected cases pass in final focused and full suites without weakening the original assertions. Focused PHP: **95 / 4,425**; full PHP: **2,867 / 53,961**, exit 0, no errors/failures/skips, four unchanged historical warnings. Existing Python compatibility (16 cases), lint and synthetic PHP proof passed. Reviewer reran public verification of six frames/four Ed25519 acts/one admission, but did not rerun PHP because it was unavailable. GitHub CI is separate observed evidence on the accepted PR head.

Positive protocol evidence is synthetic and does not authenticate installed people, historic producer execution or genuine appointments. Owner-attested adoption remains qualified. Real policy/issuer/custody selection and enrollment, other formation Seats, suitability/delegation, candidates/appointments and B1 remain unresolved. Unsupported legacy workflows are fenced after enrollment; native inventory transport, Garrison succession, administrator rollback and power-loss guarantees are not silently added.

Formation baseline remains integration `645d53bdbb80d537ef0a7f226b8ad48f192ea1ef`, tree `ae5d701c1ef69cc6610306cfe40f0f518cb7e5cc`, original local review `21bb307d4a573980383aa0fbcd59cb1148efff0a`, historical **2,729 tests / 53,069 assertions** with four linked-worktree warnings. CF01 terminal refusal, CF02 authenticated completed child-publication recognition, IR01 interview closure, the accepted 16-record Guildhall planning follow-up, and A0–A3 preparation/refusal acceptance remain closed in scope.

Current next work is [CP0–CP3 commissioning preparation](next-campaign-citadel-native-authority-commissioning.md). Historical pending-review text in the original reports is retained as dated evidence and does not reopen those reviews.
