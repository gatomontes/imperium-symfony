"""Offline O0 preparation checks, including V1 selector/condition corrections.

Public-ref shape checks are not provenance/admission checks. This is not a
complete runtime policy validator. The original 43 examples remain attributed
to the provider packet; additional named checks exercise the review correction.
"""
from pathlib import Path
import json,copy,itertools,hashlib,re,sys
if not __debug__:
 raise SystemExit('Refusing optimized Python: these offline checks require assertions.')
R=Path(sys.argv[1]) if len(sys.argv)>1 else Path(__file__).resolve().parents[1]
D=R/'docs/provider-onboarding'
p=json.loads((D/'o0-public-preparation.json').read_text(encoding='utf-8'))
checks=[]
def ck(name,ok):
 assert ok,name
 checks.append(name)
def unique(xs):return len(xs)==len(set(xs))
def input_selector(v,p):
 assert isinstance(v,dict)
 if 'preparation_unresolved_ref' in v:
  assert set(v)=={'preparation_unresolved_ref'}
  assert isinstance(v['preparation_unresolved_ref'],str)
  assert v['preparation_unresolved_ref'] in p['unresolved_public_refs']
 elif v.get('kind')=='public_ref':
  assert set(v)=={'kind','ref'} and isinstance(v['ref'],dict)
  ref=v['ref'];assert set(ref)=={'schema','id','digest'}
  assert isinstance(ref['schema'],str) and bool(ref['schema'].strip())
  assert isinstance(ref['id'],str) and re.fullmatch(r'[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}',ref['id'])
  assert isinstance(ref['digest'],str) and re.fullmatch(r'sha256:[0-9a-f]{64}',ref['digest'])
 elif v.get('kind')=='group_success_result':
  assert set(v)=={'kind','group_id'} and isinstance(v['group_id'],str)
  assert v['group_id'] in {g['group_id'] for g in p['assessment_groups']}
 else:
  raise AssertionError('Unknown input selector')

def run_condition(s,groups):
 rc=s['run_condition'];assert isinstance(rc,dict)
 if s['action']!='EXECUTE_ASSESSMENT':
  assert rc=={'kind':'after_success'}
  return
 kind=rc.get('kind')
 fields={'initial_assessment':{'kind','group_id'},
         'retry_after_confirmed_failure':{'kind','group_id','prior_attempt_step_id'}}
 assert isinstance(kind,str) and kind in fields and set(rc)==fields[kind]
 assert isinstance(rc['group_id'],str) and rc['group_id'] in groups
 if kind=='retry_after_confirmed_failure':
  assert isinstance(rc['prior_attempt_step_id'],str)
  assert re.fullmatch(r'[a-z0-9][a-z0-9._-]{0,79}',rc['prior_attempt_step_id'])

