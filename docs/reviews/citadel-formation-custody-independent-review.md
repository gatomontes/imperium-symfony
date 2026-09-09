# Citadel FC0–FC3 — independent review

**Verdict: ACCEPTED within local/offline software scope. No corrective campaign is required by this review.**

The submitted implementation establishes the formation-specific retained-claim custody path, durable one-use infrastructure entry, exact prepared-operation binding and response-attribution recovery, with default production transport still refusing. This is not live commissioning or proof of genuine installed authority. Owner disposition remains **DEFER_ENROLLMENT**.

## Verified identity and packet

| Item | Verified identity |
| --- | --- |
| Campaign-selection baseline | `758dcd6e5f84e32a65e4e5dda68dbb5b86d7c7e8`, tree `a3a0dbaaac4be942bcf57d473732d4a7be5a5934` |
| External archive | `citadel-formation-custody-daafb83f4597.zip` |
| Archive SHA-256 | `da9cb87bb081fc9b6abb6bab7c86bfd65cd5434627666cc2a334b8ffc158c6a5` |
| Final executable test commit | `b33226db7720d7d6768274ac3fc96aa38b787b62` |
| Tested tree | `04ac9f1465663736843ccc25c2abe258091dd938` |
| Final review commit | `daafb83f45978f1d203c9cba693bfaf96a1cd4ab` |
| Final review tree | `3826740810f42c90e6d8b6e00686947003ab62c4` |
| Local branch | `codex/citadel-formation-claim-custody` |

The outer hash matches the uploaded SHA file. The complete packet contains 148 payloads plus its root manifest, with no duplicate archive paths. Every payload hash and the full path set verified. Tested/final archives contain 3,137/3,138 source files. Their bytes, lengths, Git blobs, modes and reconstructed trees match the manifests and actual Git commit objects. The bounded bundle verifies against the exact campaign-selection prerequisite. Both supplied final/post-test diffs match independently generated full-index Git diffs.

The initial and pre-inventory source archives also match their Git manifests and commits. The external report matches the final committed report after newline normalization. No missing tested-to-final source link remains.

## Source and post-test attribution

There are 23 changed/added paths against the selection baseline. Existing configuration/dependency files and existing test assertions are unchanged. Ten production source files implement or integrate the new boundary; the remaining paths are contracts, reports/flow, the explicit runtime inventory, new tests/fixtures and the synthetic proof producer.

The development sequence is retained honestly:

1. `1b5f45d3c1a3f1a04fc62c517b6655fe3e2ce22a`: initial implementation. An identified response-recovery diagnostic failed against the exact old `FormationCognition` class with newer supporting classes. This is a mixed-source diagnostic, not a full old-tree execution; the packet states that limitation. Its archived old class matches the Git blob.
2. `31bdeb35c14c5c15b8b2bc126e4cec4948b4b9e7`: validated response metadata retained before envelope publication. The subsequent complete suite had two explicit frozen-inventory failures; those failures remain available.
3. `b33226db7720d7d6768274ac3fc96aa38b787b62`: exactly one inventory row added for the new custody consumer. Independently verified: no PHP, test, contract, configuration or dependency change in this commit. Final focused and full-suite gates identify this commit/tree.
4. `daafb83f45978f1d203c9cba693bfaf96a1cd4ab`: six Markdown-only changes: the formation-runtime contract pointer, final report, readiness matrix/runbook, Delegate flow and next-route document. No executable post-test change.

The earlier helper collision, insufficient synthetic receiving ceiling, interrupted initial full run and failed inventory run are disclosed rather than counted as final successes. The generated `config/reference.php` differences contain PHPDoc only and were retained separately and restored to the committed blob. Final archives contain the committed version; the producer's status observations disclose regeneration during full-suite/lint execution.

## Accepted production paths

