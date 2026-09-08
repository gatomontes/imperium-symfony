"""Verify supplied public synthetic frames/signatures; never open installation state."""
import base64
import hashlib
import json
import sys
from pathlib import Path
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PublicKey

def native(value):
    if isinstance(value, dict):
        return {k: native(v) for k, v in value.items()} if value else []
    if isinstance(value, list):
        return [native(v) for v in value]
    return value

def canonical(value):
    return json.dumps(native(value), sort_keys=True, ensure_ascii=False, separators=(',', ':')).replace('\u2028', '\\u2028').replace('\u2029', '\\u2029').encode()

def digest(value):
    return hashlib.sha256(canonical(value)).hexdigest()

def intact(value):
    assert value['record_digest'] == digest({k: v for k, v in value.items() if k != 'record_digest'})

proof = json.loads(Path(sys.argv[1]).read_text(encoding='utf-8'))
assert proof['label'] == 'SYNTHETIC_NATIVE_PROTOCOL_ONLY_NOT_INSTALLATION_EVIDENCE'
assert all(proof[k] is False for k in ('real_keys_used', 'real_installation_read', 'live_ready', 'activation', 'execution_authority'))
assert all(proof[k] is True for k in ('private_bytes_unchanged', 'original_occupancy_bytes_unchanged', 'completed_recovery_without_new_frame'))
assert 'SYNTHETIC_PRIVATE_SENTINEL_NEVER_PUBLIC_721940' not in json.dumps(proof)
prior = None
for index, frame in enumerate(proof['public_frames'], 1):
    intact(frame)
    assert frame['generation'] == index and frame['previous_digest'] == prior
    assert frame['schema'] == 'imperium.native-authority-frame/v1'
    prior = frame['record_digest']
state = proof['public_frames'][-1]['state']
trust = state['trust']
key = base64.b64decode(trust['public_key'], validate=True)
assert hashlib.sha256(key).hexdigest() == trust['fingerprint']
assert trust['domain'] == 'IMPERIUM_NATIVE_INSTITUTIONAL_V1'
public = Ed25519PublicKey.from_public_bytes(key)
acts = sorted(state['acts'].values(), key=lambda a: a['result']['registry_revision'])
head = None
for revision, act in enumerate(acts, 1):
    p = act['decision']['payload']
    public.verify(base64.b64decode(act['decision']['signature'], validate=True), canonical(p))
    assert p['object_digest'] == digest(act['object']) and p['trust_fingerprint'] == trust['fingerprint']
    assert p['domain'] == trust['domain'] and p['instance_id'] == trust['instance_id']
    assert p['issuer_role'] == trust['issuer_role'] and p['effect'] in trust['effects']
    assert trust['not_before'] <= p['issued_at'] <= act['accepted_at'] < p['expires_at'] <= trust['expires_at']
    r = act['result']; intact(r)
    assert r['decision_digest'] == digest(act['decision']) and r['object_digest'] == digest(act['object'])
    assert r['decision_nonce'] == p['nonce'] and r['effect'] == p['effect']
    assert r['registry_revision'] == revision and r['previous_head'] == head and act['object']['expected_head'] == head
    assert r['authority_consumed'] is True and r['execution_authority'] is False
    head = r['record_digest']
assert head == state['head'] and len(acts) == state['revision']
v = state['garrison_revision']; intact(v)
assert set(v['extension']) == {'persona_admission_disposition_authority', 'custody_registration_authority'}
assert v['occupancy_generation'] == 1
assert len(state['admissions']) == 1
for admission in state['admissions'].values():
    intact(admission['disposition']); intact(admission['custody'])
    assert admission['disposition']['custody_digest'] == admission['custody']['record_digest']
    assert admission['disposition']['constable']['authority_revision_digest'] == v['record_digest']
    assert admission['authority_nonce'] == v['decision_nonce']
    revocation = state['acts'][state['revoked'][v['decision_nonce']]]
    assert revocation['result']['registry_revision'] > admission['observed_registry_revision']
assert proof['admission'] == next(iter(state['admissions'].values()))['disposition']
assert proof['inventory']['inventory_records'] == [next(iter(state['admissions'].values()))['custody']]
result = {'scope': 'SUPPLIED_SYNTHETIC_PUBLIC_BYTES_ONLY', 'frames': len(proof['public_frames']),
          'verified_ed25519_acts': len(acts), 'native_admissions': 1, 'checks': 'passed',
          'installation_or_historical_producer_authenticated': False}
Path(sys.argv[2]).write_text(json.dumps(result, indent=2)+'\n', encoding='utf-8')
print(json.dumps(result))
