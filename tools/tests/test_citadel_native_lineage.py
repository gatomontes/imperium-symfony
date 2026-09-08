"""Synthetic-only adverse proof for the standalone public collector."""
import base64
import copy
import importlib.util
import json
import os
from pathlib import Path
import socket
import subprocess
import tempfile
import unittest
from unittest.mock import patch

SPEC = importlib.util.spec_from_file_location('native', Path(__file__).parents[1] / 'citadel_native_lineage.py')
n = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(n)


def seal(record):
    record['record_digest'] = n.digest(record)
    return record


def fixture():
    records = {}
    instance = 'synthetic-no-installed-authority'
    case_id = 'constable-provisioning-' + 'a' * 20
    case = seal({'schema': 'imperium.garrison-constable-provisioning-case/v1', 'case_id': case_id,
                 'instance_id': instance, 'status': 'CANONICAL_CONSTABLE_READY'})
    records[n.PREFIX + f'mastermason/activation-cases/{case_id}.json'] = case
    summons = seal({'schema': 'imperium.guildhall-summons/v1', 'summons_id': n.SUMMONS,
                    'instance_id': instance, 'mastermason': {'disposition': 'EXACT_SUMMONS_VALIDATED'},
                    'spawning_authority': True, 'execution_authority': False})
    records[n.PREFIX + f'curia/proceedings/synthetic.summons.{n.SUMMONS}.json'] = summons
    bindings = {}
    for index, (delivery_id, (seat, _)) in enumerate(n.DELIVERIES.items()):
        cid = ('constable' if index == 0 else 'guildhall') + '-construction-' + str(index) * 20
        candidate = {'instance_id': instance, 'target_seat': seat, 'manifestation_id': 'synthetic-' + str(index),
                     'target_occupancy_generation': 1, 'status': 'QUALIFIED_UNBOUND',
                     'substrate_instance': {'status': 'PROFILE_INSTALLED'}, 'persona': {'id': 'synthetic'}, 'profile': {'id': 'synthetic'}}
        q = {'disposition': 'QUALIFIED', 'candidate_id': candidate['manifestation_id'], 'qualification_contract': {'test': True}}
        c = {'schema': 'imperium.construction-commission/v1', 'commission_id': cid, 'instance_id': instance,
             'target_seat': seat, 'issuer': 'mastermason', 'status': 'ISSUED_PENDING_CONSCRIPTION',
             'spawning_authority': True, 'execution_authority': False, 'persona': candidate['persona'],
             'profile': candidate['profile'], 'qualification_contract': q['qualification_contract']}
        if index == 0:
            c.update(source_provisioning_case_id=case_id, source_provisioning_case_digest=case['record_digest'])
        else:
            c.update(source_summons_id=n.SUMMONS, source_summons_digest=summons['record_digest'])
        seal(c)
        records[n.PREFIX + f'offices/conscription/inbox/{cid}.json'] = c
        p = {'schema': 'imperium.qualified-manifestation-packet/v1', 'delivery_id': delivery_id,
             'candidate': candidate, 'qualification': q, 'qualification_digest': n.sha(n.canonical(q)),
             'commission': {'id': cid, 'digest': c['record_digest'], 'consumed': True},
             'sealed': True, 'seat_binding_authority': False, 'execution_authority': False}
        if index == 0:
            p['source_provisioning_case_id'] = case_id
        else:
            p.update(source_summons_id=n.SUMMONS, source_summons_digest=summons['record_digest'])
        seal(p)
        records[n.PREFIX + f'mastermason/qualified-manifestations/{delivery_id}.json'] = p
        b = {'seat': seat, 'source_delivery_id': delivery_id, 'source_packet_digest': p['record_digest'],
             'manifestation_id': candidate['manifestation_id'], 'occupancy_generation': 1}
        if index == 0:
            b.update(schema='imperium.garrison-constable-occupancy/v1', instance_id=instance, status='ACTIVE')
            records[n.PREFIX + f'offices/garrison/occupancy/{n.GARRISON}.json'] = seal(b)
        else:
            b['status'] = 'BOUND_PENDING_COMMISSION_ACCEPTANCE'
            bindings[seat] = b
    records[n.PREFIX + f'offices/guildhall/occupancy/{n.COHORT}.json'] = seal({
        'schema': 'imperium.guildhall-seat-binding-cohort/v1', 'instance_id': instance, 'bindings': bindings,
        'source_summons_id': n.SUMMONS, 'source_summons_digest': summons['record_digest'],
        'office_status': 'ACTIVE_AWAITING_COMMISSION_ACCEPTANCE'})
    return records


class CitadelNativeLineageTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / 'input'
        self.root.mkdir()
        self.records = fixture()
        for path, record in self.records.items():
            self.write(path, record)
        seeds = {path: (schema, self.records[path]['record_digest']) for path, (schema, _) in n.SEEDS.items()}
        p = patch.dict(n.SEEDS, seeds)
        p.start()
        self.addCleanup(p.stop)
        p = patch.object(n, 'SUMMONS_DIGEST', next(r['record_digest'] for r in self.records.values() if r.get('summons_id') == n.SUMMONS))
        p.start()
        self.addCleanup(p.stop)

    def write(self, path, record):
        target = self.root / path
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(record), encoding='utf-8')

    def collect(self):
        return n.Collector(self.root).collect()

    def test_intact_structural_chain_stays_refusing_without_io_capabilities(self):
        opened = []
        real = n.open_public
        def reader(path):
            relative = path.relative_to(self.root).as_posix()
            self.assertIn(relative, self.records)
            opened.append(relative)
            return real(path)
        with patch.object(n, 'open_public', side_effect=reader), patch.object(socket, 'socket', side_effect=AssertionError('provider access')) as network, patch.object(subprocess, 'Popen', side_effect=AssertionError('application or credential process')) as process:
            packet = self.collect()
        self.assertEqual(14, len(opened))
        network.assert_not_called()
        process.assert_not_called()
        self.assertTrue(all(not row['issues'] for row in packet['assessment']['structural_links']))
        self.assertEqual(2, len(packet['assessment']['garrison']['missing_powers']))
        self.assertFalse(packet['live_ready'])
        self.assertFalse(n.verify(packet)['custody_verified'])

    def test_private_paths_and_traversal_refuse_before_open(self):
        with patch.object(n, 'open_public', side_effect=AssertionError('must not open')):
            for path in ['var/imperium/bootstrap-state.json', 'var/imperium/citadel/journal.json', '../private.json', 'C:/private.json', n.PREFIX + 'offices/conscription/inbox/../../private.json']:
                with self.subTest(path=path), self.assertRaises(n.Refused):
                    n.Collector(self.root).read(path)

    def test_unknown_schema_is_not_exported(self):
        path = next(iter(n.SEEDS))
        self.write(path, seal({'schema': 'unknown', 'private': 'never export'}))
        row = next(r for r in self.collect()['records'] if r['path'] == path)
        self.assertEqual('UNSUPPORTED_SCHEMA', row['status'])
        self.assertNotIn('original_base64', row)

    def test_corrupt_and_changed_records_are_preserved_without_expansion(self):
        path = n.PREFIX + f'mastermason/qualified-manifestations/{n.DELIVERY}.json'
        for reseal in (False, True):
            with self.subTest(reseal=reseal):
                r = copy.deepcopy(self.records[path])
                r['candidate']['instance_id'] = 'wrong'
                if reseal:
                    seal(r)
                self.write(path, r)
                packet = self.collect()
                row = next(row for row in packet['records'] if row['path'] == path)
                self.assertEqual('UNAVAILABLE_CHANGED_FROM_REVIEWED_RECORD' if reseal else 'CORRUPT_RECORD_DIGEST', row['status'])
                self.assertIn('original_base64', row)
                self.assertNotIn(n.PREFIX + 'offices/conscription/inbox/constable-construction-' + '0' * 20 + '.json', [x['path'] for x in packet['records']])
                n.verify(packet)

    def test_missing_commission_is_distinct_from_corruption(self):
        path = n.PREFIX + 'offices/conscription/inbox/constable-construction-' + '0' * 20 + '.json'
        (self.root / path).unlink()
        packet = self.collect()
        self.assertEqual('UNAVAILABLE', next(r['status'] for r in packet['records'] if r['path'] == path))
        self.assertIn('UNAVAILABLE_COMMISSION', packet['assessment']['structural_links'][0]['issues'])

    def test_wrong_actor_seat_generation_and_instance_fail_structural_binding(self):
        path = n.PREFIX + f'mastermason/qualified-manifestations/{n.DELIVERY}.json'
        for field, value in [('manifestation_id', 'wrong'), ('target_seat', 'senate.lord-speaker'), ('target_occupancy_generation', 2), ('instance_id', 'wrong')]:
            with self.subTest(field=field):
                records = copy.deepcopy(self.records)
                records[path]['candidate'][field] = value
                self.assertTrue(n.assess(records)['structural_links'][0]['issues'])

    def test_extra_occupants_and_new_flags_cannot_establish_currentness_or_powers(self):
        extra = self.root / (n.PREFIX + 'offices/garrison/occupancy/other.json')
        extra.write_text('PRIVATE UNRELATED SENTINEL')
        packet = self.collect()
        self.assertNotIn(str(extra), json.dumps(packet))
        r = copy.deepcopy(self.records)
        path = n.PREFIX + f'offices/garrison/occupancy/{n.GARRISON}.json'
        r[path].update(persona_admission_disposition_authority=True, custody_registration_authority=True, current=True, supersedes='anything')
        result = n.assess(r)
        self.assertEqual('UNSUPPORTED_AUTHORITY', result['garrison']['status'])
        self.assertTrue(result['currentness'].startswith('UNAVAILABLE'))
        r[path]['status'] = 'RETIRED'
        self.assertEqual('INACTIVE', n.assess(r)['garrison']['status'])

    def test_ambiguous_summons_names_do_not_scan_contents(self):
        path = n.PREFIX + f'curia/proceedings/other.summons.{n.SUMMONS}.json'
        (self.root / path).write_text('DO NOT READ')
        packet = self.collect()
        self.assertEqual('AMBIGUOUS', packet['summons_resolution'])
        self.assertFalse(any('.summons.' in r['path'] for r in packet['records']))

    def test_unsupplied_acceptance_directory_is_never_opened(self):
        directory = self.root / (n.PREFIX + 'offices/guildhall/acceptances')
        directory.mkdir(parents=True)
        (directory / 'guildhall-acceptance-aaaaaaaaaaaaaaaaaaaa.json').write_text('PRIVATE UNRELATED SENTINEL')
        packet = self.collect()
        self.assertTrue(packet['assessment']['guildhall']['status'].startswith('UNAVAILABLE'))
        self.assertFalse(any('/acceptances/' in r['path'] for r in packet['records']))

    def test_wrong_and_planning_acceptances_never_supply_formation_competence(self):
        records = copy.deepcopy(self.records)
        cohort = records[n.PREFIX + f'offices/guildhall/occupancy/{n.COHORT}.json']
        actor = cohort['bindings']['guildhall.guildmaster']
        summons = next(r for r in records.values() if r.get('summons_id') == n.SUMMONS)
        cid = 'planning-guildhall-' + 'e' * 20
        commission = seal({'schema': 'imperium.planning-commission/v1', 'phase': 'planning-only'})
        envelope = seal({'schema': 'imperium.office-inbox-envelope/v1', 'commission_id': cid, 'packet': commission})
        records[n.PREFIX + f'offices/guildhall/inbox/{cid}.json'] = envelope
        aid = 'guildhall-acceptance-' + 'd' * 20
        acceptance = seal({'schema': 'imperium.guildhall-commission-acceptance/v1', 'acceptance_id': aid,
                           'binding_id': n.COHORT, 'binding_digest': cohort['record_digest'], 'instance_id': cohort['instance_id'],
                           'actor': actor, 'recipient_acceptance': True, 'disposition': 'ACCEPTED_FOR_INSTITUTIONAL_DELIBERATION',
                           'execution_authority': False, 'summons_id': n.SUMMONS, 'summons_digest': summons['record_digest'],
                           'commission_id': cid, 'commission_digest': commission['record_digest'], 'delivery_digest': envelope['record_digest']})
        records[n.PREFIX + f'offices/guildhall/acceptances/{aid}.json'] = acceptance
        self.assertEqual('OUT_OF_SCOPE_PLANNING_NOT_FORMATION', n.assess(records)['guildhall']['status'])
        acceptance['binding_digest'] = 'f' * 64
        self.assertEqual('CORRUPT_OR_INCOMPLETE_ACCEPTANCE_BINDING', n.assess(records)['guildhall']['status'])

    def test_projection_cannot_claim_original_identity_or_positive_assessment(self):
        packet = self.collect()
        row = next(r for r in packet['records'] if 'original_base64' in r)
        row['original_base64'] = base64.b64encode(b'{"projection":true}').decode()
        with self.assertRaisesRegex(n.Refused, 'ORIGINAL_IDENTITY'):
            n.verify(packet)
        packet = self.collect()
        packet['assessment']['live_ready'] = True
        with self.assertRaisesRegex(n.Refused, 'ASSESSMENT'):
            n.verify(packet)

    def test_verifier_rejects_duplicate_or_unreferenced_records(self):
        packet = self.collect()
        packet['records'].append(copy.deepcopy(packet['records'][0]))
        with self.assertRaisesRegex(n.Refused, 'DUPLICATE_PATH'):
            n.verify(packet)
        packet = self.collect()
        packet['records'].append({'path': n.PREFIX + 'offices/conscription/inbox/constable-construction-' + 'f' * 20 + '.json', 'status': 'UNAVAILABLE'})
        with self.assertRaisesRegex(n.Refused, 'UNREFERENCED'):
            n.verify(packet)

    def test_limits_and_duplicate_json_are_refusing(self):
        with self.assertRaises(n.Refused):
            n.parse(b'{"schema":1,"schema":2}')
        with self.assertRaises(n.Refused):
            n.parse(b'{"number":1.5}')
        with patch.object(n, 'MAX_FILE', 1):
            packet = self.collect()
        self.assertTrue(all(r['status'] == 'OUT_OF_SCOPE_BYTE_LIMIT' for r in packet['records']))
        with patch.object(n, 'MAX_NAMES', 0):
            self.assertEqual('OUT_OF_SCOPE_DIRECTORY_LIMIT', self.collect()['summons_resolution'])

    def test_empty_json_objects_follow_php_associative_decode_without_rewriting_originals(self):
        raw = b'{"empty":{},"nested":[{}],"line":"\\u2028"}'
        self.assertEqual(b'{"empty":[],"line":"\\u2028","nested":[[]]}', n.canonical(n.parse(raw)))
        self.assertEqual(raw, base64.b64decode(base64.b64encode(raw)))

    def test_existing_or_in_tree_output_preserved(self):
        packet = self.collect()
        with self.assertRaises(n.Refused):
            n.write_packet(self.root / 'output', self.root, packet)
        checkout = Path(self.temp.name) / 'checkout'
        checkout.mkdir()
        (checkout / '.git').write_text('test')
        with self.assertRaises(n.Refused):
            n.write_packet(checkout / 'output', self.root, packet)
        output = Path(self.temp.name) / 'output'
        n.write_packet(output, self.root, packet)
        before = (output / 'collection.json').read_bytes()
        with self.assertRaises(FileExistsError):
            n.write_packet(output, self.root, packet)
        self.assertEqual(before, (output / 'collection.json').read_bytes())

    def test_hardlink_and_reparse_evidence_are_not_read(self):
        path = self.root / next(iter(n.SEEDS))
        other = self.root / 'hardlink.json'
        os.link(path, other)
        try:
            with self.assertRaisesRegex(n.Refused, 'HARDLINK'):
                n.regular_path(path)
        finally:
            other.unlink()
        # A recording stat double exercises Windows reparse denial even on hosts
        # without symlink creation privilege; the native safe-handle path is used
        # by every successful Windows collection test above.
        original = Path.lstat
        class Reparse:
            st_mode = 0o100600
            st_file_attributes = 0x400
        def info(p):
            return Reparse() if p == path else original(p)
        with patch.object(Path, 'lstat', info), self.assertRaisesRegex(n.Refused, 'REPARSE'):
            n.regular_path(path)


if __name__ == '__main__':
    unittest.main()
