# O2-B1 correction design

R1 uses one closed response-evidence validator at metadata retention, envelope publication/recovery, state decoding and settlement. Every checkpoint operation_digest denotes the exact prepared operation, not its storage wrapper. Original source-specific response validation remains required for settlement.

R2 closes all populated v2 shapes and their original cross-links. Historical migration subtree digests are recomputed from retained B0 first-admission originals whose predecessor generation predates migration, including the original enrollment and revocations. Later admissions are excluded; current evolved maps are never substituted for the historical maps. All H identities bind to retained enrollment. Structural decoding has no current-time authority effect. No nested journal access or new persistence is introduced.

The existing B0 Admission writer needs one narrowly bounded post-mutation v2 validation call so its publication cannot introduce invalid or oversized v2 state. This does not change B0 acceptance, raw originals or signature/currentness rules. Other B1 writers validate completed state before publication. FormationJournal, SessionExposure, service configuration, original fixtures/tests and reviewer requirements remain unchanged. New validators use Symfony Exclude and have no operational wiring.

R3 resolves the advancing sequence head inside the same lock for new and historical resume presentations. The recognition command keeps its own immutable result_ref; it cannot become an advancing predecessor.
