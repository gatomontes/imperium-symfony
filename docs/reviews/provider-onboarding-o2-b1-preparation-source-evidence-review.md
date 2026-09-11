# O2-B1 preparation — source and evidence review

Verdict: PASS — O2-B1 preparation accepted with the separate reviewer clarifications, full CI passed, and PR #789 is merged. No unresolved preparation blocker remains. This packet specifies O2-B1; it implements no ledger, consumption or custody runtime.

Reviewer provenance: the reviewer authored prior onboarding specifications/reviews and the current clarifications. This is attributed source/evidence review, not a claim of independent authorship. CY/FC, O1 and corrected O2-B0 acceptance remain intact.

## Contract assessment and clarifications

The preparation inspects the actual architecture correctly. FormationJournal already exposes head and state under its owning lock. Public B0 Resolver methods acquire that lock themselves, so nesting them inside a reservation is invalid. FC reservation uses per-session SessionExposure and its custody broker rechecks those limits; a second onboarding balance would not establish a shared budget.

The selected contract therefore requires explicit v1-to-v2 migration, separately enumerated state schemas, preservation of B0 original bytes and replay, genuine canonical budget-source association, and shared accounting in both FC and onboarding reservation/custody paths. Migration enables a deliberate refusal for unmapped FC sources; pre-v2 behavior and original FC schemas/settlement remain unchanged. This is a declared compatibility boundary, not retroactive withdrawal of offline FC acceptance.

Three bounded clarifications were committed before publication:

| Issue | Clarification |
| --- | --- |
| Reusing a core that always throws B0's dynamic-prerequisite refusal would prevent B1 from resolving genuine completed prerequisites. | The internal core separates verified original/static facts from closed typed unresolved obligations. Public B0 retains its conservative final gate; B1 must resolve every obligation under its own lock before reservation. No bypass flag or externally supplied satisfied list is allowed. |
| sequence-registration's sequence_ref was otherwise ambiguous and could be implemented as a self-reference. | It is the logical instance/sequence pair. Compute the closed F1 admission result and command ref first, then the registration H. No digest depends on itself. Exact remaining internal custody entry schemas must be declared before coding. |
| The matrix said a slot could not be reused “across policies,” while the actual logical key includes policy digest. | The same policy slot is one-use across sequences/commands; different policies do not reset original budget/source fences. Pure versus non-I/O prepared-operation nullability is also stated directly. |

The author upload is unchanged. Review changes affect only the contract and surface matrix; they do not reopen owner decisions or alter runtime. No unresolved preparation blocker remains in the inspected scope. The extensive implementation proof is still required; documentation acceptance is not evidence that B1 works.

## Authority, custody and recovery boundaries

F1 command identity remains the complete decoded envelope, including expected head/mode/predecessor. Historical command recognition precedes new currentness checks and cannot redispatch. Global step keys, exact S/P authority keys and owning slot obligations are consumed in one transaction; pure steps consume no authority. B0 retention cannot stand in for a completed F2 effect.

Shared exposure must include actual FC and onboarding sources, trusted settled usage plus maxima for uncertainty, source-specific limits and a single shared concurrency/fence domain. Similar account/provider text does not prove common or separate budget lineage. Default missing-source behavior refuses. Nonnegative bootstrap access meters are separate from positive FC maxima; no old validator is weakened.

Custody uses fixed constructor-selected ports, durable pre-effect checkpoints, exact currentness rechecks and process-local capabilities. Resume is evidence-only, including after loss of a RESERVED process. Missing response/usage does not prove no call or free budget. The contract explicitly limits revocation and process-crash claims; it invents no remote cancellation or power-loss guarantee. Later O3 adapters/founding, O4 application and O5 CLI remain outside B1, with the later application required to join the same consumption transaction.

