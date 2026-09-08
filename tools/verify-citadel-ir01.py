"""Run committed offline validation into a fresh, never overwritten proof directory."""
import datetime
import hashlib
import json
from pathlib import Path
import subprocess
import time
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
PROOF = ROOT / 'var/citadel-ir01-proof'


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT).decode().strip()


def main():
    if git('diff', 'HEAD', '--name-only'):
        raise SystemExit('Committed executable tree required')
    PROOF.mkdir(exist_ok=False)
    identity = {'tested_commit': git('rev-parse', 'HEAD'), 'tested_tree': git('rev-parse', 'HEAD^{tree}'), 'runs': {}}
    (PROOF / 'entry-identity.json').write_text(json.dumps(identity, indent=2) + '\n', encoding='utf-8')
    historical = [ROOT / 'var/citadel-readiness-ir01-diagnostic.json', ROOT / 'var/citadel-readiness-review-history.bundle']
    historical += sorted((ROOT / 'var/review-packets').glob('*'))
    preserved = {str(p.relative_to(ROOT)): hashlib.sha256(p.read_bytes()).hexdigest() for p in historical if p.is_file()}
    commands = {
        'focused': ['php', 'vendor/bin/phpunit', 'tests/Imperium/Runtime/CitadelInterviewCompletionTest.php', 'tests/Imperium/Runtime/CitadelFormationCorrectionTest.php', 'tests/Imperium/Runtime/CitadelMissionFormationTest.php', 'tests/Imperium/Runtime/CitadelReadinessPreparationTest.php', '--log-junit', str(PROOF / 'focused.xml')],
        'container-lint': ['php', 'bin/console', 'lint:container'],
        'ir01-post-correction': ['php', 'tools/prove-citadel-ir01.php'],
        'correction-demo': ['php', 'tools/prove-citadel-correction.php'],
        'offline-demo': ['php', 'tools/prove-citadel-formation.php'],
        'readiness-demo': ['php', 'tools/prove-citadel-readiness.php'],
        'full-suite': ['php', 'vendor/bin/phpunit', 'tests', '--display-warnings', '--log-junit', str(PROOF / 'full-suite.xml')],
    }
    for name, command in commands.items():
        print('Starting ' + name, flush=True)
        start = datetime.datetime.now(datetime.timezone.utc).isoformat(); tick = time.monotonic()
        suffix = '.json' if name.endswith('demo') or name == 'ir01-post-correction' else '.txt'
        with (PROOF / (name + suffix)).open('wb') as out, (PROOF / (name + '.stderr.txt')).open('wb') as err:
            result = subprocess.run(command, cwd=ROOT, stdout=out, stderr=err)
        row = {'command': command, 'started_utc': start, 'ended_utc': datetime.datetime.now(datetime.timezone.utc).isoformat(),
               'seconds': time.monotonic() - tick, 'exit_code': result.returncode}
        if name in ('focused', 'full-suite'):
            row['junit'] = ET.parse(PROOF / (name + '.xml')).getroot().find('testsuite').attrib
        identity['runs'][name] = row
        (PROOF / 'verification.json').write_text(json.dumps(identity, indent=2) + '\n', encoding='utf-8')
        print(name + ': ' + json.dumps(row), flush=True)
        if result.returncode:
            raise SystemExit('Validation failed: ' + name)
    after = {p: hashlib.sha256((ROOT / p).read_bytes()).hexdigest() for p in preserved}
    if after != preserved:
        raise SystemExit('Historical evidence changed')
    (PROOF / 'preservation-check.json').write_text(json.dumps(preserved, indent=2) + '\n', encoding='utf-8')


if __name__ == '__main__':
    main()
