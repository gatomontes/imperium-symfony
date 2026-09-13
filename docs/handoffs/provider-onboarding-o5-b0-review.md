# O5-B0 implementation review

The user instructed O5 to proceed after accepted O4 integration. Implementation starts from main `b4e6e8c57f729c1e1e49284207f34f8909276e0b`, tree `f272855e04ff92898c42dc291eb7ec0f16a09614`. [CLI behavior and limits](../provider-onboarding-o5-cli.md).

Reviewed boundaries:

- Public ingress retains the exact existing schemas and domain validators. Presentation never enters command identity.
- Read-only observations use a non-creating lock path under the same formation writer fence. Missing custody refuses; there is no read-triggered migration or enrollment.
- The shared `AtomicTransition.php` remains byte-for-byte pinned to its historical reviewed source. The new observation path belongs to `FormationJournal`, which opens its established `citadel-formation` lock read-only; no successor-review ledger is rewritten to authorize a shared writer change.
- Advances use the existing runtime/ledger/application owners. The new optional assignment evidence dependency preserves prior constructor behavior.
- Status/preview inspect retained public originals, with no key, generation, adapter or provider calls. Historical receipts remain visible separately from current prerequisites.
- Exceptions following publication are re-observed to retain command identity, reservations and unknown outcomes. Resume recognizes evidence and cannot re-dispatch.
- Retained signatures are checked independently of current time. Expiry/revocation blocks new work but does not demote an uncertain dispatched effect to an ordinary refusal; its reservation and original result remain visible on replay. Integrity failures still take precedence.
- JSON and human output share one status result and exit mapping; arbitrary exception payloads do not leave the command boundary.
- Contracts, the pinned service configuration, budget limits, retry policy, workflows and operational flags are preserved. The default alias uses a service attribute. The existing exact runtime-method boundary assertion is explicitly extended to include evidence-only `resume(string)` and construction-only `assertOwner(AuthorityStore)`; it still prohibits every public delivery/capability entry and now checks those new parameter types. Fixture extensions retain their prior defaults.

Validation is in progress. This file does not declare O5 accepted or merged. The unchanged complete parallel gate must verify every enumerated test exactly once and the exact implementation tree before closure.