DeepSeek/API-key, FRESH, D2-A and P1–P9 remain fixed. Medium capacity applies to target roles; initial Augur base remains least-cost eligible. Three retries means four attempts per assessment/twelve total within the approved limits, but the actual safe-retry allowlist is empty. Persistent operator-controlled assignments, DEFER_ENROLLMENT, unresolved B1 remote guarantees and all five false authority flags remain unchanged. No provider/authentication check, credential, installed-state access, enrollment, assignment or activation occurred.

## Source and evidence verification rerun

- Archive CRC, safe unique paths, exact inner/outer membership, inner SHA-256 and all 47 payload hashes: PASS (50 outer entries).
- Author entry/final/tree/ancestry, all thirteen changed canonical files and exact binary patch: PASS. Author tested commit equals final commit; post-test.patch is empty.
- Six supplied tested static files match the author Git blobs and hashes exactly. All 3,275 other entry blobs remain unchanged.
- All 211 relative document links resolve in both author and reviewer trees. Diff whitespace checks pass. src/tests/config subtrees are identical to integrated B0.
- The new B0 review/integration provenance and inherited CI log match the previously delivered originals byte-for-byte; CI-log hash matches its identity record.
- All 86 Python specification checks rerun on the corrected preparation: PASS. These are existing static/abstract checks, not B1 runtime tests.

Inspected, not rerun locally: author preparation audits and inherited B0 CI (run 34522234611/job 103022324183; 3,463 tests/55,572 assertions/four skips). Neither author nor reviewer ran local PHP for this documentation preparation. Fresh hosted CI below is separately attributed and tests the existing runtime against the published documentation tree; it cannot prove future B1 behavior.

## Identities

| Role | Commit | Tree |
| --- | --- | --- |
| Integrated B0 / entry | cafa93f9665f0e5734f26491a092b28995e1cf14 | e2070c7a2184c7481882f27297c976d248b2c2be |
| Author tested/final preparation | f214621d674ecf4cc2963f3440bdb3ddba6f4550 | c898d68e8e9a311ff7a34eed1364059adaa6e479 |
| Reviewer clarification | 237c353e0de10b22a318fac6d62455032f4825ad | bc010341a934706e2c75b8d8b6e203882473bedf |
| Published PR #789 head | 770dfddb07ee221a58c24d8ebf927470d1db61f2 | bc010341a934706e2c75b8d8b6e203882473bedf |

The author-plus-reviewer bundle retains their distinct history. The published commit imports the same corrected tree with a direct main parent. reviewer-clarifications.patch isolates review edits; publication.patch spans entry to corrected publication. No source changes after the final CI checkout are permitted in this disposition.

## Next executable action

After integration, fetch main containing the reviewed preparation and create the isolated O2-B1 implementation worktree using instructions.md. Run local-codex-prompt.txt against the corrected contract and mandatory surface/test matrix. Complete only B1, preserving B0's public refusal semantics, real FC accounting participation, exact original authority and evidence-only recovery. Return the source-bound implementation/evidence packet for review.

Five implementation batches remain across O2–O5. B1 is the remaining O2 batch; this preparation does not reduce that count. Live commissioning stays deferred.

## Final hosted gate and integration

[PR #789](https://github.com/gatomontes/imperium-symfony/pull/789) merged as `0399b581e8e61b5306813479df6a8af363c35c0a`, tree `bc010341a934706e2c75b8d8b6e203882473bedf`. A fresh fetch verified main, both parents and exact equality to the corrected publication and CI tree. src/tests/config remain identical to integrated B0. No source changed after CI.

Fresh [CI run 34525838331](https://github.com/gatomontes/imperium-symfony/actions/runs/34525838331), job 103034390826, passed on PHP 8.4.25 / PHPUnit 13.3.0: **3,463 tests, 55,570 assertions, 4 skips**, elapsed 09:31.060. The log identifies temporary merge `f7314a3cf0ea7bdc3e8c0095666108993f972da8`. These are the actual current-run counts; historical B0 counts are preserved separately. No local reviewer PHP run is claimed.

The next executable action is the isolated O2-B1 implementation in instructions.md and local-codex-prompt.txt. Five implementation batches remain across O2–O5; live commissioning remains deferred.
