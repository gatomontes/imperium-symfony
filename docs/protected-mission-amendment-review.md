# Protected mission candidate — amendment source review

Candidate: `7deb84b9f687ecb09c93f568464a606f0a602a89`.
Review disposition: implementation acceptance withheld; preserve branch for focused correction.

## AM01 — unsigned proposal mutates current authority

AuthorityOwner::dispatch exposes prepare without signature verification.
Ceremony::prepare replaces current_challenges and marks existing current authorization inactive.
Consequently knowledge of an active mission ID plus a shape-valid proposal permits interference
with its authority/pending challenge. Proposal permission does not confer amendment control.

## AM02 — evidence crosses authorization versions

AuthorityOwner::consume reads lifecycles and inspections by mission_id. New authorization
derivation does not establish distinct execution state. Completion compares inspection commit/tree
but not the authorization generation, path allowlist or budget under which inspection occurred.
A signed amendment sharing mission ID and commit/tree can therefore attempt completion with its
predecessor's INSPECTING state and findings. Status also selects historical evidence by mission ID.

These are source-level P1 findings; this reviewer did not execute exploit reproductions because
PHP was unavailable. The local campaign must reproduce both before claiming measured fixes.

Verified improvements: public dispatch no longer returns issuer secrets, verification/consumption
share the owner transaction, and a test-only CLI ceremony exists. This does not prove installation
isolation. Final evidence commit was verified documentation-only; Actions returned zero runs for
the published head at review. Local audit 2665 / 52515 remains reported evidence.

Corrective selection: `docs/next-campaign-mission-amendment-correction.md`.
Do not restart the entire protected-authority construction or erase prior results.
