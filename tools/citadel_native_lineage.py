#!/usr/bin/env python3
"""Bounded public evidence, never a runtime authority resolver. Python 3.12 stdlib only.

No application bootstrap, provider, signing, credential or private-state dependency.
Digests establish byte consistency; this tool never establishes current authority.
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
from datetime import datetime, timezone

MAX_FILE = 1024 * 1024
MAX_TOTAL = 8 * MAX_FILE
MAX_RECORDS = 32
MAX_NAMES = 4096
PREFIX = 'var/imperium/'
GARRISON = 'garrison-constable-binding-37b4c4192f137e2601ea'
COHORT = 'guildhall-binding-10626add6873c1abf6d9'
SUMMONS = 'guildhall-summons-552602ca89e9b8a072b3'
DELIVERY = 'qualified-delivery-9ff8f5c10d594146f436'
SEEDS = {
    PREFIX + f'offices/garrison/occupancy/{GARRISON}.json': ('imperium.garrison-constable-occupancy/v1', '40c824cf0537a432c2968cbe38b775c5c4181bb4981b4458977b468f45e55079'),
    PREFIX + f'offices/guildhall/occupancy/{COHORT}.json': ('imperium.guildhall-seat-binding-cohort/v1', '29c99f3b0e2c5c6b0ff75efc7e6456ff9940d17dd05607f47a68e92da260d9b6'),
}
DELIVERIES = {
    DELIVERY: ('garrison.constable', '304a310b24b5ec8d62fe3acffa4561704635472a45328d5a73f3eae6a6507e64'),
    'qualified-delivery-e9cf6b75f377a5d6ac2d': ('guildhall.guildmaster', '23cfae75a61453105ce091eeb78c918aceb4d2584b176054344fbbab71079633'),
    'qualified-delivery-48fa0e6ee99ce58fa05e': ('guildhall.committee.disciplinary-fit', 'bbef5da564c41f308a24587054f0a2dca5d9045ec5d61cecdee629819833bb26'),
    'qualified-delivery-ea075145d740b86e2b1f': ('guildhall.committee.composition', 'ea7c9fd18c13ab493c7114a8abc692600fd5ba237b22520f46f1e86fabb9f221'),
    'qualified-delivery-046a924ffb9fbe0cb886': ('guildhall.committee.boundary-challenge', '351b615753c1597ea67543f0f3801b14f487a1980df8fcc2e70f7c9bf58abc6e'),
}
for _id, (_seat, _digest) in DELIVERIES.items():
    SEEDS[PREFIX + f'mastermason/qualified-manifestations/{_id}.json'] = ('imperium.qualified-manifestation-packet/v1', _digest)
SUMMONS_DIGEST = '6d94c8373febdd31fef69f771c78dbc040c98bd7552c90fbd717ec7e9beb42f9'
SCHEMAS = {
    **{path: spec[0] for path, spec in SEEDS.items()},
}


class Refused(Exception):
    """Only fixed, public error codes are emitted; no arbitrary exception contents."""


def sha(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def pairs(items):
    result = {}
    for key, value in items:
        if key in result:
            raise Refused('CORRUPT_DUPLICATE_JSON_KEY')
        result[key] = value
    return result


def parse(data: bytes):
    try:
        value = json.loads(data.decode('utf-8'), object_pairs_hook=pairs,
                           parse_float=lambda _: (_ for _ in ()).throw(Refused('UNSUPPORTED_FLOAT')),
                           parse_constant=lambda _: (_ for _ in ()).throw(Refused('CORRUPT_NUMBER')))
        # CanonicalJson compatibility is intentionally limited to nonnumeric object
        # keys and integers within PHP's signed 64-bit range. No lossy coercions.
        def check(item, depth=0):
            if depth > 64:
                raise Refused('OUT_OF_SCOPE_DEPTH')
            if type(item) is int and not -(2**63) <= item < 2**63:
                raise Refused('UNSUPPORTED_INTEGER')
            if isinstance(item, dict):
                for key, child in item.items():
                    if re.fullmatch(r'-?\d+', key):
                        raise Refused('UNSUPPORTED_NUMERIC_OBJECT_KEY')
                    check(child, depth + 1)
            elif isinstance(item, list):
                for child in item:
                    check(child, depth + 1)
        check(value)
        return value
    except (UnicodeError, ValueError, RecursionError):
        raise Refused('CORRUPT_JSON') from None


def canonical(value) -> bytes:
    # json_decode(..., true) turns empty JSON objects into empty PHP arrays.
    # Preserve that native digest convention without altering retained raw bytes.
    def native(item):
        if isinstance(item, dict):
            return {key: native(child) for key, child in item.items()} if item else []
        if isinstance(item, list):
            return [native(child) for child in item]
        return item
    # PHP escapes U+2028/U+2029 without JSON_UNESCAPED_LINE_TERMINATORS.
    text = json.dumps(native(value), sort_keys=True, ensure_ascii=False, separators=(',', ':'))
    return text.replace('\u2028', '\\u2028').replace('\u2029', '\\u2029').encode('utf-8')


def digest(record) -> str:
    return sha(canonical({key: value for key, value in record.items() if key != 'record_digest'}))


def regular_path(path: Path, *, directory=False) -> Path:
    absolute = Path(os.path.abspath(path))
    if str(absolute).startswith(('\\\\', '//')):
        raise Refused('OUT_OF_SCOPE_NETWORK_PATH')
    for component in [*reversed(absolute.parents), absolute]:
        try:
            info = component.lstat()
        except FileNotFoundError:
            raise Refused('UNAVAILABLE') from None
        if stat.S_ISLNK(info.st_mode) or getattr(info, 'st_file_attributes', 0) & 0x400:
            raise Refused('OUT_OF_SCOPE_REPARSE')
    info = absolute.stat()
    if not (stat.S_ISDIR(info.st_mode) if directory else stat.S_ISREG(info.st_mode)):
        raise Refused('OUT_OF_SCOPE_NOT_REGULAR')
    # Hard-linked evidence may alias another store. Refuse rather than infer custody.
    if not directory and info.st_nlink != 1:
        raise Refused('OUT_OF_SCOPE_HARDLINK')
    return absolute


def identifier(value, prefix):
    if not isinstance(value, str) or not re.fullmatch(re.escape(prefix) + r'-[a-f0-9]{20}', value):
        raise Refused('OUT_OF_SCOPE_REFERENCE')
    return value


def open_public(path: Path):
    """Validate the opened handle before reading, including Windows junctions.

    Windows denies write/delete sharing for the lifetime of the read. POSIX
    traverses every component using directory handles and O_NOFOLLOW.
    """
    if os.name == 'nt':
        import ctypes
        from ctypes import wintypes
        import msvcrt
        kernel = ctypes.WinDLL('kernel32', use_last_error=True)
        create = kernel.CreateFileW
        create.argtypes = [wintypes.LPCWSTR, wintypes.DWORD, wintypes.DWORD, wintypes.LPVOID, wintypes.DWORD, wintypes.DWORD, wintypes.HANDLE]
        create.restype = wintypes.HANDLE
        handle = create(str(path), 0x80000000, 1, None, 3, 0x00200000, None)
        if handle == ctypes.c_void_p(-1).value:
            raise Refused('UNAVAILABLE_SAFE_OPEN')
        close = kernel.CloseHandle
        close.argtypes = [wintypes.HANDLE]
        final = kernel.GetFinalPathNameByHandleW
        final.argtypes = [wintypes.HANDLE, wintypes.LPWSTR, wintypes.DWORD, wintypes.DWORD]
        final.restype = wintypes.DWORD
        try:
            info = (wintypes.DWORD * 13)()
            information = kernel.GetFileInformationByHandle
            information.argtypes = [wintypes.HANDLE, ctypes.c_void_p]
            if not information(handle, ctypes.byref(info)) or info[0] & (0x400 | 0x10) or info[10] != 1:
                raise Refused('OUT_OF_SCOPE_HANDLE_TYPE')
            buffer = ctypes.create_unicode_buffer(32768)
            length = final(handle, buffer, len(buffer), 0)
            if not 0 < length < len(buffer):
                raise Refused('OUT_OF_SCOPE_HANDLE_PATH')
            resolved = buffer.value
            if resolved.startswith('\\\\?\\'):
                resolved = resolved[4:]
            if os.path.normcase(resolved) != os.path.normcase(str(path)):
                raise Refused('OUT_OF_SCOPE_HANDLE_PATH')
            descriptor = msvcrt.open_osfhandle(handle, os.O_RDONLY | os.O_BINARY)
        except BaseException:
            close(handle)
            raise
        return os.fdopen(descriptor, 'rb')
    flags = os.O_RDONLY | os.O_NOFOLLOW
    descriptor = os.open(path.anchor, flags | os.O_DIRECTORY)
    try:
        for component in path.parts[1:-1]:
            child = os.open(component, flags | os.O_DIRECTORY, dir_fd=descriptor)
            os.close(descriptor)
            descriptor = child
        leaf = os.open(path.name, flags, dir_fd=descriptor)
        return os.fdopen(leaf, 'rb')
    finally:
        os.close(descriptor)


def schema_for(path):
    if path in SCHEMAS:
        return SCHEMAS[path]
    patterns = {
        r'curia/proceedings/[A-Za-z0-9_-]+\.summons\.' + SUMMONS + r'\.json': 'imperium.guildhall-summons/v1',
        r'offices/conscription/inbox/(?:constable|guildhall)-construction-[a-f0-9]{20}\.json': 'imperium.construction-commission/v1',
        r'mastermason/activation-cases/constable-provisioning-[a-f0-9]{20}\.json': 'imperium.garrison-constable-provisioning-case/v1',
        r'offices/guildhall/acceptances/guildhall-acceptance-[a-f0-9]{20}\.json': 'imperium.guildhall-commission-acceptance/v1',
        r'offices/guildhall/inbox/planning-guildhall-[a-f0-9]{20}\.json': 'imperium.office-inbox-envelope/v1',
    }
    for pattern, schema in patterns.items():
        if re.fullmatch(re.escape(PREFIX) + pattern, path):
            return schema
    raise Refused('OUT_OF_SCOPE_PATH')


class Collector:
    def __init__(self, root: Path):
        self.root = regular_path(root, directory=True)
        self.rows = {}
        self.data = {}
        self.total = 0

    def read(self, relative, expected=None):
        schema = schema_for(relative)  # Before touching any path.
        if relative in self.rows:
            return self.data.get(relative)
        if len(self.rows) >= MAX_RECORDS:
            raise Refused('OUT_OF_SCOPE_RECORD_COUNT')
        row = {'path': relative, 'expected_record_digest': expected}
        self.rows[relative] = row
        try:
            path = regular_path(self.root / relative)
            before = path.stat()
            if before.st_size > MAX_FILE or self.total + before.st_size > MAX_TOTAL:
                raise Refused('OUT_OF_SCOPE_BYTE_LIMIT')
            with open_public(path) as handle:
                opened = os.fstat(handle.fileno())
                if not stat.S_ISREG(opened.st_mode) or opened.st_nlink != 1 or (opened.st_dev, opened.st_ino) != (before.st_dev, before.st_ino):
                    raise Refused('UNAVAILABLE_CHANGED_DURING_READ')
                raw = handle.read(MAX_FILE + 1)
                after = os.fstat(handle.fileno())
            regular_path(path)
            if len(raw) > MAX_FILE or (before.st_size, before.st_mtime_ns, before.st_ino) != (after.st_size, after.st_mtime_ns, after.st_ino) or path.stat().st_ino != before.st_ino:
                raise Refused('UNAVAILABLE_CHANGED_DURING_READ')
            self.total += len(raw)
            row.update(bytes=len(raw), original_sha256=sha(raw))
            record = parse(raw)
            if not isinstance(record, dict) or record.get('schema') != schema:
                raise Refused('UNSUPPORTED_SCHEMA')
            # Only original, known public-schema bytes are retained, never projections.
            row['original_base64'] = base64.b64encode(raw).decode('ascii')
            row['record_digest'] = record.get('record_digest')
            if not isinstance(record.get('record_digest'), str) or digest(record) != record['record_digest']:
                raise Refused('CORRUPT_RECORD_DIGEST')
            row['status'] = 'CONTENT_INTACT'
            row['historical_match'] = None if expected is None else record['record_digest'] == expected
            if expected is not None and not row['historical_match']:
                row['status'] = 'UNAVAILABLE_CHANGED_FROM_REVIEWED_RECORD'
                return None  # Preserve changed bytes, never expand stale references.
            self.data[relative] = record
            return record
        except Refused as error:
            row['status'] = str(error)
        except OSError:
            row['status'] = 'UNAVAILABLE_IO'
        return None

    def collect(self, acceptance_id=None):
        if acceptance_id is not None:
            identifier(acceptance_id, 'guildhall-acceptance')
        for path, (_, expected) in SEEDS.items():
            self.read(path, expected)
        directory = PREFIX + 'curia/proceedings'
        try:
            parent = regular_path(self.root / directory, directory=True)
            matches = []
            with os.scandir(parent) as entries:
                for count, entry in enumerate(entries, 1):
                    if count > MAX_NAMES:
                        raise Refused('OUT_OF_SCOPE_DIRECTORY_LIMIT')
                    if entry.name.endswith(f'.summons.{SUMMONS}.json'):
                        matches.append(directory + '/' + entry.name)
            if len(matches) != 1:
                raise Refused('AMBIGUOUS' if matches else 'UNAVAILABLE')
            self.read(matches[0], SUMMONS_DIGEST)
            summons_status = 'EXACT_NAME_RESOLVED'
        except (Refused, OSError) as error:
            summons_status = str(error) if isinstance(error, Refused) else 'UNAVAILABLE_IO'
        # Only intact historically matching fixed deliveries may expand to their
        # actual commissioning IDs. No generic recursive traversal is provided.
        for path in list(SEEDS):
            packet = self.data.get(path)
            if not packet or packet.get('schema') != 'imperium.qualified-manifestation-packet/v1':
                continue
            delivery_id = path.rsplit('/', 1)[1][:-5]
            seat = DELIVERIES[delivery_id][0]
            if delivery_issues(packet, delivery_id, seat):
                continue
            try:
                office = seat.split('.')[0]
                commission_ref = packet.get('commission', {})
                commission_id = identifier(commission_ref.get('id'), 'constable-construction' if office == 'garrison' else 'guildhall-construction')
                expected = commission_ref.get('digest')
                if not isinstance(expected, str) or not re.fullmatch('[a-f0-9]{64}', expected):
                    raise Refused('OUT_OF_SCOPE_REFERENCE')
                commission = self.read(PREFIX + f'offices/conscription/inbox/{commission_id}.json', expected)
                if office == 'garrison' and commission and commission.get('commission_id') == commission_id and commission.get('instance_id') == packet.get('candidate', {}).get('instance_id'):
                    case_id = identifier(commission.get('source_provisioning_case_id'), 'constable-provisioning')
                    if case_id != packet.get('source_provisioning_case_id'):
                        raise Refused('CORRUPT_CASE_REFERENCE')
                    expected_case = commission.get('source_provisioning_case_digest')
                    if not isinstance(expected_case, str) or not re.fullmatch('[a-f0-9]{64}', expected_case):
                        raise Refused('OUT_OF_SCOPE_REFERENCE')
                    self.read(PREFIX + f'mastermason/activation-cases/{case_id}.json', expected_case)
            except Refused as error:
                self.rows[path]['expansion_status'] = str(error)
        if acceptance_id is not None:
            identifier(acceptance_id, 'guildhall-acceptance')
            acceptance = self.read(PREFIX + f'offices/guildhall/acceptances/{acceptance_id}.json')
            cohort = self.data.get(PREFIX + f'offices/guildhall/occupancy/{COHORT}.json', {})
            if acceptance and acceptance.get('acceptance_id') == acceptance_id and acceptance.get('binding_id') == COHORT and acceptance.get('binding_digest') == cohort.get('record_digest'):
                try:
                    commission_id = identifier(acceptance.get('commission_id'), 'planning-guildhall')
                    self.read(PREFIX + f'offices/guildhall/inbox/{commission_id}.json', acceptance.get('delivery_digest'))
                except Refused:
                    pass  # Assessment records the missing/invalid bound envelope.
        return {'schema': 'imperium.citadel-native-public-collection/v1',
                'collected_at': datetime.now(timezone.utc).isoformat(), 'installation_root': str(self.root),
                'supplied_acceptance_id': acceptance_id, 'summons_resolution': summons_status,
                'records': list(self.rows.values()), 'assessment': assess(self.data),
                'limits': {'file_bytes': MAX_FILE, 'total_bytes': MAX_TOTAL, 'records': MAX_RECORDS, 'directory_names': MAX_NAMES},
                'provenance': 'Original public bytes only; collection identity is a supplied observation, not independent custody.',
                'live_ready': False, 'activation': False, 'execution_authority': False}


def delivery_issues(record, delivery_id, seat):
    candidate = record.get('candidate', {})
    qualification = record.get('qualification', {})
    issues = []
    checks = {
        'DELIVERY_ID_OR_SEAT': record.get('delivery_id') == delivery_id and candidate.get('target_seat') == seat,
        'GENERATION_OR_STATUS': type(candidate.get('target_occupancy_generation')) is int and candidate.get('target_occupancy_generation') == 1 and candidate.get('status') == 'QUALIFIED_UNBOUND',
        'QUALIFICATION': qualification.get('disposition') == 'QUALIFIED' and qualification.get('candidate_id') == candidate.get('manifestation_id') and sha(canonical(qualification)) == record.get('qualification_digest'),
        'SEALED_COMMISSION': record.get('sealed') is True and record.get('commission', {}).get('consumed') is True,
        'SUBSTRATE': candidate.get('substrate_instance', {}).get('status') == 'PROFILE_INSTALLED',
        'UNEXPECTED_POWER': all(record.get(key) is False for key in ('seat_binding_authority', 'execution_authority')),
    }
    return [key for key, ok in checks.items() if not ok]


def assess(records):
    """Finite, structural checks only. No positive institutional-authority branch."""
    by_id = {path.rsplit('/', 1)[1][:-5]: record for path, record in records.items()}
    garrison = by_id.get(GARRISON, {})
    cohort = by_id.get(COHORT, {})
    summons = by_id.get(SUMMONS, {})
    # Summons filename contains a proceeding prefix.
    summons = next((r for r in records.values() if r.get('summons_id') == SUMMONS), summons)
    links = []
    for delivery_id, (seat, _) in DELIVERIES.items():
        packet = by_id.get(delivery_id)
        issues = [] if packet else ['UNAVAILABLE_DELIVERY']
        if packet:
            issues += delivery_issues(packet, delivery_id, seat)
            candidate = packet.get('candidate', {})
            binding = garrison if seat == 'garrison.constable' else cohort.get('bindings', {}).get(seat, {})
            instance = garrison.get('instance_id') if seat == 'garrison.constable' else cohort.get('instance_id')
            if not binding or binding.get('source_delivery_id') != delivery_id or binding.get('source_packet_digest') != packet.get('record_digest') or binding.get('manifestation_id') != candidate.get('manifestation_id') or instance != candidate.get('instance_id') or binding.get('occupancy_generation') != candidate.get('target_occupancy_generation') or binding.get('seat') != seat:
                issues.append('CORRUPT_OCCUPANCY_DELIVERY_BINDING')
            commission = by_id.get(packet.get('commission', {}).get('id'), {})
            if not commission:
                issues.append('UNAVAILABLE_COMMISSION')
            elif commission.get('record_digest') != packet.get('commission', {}).get('digest') or commission.get('target_seat') != seat or commission.get('instance_id') != instance or commission.get('commission_id') != packet['commission']['id'] or commission.get('issuer') != 'mastermason' or commission.get('status') != 'ISSUED_PENDING_CONSCRIPTION' or commission.get('spawning_authority') is not True or commission.get('execution_authority') is not False:
                issues.append('CORRUPT_COMMISSION_BINDING')
            if commission and any(commission.get(key) != candidate.get(key) for key in ('persona', 'profile')):
                issues.append('CORRUPT_CANDIDATE_SOURCE')
            if commission and commission.get('qualification_contract') != packet.get('qualification', {}).get('qualification_contract'):
                issues.append('CORRUPT_QUALIFICATION_CONTRACT')
            if seat == 'garrison.constable':
                case = by_id.get(packet.get('source_provisioning_case_id'), {})
                if not case:
                    issues.append('UNAVAILABLE_PROVISIONING_CASE')
                elif case.get('case_id') != packet.get('source_provisioning_case_id') or case.get('record_digest') != commission.get('source_provisioning_case_digest') or case.get('instance_id') != instance or case.get('status') != 'CANONICAL_CONSTABLE_READY':
                    issues.append('CORRUPT_PROVISIONING_BINDING')
            elif not summons:
                issues.append('UNAVAILABLE_SUMMONS')
            elif any(r.get('source_summons_id') != SUMMONS or r.get('source_summons_digest') != summons.get('record_digest') for r in (packet, commission, cohort)) or summons.get('instance_id') != instance or summons.get('mastermason', {}).get('disposition') != 'EXACT_SUMMONS_VALIDATED' or summons.get('spawning_authority') is not True or summons.get('execution_authority') is not False:
                issues.append('CORRUPT_SUMMONS_BINDING')
        links.append({'seat': seat, 'status': 'STRUCTURAL_CHECKS_PASSED_NOT_AUTHORITY' if not issues else 'UNVERIFIED', 'issues': issues})
    missing_powers = [key for key in ('persona_admission_disposition_authority', 'custody_registration_authority') if garrison.get(key) is not True]
    acceptance = [r for r in records.values() if r.get('schema') == 'imperium.guildhall-commission-acceptance/v1']
    acceptance_status = 'UNAVAILABLE_EXACT_PUBLIC_ACCEPTANCE_ID_OR_RECORD'
    if len(acceptance) > 1:
        acceptance_status = 'AMBIGUOUS'
    elif acceptance:
        a = acceptance[0]
        actor = cohort.get('bindings', {}).get('guildhall.guildmaster', {})
        envelope = by_id.get(a.get('commission_id'), {})
        commission = envelope.get('packet', {})
        valid = (a.get('binding_id') == COHORT and a.get('binding_digest') == cohort.get('record_digest') and a.get('instance_id') == cohort.get('instance_id')
                 and a.get('actor', {}).get('manifestation_id') == actor.get('manifestation_id') and a.get('actor', {}).get('seat') == 'guildhall.guildmaster'
                 and a.get('actor', {}).get('occupancy_generation') == actor.get('occupancy_generation') and a.get('recipient_acceptance') is True
                 and a.get('disposition') == 'ACCEPTED_FOR_INSTITUTIONAL_DELIBERATION' and a.get('execution_authority') is False
                 and a.get('summons_digest') == summons.get('record_digest') and a.get('summons_id') == SUMMONS
                 and envelope.get('record_digest') == a.get('delivery_digest') and envelope.get('commission_id') == a.get('commission_id')
                 and commission.get('schema') == 'imperium.planning-commission/v1' and digest(commission) == commission.get('record_digest')
                 and commission.get('record_digest') == a.get('commission_digest') and commission.get('phase') == 'planning-only')
        acceptance_status = 'OUT_OF_SCOPE_PLANNING_NOT_FORMATION' if valid else 'CORRUPT_OR_INCOMPLETE_ACCEPTANCE_BINDING'
    return {'structural_links': links,
            'garrison': {'action': 'ADMITTED_PERSONA', 'record_status': garrison.get('status'), 'status': 'UNAVAILABLE' if not garrison else ('INACTIVE' if garrison.get('status') != 'ACTIVE' else 'UNSUPPORTED_AUTHORITY'), 'missing_powers': missing_powers},
            'guildhall': {'action': 'SUITABLE_CANDIDATE', 'status': acceptance_status, 'cohort_status': cohort.get('office_status')},
            'currentness': 'UNAVAILABLE_PUBLIC_CURRENTNESS_AND_SUPERSESSION_CLOSURE',
            'producer_provenance': 'UNVERIFIED_HISTORICAL_PRODUCER_EXECUTION_AND_RECRUITER_RECEIPT',
            'custody': 'UNVERIFIED', 'recruiter': 'UNAVAILABLE_PUBLIC_T04_SUCCESSOR_EXPORT_PRIVATE_STATE_NOT_READ',
            'other_seats': 'UNAVAILABLE_EXACT_PUBLIC_IDS_NOT_SUPPLIED',
            'formation_delegation': 'UNAVAILABLE_AUTHENTIC_ROLE_LIMITED_OWNER_DECISION',
            'native_positive_adapter': 'NOT_IMPLEMENTED_NO_COMPLETE_AUTHORIZED_LINEAGE',
            'live_ready': False, 'activation': False, 'execution_authority': False}


def verify(packet):
    if not isinstance(packet, dict) or packet.get('schema') != 'imperium.citadel-native-public-collection/v1' or not isinstance(packet.get('records'), list) or len(packet['records']) > MAX_RECORDS:
        raise Refused('CORRUPT_PACKET_SCHEMA')
    records = {}
    seen = set()
    total = 0
    for row in packet['records']:
        path = row.get('path')
        if not isinstance(path, str) or path in seen:
            raise Refused('CORRUPT_DUPLICATE_PATH')
        seen.add(path)
        schema = schema_for(path)
        if 'original_base64' not in row:
            if row.get('status') == 'CONTENT_INTACT':
                raise Refused('CORRUPT_MISSING_ORIGINAL')
            continue  # Unavailable observations cannot be independently certified.
        try:
            raw = base64.b64decode(row['original_base64'], validate=True)
        except (ValueError, TypeError):
            raise Refused('CORRUPT_ORIGINAL_ENCODING') from None
        total += len(raw)
        if len(raw) > MAX_FILE or total > MAX_TOTAL or sha(raw) != row.get('original_sha256') or len(raw) != row.get('bytes'):
            raise Refused('CORRUPT_ORIGINAL_IDENTITY')
        record = parse(raw)
        if not isinstance(record, dict) or record.get('schema') != schema:
            raise Refused('UNSUPPORTED_SCHEMA')
        if row.get('status') == 'CONTENT_INTACT':
            if digest(record) != record.get('record_digest') or row.get('record_digest') != record.get('record_digest'):
                raise Refused('CORRUPT_RECORD_DIGEST')
            expected = SEEDS[path][1] if path in SEEDS else (SUMMONS_DIGEST if '.summons.' in path else row.get('expected_record_digest'))
            if expected is not None and expected != record.get('record_digest'):
                raise Refused('CORRUPT_EXPECTED_REFERENCE')
            records[path] = record
    allowed = set(SEEDS)
    summons_paths = [path for path in seen if '.summons.' in path]
    if len(summons_paths) > 1:
        raise Refused('AMBIGUOUS_SUMMONS')
    allowed.update(summons_paths)
    for path in SEEDS:
        r = records.get(path, {})
        delivery_id = path.rsplit('/', 1)[1][:-5]
        if delivery_id not in DELIVERIES or not r or delivery_issues(r, delivery_id, DELIVERIES[delivery_id][0]):
            continue
        try:
            prefix = 'constable-construction' if delivery_id == DELIVERY else 'guildhall-construction'
            cid = identifier(r.get('commission', {}).get('id'), prefix)
            cpath = PREFIX + f'offices/conscription/inbox/{cid}.json'
            allowed.add(cpath)
            c = records.get(cpath)
            if c and c.get('record_digest') != r.get('commission', {}).get('digest'):
                raise Refused('CORRUPT_EXPECTED_REFERENCE')
            if delivery_id == DELIVERY and c and c.get('commission_id') == cid and c.get('instance_id') == r.get('candidate', {}).get('instance_id'):
                case_id = identifier(c.get('source_provisioning_case_id'), 'constable-provisioning')
                if case_id == r.get('source_provisioning_case_id'):
                    case_path = PREFIX + f'mastermason/activation-cases/{case_id}.json'
                    allowed.add(case_path)
                    if case_path in records and records[case_path].get('record_digest') != c.get('source_provisioning_case_digest'):
                        raise Refused('CORRUPT_EXPECTED_REFERENCE')
        except Refused as error:
            if str(error) != 'OUT_OF_SCOPE_REFERENCE':
                raise
    aid = packet.get('supplied_acceptance_id')
    if aid is not None:
        identifier(aid, 'guildhall-acceptance')
        apath = PREFIX + f'offices/guildhall/acceptances/{aid}.json'
        allowed.add(apath)
        a = records.get(apath)
        cohort = records.get(PREFIX + f'offices/guildhall/occupancy/{COHORT}.json', {})
        if a and a.get('acceptance_id') == aid and a.get('binding_id') == COHORT and a.get('binding_digest') == cohort.get('record_digest'):
            cid = identifier(a.get('commission_id'), 'planning-guildhall')
            allowed.add(PREFIX + f'offices/guildhall/inbox/{cid}.json')
    if not seen <= allowed:
        raise Refused('OUT_OF_SCOPE_UNREFERENCED_RECORD')
    if assess(records) != packet.get('assessment') or any(packet.get(key) is not False for key in ('live_ready', 'activation', 'execution_authority')):
        raise Refused('CORRUPT_ASSESSMENT_OR_AUTHORITY_FLAGS')
    return {'status': 'SUPPLIED_BYTES_AND_REFUSAL_ASSESSMENT_VERIFIED', 'originals': len(records), 'custody_verified': False, 'currentness_verified': False, 'live_ready': False}


def write_packet(output: Path, root: Path, packet):
    output = Path(os.path.abspath(output))
    parent = regular_path(output.parent, directory=True)
    # No output within ANY checkout, nor the collection root. Walking parents
    # only tests .git presence; it never opens Git config or runtime stores.
    if output == root or root in output.parents or any((p / '.git').exists() for p in [parent, *parent.parents]):
        raise Refused('OUT_OF_SCOPE_OUTPUT_IN_SOURCE_TREE')
    output.mkdir()  # Fresh only: preserve prior evidence.
    (output / 'collection.json').write_bytes(json.dumps(packet, indent=2, ensure_ascii=False).encode('utf-8') + b'\n')
    (output / 'verification.json').write_bytes(json.dumps(verify(packet), indent=2).encode() + b'\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='mode', required=True)
    collect = sub.add_parser('collect')
    collect.add_argument('--root', required=True, type=Path)
    collect.add_argument('--output', required=True, type=Path)
    collect.add_argument('--guildhall-acceptance-id')
    check = sub.add_parser('verify')
    check.add_argument('packet', type=Path)
    args = parser.parse_args()
    try:
        if args.mode == 'collect':
            collector = Collector(args.root)
            packet = collector.collect(args.guildhall_acceptance_id)
            write_packet(args.output, collector.root, packet)
            print(json.dumps({'records': len(packet['records']), 'assessment': packet['assessment']}, indent=2))
            return 2  # Explicit preparation blockers, never readiness approval.
        path = regular_path(args.packet)
        if path.stat().st_size > 2 * MAX_TOTAL:
            raise Refused('OUT_OF_SCOPE_PACKET_LIMIT')
        print(json.dumps(verify(parse(path.read_bytes())), indent=2))
        return 0
    except (Refused, OSError, ValueError, TypeError, KeyError, AttributeError) as error:
        print('REFUSED ' + (str(error) if isinstance(error, Refused) else 'PUBLIC_INPUT_OR_IO_INVALID'), file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
