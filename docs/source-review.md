# Bounded source review

This is a separate, additive integration. The closed inspection campaign and its
installed package, accounts, trust, evidence and consumed authorities are unchanged.
No live mission or deployment is authorized by this implementation.

## Route and actual contracts

The selected route is the existing operational cognition path, not Curia audience
submission and not the protected Git inspector:

1. `SnapshotStore::prepare` accepts a local JSON input file, reads only its explicit
   files and behavior file, and stores a content-addressed immutable proposal through
   `ImmutableRecordStore`. No cognition is called. It copies exact UTF-8 bytes as
   base64 and records hashes, sizes and LF-based line counts. CRLF is preserved.
2. `BoundedExecutionAuthorizationService::authorize` now additionally accepts exactly
   `['source_review' => <validated proposal>]`. Its existing deployed-custody and
   Seneschal checks still apply. Source-review authorization is serialized per
   custody transition. The existing `target_url` contract is retained.
3. `OperationalCognitionRequestService::request` binds the authorization digest and
   input digest to a one-iteration request. `OperationalProviderResourceDecisionService`
   records the explicit Operator provider/resource decision under the existing
   `development-local-cli` authority basis. Neither preparation nor plan approval
   substitutes for this decision. `authorize` is an authority-changing owner action.
4. `OperationalCognitionLeaseService::issue` requires the decision's activation
   authority and an existing authorized Locksmith binding. `OperationalCognitionInvocationClaimService`
   atomically consumes the exact lease and cognition authority using its existing
   `AtomicTransition`/transactional-consumption machinery.
5. `BoundedOperationalExecutionService` validates the deployed chain and dispatches
   through `SymfonyAiOperationalExecutionCognitionGateway`. The additive source-review
   branch uses `SourceReview\Gateway`, `OperationalClaimBoundCredentialBroker`,
   `ProviderInvocationJournalService` and `ProviderResponseEnvelopeService`.
   The journal reservation is the single provider winner even if concurrent callers
   converge on the same atomic claim. A repeated completed call may return the saved
   execution, but does not call the provider again.
6. The source gateway sends the already validated in-memory payload, never another
   filesystem read. It seals the response before result parsing. Result validation
   and the existing bounded-execution record preserve provenance locally.

The newer `CanonicalNativeEffectCorridor` exposes a provider-double-only boundary,
not a production cognition transport. This integration does not repurpose that proof
boundary. It uses the existing operational atomic claim and provider journal. There
is no new signing, trust-enrollment, general authority service or protected-inspector
provider permission. Source-review code is under `src/SourceReview`; the command is
`src/Command/SourceReviewCommand.php`.

## Input schema

The original string-path input remains supported with byte-identical v1 proposals.
Explicit selections are also supported; see "Provenance-preserving excerpts" below.

Supply a directory containing the explicitly named source files, behavior text and
this JSON file. Paths are relative to the JSON file's directory. This example is an
input illustration; use your actual filenames and runtime context.

```json
{
  "files": ["Service.php", "Dependency.php", "ServiceTest.php"],
  "expected_behavior_file": "behavior.txt",
  "runtime": "PHP 8.4; describe necessary framework/runtime versions here",
  "pricing": null
}
```

All four keys are required; extra keys are rejected. `pricing: null` permits local
preparation and inspection but blocks authorization/dispatch. To prepare an
authorizable proposal, supply a versioned, owner-reviewed conservative tariff:

```json
{
  "version": "owner-reviewed-tariff-version",
  "source": "URL or record identifying the verified provider tariff",
  "valid_until": "YYYY-MM-DDTHH:MM:SS+00:00",
  "input_microusd_per_token": 1,
  "output_microusd_per_token": 1
}
```

The numbers above illustrate integer units, **not current prices**. Round each
verified rate upward to whole micro-USD per token, using the highest applicable
input rate rather than assuming cache discounts. A micro-USD is 0.000001 USD.
Changing pricing or any other approved field changes the proposal identity.

## Provenance-preserving excerpts

Each `files` entry may instead be an object with exactly `path`, `sha256`, and
`ranges`. Paths are original repository-relative paths within the input directory,
using the same safe-path rules as whole files. Example entry (replace the hash with
the expected original SHA-256):

```json
{
  "path": "src/Service/Example.php",
  "sha256": "<64 lowercase hexadecimal characters identifying the complete original>",
  "ranges": [[1, 25], [100, 160]]
}
```

