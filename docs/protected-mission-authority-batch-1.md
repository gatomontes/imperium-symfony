# Batch 1 — protected owner and bootstrap

The narrow `AuthorityOwner::dispatch` protocol cannot enroll trust, select a path/verifier/clock,
or export a signing secret. `InstalledRuntime` resolves a fixed platform installation root without
environment or project configuration fallback. `enroll` is a deployment-owner operation outside
the request protocol; it validates identity, competence and confirmed public-key fingerprint,
records custody and refuses replacement. Rotation must use existing signed authority in a later
protocol operation; total key loss requires offline owner recovery with existing missions retired.

Runtime account and installed code are trusted. Constructing an owner over one's own directory
does not confer access to the installed authority. The PHP constructor and journal digest provide
no isolation from arbitrary same-account code. Installation records are not ACL measurements.
Actual account isolation remains UNPROVED; no accounts or production ACLs have been provisioned.

The single locked journal is the selected common exclusion owner. Each complete frame contains
the whole authoritative state. A partial trailing frame cannot publish authority; a complete frame
with a bad digest refuses. Writes flush and fsync before returning. After an ambiguous I/O failure,
the client must inspect state and must not assume the action was unconsumed. Filesystem/hardware
power-loss behavior, journal growth limits and genuine account isolation require deployment review.

Focused command: `php vendor/bin/phpunit tests/Imperium/Runtime/ProtectedMissionAuthorityBatch1Test.php`.
Result: **2 tests / 24 assertions**, PHP 8.4.14, PHPUnit 13.3.0; no skips.
Tests create new disposable roots and keys; no real trust or issuer key is created.
Production lifecycle and ceremony are not delivered by this batch.