def validate(p):
 assert p['runnable'] is False and p['policy_schema'] is None
 ss=p['steps']; gs=p['assessment_groups']; slots=p['effect_slots']; by={s['step_id']:s for s in ss}; sb={s['slot_id']:s for s in slots}; gb={g['group_id']:g for g in gs}
 assert len(ss)==19 and len(slots)==17 and len(gs)==3
 assert len(by)==len(ss) and len(sb)==len(slots) and list(gb)==['W1','W2','W3']
 seen=set();claimed=[]
 for s in ss:
  assert set(s)=={'step_id','action','effect_slot_id','depends_on','input_refs','run_condition'}
  run_condition(s,gb)
  assert isinstance(s['input_refs'],list)
  for v in s['input_refs']:input_selector(v,p)
  group_inputs=[v['group_id'] for v in s['input_refs'] if v.get('kind')=='group_success_result']
  if s['action']=='APPLY_ASSIGNMENTS':assert group_inputs==['W1','W2','W3']
  elif s['action']!='EXECUTE_ASSESSMENT':assert group_inputs==[]
  assert re.fullmatch(r'[a-z0-9][a-z0-9._-]{0,79}',s['step_id'])
  assert unique(s['depends_on']) and set(s['depends_on'])<=seen
  seen.add(s['step_id']);slot=s['effect_slot_id']
  if slot:
   assert slot in sb;claimed.append(slot);q=sb[slot]
   assert set(q)=={'slot_id','effect','authority_mode','terms_rule','depends_on','max_uses','expires_at'}
   assert q['depends_on']==s['depends_on'] and q['max_uses']==1 and q['authority_mode']=='policy_effect'
   expected={'EXECUTE_ACCESS':'AUTHORIZE_BOOTSTRAP_ACCESS','EXECUTE_ASSESSMENT':'AUTHORIZE_BOOTSTRAP_ASSESSMENT','APPLY_ASSIGNMENTS':'APPLY_BOOTSTRAP_ASSIGNMENTS'}
   if s['action'] in expected:assert q['effect']==expected[s['action']]
   else:assert s['action']=='ADMIT_AUTHORIZED_RECORD' and q['effect'] in {'ADMIT_BOOTSTRAP_EVIDENCE','CONSTITUTE_FOUNDING_AUGUR','APPROVE_RUNTIME_BINDING_MAP'}
  else:assert s['action'] in {'RECORD_CONFIGURATION','SELECT_BASE'}
 assert set(claimed)==set(sb) and unique(claimed)
 attempts=[]
 for i,g in enumerate(gs):
  assert set(g)=={'group_id','attempt_step_ids','requires_success_of','workload_ref'}
  assert g['requires_success_of']==([] if i==0 else ['W'+str(i)])
  ids=g['attempt_step_ids'];assert len(ids)==4 and unique(ids)
  for a,sid in enumerate(ids):
   assert sid==g['group_id'].lower()+'.attempt.'+str(a);s=by[sid];attempts.append(sid)
   assert s['action']=='EXECUTE_ASSESSMENT' and s['depends_on']==['found-augur']
   rc={'kind':'initial_assessment','group_id':g['group_id']} if a==0 else {'kind':'retry_after_confirmed_failure','group_id':g['group_id'],'prior_attempt_step_id':ids[a-1]}
   assert s['run_condition']==rc
   ins=[v['group_id'] for v in s['input_refs'] if v.get('kind')=='group_success_result'];assert ins==g['requires_success_of']
 assert unique(attempts) and len(attempts)==12
 app=sb[by['apply-assignments']['effect_slot_id']]
 assert app['terms_rule']['result_group_ids']==['W1','W2','W3'] and 'result_step_id' not in app['terms_rule']
 assert set(app['terms_rule'])=={'kind','permitted_tuple_set_ref','result_group_ids','required_predicates_ref','expected_assignments_ref','selection_rule_ref'}
 assert app['terms_rule']['selection_rule_ref']=={'preparation_unresolved_ref':'assignment_selection_rule'}
 assert p['retryable_failure_allowlist']==[]
 assert p['selected']['limits']=={'cognition_attempts':12,'attempt_micro_usd':100000,'total_micro_usd':1200000,'max_retries_per_group':3}
 assert p['application_targets']==['courtyard.courtthane','clavium.locksmith']
 def walk(x):
  if isinstance(x,dict):
   if 'preparation_unresolved_ref' in x:assert set(x)=={'preparation_unresolved_ref'} and x['preparation_unresolved_ref'] in p['unresolved_public_refs']
   else:
    for v in x.values():walk(v)
  elif isinstance(x,list):
   for v in x:walk(v)
 walk(ss);walk(slots)
validate(p);ck('closed finite graph and explicit non-runnable placeholders',True)
def bad(name,fn):
 q=copy.deepcopy(p);fn(q)
 try:validate(q)
 except (AssertionError,KeyError):ck(name,True);return
 raise AssertionError('mutation accepted: '+name)
