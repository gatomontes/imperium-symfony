"""Public, offline cross-check independent of the PHP admission implementation."""
import base64
import hashlib
import json
from pathlib import Path
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PublicKey

ROOT = Path(__file__).resolve().parents[1]
proof = json.loads((ROOT / 'var/citadel-correction-proof/correction-demo.json').read_text(encoding='utf-8-sig'))


def canonical(value):
    return json.dumps(value, sort_keys=True, separators=(',', ':'), ensure_ascii=False).encode('utf-8')


def digest(value):
    return hashlib.sha256(canonical(value)).hexdigest()


def intact(record):
    assert record['record_digest'] == digest({k: v for k, v in record.items() if k != 'record_digest'})


def signatures(value, trust, delegations, seen):
    if isinstance(value, dict):
        if set(value) == {'payload', 'signature'}:
            p = value['payload']
            if 'trust_fingerprint' in p:
                public = base64.b64decode(trust['public_key'])
                assert hashlib.sha256(public).hexdigest() == p['trust_fingerprint'] == trust['fingerprint']
            elif 'delegation' in p:
                public = base64.b64decode(delegations[p['delegation']]['terms']['public_key'])
            else:
                raise AssertionError('Unknown signed evidence type')
            Ed25519PublicKey.from_public_bytes(public).verify(base64.b64decode(value['signature']), canonical(p))
            seen.add(digest(value))
        for v in value.values():
            signatures(v, trust, delegations, seen)
    elif isinstance(value, list):
        for v in value:
            signatures(v, trust, delegations, seen)


cf = proof['CF02']
before, after, frame, receipt = [cf[k] for k in ['before_parent_frame', 'after_parent_frame', 'authority_frame', 'receipt']]
for record in [before, after, frame, receipt]:
    intact(record)
assert after['previous_digest'] == before['record_digest']
assert after['generation'] == before['generation'] + 1
pub = receipt['publication']
assert pub['authority_frame_digest'] == frame['record_digest']
assert pub['authority_generation'] == frame['generation']
assert pub['prepared_digest'] == digest({k: v for k, v in receipt.items() if k not in ['publication', 'record_digest']})
id_ = receipt['packet']['intake_id']
assert pub['reservation_digest'] == digest(frame['state']['reservations'][id_])
review = receipt['packet']['review']
assert pub['authorized_at'] < review['terms']['expires_at'] < cf['recovery_at']
assert review['decision']['payload']['expires_at'] < cf['recovery_at']
assert review['decision']['payload']['object_digest'] == digest(receipt['constitution']['signed_review'])
assert before['state']['sessions'] == after['state']['sessions']
assert id_ not in before['state'].get('handoffs', {})
assert len(after['state']['handoffs']) == len(after['state']['child_roots']) == 1
assert len(after['state']['occupied_manifestations']) == len(before['state']['occupied_manifestations']) + 3
assert cf['receipt_bytes_sha256_before'] == cf['receipt_bytes_sha256_after']
assert cf['handoff']['acceptances'] == [] and not cf['handoff']['execution_authority']
for w in pub['institutions'].values():
    intact(w['occupancy']); intact(w['installation'])
seen = set()
signatures(cf, frame['state']['trust'], frame['state']['personnel_delegations'], seen)
cf2_count = len(seen)
seen = set()
cf1 = proof['CF01']
# CF01 request embeds institutional chains; recover their delegation map recursively.
delegations = {}
def collect(value):
    if isinstance(value, dict):
        if set(value) == {'terms', 'decision'} and value['decision'].get('payload', {}).get('effect') == 'DELEGATE_PERSONNEL_EVIDENCE':
            delegations[digest(value['terms'])] = value
        for v in value.values(): collect(v)
    elif isinstance(value, list):
        for v in value: collect(v)
collect(cf1)
# The grant and all control signatures suffice for the refusal boundary; provider
# personnel provenance is independently exported and checked by the full-flow proof.
for value in [cf1['stale_signed_controls'], cf1['session']['decision'], cf1['session']['controls']]:
    signatures(value, cf1['public_trust'], delegations, seen)
assert cf1['session']['status'] == 'REFUSED' and cf1['journal_unchanged_after_refusal']
assert len(cf1['provider_calls']) == 1 and len(cf['provider_calls']) == 2
assert proof['expired_approval_no_child']['children'] == 0
assert proof['boundary']['network_calls'] == 0 and not proof['boundary']['new_authority_from_recovery']
print(json.dumps({'result': 'PASS', 'CF01_unique_owner_signatures': len(seen),
    'CF02_unique_owner_and_institutional_signatures': cf2_count, 'intact_journal_frames': 3,
    'intact_child_receipts': 1, 'native_witnesses': len(pub['institutions']),
    'receipt_bytes_unchanged': True, 'one_parent_transition': True, 'new_authority': False,
    'limitation': 'Verifies public synthetic proof integrity and signatures, not independent historical custody or live readiness.'}, indent=2))
