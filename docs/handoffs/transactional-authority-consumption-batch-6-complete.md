# Transactional Authority Consumption Adoption Batch 6 complete

## Result

Batch 6 adopts the shared transaction and recovery contracts across the deterministic Delegate
Senate subcluster in Mission Steps 19–42:

1. first question-commission disposition;
2. question-dispatch authorization;
3. Bailiff question dispatch;
4. subsequent question-commission issuance;
5. subsequent question-commission disposition;
6. finding-authority opening;
7. deliberation opening; and
8. disposition-authority opening.

`DelegateMissionSenateAuthorityTransition` provides one shared mechanical boundary. Every adopted consumer
retains its existing public act and validates the same authority-bearing source, occupied actor,
custody, hearing lineage, jurisdiction, and lifecycle conditions. Before replay selection, the
consumer acquires:

`delegate-senate-authority:<sha256 authorityId>`

The consumer then rereads and validates inside the lock. Its existing result schema and ID remain
unchanged. The result now embeds one `imperium.runtime-transactional-authority-consumption/v1`
envelope and commits through `ImmutableRecordStore` as the consumption and result's single physical
write.

Historical immutable results without an envelope remain valid and are not rewritten. Adopted
results reconstruct and validate the exact envelope from their unchanged schema, jurisdiction,
source digest, actor, authority consumption, result surface, and committed timestamp. Divergent
transaction metadata fails validation.

One two-process proof makes competing result identities contend on the same authority and converge
before a second result can commit. The fault proof separately injects failure immediately after the
immutable result commit; exact retry returns the same complete result and transaction. No rollback,
authority unconsumption, external effect, or second result is exposed.

## Recovery boundary discovered

Five consumers are intentionally not migrated: jurisdiction question authorship, testimony
response, Senator finding, finding reconciliation, and final Senate disposition. Each invokes a
Symfony AI cognition gateway before its lifecycle result is durable. A crash after cognition but
before result commit can repeat cognition on retry. Holding a lock cannot reveal whether that model
call completed, and a post-I/O envelope declaring no external effect would be false.

Those consumers therefore remain `RECOVERY_INCOMPLETE`. Migrating them requires a separately
prepared and authorized pre-I/O claim, journal, unknown-outcome, and forward-recovery boundary.
Batch 6 does not open or simulate that boundary.

## Preserved boundaries

No authority schema, authority ID, issuer, holder, competent consumer, source identity,
jurisdiction, custody rule, hearing contract, cognition gateway, result schema, result ID, public
method, disposition, or downstream authority changed. `NO_EXPIRY_DECLARED` records the pre-existing
absence of an expiry; it does not create expiry or revocation behavior.

The migration does not open cognition recovery, Profile Senate migration, operational adoption, Oracle/model
governance, construction/admission, older recovery, generalized authority, revocation,
propagation, telemetry, reassessment, containment, incident, Iron Gate, Lazaretto, sortie,
external-receipt, or credential-platform boundaries.

## Next separately bounded batch

Only Batch 7 may next be considered: migrate the deterministic portion of the legacy and
model-bound Profile Senate clusters while preserving their distinct lifecycle records and
competent actors. Cognition-bearing consumers must remain outside adoption unless their recovery
boundary is separately authorized.

Batch 7 is not authorized by this handoff; it requires an explicit continuation instruction.
