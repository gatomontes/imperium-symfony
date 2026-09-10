# Local launch — O2-B1 ledger/custody

Prepared for review, not an instruction to implement before preparation acceptance. Use the final preparation identity from the supplied source-identities.json; do not assume main already contains it. Preserve existing worktrees and use a separate codex/provider-onboarding-o2-b1 branch from that reviewed preparation ref. Verify the entry contains corrected O2-B0 published commit 4001f08d343d289cecb913633961bd90b7797fcf and record actual HEAD/tree.

After preparation review, the bounded local prompt is:

```text
Implement Provider Onboarding O2-B1 only from the accepted preparation. Read applicable AGENTS.md, docs/next-campaign-provider-onboarding-o2-ledger-custody.md, contracts/provider-onboarding-ledger-custody.md, docs/provider-onboarding-ledger-custody-surfaces.md and their governing D2/F1/F2/retry/approved-decision links. Verify entry commit/tree. Preserve existing worktrees and original reports/source bytes. No subagents.

Implement explicit v1-to-v2 onboarding migration, compatible B0 admission/resolver behavior, no-lock internal currentness verification under B1's owning transaction, F1 sequence/command/step replay, exact S/P and slot consumption, shared FC/onboarding exposure and bounded custody/recovery interfaces. No second ledger or transferable current-authority token. Distinguish original retention from completed effects. Real production defaults must refuse missing later-stage custody/holder/adapters. Do not implement O3 adapters/founding, O4 assignments or O5 CLI.

Run every required matrix gate through real signed synthetic producers and temporary roots, including FC cross-type contention and all process interruption checkpoints. Keep approved policy, empty actual retry allowlist, all five false authority flags and commissioning deferrals unchanged. Commit and test exact source bytes; package commands/logs, identities, changed source/docs, patch/bundle, manifest and checksummed inner review ZIP with no outer checksum. Stop for review; no push, merge or live operation.
```

The [campaign](../next-campaign-provider-onboarding-o2-ledger-custody.md) and [contract](../../contracts/provider-onboarding-ledger-custody.md) contain the full requirements. If implementation reveals an incompatible existing producer, report the concrete interface gap and preserve refusal; do not manufacture evidence or reopen settled owner choices.
