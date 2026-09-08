"""Public package preparation only. Never boot, inspect runtime state, or authorize.

Only proposal.json, assessment.json, evidence-index.json and two explicitly
named public evidence files may be read. No recursive collection or subprocess.
Custodian-controlled directories are required; outputs must be fresh/external.
"""
from __future__ import annotations
import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import sys
import time
from citadel_native_lineage import Refused, parse, regular_path, open_public

TARGET = {'commit': 'f35a0b33ddab9e8815470a697d8593984e050023',
          'tree': 'e4140d95cbb83b3dbf9125849953cec0c7eda989'}
EFFECTS = ['ADOPT_ROSTER', 'SUPERSEDE_RECRUITER', 'RETIRE_ROSTER',
           'REVISE_GARRISON', 'REVOKE_DECISION', 'REVOKE_ISSUER']
FLAGS = {k: False for k in ('deployment_approved', 'enrollment_authorized',
                           'live_ready', 'activation', 'execution_authority')}
FILES = {'proposal.json', 'assessment.json', 'evidence-index.json',
         'garrison.json', 'recruiter.json'}
DECISIONS = ['deployment_custodian', 'authority_custodian', 'signer_interface',
             'fingerprint_confirmation', 'storage_protection', 'administrative_access',
             'writer_quiescence', 'backup_restore', 'legacy_fence_acceptance',
             'initial_adoption_attestation']

def need(ok, code):
    if not ok:
        raise Refused(code)

def keys(value, expected):
    need(type(value) is dict and set(value) == set(expected), 'CP_FIELDS_INVALID')

def hexvalue(value, length=64):
    return type(value) is str and re.fullmatch('[a-f0-9]{%d}' % length, value) is not None

def public_read(directory, name):
    need(name in FILES, 'CP_PATH_OUT_OF_SCOPE')  # before any filesystem access
    path = regular_path(directory / name)
    before = path.stat()
    need(before.st_size <= 1048576, 'CP_INPUT_LIMIT')
    with open_public(path) as stream:
        opened = os.fstat(stream.fileno())
        need(stat.S_ISREG(opened.st_mode) and opened.st_nlink == 1 and
             (opened.st_dev, opened.st_ino) == (before.st_dev, before.st_ino), 'CP_CHANGED_DURING_READ')
        raw = stream.read(1048577)
        after = os.fstat(stream.fileno())
    need(len(raw) <= 1048576 and
         (before.st_size, before.st_mtime_ns, before.st_ino) ==
         (after.st_size, after.st_mtime_ns, after.st_ino), 'CP_CHANGED_DURING_READ')
    return raw, parse(raw)

def within(path, root):
    return path == root or root in path.parents