bad('duplicate slot refuses',lambda q:q['effect_slots'].__setitem__(1,q['effect_slots'][0]))
bad('ordinary dependency cycle refuses',lambda q:q['steps'][0]['depends_on'].append('apply-assignments'))
bad('group cycle refuses',lambda q:q['assessment_groups'][0]['requires_success_of'].append('W3'))
bad('missing attempt refuses',lambda q:q['assessment_groups'][0]['attempt_step_ids'].pop())
bad('extra attempt refuses',lambda q:q['assessment_groups'][0]['attempt_step_ids'].append('w1.attempt.4'))
bad('cross-group retry predecessor refuses',lambda q:q['steps'][7]['run_condition'].__setitem__('prior_attempt_step_id','w2.attempt.0'))
bad('non-immediate predecessor refuses',lambda q:q['steps'][8]['run_condition'].__setitem__('prior_attempt_step_id','w1.attempt.0'))
bad('failure edge in success dependencies refuses',lambda q:q['steps'][7]['depends_on'].append('w1.attempt.0'))
bad('slot dependency bypass refuses',lambda q:q['effect_slots'][5]['depends_on'].clear())
bad('assessment authority substitution refuses',lambda q:q['effect_slots'][4].__setitem__('effect','AUTHORIZE_BOOTSTRAP_ACCESS'))
bad('reuse count refuses',lambda q:q['effect_slots'][4].__setitem__('max_uses',2))
bad('application missing group refuses',lambda q:q['effect_slots'][-1]['terms_rule']['result_group_ids'].pop())
bad('fixed attempt application selector refuses',lambda q:q['effect_slots'][-1]['terms_rule'].__setitem__('result_step_id','w3.attempt.0'))
bad('unsafe retry reason refuses',lambda q:q['retryable_failure_allowlist'].append('HTTP_503'))
bad('undeclared future input refuses',lambda q:q['steps'][6]['input_refs'].append({'preparation_unresolved_ref':'invented'}))
bad('model settings cannot appoint Augur',lambda q:q['application_targets'].append('oracle.augur'))
# V1 regression cases and adjacent malformed variants; fixtures exist in memory
# only and establish no genuine reference, authority or provider-safe failure.
bad('V1 unknown input selector refuses',lambda q:q['steps'][6]['input_refs'].append({'kind':'unrecognized_selector','ref':'not-a-public-ref'}))
bad('V1 unknown non-assessment condition refuses',lambda q:q['steps'][0].__setitem__('run_condition',{'kind':'unrecognized_condition'}))
bad('selector unknown field refuses',lambda q:q['steps'][-1]['input_refs'][0].__setitem__('extra',True))
bad('selector missing group refuses',lambda q:q['steps'][-1]['input_refs'][0].pop('group_id'))
bad('selector unknown group refuses',lambda q:q['steps'][-1]['input_refs'][0].__setitem__('group_id','W4'))
bad('application reordered group inputs refuse',lambda q:q['steps'][-1]['input_refs'].reverse())
bad('group result on configuration refuses',lambda q:q['steps'][0]['input_refs'].append({'kind':'group_success_result','group_id':'W1'}))
bad('non-object selector refuses',lambda q:q['steps'][6]['input_refs'].append('not-a-selector'))
bad('non-list inputs refuse',lambda q:q['steps'][0].__setitem__('input_refs',{}))
bad('mixed placeholder and public selector refuses',lambda q:q['steps'][0]['input_refs'][0].__setitem__('kind','public_ref'))
bad('placeholder disguised as public ref refuses',lambda q:q['steps'][0]['input_refs'].__setitem__(0,{'kind':'public_ref','ref':{'preparation_unresolved_ref':'candidate_bindings'}}))
bad('malformed public digest refuses',lambda q:q['steps'][0]['input_refs'].__setitem__(0,{'kind':'public_ref','ref':{'schema':'imperium.review-fixture/v1','id':'fixture-only','digest':'invalid'}}))
bad('after_success extra field refuses',lambda q:q['steps'][0]['run_condition'].__setitem__('group_id','W1'))
bad('assessment condition on configuration refuses',lambda q:q['steps'][0].__setitem__('run_condition',{'kind':'initial_assessment','group_id':'W1'}))
bad('after_success on assessment refuses',lambda q:q['steps'][6].__setitem__('run_condition',{'kind':'after_success'}))
bad('non-object condition refuses',lambda q:q['steps'][0].__setitem__('run_condition','after_success'))
bad('retry condition extra field refuses',lambda q:q['steps'][7]['run_condition'].__setitem__('extra',True))
bad('retry condition missing predecessor refuses',lambda q:q['steps'][7]['run_condition'].pop('prior_attempt_step_id'))
fixture={'kind':'public_ref','ref':{'schema':'imperium.review-fixture/v1','id':'fixture-only','digest':'sha256:'+'1'*64}}
input_selector(fixture,p)
ck('public-ref shape accepted without claiming evidence authenticity',True)
# Abstract transition oracle: deliberately independent of app implementation.
# R is synthetic evidence satisfying a future admitted retry rule; NEVER real DeepSeek evidence.
def ready(outcomes,allow_retry):
 if any(x=='U' for x in outcomes):return 'FENCED'
 if 'S' in outcomes:
  assert outcomes.count('S')==1 and outcomes[-1]=='S'
  return 'SUCCESS'
 if 'T' in outcomes:return 'TERMINAL'
 if len(outcomes)==4:return 'EXHAUSTED'
 if outcomes and not allow_retry:return 'RETRY_DISABLED'
 return len(outcomes)
for n in range(4):ck('synthetic success at attempt '+str(n)+' skips later slots',ready(['R']*n+['S'],True)=='SUCCESS')
ck('four synthetic confirmed failures exhaust',ready(['R']*4,True)=='EXHAUSTED')
for n in range(4):
 ck('unknown attempt '+str(n)+' fences',ready(['R']*n+['U'],True)=='FENCED')
 ck('terminal attempt '+str(n)+' blocks',ready(['R']*n+['T'],True)=='TERMINAL')