| Boundary | Source evidence and conclusion |
| --- | --- |
| Authentic formation authority | `FormationClaimCustodyBroker::validate` requires exact equality with the actual aggregate session/attempt/claim/request/terms, v2 schema and started/unsettled state. It revalidates the signed phase/source, current holder/Locksmith, provider-resource decision, aggregate exposure and rebuilt consumed authority/lease. Caller flags or re-sealed copies do not establish authority. |
| Exact operation | `FormationPreparedOperation` binds canonical request bytes, exact wire bytes/hash, provider/model/destination, signed public credential reference/operation/adapter, authority-source/pricing digests, limits and expiry. `FormationCognition::call` retains the initial prepared operation; custody and the final callback compare it again. The fixed deployment adapter must honor all non-secret dispatch inputs represented by those bytes. |
| One-use custody | `FormationClaimCustodyBroker::deliver/advance` commits delivery, consumption and dispatch markers before their possible effects under `FormationJournal`. Exact capability scope is checked. No caller callback enters the public broker API. Repeated callbacks cannot pass the durable dispatch transition twice. |
| Currentness and locks | Shared `FormationSessionAuthority` preserves the original source, refusal history and understanding checks. Currentness is checked at successive custody boundaries. Credential operations, adapter dispatch and response-store publication occur outside the aggregate lock. The final dispatch authorization checkpoint is not a guarantee of physical I/O before expiry or remote cancellation. |
| Response attribution | The broker validates operation identity, adapter provenance, response ID, body and ordered integer usage, then durably records response identity and attribution before sealing the real envelope. V2 recovery requires that retained metadata and matching envelope/body. A dispatch marker plus an independently self-sealed response cannot create admission. |
| Exposure and recovery | Invalid or absent usage cannot settle exposure. Unknown attempts retain their reservation and one-use markers; no automatic reissue/dispatch/refund occurs. Recovery after envelope publication preserves original provider ID/provenance and can retain full maximum when caller settlement was interrupted. Already-admitted completion is recognized without fresh authority. |
| Defaults and preparation | `UnavailableFormationTransport` remains the production alias; `UnavailableFormationWireAdapter` is the new refusing wire alias. `CustodiedFormationTransport` is a dormant integration seam. `FormationPreparation` serializes the optional signed transport terms without acquiring authority. No live activation command, environment switch or ordinary root/clock/adapter override is added. |

Institutional resolvers, formation-specific trust enrollment, genuine appointment requirements, native protocol and the legacy brokers are not replaced. The session interpreter was extracted with the original phase/control logic preserved. CF01, CF02, IR01 and NA-IR01/02 remain within their existing accepted scopes; the full supplied suite includes their existing regressions. Native enrollment is not promoted into formation competence.

## Verification performed here versus supplied results

**Rerun by this reviewer:** external archive hash; complete packet/source verifier; Git bundle verification; source-manifest comparisons against Git objects; initial/pre-inventory archive verification; exact diff and inventory-only comparison; warning normalization comparison; public proof verifier and its substitution negative control. The public proof verifies 32 unique synthetic signatures, two supplied frames, exact operation/claim/lease/envelope/response attribution and supplied counts of one issue/consume/dispatch. Those counts are inspected producer evidence, not independently observed PHP effects.

**Supplied local PHP results inspected, not rerun here:**

| Final gate | Supplied result |
| --- | --- |
| Focused | 78 tests / 4,266 assertions; exit 0; no failures, errors, skips or warnings |
| Full suite | 2,929 tests / 54,268 assertions; exit 0; no failures, errors or skips; four qualified warnings |
| Container lint | Passed; refusing alias wiring retained |
| Synthetic PHP proof | Passed through actual formation command/services with generated authority and recording infrastructure |

PHP is unavailable in this review environment. I inspected test source, JUnit, raw output, command arrays, source/environment identities and failure history. No claim is made that I reran the PHP suite, the Windows process tests or the PHP proof producer.

The 62 new custody cases include 23 substitutions, seven currentness changes, changed-wire and late-refusal hooks, pure-preparation/default-alias checks, wrong or missing usage/provenance, cross-root claims, forged-envelope recovery, callback replay, separate drafting/receiving authority, and aggregate-budget retention. Process tests synchronize two workers at the real custody seam and assert one issue/consume/dispatch. Eight abrupt-exit cases cover pre-delivery through post-envelope and post-receipt publication, including retained attribution and replay refusal. These use actual validators and persistence with synthetic infrastructure, not duplicate implementations of the validation logic.

