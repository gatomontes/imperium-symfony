"""Allowlisted local correction packet; never overwrites earlier evidence."""
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
PROOF = ROOT / 'var/citadel-correction-proof'
BASE = 'e0386e75ce7619fbbeaac450af078d2d606df012'


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT)


def sha(data):
    return hashlib.sha256(data).hexdigest()


def sanitized(data):
    text = data.decode('utf-8-sig')
    # Plain replacement is XML-safe as well as JSON-safe; no angle-bracket marker.
    for root in [str(ROOT), ROOT.as_posix(), str(ROOT).replace('\\', '\\\\')]:
        text = text.replace(root, 'candidate-root')
    return text.encode('utf-8')


def main():
    if git('status', '--porcelain').strip():
        raise SystemExit('Clean committed review tree required')
    head = git('rev-parse', 'HEAD').decode().strip()
    tree = git('rev-parse', 'HEAD^{tree}').decode().strip()
    verification = json.loads((PROOF / 'verification.json').read_text(encoding='utf-8'))
    tested = verification['tested_commit']
    if verification['tested_tree'] != git('rev-parse', tested + '^{tree}').decode().strip():
        raise SystemExit('Tested tree mismatch')
    late = git('diff', '--name-only', tested, head).decode().splitlines()
    if any(not (p.startswith('docs/') and p.endswith('.md')) for p in late):
        raise SystemExit('Executable or contract changes after tested commit')
    full = verification['full_suite']
    if full['exit_code'] != 0 or full['failures'] or full['errors']:
        raise SystemExit('Full-suite success required')
    for name in ['offline-demo.json', 'correction-demo.json']:
        demo = json.loads((PROOF / name).read_text(encoding='utf-8-sig'))
        if demo['boundary']['network_calls'] != 0 or demo['boundary']['mission_execution']:
            raise SystemExit('Offline proof boundary invalid')
    correction = json.loads((PROOF / 'correction-demo.json').read_text(encoding='utf-8-sig'))
    if any(correction[k]['disposition'] != 'CLOSED_LOCAL' for k in ['CF01', 'CF02']):
        raise SystemExit('Both corrections must be closed')
    if correction['CF02']['receipt_bytes_sha256_before'] != correction['CF02']['receipt_bytes_sha256_after']:
        raise SystemExit('Recovery changed child receipt bytes')
    entries = {}
    source = {}
    fixed = {'composer.json', 'composer.lock', 'symfony.lock', 'imperium-doctrine.md', '.github/workflows/phpunit.yml',
             'bin/console', 'phpunit.xml', 'phpunit.xml.dist',
             'tools/prove-citadel-formation.php', 'tools/prove-citadel-correction.php',
             'tools/package-citadel-correction.py', 'tools/verify-citadel-correction-proof.py'}
    docs = {'docs/citadel-mission-formation-decisions.md', 'docs/citadel-mission-formation-implementation.md',
            'docs/citadel-formation-correction-report.md', 'docs/citadel-formation-correction-changed-tests.md',
            'docs/next-campaign-citadel-formation-correction.md', 'docs/next-lifecycle-delegate-mission-route.md',
            'docs/delegate-mission-flow.md', 'docs/handoffs/citadel-formation-correction-ready.md',
            'docs/handoffs/citadel-formation-correction-review.md'}
    index = json.loads(git('show', head + ':docs/citadel-batch0/source-identities.json'))
    docs.update(item['path'] for item in index['sources'])
    # Include required sources and frozen inventories. The full suite is reproduced
    # from the exact Git commit, including its other historical documentary fixtures.
    blobs = {line.split('\t', 1)[1]: line.split('\t', 1)[0].split()[2]
             for line in git('ls-tree', '-r', head).decode().splitlines()}
    selected = []
    for p in blobs:
        if p in fixed or p in docs or p.startswith(('src/', 'tests/', 'contracts/', 'config/', 'doctrine/')) \
                or (p.startswith('docs/') and (p.endswith('.tsv') or p.endswith('source-identities.json'))):
            selected.append(p)
    # Avoid Windows command-line length limits: archive broad directories then filter.
    with tarfile.open(fileobj=io.BytesIO(git('archive', '--format=tar', head))) as archive:
        for member in archive.getmembers():
            if member.isfile() and member.name in selected:
                data = archive.extractfile(member).read()
                entries[member.name] = data
                source[member.name] = {'git_blob': blobs[member.name], 'sha256': sha(data)}
    for item in index['historical_evidence']:
        data = git('show', item['revision'] + ':' + item['path'])
        if sha(data) != item['git_bytes_sha256']:
            raise SystemExit('Historical source identity mismatch')
        entries['historical/' + item['revision'][:8] + '/' + item['path']] = data
    # Preserve the original hand-inserted-receipt test as independently inspectable history.
    for p in ['tests/Imperium/Runtime/CitadelMissionFormationTest.php',
              'src/Imperium/Runtime/Citadel/Formation/FormationCognition.php',
              'src/Imperium/Runtime/Citadel/Formation/CuriaFormationService.php',
              'src/Imperium/Runtime/MasterMason/ChildCuriaFormationService.php']:
        entries['historical/' + BASE + '/' + p] = git('show', BASE + ':' + p)
    proof_files = ['start-identity.json', 'c0-original.txt', 'c0-original.xml', 'c0-regression.php', 'c0-interruption.php',
                   'c0-attribution.json', 'c1.txt', 'c1.xml', 'focused.txt', 'focused.xml', 'focused-run.json',
                   'concurrent-stale-snapshot-failure.txt', 'final-focused.txt', 'final-focused.xml', 'final-focused-run.json',
                   'container-lint.txt', 'offline-demo.json', 'correction-demo.json',
                   'full-suite.txt', 'full-suite.xml', 'full-run.json', 'verification.json', 'preservation-check.json',
                   'generated-reference.diff', 'public-proof-check.json']
    for name in proof_files:
        entries['proof/' + name] = sanitized((PROOF / name).read_bytes())
        if name.endswith('.xml'):
            ET.fromstring(entries['proof/' + name])
        if name.endswith('.json'):
            json.loads(entries['proof/' + name])
    entries['changes-from-reviewed-candidate.patch'] = git('diff', '--binary', BASE, head)
    entries['SOURCE-IDENTITIES.json'] = (json.dumps(source, indent=2, sort_keys=True) + '\n').encode()
    entries['REVIEW-IDENTITY.json'] = (json.dumps({'baseline': BASE, 'tested_commit': tested,
        'tested_tree': verification['tested_tree'], 'review_commit': head, 'review_tree': tree,
        'documentation_only_after_testing': late,
        'status': 'LOCAL_CORRECTION_COMPLETE_PENDING_INDEPENDENT_REVIEW'}, indent=2) + '\n').encode()
    for p, data in entries.items():
        if re.search(rb'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----', data):
            raise SystemExit('Private key marker: ' + p)
    manifest = ''.join(sha(data) + '  ' + p + '\n' for p, data in sorted(entries.items())).encode()
    entries['MANIFEST.sha256'] = manifest
    out = ROOT / 'var/review-packets'
    out.mkdir(exist_ok=True, parents=True)
    archive = out / ('citadel-correction-' + head[:12] + '.zip')
    external = archive.with_suffix('.manifest.json')
    if archive.exists() or external.exists():
        raise SystemExit('Refusing to overwrite an existing packet')
    with zipfile.ZipFile(archive, 'x', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for p, data in sorted(entries.items()):
            z.writestr(p, data)
    with zipfile.ZipFile(archive) as z:
        if z.testzip() is not None or set(z.namelist()) != set(entries):
            raise SystemExit('ZIP verification failed')
        for p, data in entries.items():
            if sha(z.read(p)) != sha(data):
                raise SystemExit('ZIP entry mismatch: ' + p)
    result = {'archive': archive.name, 'archive_sha256': sha(archive.read_bytes()), 'archive_bytes': archive.stat().st_size,
              'tested_commit': tested, 'tested_tree': verification['tested_tree'], 'review_commit': head, 'review_tree': tree,
              'entries': {p: sha(data) for p, data in sorted(entries.items())}}
    external.write_text(json.dumps(result, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({k: v for k, v in result.items() if k != 'entries'}, indent=2))
    print('Verified entries:', len(entries))


if __name__ == '__main__':
    main()
