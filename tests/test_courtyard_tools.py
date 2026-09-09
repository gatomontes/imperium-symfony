"""Packet checks reject altered payloads, archives and Git blob/mode claims."""
import hashlib
import importlib.util
import json
from pathlib import Path
import tempfile
import unittest
import zipfile
from contextlib import redirect_stdout
import io

ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('courtyard_verifier',ROOT/'tools/verify-courtyard-packet.py')
module=importlib.util.module_from_spec(spec); spec.loader.exec_module(module)

class CourtyardToolsTest(unittest.TestCase):
    def setUp(self):
        self.tmp=tempfile.TemporaryDirectory(prefix='courtyard-packet-test-'); self.root=Path(self.tmp.name)
        data=b'public synthetic source\n'
        row={'path':'source.txt','mode':'100644','blob':hashlib.sha1(b'blob '+str(len(data)).encode()+b'\0'+data).hexdigest(),
             'bytes':len(data),'sha256':hashlib.sha256(data).hexdigest()}
        for kind in ('tested','final'):
            with zipfile.ZipFile(self.root/f'{kind}-source.zip','w') as z:
                info=zipfile.ZipInfo('source.txt'); info.external_attr=int('100644',8)<<16; z.writestr(info,data)
            (self.root/f'{kind}-source-manifest.json').write_text(json.dumps([row]))
        (self.root/'source-identities.json').write_text('{}'); self.manifest()
    def tearDown(self): self.tmp.cleanup()
    def manifest(self):
        rows=[{'path':p.name,'bytes':p.stat().st_size,'sha256':hashlib.sha256(p.read_bytes()).hexdigest()} for p in self.root.iterdir() if p.name!='payload-manifest.json']
        (self.root/'payload-manifest.json').write_text(json.dumps(rows))
    def test_valid_packet(self):
        with redirect_stdout(io.StringIO()): module.verify(self.root)
    def test_changed_payload_refuses(self):
        (self.root/'source-identities.json').write_text('{"changed":true}')
        with self.assertRaisesRegex(ValueError,'Payload mismatch'): module.verify(self.root)
    def test_extra_payload_refuses(self):
        (self.root/'unexpected.txt').write_text('unexpected')
        with self.assertRaisesRegex(ValueError,'path set'): module.verify(self.root)
    def test_wrong_blob_refuses_even_with_updated_payload_manifest(self):
        p=self.root/'tested-source-manifest.json'; rows=json.loads(p.read_text()); rows[0]['blob']='0'*40; p.write_text(json.dumps(rows)); self.manifest()
        with self.assertRaisesRegex(ValueError,'Git source mismatch'): module.verify(self.root)
    def test_wrong_mode_refuses_even_with_updated_payload_manifest(self):
        p=self.root/'tested-source-manifest.json'; rows=json.loads(p.read_text()); rows[0]['mode']='100755'; p.write_text(json.dumps(rows)); self.manifest()
        with self.assertRaisesRegex(ValueError,'Git source mismatch'): module.verify(self.root)
    def test_helpers_compile(self):
        for name in ('inventory-courtyard.py','verify-courtyard.py','verify-courtyard-packet.py','package-courtyard.py'):
            path=ROOT/'tools'/name; compile(path.read_bytes(),str(path),'exec')

if __name__=='__main__': unittest.main()
