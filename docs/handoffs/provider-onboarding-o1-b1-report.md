# Provider onboarding O1-B1 — local implementation report

O1-B1 is implemented and its required local validation passed. Disposition:
**validated locally, pending source/integration review**. O1-B0 is preserved;
O1-B2 and all later batches remain planned. This is an author execution report,
not independent acceptance, a remote CI result or a live commissioning result.

Entry: `33354ba5c051738b3734c7d1b2449ef6d4a45c78`, tree
`d175e898707a1bf167d84a581c4c746cdbec3201`. The requested branch/worktree already
existed clean at that entry and was reused without reset after fetching main:
`codex/provider-onboarding-o1-response-validation` at
`E:/htdocs/imperium-onboarding-o1-b1`. The original checkout was preserved.
The tested PHP implementation is committed locally as
`d685d0d3d4960fabf407670279f714d20d1e079c`, tree
`9a4293d0e99d1c1fd8ce144caede1e9cb0a967c6`. A later documentation-only
commit records the final report and roadmap. The review packet's source-identities.json records the exact tested PHP hashes,
staged implementation tree, final commit/tree, changed-source hashes and protected
source hashes. No implementation or test PHP source changed after the successful focused validation.

Seven new classes implement the raw and consistency boundary. RawJsonDecoder
limits original response bytes to 1 MiB and nesting to 32 containers, counting the
root. It preserves objects separately from lists, checks duplicate decoded keys
before assignment, rejects invalid UTF-8/BOM/escapes/surrogates/trailing data and
rejects scalar types absent from this contract without numeric conversion.
ExpectedContext stores immutable supplied reference expectations. ParsedResponse,
CandidateClaim and PredicateClaim enforce the complete W1/W2/W3 closed shapes,
all ten predicates, exact candidate/context identity, frozen evidence membership,
PASS support, FIT consistency and complete ranked capacity coverage. Checked
capacity shapes reuse the existing immutable CapacityOrder parser. Unknowns,
limitations and contradictions remain claims, with original text and raw-byte
digest retained. Declared ascending capacity is retained, not independently proven.

Parsed output cannot be passed as selector RoleInput. There is no conversion,
operational caller, record writer or admission authority. Later real admission
must authenticate the supplied context/original evidence and establish substantive
fitness before constructing selector projections. No caller boolean confers that
trust. All seven classes carry Symfony's class-level Exclude attribute.

Locally executed with PHP 8.4.14 and locked PHPUnit 13.3.0:

| Check | Result |
| --- | --- |
| New raw-response tests | PASS — 147 tests, 209 assertions, no skips/issues |
| Selector plus three named source-pin regressions | PASS — 74 tests, 865 assertions, no skips/issues |
| Python onboarding specifications | PASS — all 86 checks |
| PHP lint | PASS — seven source classes and focused test |
| Symfony test kernel boot | PASS — KERNEL_BOOT_OK |
| Locked dependency installation | PASS — no dependency/lock changes; scripts/plugins disabled |
| Supplemental full local suite | INCOMPLETE — stopped at 917.9 seconds under a 15-minute local budget; no full pass |

The raw-input cases cover W1 and each W2/W3, escaped/equal duplicates at root and
nested predicates/refs, malformed bytes, byte/depth boundaries, object/list and
numeric-key confusion, scalar coercion, unknown/missing keys/tags, candidate and
predicate coverage, changed schema/digest identities, out-of-set evidence,
unsupported PASS and inconsistent FIT, unknown claims, ranked coverage/ties,
malformed orders before any permission filtering, immutable nested output and
absence of a selector conversion. Logs and commands.json retain the actual runs.
The supplemental Windows run ended by local process termination (native exit -1),
with its last numbered progress at 976/3,162 and 39 further progress dots. It printed
no failures before termination, but did not produce a completed suite result. Its
partial log is not a regression pass. Full repository CI remains the integration gate; this run publishes or merges
nothing and claims no fresh remote CI execution.

The first test launch failed before test execution because a data provider was
named groups(), colliding with a final PHPUnit method. Renaming it to
responseGroups fixed the test harness; the subsequent focused run passed. One
unused test-local string was later removed and the final focused run repeated.
No historical tests were weakened. Validation regenerated config/reference.php's
PHPDoc metadata; that generated change is inspected and restored before final
source audit. The pinned services.yaml, selector, dependency files, original
workloads, approval bytes and historical evidence are preserved. The final packet
contains only the new source/test, contract implementation notes, roadmap state
update and this report as committed changes.

CY/FC acceptance, author-review provenance, DEFER_ENROLLMENT, unresolved B1, the
empty safe-retry allowlist, persistent operator-controlled assignments and all
five false operational flags remain. No provider call, credential handling,
installed-state mutation, appointment, application or activation occurred.

The deliverable is provider-onboarding-o1-b1-all-deliverables.zip: README, this
report, reproduction instructions, all changed files, a full-index patch, an
incremental Git bundle, identities/manifests, execution logs and the checksummed
inner review packet. No outer hash. Individual report/instructions are provided
alongside the ZIP. Stop here for source/integration review.
