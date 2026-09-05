# Batch 2 — verified durable consumption

The owner verifies the persisted Operator signature and exact canonical dossier/review/authorization
chain inside the same journal lock used by issuance, consumption, revocation, cancellation and
supersession. It uses Runtime wall time. Capabilities carry an asymmetric owner signature and the
exact authorization/dossier/mission/actor/target/issuer/state/expiry/nonce tuple. Only public
verification material crosses the protocol. The issuer secret remains in protected owner storage.
An absent authority cannot initialize it. Untrusted callers cannot write lifecycle DTOs directly.

The first valid transition linearizes when its complete journal frame is published; nonce and
lifecycle history are one state. Revocation that wins the lock prevents consumption. Consumption
that wins first remains historical after revocation. Fresh capabilities cannot reopen terminal
missions. A partial final frame is ignored, and retry repairs only that incomplete tail under lock.
A complete corrupt frame refuses. This is process-crash behavior, not measured hardware durability.

Focused run: **18 tests / 653 assertions**, no skips, including Batches 1/2 and the two historical
native-inspection test classes. Separate `proc_open` processes prove one winner and revoke/supersede
contention. These are same-user tests, not account isolation measurements.

The Batch 2 fixture uses fresh canonical services and fresh disposable signatures, but writes a
test-only journal directly. It is storage-boundary evidence, explicitly not a ceremony proof.
Batch 3 must replace fixture admission with a complete executable challenge/approval route.

Test adaptation: the Batch 1 unknown-operation check for `consume` becomes a malformed-arguments
refusal now that consumption exists. Its unchanged-journal assertion is retained. Service wiring
was restored byte-for-byte; a class-level Symfony exclusion avoids changing pinned historical
configuration. An exploratory full run begun before edits overlapped implementation and therefore
is not exact-head evidence: 2649 tests / 52194 assertions, two configuration-hash failures. Both
passed unchanged after restoration. The required Batch 2 full run is recorded separately.
