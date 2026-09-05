# Batch 1 — executable owner route

Added LocalIsolation.php/local-isolation.php for local object materialization,
exact target inventory, mission draft, manifests and independent public receipt
reconstruction. No production authority or canonical service changed.

Added owner package builder/fresh installer, exhaustive probe-plan generator and
native CreateFile handle probes. Probes distinguish OS access denied (5), absent
file (2), absent parent (3), unexpected success and unknown error. They request
rights without exercising writes, deletion or reading content. Separate actual
Runtime/caller tokens remain required; no same-user result proves isolation.

Added LocalMission.ps1 and installed Invoke-LocalMission.ps1 for preparation,
external-signature acceptance, persisted status and one transition per invocation.
CreateNew attempt markers prevent blind replay after uncertainty. Status recovers
derived IDs and completed missions without repeat execution. Capabilities and
pending payloads stay in owner-only exchange. Human Runtime mediation is required;
the caller is never allowed to choose a command, PHP argument, script or shell.

ProtectedMission.ps1 now accepts an explicit PHP binary and a 120-second transport
timeout to accommodate the unchanged 60-second worker ceiling and startup overhead.
No worker budget was increased. Timeout means stop/query, never completion.

Initial focused tests: 28 tests / 417 assertions, zero skips, precommit tree.
PowerShell rehearsal used a new real 15-path Git fixture and disposable signing
identity; completed all four states and public reconstruction. Initial failed
transcripts remain in var/local-isolation-evidence: JSON timestamp conversion
changed signed record digests; fixed with PowerShell 7.5+ DateKind String. A second
verifier refusal exposed forward/backslash path spelling in the fixture invocation;
the test now supplies the exact signed repository string, without relaxing matching.
Passing transcript: batch-1-powershell-pass.txt. No real setup or mission performed.

Batch 2 will audit the committed package, run full tests and retain final hashes,
concrete owner commands, target object evidence and further negative rehearsal.