ck('actual provider allowlist disables first retry',ready(['R'],False)=='RETRY_DISABLED')
for places in itertools.product(range(4),repeat=3):
 histories=[['R']*n+['S'] for n in places]
 assert all(ready(h,True)=='SUCCESS' for h in histories)
 assert sum(map(len,histories))<=12
ck('64 synthetic three-group success positions bounded by 12',True)
for val in ['U','T']:
 histories=[['S'],[val],[]];ck(val+' in W2 blocks W3 and application',not all(ready(h,True)=='SUCCESS' for h in histories))
# Admission/replay abstract rule: same command checked before changed currentness.
def command(saved,fingerprint,current=True,slot_unused=True):
 if saved is not None:return 'RECOGNIZE' if saved==fingerprint else 'CONFLICT'
 return 'ADMIT' if current and slot_unused else 'REFUSE'
ck('original replay after expiry recognizes',command('a','a',False,False)=='RECOGNIZE')
ck('changed head same command conflicts',command('a','b')=='CONFLICT')
ck('new command expired refuses',command(None,'a',False)=='REFUSE')
ck('new command consumed slot refuses',command(None,'a',True,False)=='REFUSE')
ck('competing serialized admissions admit only one', [command(None,'a',True,x) for x in [True,False]]==['ADMIT','REFUSE'])
# Budget enumeration includes settled values and unknown maxima, no hidden release.
for used in range(13):
 for unknown in range(used+1):
  exposure=unknown*100000+(used-unknown)*100000
  can_reserve=used<12 and exposure+100000<=1200000 and unknown==0
  assert not(unknown and can_reserve)
  if used==12:assert not can_reserve