Ranges are inclusive original 1-based line numbers, in ascending order, with no
overlap. There must be 1-30 ranges per original; paths cannot repeat, including
case variants or mixed string/object entries. A complete file can be selected as
`[[1, N]]` with its expected hash, or retain the historical string entry.

`SnapshotStore::prepare` reads each original at most once through the same bounded
safe local reader. `Selection::derive` hashes that in-memory content, compares it
with the expected SHA-256, and derives all selected bytes from that same content.
LF determines line boundaries; CRLF and final newline state remain unchanged.
Omitted originals are neither stored in the proposal nor re-read during dispatch.
The local original file read remains capped at 131,072 bytes. No directory crawling,
automatic splitting, limit increases or provider call is introduced.

A proposal containing selections uses `imperium.source-review-proposal/v2`.
Its sealed `files` store original path/hash/byte count/line count plus separate
segments with original start/end and exact base64 bytes. Rebuilt manifest and
outgoing payload include segment hashes/sizes and explicit local-to-original
mapping (`segment_start_line: 1`, `segment_end_line: N` maps to original
`start_line` through `end_line`). `omitted_ranges` lists all absent original lines.
The provider receives separate segment content strings, never concatenated across
gaps. Fully selected files have empty omitted ranges and a verified full-content
hash. Whole-file string entries in mixed proposals retain their original format.

All identity, selection, mappings and content participate in the proposal ID,
manifest digest, approval digest and exact payload digest. Validation rebuilds
metadata from the sealed selections. As with v1, the existing immutable store and
authorization chain protect the prepared snapshot; an original hash is not a
signature or proof of semantic completeness. The original-to-excerpt relationship
is verified locally at acquisition; after preparation, validation uses sealed
selected bytes and their bound provenance, without retrieving omitted content.

The existing gateway sends the already validated payload string unchanged. The
result parser first validates the proposal and accepts a citation only if its
original path and entire original line range lie within one supplied segment.
Omitted lines and cross-segment citations are rejected, even for adjacent segments;
select a contiguous range when one finding must span it. Results retain the proposal
and manifest digest references needed to retrieve the exact mappings.

Limits still apply to 30 original files, 131,072 selected source bytes, serialized
HTTP payload bytes + 1,024 <= 32,000, 4,000 output tokens, one request, 120 seconds,
and the existing tariff-based USD 1 ceiling. Metadata consumes serialized-payload
budget too. `pricing: null` continues to permit only preparation/inspection.
The command syntax and all authority/lease/claim/journal controls are unchanged.

Files must be regular, single-link UTF-8 text without BOM, NUL or binary control
characters. Paths use portable ASCII segments, `/`, no escapes, device names,
trailing dots or case-insensitive duplicates. Links, network paths, stream wrappers,
alternate data streams and directories are refused. No recursion or dependency
installation occurs. The input root and store still rely on normal local OS custody;
these checks are not a replacement for an independently isolated deployment.

## Real commands

Run from the reviewed repository checkout with PHP 8.4 and existing locked
dependencies. These are implemented commands, not permission to execute the later
authority/provider steps. PowerShell examples use output identifiers returned by
the preceding command. Do not paste real credentials into any command.

```powershell
# Offline preparation only. Use your actual input-file path.
$prepared = php bin/console imperium:source-review prepare C:/Owner/Review/input.json | ConvertFrom-Json
$proposalId = $prepared.proposal.proposal_id
php bin/console imperium:source-review inspect $proposalId
```

`inspect` returns the complete proposal and `proposal_digest_for_approval`. Decode
the `payload` JSON string to inspect the exact application-controlled HTTP request
body: messages, model, temperature, output limit, disabled thinking, JSON response
format and nonstreaming setting. Source bytes are also included separately as
base64 with manifest hashes. The disclosure and limits are explicit proposal fields.
Inspect the supplied source and behavior, not only the digest.

The following owner actions require a separately authorized live run and genuine
current deployment/custody, Seneschal and Locksmith bindings. No command fabricates
these prerequisites. Values in angle brackets are identifiers the owner must supply.
The new integration has not been installed into the closed inspection package.

```powershell
$approval = php bin/console imperium:source-review authorize $proposalId $prepared.proposal_digest_for_approval '<operational-custody-transition-id>' '<seneschal-binding-id>' | ConvertFrom-Json
$lease = php bin/console imperium:source-review lease $approval.decision_id '<locksmith-binding-id>' | ConvertFrom-Json
# This is the sole source-transmitting step; do not run before explicit activation.
php bin/console imperium:source-review execute $approval.authorization_id $lease.lease_id
# Read-only status includes the validated result, or failure/unknown journal state.
php bin/console imperium:source-review status $approval.authorization_id
```

