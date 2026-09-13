"""Fault-injection checks for the complete-suite acceptance guard."""
import contextlib
import copy
import hashlib
import io
import json
import pathlib
import tempfile
import unittest
from unittest import mock

import phpunit_partitions as guard


class CoverageGuardTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = pathlib.Path(self.temporary.name)
        self.files = ['tests/ATest.php', 'tests/BTest.php']
        enumeration = '''<testSuite xmlns="https://xml.phpunit.de/testSuite">
          <testClass file="/repo/tests/ATest.php"><testMethod id="A::testOne"/></testClass>
          <testClass file="/repo/tests/BTest.php"><testMethod id="B::testData#label"/></testClass>
        </testSuite>'''
        for index in range(2):
            directory = self.root / f'phpunit-{index}'
            directory.mkdir()
            (directory / 'complete-test-list.xml').write_text(enumeration)
            metadata = dict(index=index, count=2, tree='expected-tree', exit=0,
                            source_before='digest', source_after='digest',
                            all_files=self.files, selected=[self.files[index]])
            (directory / 'selection.json').write_text(json.dumps(metadata))
            case = ('<testcase class="A" name="testOne"/>' if index == 0 else
                    '<testcase class="B" name="testData with data set &quot;label&quot;"><skipped/></testcase>')
            (directory / 'results.xml').write_text(
                f'<testsuites><testsuite tests="1" assertions="2" skipped="{index}" errors="0" failures="0" time="1">{case}</testsuite></testsuites>')

    def verify(self):
        with contextlib.redirect_stdout(io.StringIO()):
            return guard.verify(self.root, 2, 'expected-tree')

    def test_complete_results_include_explicit_skips(self):
        result = self.verify()
        self.assertEqual((result['tests'], result['assertions'], result['skipped']), (2, 4, 1))

    def test_missing_partition_fails(self):
        (self.root / 'phpunit-1' / 'results.xml').unlink()
        with self.assertRaises(FileNotFoundError):
            self.verify()

    def test_source_and_selection_faults_fail(self):
        path = self.root / 'phpunit-1' / 'selection.json'
        original = json.loads(path.read_text())
        for field, value in [('tree', 'other'), ('exit', 1), ('index', 0), ('count', 3),
                             ('source_after', 'mutated'), ('selected', [self.files[0]]),
                             ('all_files', [self.files[1]])]:
            with self.subTest(field=field):
                changed = copy.deepcopy(original)
                changed[field] = value
                path.write_text(json.dumps(changed))
                with self.assertRaises(RuntimeError):
                    self.verify()
        changed = dict(original, source_before='other', source_after='other')
        path.write_text(json.dumps(changed))
        with self.assertRaisesRegex(RuntimeError, 'different source'):
            self.verify()

    def test_junit_faults_fail(self):
        path = self.root / 'phpunit-0' / 'results.xml'
        original = path.read_text()
        faults = [original.replace('errors="0"', 'errors="1"'),
                  original.replace('failures="0"', 'failures="1"'),
                  original.replace('skipped="0"', 'skipped="1"'),
                  original.replace('testOne', 'otherTest'),
                  original.replace('<testcase class="A" name="testOne"/>', ''),
                  original.replace('</testsuite>', '<testcase class="A" name="testOne"/></testsuite>').replace('tests="1"', 'tests="2"'),
                  original.replace('name="testOne"/>', 'name="testOne"><failure/></testcase>')]
        for number, fault in enumerate(faults):
            with self.subTest(fault=number):
                path.write_text(fault)
                with self.assertRaises(RuntimeError):
                    self.verify()

    def test_disagreeing_enumerations_fail(self):
        path = self.root / 'phpunit-1' / 'complete-test-list.xml'
        path.write_text(path.read_text().replace('A::testOne', 'A::otherTest'))
        with self.assertRaisesRegex(RuntimeError, 'disagree'):
            self.verify()


class SourceBindingTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = pathlib.Path(self.temporary.name)
        (self.root / 'src').mkdir()
        self.path = self.root / 'src/Example.php'
        self.data = b'<?php // original\n'
        self.path.write_bytes(self.data)
        blob = hashlib.sha1(b'blob ' + str(len(self.data)).encode() + b'\0' + self.data).hexdigest()
        self.entry = ('100644 blob ' + blob + '\tsrc/Example.php\0').encode()

    def digest(self):
        with contextlib.chdir(self.root), mock.patch.object(guard.subprocess, 'check_output', return_value=self.entry):
            return guard.source_digest()

    def test_exact_committed_bytes_pass(self):
        self.assertEqual(len(self.digest()), 64)

    def test_consistently_modified_source_still_fails(self):
        self.path.write_bytes(b'<?php // changed before every worker\n')
        with self.assertRaisesRegex(RuntimeError, 'committed Git tree'):
            self.digest()

    def test_untracked_code_and_configuration_fail(self):
        for name in ('src/Extra.php', 'phpunit.xml'):
            path = self.root / name
            path.write_text('unexpected')
            with self.subTest(name=name), self.assertRaisesRegex(RuntimeError, 'Untracked'):
                self.digest()
            path.unlink()

    def test_changed_file_type_fails(self):
        self.path.unlink()
        self.path.symlink_to('Missing.php')
        with self.assertRaisesRegex(RuntimeError, 'file type'):
            self.digest()


if __name__ == '__main__':
    unittest.main()