ck('91 budget/unknown boundary combinations',True)
ck('resume is evidence-only in specification', 'it cannot create a new claim, dispatch,' in (R/'contracts/provider-onboarding-continuation.md').read_text(encoding='utf-8'))
cs=json.loads((D/'o0-cost-comparison.json').read_text())
for row in cs['rows']:
 c=sum((row[q]*row[rate]+999999)//1000000 for q,rate in [('input_quantity_proposed','peak_cache_miss_rate_micro_usd'),('output_quantity_proposed','peak_output_rate_micro_usd')])
 ck(row['model_id']+' exact rational cost',c==row['attempt_micro_usd'] and 3*c==row['three_success_baseline_micro_usd'] and 12*c==row['twelve_attempt_max_micro_usd'] and row['eligibility']=='UNKNOWN' and not row['base_selected'])
# A1 medium-capacity correction: abstract choice examples, not a production
# response verifier. Inputs below are synthetic labels, never provider rankings.
w=json.loads((D/'o0-workloads.json').read_text(encoding='utf-8'))
rule=json.loads((D/'o0-assignment-selection-rule.json').read_text(encoding='utf-8'))
ck('A1 exact medium-capacity rule is non-runnable',rule['runnable'] is False and rule['runtime_body']=={
 'algorithm':'role_capacity_middle_tier_v1','capacity_basis':'augur_profile_specific_evidence_assessment',
 'even_rule':'lower_middle','same_tier_tie_break':['provider','model_id','model_version','configuration_digest','profile_digest','binding_digest'],
 'unknown_behavior':'refuse_when_multiple_eligible_candidates','group_roles':{'W2':'courtyard.courtthane','W3':'clavium.locksmith'}})
for g in w['workloads']:
 if g['group_id']=='W1':
  ck('A1 W1 response remains unchanged','capacity_order' not in g['result_contract']['required'])
 else:
  ck('A1 '+g['group_id']+' declares role capacity response',g['result_contract']['schema_proposal']=='imperium.bootstrap-role-assessment-response/v2' and g['result_contract']['required'].count('capacity_order')==1 and g['result_contract']['capacity_order']['ranked']['tier_required']==['binding_refs','rationale','evidence_refs'])

def select_role(fitting,permitted,tiers,identity_keys):
 """Small abstract oracle over already verified findings/identity keys."""
 assert unique(fitting) and all(x in identity_keys for x in fitting)
 if tiers is not None:
  if not isinstance(tiers,list) or not tiers or any(not isinstance(t,list) or not t for t in tiers):
   return ('INVALID_CAPACITY_ORDER',None,None)
  flattened=[x for t in tiers for x in t]
  if not unique(flattened) or set(flattened)!=set(fitting):
   return ('INVALID_CAPACITY_ORDER',None,None)
 eligible=set(fitting)&set(permitted)
 if not eligible:return ('NO_ELIGIBLE_ASSIGNMENT',None,None)
 if len(eligible)==1:return ('SELECTED',next(iter(eligible)),None)
 if tiers is None:return ('CAPACITY_ORDER_UNKNOWN',None,None)
 filtered=[[x for x in t if x in eligible] for t in tiers]
 filtered=[t for t in filtered if t]
 index=(len(filtered)-1)//2
 selected=min(filtered[index],key=lambda x:tuple(v.encode('utf-8') for v in identity_keys[x]))
 return ('SELECTED',selected,index)

keys={x:('provider',x,'revision','config','profile',x) for x in 'abcde'}
ck('A1 no fitting candidate refuses',select_role([],list(keys),None,keys)[0]=='NO_ELIGIBLE_ASSIGNMENT')
ck('A1 one candidate needs no comparative ranking',select_role(['c'],['c'],None,keys)==('SELECTED','c',None))
ck('A1 three tiers select middle',select_role(list('abc'),list('abc'),[['a'],['b'],['c']],keys)==('SELECTED','b',1))
ck('A1 two tiers select lower middle',select_role(list('ab'),list('ab'),[['a'],['b']],keys)==('SELECTED','a',0))
ck('A1 four tiers select lower middle',select_role(list('abcd'),list('abcd'),[[x] for x in 'abcd'],keys)==('SELECTED','b',1))
ck('A1 same-tier identities resolve deterministically',select_role(list('abc'),list('abc'),[['c','b','a']],keys)==('SELECTED','a',0))
ck('A1 equivalent bindings do not weight tier median',select_role(list('abcde'),list('abcde'),[['a','b','c'],['d'],['e']],keys)==('SELECTED','d',1))
ck('A1 filter permitted candidates before tier median',select_role(list('abcde'),list('bde'),[[x] for x in 'abcde'],keys)==('SELECTED','d',1))
ck('A1 unknown order with multiple options refuses',select_role(list('ab'),list('ab'),None,keys)[0]=='CAPACITY_ORDER_UNKNOWN')
ck('A1 incomplete capacity order refuses',select_role(list('abc'),list('abc'),[['a'],['b']],keys)[0]=='INVALID_CAPACITY_ORDER')
ck('A1 duplicate ranked binding refuses',select_role(list('ab'),list('ab'),[['a'],['a','b']],keys)[0]=='INVALID_CAPACITY_ORDER')
ck('A1 extra ranked binding refuses',select_role(list('ab'),list('ab'),[['a'],['b'],['c']],keys)[0]=='INVALID_CAPACITY_ORDER')
ck('A1 empty ranked tier refuses',select_role(list('ab'),list('ab'),[['a'],[],['b']],keys)[0]=='INVALID_CAPACITY_ORDER')
ck('A1 invalid supplied order refuses even with sole permitted candidate',select_role(list('ab'),['a'],[['a']],keys)[0]=='INVALID_CAPACITY_ORDER')
for order in itertools.permutations('abc'):
 for ties in itertools.permutations('abc'):
  assert select_role(list(order),list(reversed(order)),[list(ties)],keys)==('SELECTED','a',0)
ck('A1 candidate and same-tier permutations preserve selection',True)
ck('A1 declared capacity order beats candidate ID order',select_role(list('abc'),list('abc'),[['c'],['a'],['b']],keys)==('SELECTED','a',1))
def selected_set(results,permitted_pairs):
 if any(result[0]!='SELECTED' for result in results):return ('REFUSED',None)
 pair=tuple(result[1] for result in results)
 if permitted_pairs is not None and pair not in permitted_pairs:
  return ('SELECTED_ASSIGNMENT_SET_NOT_PERMITTED',None)
 return ('SELECTED',pair)
pair_results=[select_role(list('ab'),list('ab'),[['a'],['b']],keys),select_role(list('ab'),list('ab'),[['b'],['a']],keys)]
ck('A1 impermissible combined pair refuses without alternate search',selected_set(pair_results,{('b','a')})==('SELECTED_ASSIGNMENT_SET_NOT_PERMITTED',None))
ck('A1 permitted whole pair preserves role-specific rankings',selected_set(pair_results,{('a','b')})==('SELECTED',('a','b')))
ck('A1 unknown role blocks whole-set selection',selected_set([pair_results[0],select_role(list('ab'),list('ab'),None,keys)],None)==('REFUSED',None))
bad('A1 unbound selection rule refuses',lambda q:q['effect_slots'][-1]['terms_rule'].pop('selection_rule_ref'))
print(json.dumps({'result':'PASS','scope':'Static graph mutations, exact arithmetic and abstract protocol/selection examples ONLY. No runtime, concurrency, crash, provider-capacity or PHP proof. Synthetic R cases do not enable real retries.','checks':checks,'count':len(checks)},indent=2))
