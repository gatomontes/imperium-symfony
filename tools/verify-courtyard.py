"""Offline gates with exact source, dependency, command, time and native-exit evidence."""
import datetime as dt
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import time
import xml.etree.ElementTree as ET

ROOT=Path(__file__).resolve().parents[1]
PHP=shutil.which('php')
PREFIX=[PHP,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect']
ENV={k:v for k,v in os.environ.items() if k.upper() in ('SYSTEMROOT','WINDIR','COMSPEC','PATH','PATHEXT','TEMP','TMP','USERPROFILE','HOMEDRIVE','HOMEPATH','LOCALAPPDATA','APPDATA','PROGRAMDATA','PROGRAMFILES','PROGRAMFILES(X86)','PYTHONPATH')}
ENV.update(APP_ENV='test',APP_SECRET='',COMPOSER_DISABLE_NETWORK='1',PYTHONDONTWRITEBYTECODE='1')

def git(*args):
    return subprocess.check_output(['git',*args],cwd=ROOT).decode().strip()

def main():
    mode=sys.argv[1] if len(sys.argv)>1 else 'final'
    stamp=dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%S%fZ')
    out=ROOT/'var/courtyard-gates'/f'{mode}-{stamp}'; out.mkdir(parents=True)
    dirty=git('status','--porcelain')
    if mode=='final' and dirty: raise SystemExit('Final gates require clean committed source')
    identity={'tested_commit':git('rev-parse','HEAD'),'tested_tree':git('rev-parse','HEAD^{tree}'),
              'branch':git('branch','--show-current'),'status_before':dirty,'mode':mode,'environment_keys':sorted(ENV),
              'php_binary':PHP,'php_sha256':hashlib.sha256(Path(PHP).read_bytes()).hexdigest(),
              'dependencies':{p:hashlib.sha256((ROOT/p).read_bytes()).hexdigest() for p in ('composer.lock','vendor/composer/installed.json','vendor/composer/autoload_psr4.php')},'runs':{}}
    focus=['CourtyardIdentityTest','CourtyardProcessTest','CitadelMissionFormationTest','CitadelFormationCorrectionTest',
           'CitadelInterviewCompletionTest','CitadelReadinessPreparationTest','FormationClaimCustodyTest','FormationClaimCustodyProcessTest',
           'NativeAuthorityProtocolTest','NativeAuthorityCorrectionTest','TransactionalAuthorityConsumptionBatch12CoverageTest',
           'FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest','FrozenRuntimeCoverageTripwireRestorationBatch2Test']
    commands={
        'runtime':PREFIX+['-r','require "vendor/autoload.php"; echo PHP_VERSION, "\\n", (new ReflectionClass(App\\Imperium\\Runtime\\Citadel\\Formation\\FormationJournal::class))->getFileName(), "\\n";'],
        'phpunit-version':PREFIX+['vendor/bin/phpunit','--version'],
        'focused':PREFIX+['vendor/bin/phpunit',*[f'tests/Imperium/Runtime/{name}.php' for name in focus if (ROOT/f'tests/Imperium/Runtime/{name}.php').exists()], '--display-warnings','--log-junit',str(out/'focused.xml')],
    }
    if mode=='identity':
        commands['focused']=PREFIX+['vendor/bin/phpunit','tests/Imperium/Runtime/CourtyardIdentityTest.php','tests/Imperium/Runtime/CourtyardProcessTest.php','--display-warnings','--log-junit',str(out/'focused.xml')]
    if mode=='proof': commands.pop('focused')
    if mode in ('final','proof'):
        commands.update({'container-lint':PREFIX+['bin/console','lint:container'],
                        'formation-proof':PREFIX+['tools/prove-citadel-formation.php'],
                        'custody-proof':PREFIX+['tools/prove-citadel-formation-custody.php'],
                        'public-verification':PREFIX+['tools/verify-courtyard-public.php',str(out/'formation-proof.stdout.txt'),str(out/'custody-proof.stdout.txt')],
                        'python-tools':[sys.executable,'-B','-m','unittest','discover','-s','tests','-p','test_courtyard_tools.py']})
    if mode=='final':
        commands['full-suite']=PREFIX+['vendor/bin/phpunit','tests','--display-warnings','--log-junit',str(out/'full-suite.xml')]
    for name,command in commands.items():
        print('START '+name+' '+str(out),flush=True)
        start=dt.datetime.now(dt.timezone.utc).isoformat(); tick=time.monotonic()
        with (out/(name+'.stdout.txt')).open('wb') as stdout,(out/(name+'.stderr.txt')).open('wb') as stderr:
            result=subprocess.run(command,cwd=ROOT,env=ENV,stdout=stdout,stderr=stderr)
        row={'command':command,'started_utc':start,'ended_utc':dt.datetime.now(dt.timezone.utc).isoformat(),'seconds':time.monotonic()-tick,'exit_code':result.returncode}
        row['status_after_command']=git('status','--porcelain')
        generated=subprocess.check_output(['git','diff','--','config/reference.php'],cwd=ROOT)
        if generated:
            (out/(name+'.generated-reference.diff')).write_bytes(generated)
            changes=[line[1:] for line in generated.decode().splitlines() if line.startswith(('+','-')) and not line.startswith(('+++','---'))]
            if any(not line.lstrip().startswith('*') for line in changes): raise SystemExit('Unexpected executable reference.php drift')
            (ROOT/'config/reference.php').write_bytes(subprocess.check_output(['git','show','HEAD:config/reference.php'],cwd=ROOT))
            row['generated_phpdoc_restored']=True
        if (out/(name+'.xml')).exists(): row['junit']=ET.parse(out/(name+'.xml')).getroot().find('testsuite').attrib
        identity['runs'][name]=row; identity['status_after']=git('status','--porcelain')
        (out/'verification.json').write_text(json.dumps(identity,indent=2)+'\n',encoding='utf-8')
        print(name+': '+json.dumps(row),flush=True)
        if result.returncode: raise SystemExit(result.returncode)
    print('EVIDENCE '+str(out),flush=True)

if __name__=='__main__': main()
