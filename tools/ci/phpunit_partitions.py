#!/usr/bin/env python3
"""Run disjoint PHPUnit file partitions and require exact test-case coverage."""
import collections
import hashlib
import json
import os
import pathlib
import subprocess
import sys
import time
import xml.etree.ElementTree as ET


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


def git(*args):
    return subprocess.check_output(['git', *args], text=True).strip()


def inventory(xml):
    classes = ET.parse(xml).getroot().findall('.//{*}testClass')
    result = {}
    for cls in classes:
        path = cls.attrib['file'].replace('\\', '/')
        require('/tests/' in path, 'Test file is outside tests directory')
        relative = path.split('/tests/', 1)[1]
        require('..' not in pathlib.PurePosixPath(relative).parts, 'Invalid test path')
        for method in cls.findall('{*}testMethod'):
            identity = method.attrib['id']
            require(identity not in result, 'Duplicate enumerated test identity')
            result[identity] = relative
    require(result, 'Empty test enumeration')
    return {identity: 'tests/' + file for identity, file in result.items()}


def result_identity(case):
    name = case.attrib['name']
    method, separator, dataset = name.partition(' with data set ')
    if separator:
        if dataset.startswith('#'):
            dataset = dataset[1:]
        else:
            require(dataset.startswith('"') and dataset.endswith('"'), 'Unsupported dataset label')
            dataset = dataset[1:-1]
        name = method + '#' + dataset
    return case.attrib['class'] + '::' + name


def source_digest():
    entries = subprocess.check_output(['git', 'ls-tree', '-rz', 'HEAD']).split(b'\0')
    digest = hashlib.sha256()
    tracked = set()
    for entry in sorted(p for p in entries if p):
        info, raw = entry.split(b'\t', 1)
        mode, kind, blob = info.split()
        require(kind == b'blob', 'Unsupported non-file source entry')
        path = pathlib.Path(os.fsdecode(raw))
        tracked.add(path.as_posix())
        require(path.is_symlink() == (mode == b'120000'), 'Tracked file type changed')
        data = os.fsencode(os.readlink(path)) if path.is_symlink() else path.read_bytes()
        actual_blob = hashlib.sha1(b'blob ' + str(len(data)).encode() + b'\0' + data).hexdigest().encode()
        require(actual_blob == blob, 'Working source bytes differ from committed Git tree: ' + path.as_posix())
        digest.update(raw + b'\0')
        digest.update(hashlib.sha256(data).digest())
    for folder in ('src', 'tests', 'config', 'bootstrap', 'runtime', 'bin'):
        for path in pathlib.Path(folder).rglob('*.php'):
            require(path.as_posix() in tracked, 'Untracked executable source: ' + path.as_posix())
    for name in ('phpunit.xml', 'phpunit.xml.dist'):
        require(not pathlib.Path(name).exists() or name in tracked, 'Untracked PHPUnit configuration')
    return digest.hexdigest()


def run(index, count, output):
    require(0 <= index < count and count > 0, 'Invalid partition')
    output.mkdir(parents=True, exist_ok=True)
    before = source_digest()
    enumeration = output / 'complete-test-list.xml'
    subprocess.run(['vendor/bin/phpunit', '--list-tests-xml', str(enumeration), 'tests'], check=True)
    tests = inventory(enumeration)
    files = sorted(set(tests.values()))
    selected = files[index::count]
    require(selected, 'Empty partition')
    metadata = {'index': index, 'count': count, 'commit': git('rev-parse', 'HEAD'),
                'tree': git('rev-parse', 'HEAD^{tree}'), 'all_files': files, 'selected': selected,
                'source_before': before, 'exit': None}
    require(source_digest() == before, 'Source changed during test enumeration')
    (output / 'selection.json').write_text(json.dumps(metadata, indent=2))
    start = time.monotonic()
    result = subprocess.run(['vendor/bin/phpunit', *selected, '--log-junit', str(output / 'results.xml')])
    metadata.update(exit=result.returncode, seconds=time.monotonic()-start, source_after=source_digest())
    (output / 'selection.json').write_text(json.dumps(metadata, indent=2))
    require(metadata['source_before'] == metadata['source_after'], 'Tracked test/source/configuration bytes changed during tests')
    return result.returncode


def coverage(directories):
    expected = None
    observed = collections.Counter()
    files_seen = collections.Counter()
    rows = []
    for index, directory in enumerate(directories):
        tests = inventory(directory / 'complete-test-list.xml')
        if expected is None:
            expected = tests
        require(tests == expected, 'Partitions disagree on complete test enumeration')
        metadata = json.loads((directory / 'selection.json').read_text())
        files = sorted(set(tests.values()))
        selected = files[index::len(directories)]
        require(metadata['all_files'] == files and metadata['selected'] == selected, 'Missing, overlapping or altered file partition')
        files_seen.update(selected)
        root = ET.parse(directory / 'results.xml').getroot()
        cases = list(root.iter('testcase'))
        summary = root.find('testsuite')
        require(summary is not None and int(summary.attrib['tests']) == len(cases), 'Incomplete JUnit result')
        require(int(summary.attrib['errors']) == 0 and int(summary.attrib['failures']) == 0, 'PHPUnit errors or failures')
        ids = [result_identity(case) for case in cases]
        require(collections.Counter(ids) == collections.Counter(identity for identity, file in tests.items() if file in selected), 'Partition test cases do not match enumeration exactly')
        require(not any(case.find('failure') is not None or case.find('error') is not None for case in cases), 'Failed test case')
        require(int(summary.attrib['skipped']) == sum(case.find('skipped') is not None for case in cases), 'Skipped-test summary does not match cases')
        observed.update(ids)
        rows.append({'index': index, 'tests': len(cases), 'assertions': int(summary.attrib['assertions']),
                     'skipped': int(summary.attrib['skipped']), 'seconds': float(summary.attrib['time'])})
    require(expected is not None and observed == collections.Counter(expected.keys()), 'Missing or duplicate test cases')
    require(files_seen == collections.Counter(set(expected.values())), 'Missing or duplicate test files')
    return {'files': len(files_seen), 'tests': len(observed), 'assertions': sum(row['assertions'] for row in rows),
            'skipped': sum(row['skipped'] for row in rows), 'summed_test_seconds': sum(row['seconds'] for row in rows),
            'longest_partition_seconds': max(row['seconds'] for row in rows), 'partitions': rows}


def verify(root, count, expected_tree):
    require(count > 0, 'Invalid partition count')
    directories = [root / ('phpunit-' + str(index)) for index in range(count)]
    source = None
    for index, directory in enumerate(directories):
        metadata = json.loads((directory / 'selection.json').read_text())
        require(metadata['index'] == index and metadata['count'] == count, 'Wrong partition identity')
        require(metadata['tree'] == expected_tree and metadata['exit'] == 0, 'Wrong source tree or unsuccessful PHPUnit exit')
        require(metadata['source_before'] == metadata['source_after'], 'Source changed during partition')
        if source is None:
            source = metadata['source_before']
        require(source == metadata['source_before'], 'Partitions ran different source bytes')
    result = coverage(directories)
    result.update(tree=expected_tree, source_digest=source, exact_case_coverage=True)
    print(json.dumps(result, indent=2))
    return result


if __name__ == '__main__':
    if sys.argv[1] == 'run':
        sys.exit(run(int(sys.argv[2]), int(sys.argv[3]), pathlib.Path(sys.argv[4]).resolve()))
    if sys.argv[1] == 'verify':
        verify(pathlib.Path(sys.argv[2]), int(sys.argv[3]), sys.argv[4])
    else:
        raise SystemExit('Expected run or verify')
