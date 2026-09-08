# Bounded source-review excerpts: local implementation

Entry commit: `20e2c566a049843791cb135e2cc7f16b3b692cc6` on
`codex/bounded-source-review`. The owner authorized bounded excerpt support,
focused offline tests, and preparation of the exact reviewed hourly-earnings slice.
No activation, real credential handling, provider invocation, source transmission,
Nomina change, installation, enrollment or approval ceremony is authorized.

The reviewed 2026-09-06 preparation directory, its 2026-09-07 ZIP, original
whole-file inputs and historical campaign evidence remain immutable history.
The new preparation belongs in a separate directory. It must retain the six
original-file identities and seven segments in proposed-excerpt-line-mappings.json;
do not narrow that selection silently if serialization still exceeds the bound.

`Selection` derives exact LF-mapped segments from one hash-verified original read.
`SnapshotStore` accepts mixed whole paths and explicit path/hash/ranges entries.
`Proposal` retains byte-identical v1 output for legacy whole files and uses v2
when selections are present; segment metadata and omitted ranges enter both
manifest and exact payload. `Result` validates the proposal and restricts findings
to selected original coordinates within one segment. Existing Workflow, Gateway,
authorization, lease, atomic claim, credential broker and provider journal are
unchanged. No new authority path or tokenizer/limit relaxation was introduced.

See `docs/source-review.md` for input schema and implementation limits.
Focused verification command: `php vendor/bin/phpunit tests/SourceReview --no-progress`.
The focused suite exercises legacy v1 identity, synthetic single delivery after
original removal, hash/mapping/content mutations, range and citation rejection,
CRLF/final newline preservation, size limits, null pricing and existing controls.
Its fake transport and synthetic prerequisites are not a live mission or payroll
proof. Record exact tested commit, final test counts and actual prepare/inspect
outputs in the hashed review packet. Do not update historical verification ledgers.

The payroll arithmetic contract remains provisional. Source review produces static
hypotheses only; a later finding needs a separately authorized isolated payroll
regression against original code. Preparation with pricing null is not activation.
