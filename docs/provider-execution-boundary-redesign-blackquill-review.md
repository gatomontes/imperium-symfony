# Provider Execution Boundary Redesign — Blackquill review

## Finding

`BOUNDARY_REDRAW_REQUIRED`

The terminal custody refusal is truthful, but the requirement that produced it is malformed. A
process-local PHP object cannot preserve exact object identity after its process exits unless some
external custodian becomes the new source of truth. The prior requirement simultaneously demanded
that continuity and prohibited every persisted or external mechanism capable of providing it.

The model also fused three distinct things:

| Thing | Correct role |
| --- | --- |
| Provider credential | Authentication material controlled by the deployment boundary |
| Execution authority | Exact, expiring, single-use permission for one provider operation |
| `CredentialCapability` object | Process-local enforcement mechanism joining authority to credential access |

Only execution authority requires durable identity and consumption. Reading credential material
inside the credential-owning execution boundary does not create execution authority. Without an
intact, exact and atomically consumed authorization, that material remains unusable by the governed
route.

## Threat-model correction

Imperium currently claims one authoritative filesystem root and
`TRUSTED_WRITER_CANONICAL_INTEGRITY`. It does not claim hostile-writer non-forgeability, multi-host
consensus or split-brain resistance. Requiring immortal PHP object identity does not protect against
an attacker who can replace code, rewrite records or read process credentials, and it exceeds the
identity continuity required to address the declared crash, replay, contention and stale-authority
threats.

The corrected question is not how to transfer a credential-capability object between processes. It
is how one credential-owning execution boundary validates and atomically consumes an exact durable
authorization before credential resolution and external I/O.

## Candidate postures

1. **Same-process governed executor** — the execution process validates and consumes durable
   authority, commits effect-start, resolves its locally owned credential and invokes the provider.
2. **Local credential-owning broker** — a dedicated local service authenticates a request, consumes
   exact authority and performs the provider call without transferring credential possession.
3. **External custodian or dynamic-secret platform** — a separately governed platform owns
   authentication, lease, revocation and availability semantics. This relocates custody; it does not
   abolish it.
4. **Permanent refusal** — preserve analysis and governance while declining all external execution.

The smallest plausible posture under the current declared deployment model is the same-process
governed executor. That is a review finding, not an implementation selection or runtime authority.

## Verdict

Do not attempt another cross-process PHP capability-identity transfer campaign. Redraw the boundary
so credential possession remains stationary and only exact durable execution authority crosses the
workflow boundary. Preparation must still prove that the proposed boundary is coherent; this review
does not authorize it.
