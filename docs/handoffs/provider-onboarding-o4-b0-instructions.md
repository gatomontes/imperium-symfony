# O4-B0 handoff instructions

This is an **incomplete local implementation**, not completed O4-B0. Read the
report and proof matrix before using the patch. Do not mark O4 accepted or merge
this branch as the completed campaign.

1. Verify `payload-manifest.json`, the inner review ZIP SHA-256 and
   `identities.json`. The exact patch reconstructs the recorded final tree from
   entry f544b696a0ebdf8c25a71f3a369e5a93738074e9. The incremental bundle requires
   that existing entry history; inspect it with `git bundle verify`.
2. Review the shared AugurAdapter validation extraction and read-only
   AssessmentResolver against the original O3 behavior and preserved regressions.
3. Finish the missing v4 migration/history validator, exact assessment view and
   A/B authority derivation, atomic CommandLedger whole-pair publication,
   persistent formation consumer and authorized replacement/revalidation.
   Preserve the existing fail-closed checks until each owning seam is complete.
4. Add the remaining seven-row contract proofs using real producers, synthetic
   temporary authority and mock transport. Do not manufacture completed history
   or substitute supplied claims/flags for authentic source verification.
5. Rerun the exact public commands in `evidence/*-commands.json` against the new
   final source. Run the unchanged full `vendor/bin/phpunit tests` gate with its
   30-minute allowance, then obtain source review and fresh full hosted CI.
   Local targeted passes and inherited O3 CI are insufficient for acceptance.

No further DeepSeek/FRESH/D2-A/P1–P9 decision is requested. No push/merge,
operational activation, live authority or O5 work is authorized by this handoff.
Existing personnel and session/lease authority remain separate from settings.

The ZIP is a local artifact, not a published URL. To copy it into Downloads:

```powershell
Copy-Item -LiteralPath 'E:\htdocs\imperium-o4-b0-evidence\provider-onboarding-o4-b0-all-deliverables.zip' -Destination (Join-Path $env:USERPROFILE 'Downloads\provider-onboarding-o4-b0-all-deliverables.zip')
```

No remote download endpoint is claimed. If separately published later, use its
actual verified URL; do not invent one or treat this local copy command as HTTP.
