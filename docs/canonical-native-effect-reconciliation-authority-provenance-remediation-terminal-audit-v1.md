# Canonical native-effect reconciliation authority provenance remediation — terminal audit v1

Verdict: `RECONCILIATION_AUTHORITY_PROVENANCE_ACCEPTED_BOUNDED_NO_LIVE_EFFECT`

The original closure was defective. It treated a public digest and issuer label
as though they authenticated issuance, then allowed tests to manufacture the
same array the runtime trusted. That was integrity theater, not provenance.

The corrected boundary is materially different. A canonical issuer begins from
an immutable effect admission and resolves the committed native transition,
native authority, active native Imperator principal and signed Operator Root act.
It publishes deterministic authority plus separate issuance evidence. A resolver
repeats those joins and delivers only an exact process-incarnation-bound object.
Claim admission accepts that type, not an array. One capability ID wins durable
authority-to-claim publication; the exact claim digest is then consumed for its
deterministic receipt. Replay returns established truth, while reconstruction
joins receipt back to the Root act without writing or granting authority.

Adversarial proof covers copied fields, fresh counterfeit digests, missing or
substituted issuance, Root revocation, stale principal lineage, clone/serialization,
fresh processes, competing claimants, issuance/derivation/receipt interruption
cuts, expiry, exact replay, real container construction and no-provider source
inspection.

## Evidence

- terminal audit start: clean merged Batch 4 `main`
  `98ba984c7cb808bd2195b5637f61c079bd47a22f`;
- campaign-focused local: `99 / 856`, pass;
- corrected frozen-tripwire local: `10 / 3884`, pass;
- corrected full local: `2474 / 51016`, pass;
- exact candidate merge: `98f9777959efa279aa6f93e0e240fe861409cef1`;
- GitHub Actions: run `33874716024`, job `101028835208`, Ubuntu/PHP 8.4.25,
  `2480 / 51049`, pass.

## Bounded verdict

This closes the reconciliation-authority provenance remediation campaign only.
It does not authorize a credential, provider callback, mission, email, Iron Gate,
Lazaretto, multi-host locking claim or hostile same-process-memory claim. The
existing no-provider recovery boundary remains. Batch 7 remains suspended.

`CANONICAL_NATIVE_EFFECT_RECONCILIATION_AUTHORITY_PROVENANCE_REMEDIATION_COMPLETE`

