# Owner deployment isolation matrix

DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED.
Every actual-account result below is UNMEASURED. The supplied executable probe
plans produce per-path expected/observed access, error codes, token SIDs/groups
and readable SDDL. A missing file, skipped row or unknown error cannot pass as denial.

| Surface | Runtime expectation | Caller expectation | Actual result |
|---|---|---|---|
| Account token | Dedicated PmaRuntime, non-administrator, exact owner-recorded SID | Distinct PmaCaller, non-administrator, exact SID | UNMEASURED |
| Authority state/canary | Read/write permitted | Read/write denied | UNMEASURED |
| Real authority journal after enrollment | Required state operations permitted | Handle-open read/write/delete/ACL changes denied; no bytes read by probe | UNMEASURED |
| installation.json and metadata canary | Read permitted; write/delete/ACL replacement denied | Read/write/delete/ACL replacement denied | UNMEASURED |
| Installed src/bin/tools/autoloader/vendor | Read/execute; no write/append/delete/ACL replacement | No write/append/delete/ACL replacement | UNMEASURED |
| Packaged PHP, extensions, DLLs, ini | Read/execute; immutable | Immutable | UNMEASURED |
| Packaged and system PowerShell | Read/execute; immutable | Immutable | UNMEASURED |
| Target snapshot and object files | Read only | No modification/replacement rights | UNMEASURED |
| Relevant parent directories | No rights to replace protected children or rewrite ACLs/ownership | Same denial | UNMEASURED |
| Protected startup | Checker success, then preenrollment TRUST_ABSENT; enrolled public trust afterward | Checker and CLI refusal | UNMEASURED |
| Forwarded data | Human owner uses fixed request command; shell operation refuses | No Runtime shell, PHP arguments, command selection or credential possession | UNMEASURED |
| Installed file hashes and complete file set | Must match exact reviewed package before/after | Cannot replace reviewed assets | Staging comparison passed; deployment UNMEASURED |

Fixed parent is C:/ProgramData/Imperium. Code, PHP, shell, state, target and exchange
paths and the complete command sequence are in local-isolation-owner-runbook.md.
SIDs are deliberately not invented; the owner records them after privately creating
the proposed standard accounts. Deployment administrator and independent signing
Operator are separate roles, not substitute Runtime/caller tokens.

Same-user disposable native canary denial, positive access and absence classification
passed. They validate the probe implementation only. Installer application, actual
tokens, environment, ancestor access and human forwarding arrangements remain owner
measurements. Any unexpected access success stops the real route as ISOLATION_FAILED.
