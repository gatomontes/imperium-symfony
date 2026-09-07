"""Allowlisted committed-source/public offline proof packet; never overwrite evidence."""
import hashlib
import io
import json
from pathlib import Path
import re
import subprocess
import tarfile
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[1]
PROOF = ROOT / 'var/citadel-readiness-proof'
BASE = '7a7881b91f10f8c2382b28029ba2846d448370a9'


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT)


def sha(data):
    return hashlib.sha256(data).hexdigest()


def main():
    if git('status', '--porcelain').strip():
        raise SystemExit('Clean committed review tree required')
    verification = json.loads((PROOF / 'verification.json').read_text(encoding='utf-8-sig'))
    tested = verification['tested_commit']
    head = git('rev-parse', 'HEAD').decode().strip()
    tree = git('rev-parse', 'HEAD^{tree}').decode().strip()
    if git('rev-parse', tested + '^{tree}').decode().strip() != verification['tested_tree']:
        raise SystemExit('Tested identity mismatch')
    subprocess.check_call(['git', 'merge-base', '--is-ancestor', tested, head], cwd=ROOT)
    late = git('diff', '--name-only', tested, head).decode().splitlines()
    if any(not (p.startswith('docs/') and p.endswith('.md')) for p in late):
        raise SystemExit('Executable change after tested commit')
    for name in ['focused', 'full-suite']:
        xml = ET.parse(PROOF / (name + '.xml'))
        suites = list(xml.getroot().iter('testsuite'))
        if not suites or any(int(s.get('failures', 0)) or int(s.get('errors', 0)) or int(s.get('skipped', 0)) for s in suites):
            raise SystemExit('Passing JUnit with no errors/failures/skips required: ' + name)
    if verification['full_suite']['exit_code'] != 0:
        raise SystemExit('Full suite did not complete successfully')
    for name in ['readiness-demo.json', 'offline-demo.json', 'correction-demo.json']:
        demo = json.loads((PROOF / name).read_text(encoding='utf-8-sig'))
        if demo['boundary']['network_calls'] != 0 or demo['boundary']['mission_execution']:
            raise SystemExit('Offline boundary invalid: ' + name)
    readiness = json.loads((PROOF / 'readiness-demo.json').read_text(encoding='utf-8-sig'))
    if not readiness['synthetic_only'] or readiness['inspection']['live_ready']:
        raise SystemExit('Synthetic proof confused with readiness')
    for row in readiness['preparation_transcript']:
        if not row['journal_unchanged'] or not row['provider_calls_unchanged']:
            raise SystemExit('Preparation effects detected')
    correction = json.loads((PROOF / 'correction-demo.json').read_text(encoding='utf-8-sig'))
    if any(correction[k]['disposition'] != 'CLOSED_LOCAL' for k in ['CF01', 'CF02']):
        raise SystemExit('Correction regression')
    if correction['CF02']['receipt_bytes_sha256_before'] != correction['CF02']['receipt_bytes_sha256_after']:
        raise SystemExit('Changed correction receipt')
    # Explicit source directories/extensions. No .env, vendor, var stores, keys or arbitrary working files.
    prefixes = ('src/', 'tests/', 'config/', 'contracts/', 'bootstrap/', 'offices/', 'runtime/', 'public/')
    fixed = {'composer.json', 'composer.lock', 'symfony.lock', 'imperium-doctrine.md', 'bin/console',
             '.github/workflows/phpunit.yml', 'tools/prove-citadel-formation.php', 'tools/prove-citadel-correction.php',
             'tools/prove-citadel-readiness.php', 'tools/package-citadel-readiness.py', 'tools/verify-citadel-correction-proof.py'}
    blobs = {line.split('\t', 1)[1]: line.split('\t', 1)[0].split()[2]
             for line in git('ls-tree', '-r', head).decode().splitlines()}
    selected = {p for p in blobs if p in fixed or p.startswith(prefixes)
                or (p.startswith('docs/') and p.endswith(('.md', '.json', '.tsv')))}
    entries = {}; identities = {}
    with tarfile.open(fileobj=io.BytesIO(git('archive', '--format=tar', head))) as archive:
        for member in archive.getmembers():
            if member.isfile() and member.name in selected:
                data = archive.extractfile(member).read()
                entries['source/' + member.name] = data
                identities[member.name] = {'git_blob': blobs[member.name], 'sha256': sha(data)}
    proof_files = ['environment.txt', 'entry-identity.json', 'focused.txt', 'focused.xml', 'preparation-final.txt', 'preparation-final.xml', 'container-lint.txt',
                   'transport-di.txt', 'checkout-preflight.json', 'readiness-demo.json', 'offline-demo.json',
                   'correction-demo.json', 'full-suite.txt', 'full-suite.xml', 'full-run.json', 'verification.json',
                   'preservation-check.json', 'generated-reference.diff', 'public-proof-check.json']
    for name in proof_files:
        data = (PROOF / name).read_bytes()
        if name.endswith('.json'): json.loads(data.decode('utf-8-sig'))
        if name.endswith('.xml'): ET.fromstring(data)
        entries['proof/' + name] = data
    entries['changes.patch'] = git('diff', '--binary', BASE, head)
    entries['SOURCE-IDENTITIES.json'] = (json.dumps(identities, indent=2, sort_keys=True) + '\n').encode()
    identity = {'baseline': BASE, 'tested_commit': tested, 'tested_tree': verification['tested_tree'],
                'review_commit': head, 'review_tree': tree, 'documentation_only_after_testing': late,
                'disposition': 'READINESS_PREPARATION_COMPLETE_WITH_EXPLICIT_BLOCKERS'}
    entries['REVIEW-IDENTITY.json'] = (json.dumps(identity, indent=2) + '\n').encode()
    for path, data in entries.items():
        if re.search(rb'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----', data):
            raise SystemExit('Private key marker: ' + path)
    entries['MANIFEST.sha256'] = ''.join(sha(data) + '  ' + p + '\n' for p, data in sorted(entries.items())).encode()
    out = ROOT / 'var/review-packets'; out.mkdir(parents=True, exist_ok=True)
    archive = out / ('citadel-readiness-' + head[:12] + '.zip')
    manifest = archive.with_suffix('.manifest.json')
    if archive.exists() or manifest.exists(): raise SystemExit('Refusing to overwrite evidence')
    with zipfile.ZipFile(archive, 'x', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for path, data in sorted(entries.items()): z.writestr(path, data)
    with zipfile.ZipFile(archive) as z:
        if z.testzip() or len(z.namelist()) != len(entries) or set(z.namelist()) != set(entries):
            raise SystemExit('ZIP integrity failure')
        for path, data in entries.items():
            if sha(z.read(path)) != sha(data): raise SystemExit('ZIP mismatch: ' + path)
    result = {**identity, 'archive': archive.name, 'archive_sha256': sha(archive.read_bytes()),
              'archive_bytes': archive.stat().st_size, 'entries': {p: sha(d) for p, d in sorted(entries.items())}}
    manifest.write_text(json.dumps(result, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({k: v for k, v in result.items() if k != 'entries'}, indent=2))
    print('Verified entries:', len(entries))


if __name__ == '__main__': main()