def check(directory: Path, now: int | None = None):
    now = int(time.time()) if now is None else now
    directory = regular_path(directory, directory=True)
    installed = Path(os.path.abspath('E:/htdocs/imperium'))
    need(not within(directory, installed), 'CP_INSTALLATION_INPUT_FORBIDDEN')
    raw, p = public_read(directory, 'proposal.json')
    keys(p, ['schema', 'label', 'target', 'installation', 'policy', 'public_fingerprint',
             'decisions', 'compatibility', 'approved'])
    need(p['schema'] == 'imperium.commissioning-proposal/v1' and
         p['label'] in ('PUBLIC_UNAPPROVED_PROPOSAL', 'SYNTHETIC_ONLY') and
         type(p['approved']) is bool, 'CP_PROPOSAL_INVALID')
    keys(p['target'], TARGET)
    need(p['target'] == TARGET, 'CP_TARGET_MISMATCH')
    keys(p['installation'], ['root', 'commit', 'tree'])
    identity = p['installation']
    need(type(identity['root']) is str and Path(identity['root']).is_absolute() and
         hexvalue(identity['commit'], 40) and hexvalue(identity['tree'], 40), 'CP_SOURCE_IDENTITY_INVALID')
    need(not within(directory, Path(os.path.abspath(identity['root']))), 'CP_INSTALLATION_INPUT_FORBIDDEN')
    _, a = public_read(directory, 'assessment.json')
    keys(a, ['schema', 'installation', 'target', 'observed_at', 'tracked_changes', 'target_verified'])
    need(a['schema'] == 'imperium.commissioning-source-assessment/v1' and
         a['installation'] == identity and a['target'] == TARGET and
         a['target_verified'] is True, 'CP_SOURCE_MISMATCH')
    need(type(a['observed_at']) is int and 0 <= now - a['observed_at'] <= 86400, 'CP_ASSESSMENT_STALE')
    need(type(a['tracked_changes']) is list and len(a['tracked_changes']) <= 4096 and
         all(type(x) is str and len(x) <= 1024 for x in a['tracked_changes']), 'CP_ASSESSMENT_INVALID')
    blockers = ['OWNER_APPROVAL_NOT_ESTABLISHED', 'DEPLOYMENT_NOT_AUTHORIZED',
                'ENROLLMENT_NOT_AUTHORIZED', 'RUNTIME_AND_CUSTODY_UNVERIFIED',
                'FORMATION_AND_B1_UNRESOLVED']
    if p['approved']:
        blockers.append('SELF_DECLARED_APPROVAL_HAS_NO_AUTHORITY')
    if a['tracked_changes']:
        blockers.append('INSTALLED_TRACKED_DRIFT')
    policy = p['policy']
    keys(policy, ['schema', 'domain', 'instance_id', 'public_key', 'issuer_role', 'effects',
                  'not_before', 'expires_at', 'writer_boundary'])
    need(policy['schema'] == 'imperium.native-authority-enrollment/v1' and
         policy['domain'] == 'IMPERIUM_NATIVE_INSTITUTIONAL_V1' and
         policy['issuer_role'] == 'NATIVE_INSTITUTIONAL_CUSTODIAN' and
         policy['effects'] == EFFECTS and
         policy['writer_boundary'] == 'NATIVE_REGISTRY_EXCLUSIVE_GARRISON_AND_RECRUITER_V1', 'CP_POLICY_UNSUPPORTED')
    missing = []
    for k in ('instance_id', 'public_key', 'not_before', 'expires_at'):
        if policy[k] is None:
            missing.append('policy.' + k)
    if policy['instance_id'] is not None:
        need(type(policy['instance_id']) is str and re.fullmatch('[a-z0-9][a-z0-9._:@-]{0,179}', policy['instance_id']), 'CP_INSTANCE_INVALID')
    fp = p['public_fingerprint']
    if policy['public_key'] is not None:
        try:
            key = base64.b64decode(policy['public_key'], validate=True)
        except (ValueError, TypeError):
            raise Refused('CP_PUBLIC_KEY_INVALID') from None
        need(len(key) == 32 and hexvalue(fp) and hashlib.sha256(key).hexdigest() == fp, 'CP_FINGERPRINT_MISMATCH')
    else:
        need(fp is None, 'CP_FINGERPRINT_WITHOUT_KEY')
    for k in ('not_before', 'expires_at'):
        need(policy[k] is None or type(policy[k]) is int and policy[k] > 0, 'CP_VALIDITY_INVALID')
    if policy['not_before'] is not None and policy['expires_at'] is not None:
        need(policy['not_before'] <= now < policy['expires_at'], 'CP_VALIDITY_STALE')
    keys(p['decisions'], DECISIONS)
    for k, value in p['decisions'].items():
        need(value is None or type(value) is str and 1 <= len(value) <= 512, 'CP_DECISION_INVALID')
        if value is None:
            missing.append('decisions.' + k)
    need(p['compatibility'] in ('DEFER_ENROLLMENT', 'BOUNDED_NATIVE_ONLY'), 'CP_COMPATIBILITY_UNSUPPORTED')
    if p['compatibility'] == 'DEFER_ENROLLMENT':
        blockers.append('RECOMMENDATION_DEFER_ENROLLMENT')
    _, evidence = public_read(directory, 'evidence-index.json')
    keys(evidence, ['schema', 'records'])
    need(evidence['schema'] == 'imperium.commissioning-evidence-index/v1', 'CP_EVIDENCE_INVALID')
    keys(evidence['records'], ['garrison', 'recruiter'])
    digests = {}
    for kind, entry in evidence['records'].items():
        if entry is None:
            missing.append('evidence.' + kind)
            continue
        keys(entry, ['file', 'sha256', 'observed_at'])
        need(entry['file'] == kind + '.json', 'CP_PATH_OUT_OF_SCOPE')
        need(hexvalue(entry['sha256']), 'CP_EVIDENCE_INVALID')
        need(type(entry['observed_at']) is int and 0 <= now - entry['observed_at'] <= 86400, 'CP_EVIDENCE_STALE')
        data, record = public_read(directory, entry['file'])
        need(hashlib.sha256(data).hexdigest() == entry['sha256'], 'CP_EVIDENCE_CHANGED')
        expected = {'garrison': 'imperium.garrison-constable-occupancy/v1',
                    'recruiter': 'imperium.recruiter-public-projection/v1'}[kind]
        need(type(record) is dict and record.get('schema') == expected, 'CP_EVIDENCE_SCHEMA')
        # Byte/schema check only. Actual supported PHP inspectors establish their
        # deeper structural contracts during separately authorized rehearsal/use.
        digests[kind] = entry['sha256']
    blockers += ['MISSING_' + x.upper().replace('.', '_') for x in missing]
    if p['label'] == 'SYNTHETIC_ONLY':
        blockers.append('SYNTHETIC_NOT_INSTITUTIONAL_EVIDENCE')
    return {'schema': 'imperium.commissioning-preparation-result/v1',
            'structural_status': 'CONSISTENT_PUBLIC_PACKAGE',
            'completeness': 'MISSING_PUBLIC_INPUTS' if missing else 'COMPLETE_PUBLIC_INPUTS',
            'source_compatibility': 'TRACKED_DRIFT_BLOCKED' if a['tracked_changes'] else 'PINNED_SOURCE_COMPARISON_ONLY',
            'owner_review': 'READY_WITH_EXPLICIT_BLOCKERS', 'operational_authority': 'NOT_ESTABLISHED',
            'label': p['label'], 'checked_at': now, 'blockers': blockers,
            'evidence_byte_hashes': digests, 'evidence_semantics': 'BYTE_AND_SCHEMA_ONLY_NOT_CURRENTNESS',
            'proposal_sha256': hashlib.sha256(raw).hexdigest(), **FLAGS}, identity['root']

def run(directory, output):
    result, root = check(directory)
    output = Path(os.path.abspath(output))
    parent = regular_path(output.parent, directory=True)
    for forbidden in (Path(os.path.abspath(root)), Path(os.path.abspath(directory)),
                      Path(__file__).resolve().parents[1], Path(os.path.abspath('E:/htdocs/imperium'))):
        need(not within(parent, forbidden), 'CP_OUTPUT_NOT_EXTERNAL')
    # Validated custodian-controlled parent; exclusive leaf refuses overwrite,
    # existing symlink/hardlink/reparse leaf. No directory creation in assessed root.
    with output.open('xb') as stream:
        stream.write((json.dumps(result, indent=2) + '\n').encode())
    return result

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--package', type=Path, required=True)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    try:
        result = run(args.package, args.output)
        print(json.dumps(result))
        return 2  # preparation, never authority success
    except (Refused, OSError, ValueError, TypeError, KeyError, RecursionError) as error:
        print(json.dumps({'disposition': 'REFUSED', 'code': str(error) if isinstance(error, Refused) else 'CP_INPUT_OR_OUTPUT_REFUSED', **FLAGS}))
        return 1

if __name__ == '__main__':
    sys.exit(main())
