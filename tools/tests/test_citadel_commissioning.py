import base64
import copy
import hashlib
import json
import os
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import citadel_commissioning as c

class PreparationTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)
        self.install = self.root/'installation'; self.install.mkdir()
        self.package = self.root/'package'; self.package.mkdir()
        self.sentinel = self.install/'private-state.json'
        self.sentinel.write_bytes(b'PRIVATE_CREDENTIAL_SENTINEL_NEVER_READ')
        self.now = 1788905302
        identity = dict(root=str(self.install), commit='a'*40, tree='b'*40)
        self.proposal = dict(schema='imperium.commissioning-proposal/v1', label='SYNTHETIC_ONLY',
            target=c.TARGET, installation=identity, approved=False, compatibility='BOUNDED_NATIVE_ONLY',
            public_fingerprint=hashlib.sha256(bytes(32)).hexdigest(),
            decisions={k:'SYNTHETIC_PROPOSAL_ONLY' for k in c.DECISIONS},
            policy=dict(schema='imperium.native-authority-enrollment/v1', domain='IMPERIUM_NATIVE_INSTITUTIONAL_V1',
                instance_id='synthetic-only', public_key=base64.b64encode(bytes(32)).decode(),
                issuer_role='NATIVE_INSTITUTIONAL_CUSTODIAN', effects=c.EFFECTS,
                not_before=self.now-10, expires_at=self.now+1000,
                writer_boundary='NATIVE_REGISTRY_EXCLUSIVE_GARRISON_AND_RECRUITER_V1'))
        self.assessment=dict(schema='imperium.commissioning-source-assessment/v1', installation=identity,
            target=c.TARGET, observed_at=self.now, tracked_changes=[], target_verified=True)
        self.index=dict(schema='imperium.commissioning-evidence-index/v1',records={})
        for kind, schema in [('garrison','imperium.garrison-constable-occupancy/v1'),('recruiter','imperium.recruiter-public-projection/v1')]:
            # Deliberately minimal: checker promises byte/schema checks, not PHP semantic acceptance.
            raw=json.dumps({'schema':schema}).encode()
            (self.package/(kind+'.json')).write_bytes(raw)
            self.index['records'][kind]=dict(file=kind+'.json',sha256=hashlib.sha256(raw).hexdigest(),observed_at=self.now)
        self.save()
    def tearDown(self): self.temp.cleanup()
    def save(self):
        for name,value in [('proposal',self.proposal),('assessment',self.assessment),('evidence-index',self.index)]:
            (self.package/(name+'.json')).write_text(json.dumps(value),encoding='utf-8')
    def check(self): return c.check(self.package,self.now)[0]
    def refused(self,code):
        self.save()
        with self.assertRaisesRegex(c.Refused,code): self.check()
    def test_complete_synthetic_is_only_public_preparation(self):
        result=self.check()
        self.assertEqual('COMPLETE_PUBLIC_INPUTS',result['completeness'])
        self.assertEqual('BYTE_AND_SCHEMA_ONLY_NOT_CURRENTNESS',result['evidence_semantics'])
        self.assertTrue(all(result[k] is False for k in c.FLAGS))
    def test_missing_real_values_are_named_blockers(self):
        for k in ('instance_id','public_key','not_before','expires_at'): self.proposal['policy'][k]=None
        self.proposal['public_fingerprint']=None
        self.proposal['decisions']={k:None for k in c.DECISIONS}
        self.index['records']={k:None for k in ('garrison','recruiter')}
        self.save(); result=self.check()
        self.assertIn('MISSING_POLICY_PUBLIC_KEY',result['blockers'])
        self.assertEqual('MISSING_PUBLIC_INPUTS',result['completeness'])
    def test_self_declared_approval_never_grants_authority(self):
        self.proposal['approved']=True; self.save()
        result=self.check()
        self.assertIn('SELF_DECLARED_APPROVAL_HAS_NO_AUTHORITY',result['blockers'])
        self.assertTrue(all(result[k] is False for k in c.FLAGS))
    def test_mismatched_commit_and_tree(self):
        for key in ('commit','tree'):
            with self.subTest(key=key):
                old=self.proposal['target']; self.proposal['target']={**c.TARGET,key:'0'*40}
                self.refused('CP_TARGET_MISMATCH'); self.proposal['target']=old
    def test_observed_source_mismatch(self):
        self.assessment['installation']=dict(self.assessment['installation'],commit='0'*40)
        self.refused('CP_SOURCE_MISMATCH')
    def test_fingerprint_mismatch(self):
        self.proposal['public_fingerprint']='0'*64; self.refused('CP_FINGERPRINT_MISMATCH')
    def test_stale_and_future_assessment(self):
        for timestamp in (self.now-86401,self.now+1):
            self.assessment['observed_at']=timestamp; self.refused('CP_ASSESSMENT_STALE')
    def test_stale_evidence(self):
        self.index['records']['garrison']['observed_at']=self.now-86401; self.refused('CP_EVIDENCE_STALE')
    def test_changed_evidence_preserved(self):
        p=self.package/'garrison.json'; p.write_bytes(b'{}')
        self.refused('CP_EVIDENCE_CHANGED'); self.assertEqual(b'{}',p.read_bytes())
    def test_unsupported_compatibility(self):
        self.proposal['compatibility']='TRANSPARENT_LEGACY_MIGRATION'; self.refused('CP_COMPATIBILITY_UNSUPPORTED')
    def test_unknown_policy_field_and_duplicate_effect(self):
        self.proposal['policy']['approved']=True; self.refused('CP_FIELDS_INVALID')
        del self.proposal['policy']['approved']
        self.proposal['policy']['effects']=[*c.EFFECTS,c.EFFECTS[0]]; self.refused('CP_POLICY_UNSUPPORTED')
    def test_duplicate_json_keys(self):
        (self.package/'proposal.json').write_bytes(b'{"approved":false,"approved":true}')
        with self.assertRaisesRegex(c.Refused,'DUPLICATE_JSON_KEY'): self.check()
    def test_malformed_and_oversized_input(self):
        for data in (b'{',b' ' * 1048577):
            (self.package/'proposal.json').write_bytes(data)
            with self.assertRaises(c.Refused): self.check()
    def test_private_path_rejected_before_open(self):
        for path in ('../installation/private-state.json',str(self.sentinel),'\\\\server\\private.json','garrison.json:secret'):
            self.index['records']['garrison']['file']=path; self.save()
            original=c.open_public; reads=[]
            def spy(p): reads.append(p); return original(p)
            with patch.object(c,'open_public',spy):
                with self.assertRaisesRegex(c.Refused,'CP_PATH_OUT_OF_SCOPE'): self.check()
            self.assertNotIn(self.sentinel,reads)
    def test_hardlink_public_leaf_refused(self):
        p=self.package/'garrison.json'; p.unlink(); os.link(self.sentinel,p)
        with self.assertRaisesRegex(c.Refused,'HARDLINK'): self.check()
    def test_root_and_private_sentinels_unchanged_no_reads(self):
        before={str(p.relative_to(self.install)):p.read_bytes() for p in self.install.rglob('*') if p.is_file()}
        original=c.open_public; reads=[]
        def spy(p): reads.append(p); return original(p)
        with patch.object(c,'open_public',spy),patch.object(c.time,'time',return_value=self.now):
            c.run(self.package,self.root/'result.json')
        self.assertTrue(all(self.install not in p.parents for p in reads))
        after={str(p.relative_to(self.install)):p.read_bytes() for p in self.install.rglob('*') if p.is_file()}
        self.assertEqual(before,after)
    def test_output_inside_root_or_package_refuses_without_writes(self):
        with patch.object(c.time,'time',return_value=self.now):
            for directory in (self.install,self.package):
                with self.assertRaisesRegex(c.Refused,'CP_OUTPUT_NOT_EXTERNAL'): c.run(self.package,directory/'result.json')
                self.assertFalse((directory/'result.json').exists())
    def test_output_never_overwrites(self):
        output=self.root/'result.json'; output.write_bytes(b'ORIGINAL')
        with patch.object(c.time,'time',return_value=self.now):
            with self.assertRaises(FileExistsError): c.run(self.package,output)
        self.assertEqual(b'ORIGINAL',output.read_bytes())
    def test_dirty_installation_is_a_separate_blocker(self):
        self.assessment['tracked_changes']=[' M src/changed.php']; self.save()
        self.assertEqual('TRACKED_DRIFT_BLOCKED',self.check()['source_compatibility'])
    def test_expired_policy(self):
        self.proposal['policy']['expires_at']=self.now; self.refused('CP_VALIDITY_STALE')

if __name__ == '__main__': unittest.main()