The four full-suite warnings match the historical native-correction transcript after worktree-root normalization: `.git/HEAD` reads in DeploymentCustodyCrashDemonstration:290, OperationalConstructionCrashDemonstration:399, TerminalRetirementCrashDemonstration:183 and UnknownProviderOutcomeCrashDemonstration:245. They remain qualified historical helper limitations. The previous 2,867/53,961 and original formation 2,729/53,069 results retain their historical attribution; neither is presented as this campaign's fresh result.

## Remaining limits

Acceptance is limited to cooperating processes, trusted runtime storage and reviewed adapter/infrastructure code. Hash chains do not defeat administrator rollback or malicious writers. Process-exit tests do not prove power-loss durability, directory fsync or exactly-once remote effects. The in-memory credential implementation is not being certified as transferable cross-process custody; the new aggregate fence prevents a second delivery, including when an unused capability is forfeited.

Synthetic signatures, wire output and usage establish offline behavior only. Actual institutional lineage/currentness, formation-specific competence, personnel judgments, qualified Castellan/Locksmith appointments, installed custody and public trust remain unresolved. The nine institutional witnesses and the accepted planning-only Guildhall evidence keep their existing qualifications.

Actual provider/model/destination/reference/operation/adapter selection and B1's enforceable billing/time/cancellation, pricing and usage evidence remain open. A local timeout or tariff-based reservation is not a remote guarantee. No B1 amendment occurred. Exact authentication echoes and exception-chain exclusion are limited controls, not general DLP or protection against malicious trusted adapter code.

The review made no installation change and did not freshly attest the Windows installation. No real keys, credentials, enrollment, provider calls, institutional acts or mission execution were involved. No GitHub publication, PR or merge was performed by this review.

## Next executable owner action — publish the accepted source for PR/CI

Give Loco this review. Publish the exact accepted commit from the campaign worktree, preserving any unexpected state and stopping on mismatch. Publication is repository integration only; it grants no installation or operational authority.

```powershell
Set-Location 'E:\htdocs\imperium-citadel-formation-custody'
$acceptedHead = 'daafb83f45978f1d203c9cba693bfaf96a1cd4ab'
$acceptedTree = '3826740810f42c90e6d8b6e00686947003ab62c4'
$branch = 'codex/citadel-formation-claim-custody'
$currentBranch = git branch --show-current
if ($LASTEXITCODE -ne 0 -or $currentBranch -ne $branch) { throw 'Unexpected branch; preserve it.' }
$head = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or $head -ne $acceptedHead) { throw 'HEAD differs from accepted source.' }
$tree = git rev-parse 'HEAD^{tree}'
if ($LASTEXITCODE -ne 0 -or $tree -ne $acceptedTree) { throw 'Reviewed tree mismatch.' }
$status = git status --porcelain --untracked-files=no
if ($LASTEXITCODE -ne 0 -or $status) { throw 'Tracked changes require review; do not overwrite them.' }
$origin = git remote get-url origin
if ($LASTEXITCODE -ne 0 -or $origin -notmatch '^(https://github\.com/gatomontes/imperium-symfony(?:\.git)?|git@github\.com:gatomontes/imperium-symfony(?:\.git)?)$') { throw 'Unexpected repository origin.' }
git push -u origin ('HEAD:refs/heads/' + $branch)
if ($LASTEXITCODE -ne 0) { throw 'Publication failed; preserve output. Do not force-push.' }
$remote = git ls-remote --heads origin ('refs/heads/' + $branch)
if ($LASTEXITCODE -ne 0 -or @($remote).Count -ne 1 -or ($remote -split '\s+')[0] -ne $acceptedHead) { throw 'Remote head was not verified.' }
Write-Output ('Published accepted source: ' + $acceptedHead)
```

Do not stage raw evidence, delete untracked files, reset the branch or update the installed app. After publication, verify the exact PR head and CI before integration. Any new source or integration change needs its own attributable review. Enrollment stays deferred; live readiness, activation and execution remain false.

Citadel receives; Castellan interviews; understanding closes interview authority. Separate approval permits drafting; separate mission approval precedes legitimate child-Curia constitution/handoff. Receiving assessment grants no execution authority.

*Imperium via solitaria est.*
