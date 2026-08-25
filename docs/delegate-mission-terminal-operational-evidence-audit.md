# Delegate Mission terminal operational-evidence audit

## Claim

This read-only audit proves integrity and terminal consistency for one exact fourteen-record operational subchain. It does not prove that every one of the 69 Delegate Mission lifecycle steps occurred correctly.

Canonical command:

```bash
php bin/console imperium:delegate:audit-operational-evidence <terminal-id> [--json]
```

The former `imperium:delegate:audit-terminal` name is a compatibility alias only.

## Included evidence

1. terminal return;
2. return authorization;
3. cognition-result disposition;
4. bounded cognition turn;
5. provider-invocation activation;
6. Imperator resource/invocation decision;
7. model-access attestation;
8. runtime model binding;
9. bounded cognition commission;
10. runtime activation;
11. operational custody transition;
12. deployment authorization;
13. current retired operational binding; and
14. current restored Persona custody.

Every included record must be present, digest-valid, reached through its exact stored reference where applicable, and in the expected operational or terminal state. Runtime binding, terminal binding digest, custody digest, turn exhaustion, and absence of continuing authority are cross-checked.

## Exclusions

The audit makes no completeness claim for pre-deployment governance Steps 1–52, including profession resolution, Persona selection, Profile derivation, Senate examination, Imperator Profile approval, operational qualification, and assembly preparation. Those records may be inspected by later comprehensive lifecycle evidence tooling; their exclusion here must never be interpreted as validation.

## Failure semantics

Missing evidence, digest tampering, reference substitution, stale digests, unexpected state, or inconsistent live binding/custody fails stopped. The audit is read-only and grants no mission, cognition, provider, credential, execution, continuation, redeployment, or reuse authority.