Check each process exit code before proceeding; exit 1 means refusal/error. Authorize
requires the exact proposal digest, creates the existing bounded authorization,
cognition request and Operator decision, and does not call a provider. Decision
validity is at most ten minutes; the lease is at most five minutes. Preparation can
occur well before these short-lived steps. Partial authority creation is retained;
inspect records after a refusal, do not blindly repeat or manufacture replacements.

## Source storage, disclosure and limits

Proposals are at `var/imperium/mission/source-review/proposals/<proposal-id>.json`.
They embed exact source bytes; there is no mutable snapshot directory to reopen at
dispatch. The authorization embeds the same complete proposal. Changing an original
file after preparation does not change the reviewed snapshot. Prepare a new proposal
to review changed bytes; changing sealed records is refused.

The exact body goes to `https://api.deepseek.com/chat/completions`, requesting
`deepseek-v4-flash`. This is a provider alias, not a guarantee of immutable weights.
The payload contains only the fixed review instruction, supplied source, behavior
and runtime context, plus request settings. It excludes internal authority records,
manifestations, capabilities, signatures and credential references. The existing
broker adds authentication outside the body. Normal HTTP headers, connection/IP and
any locally configured network proxy are also visible at their respective boundaries.
The owner must exclude secrets from supplied source; input text is not automatically
redacted or silently rewritten.

Enforced application limits are 30 files, 128 KiB source bytes, 32,000 input-token
conservative bound, 4,000 output tokens, one request, no retry, no redirects and a
120-second HTTP duration limit. Behavior, runtime and all model-visible instructions
count toward the input bound. The byte bound uses the entire serialized request
length plus 1,024 tokens reserved for the fixed two-message framing. This deliberately
overestimates byte-level tokenization and can reject source well below 128 KiB.
It is versioned `utf8-bytes-plus-1024-v1`; provider-added hidden context is not
independently measured. A changed provider tokenization/template contract requires
revalidation, not a silent budget increase.

The dedicated undecorated Symfony HTTP client uses one POST, HTTP/1.1 and no SDK or
retry decorator. `max_tokens: 4000` is sent to the provider; responses reporting a
length stop or excess usage are refused. API support was checked against the
[official chat-completion contract](https://api-docs.deepseek.com/api/create-chat-completion/)
on 2026-09-06. No live conformance call was made.

The USD 1 ceiling is a conservative preflight against the explicit supplied tariff,
not a provider-side account spending lock. Current pricing must be independently
reviewed before activation; no default current price is embedded. The request
deadline bounds local waiting; it cannot guarantee remote cancellation, no billing
after timeout or invoice correctness. These are retained operational boundaries.

## Results and failure handling

`FINDING` contains exactly one authorized path and valid 1-based start/end lines,
triggering input, expected/actual behavior, cause, impact, smallest suggested
correction and unexecuted regression case. `NO_ACTIONABLE_DEFECT_FOUND` and
`INSUFFICIENT_INPUT` have a null finding and rationale. Every accepted result is
labeled static, with `reproduction_executed: false`; schema validity proves neither
semantic correctness nor the existence of a defect.

Result records live under `var/imperium/mission/source-review/results/<claim-id>.json`.
Provenance binds proposal, manifest, behavior, input, outgoing payload, authorization,
claim and raw model-content response digest. Raw model content is retained in the
existing provider response envelope. HTTP envelope authorship is not independently
attested. Malformed/out-of-scope results are `RESULT_INVALID`, not no-finding success.
Transport failures retain an unknown, no-replay journal state. Interrupted pre-I/O
reservations also prohibit automatic replay. `status` never resumes execution.

## Offline demonstration

```powershell
php tools/demo-source-review.php var/source-review-public-packet-new
php vendor/bin/phpunit tests/SourceReview --no-progress
```

The demo requires a new output directory. It constructs only synthetic prerequisites
in a fresh temporary root, a fake credential broker and recording provider, and uses
the real command/workflow/authorization/lease/claim/journal/result route. It records
prepare → inspect → authorize → lease → execute → status and exact delivered payload.
It retains the synthetic root for diagnosis and writes a public synthetic source
packet and SHA-256 manifest. It does not prove live provider availability or deployed
isolation. Never copy synthetic authority records into a real store.

The next owner step is to supply the bounded source input and expected behavior for
offline preparation. Before a live activation request: review current pricing, the
exact proposal/payload, the legitimate operational prerequisites and the intended
execution environment. No installation or enrollment repetition is implied.
