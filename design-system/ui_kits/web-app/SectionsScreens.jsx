function SectionsScreen({notify}){
  const {Button,DataTable,StatusBadge,Select,Input,Dialog,EmptyState,Alert}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();
  const [term,setTerm]=React.useState(st.currentTerm().id);const [br,setBr]=React.useState('all');const [q,setQ]=React.useState('');const [create,setCreate]=React.useState(false);const [req,setReq]=React.useState(false);const [pg,setPg]=React.useState('all');const [fi,setFi]=React.useState('all');
  const loading=useFakeLoad(term+br+pg+fi);
  const scope=st.sectionScope(me);
  const inisOf=id=>st.S.assignments.filter(a=>a.section===id);
  const rows=scope.filter(s=>s.term===term&&(br==='all'||s.branch===+br)&&(pg==='all'||s.program===+pg)&&(fi==='all'||(fi==='none'?inisOf(s.id).length===0:inisOf(s.id).some(a=>a.ini===+fi)))&&(st.course(s.course).code+s.no).toLowerCase().includes(q.toLowerCase()));
  const visBranches=me.role==='SUPER_ADMIN'?st.S.branches:st.S.branches.filter(b=>me.branches.includes(b.id));
  const sub=me.role==='SUPER_ADMIN'?t('sections_sub_admin'):me.role==='BRANCH_COORDINATOR'?t('sections_sub_coord'):t('sections_sub_inst');
  const count=id=>st.S.enrollments.filter(e=>e.section===id&&e.status==='active').length;
  const reqBtn=st.can(me,'section.request')&&<Button icon="plus" onClick={()=>setReq(true)}>{lang==='en'?'Add a section':'إضافة شعبة'}</Button>;
  if(me.role==='INSTRUCTOR'&&scope.length===0) return <div><PageHeader title={t('nav_mysections')} actions={reqBtn}/><Panel><EmptyState icon="users" title={t('no_sections_inst')} message={t('no_sections_inst_msg')} action={reqBtn}/></Panel>{req&&<SectionRequestDialog onClose={()=>setReq(false)} notify={notify}/>}</div>;
  return <div>
    <PageHeader title={me.role==='INSTRUCTOR'?t('nav_mysections'):t('nav_sections')} subtitle={sub}
      actions={<>{me.role!=='SUPER_ADMIN'&&<Button variant="ghost" size="sm" icon="flask-conical" onClick={()=>go('section',13)}>{t('demo_scope')}</Button>}
        {reqBtn}{st.can(me,'section.create')&&<Button icon="plus" onClick={()=>setCreate(true)}>{t('new_section')}</Button>}</>}/>
    <Panel style={{padding:20}}>
      <div style={{display:'flex',gap:12,marginBottom:16,flexWrap:'wrap',alignItems:'flex-end'}}>
        <Input size="sm" icon="search" placeholder={t('course')+' / '+t('section_no')} value={q} onChange={e=>setQ(e.target.value)} style={{width:220}}/>
        <Select size="sm" icon="calendar-range" value={term} onChange={e=>setTerm(+e.target.value)} options={st.S.terms.map(x=>({value:x.id,label:lang==='en'?x.en:x.ar}))} style={{width:220}}/>
        {me.role!=='INSTRUCTOR'&&<Select size="sm" icon="building-2" value={br} onChange={e=>setBr(e.target.value)} options={[{value:'all',label:t('all')+' — '+t('branches')},...visBranches.map(b=>({value:b.id,label:st.branchLabel(b.id,lang)}))]} style={{width:220}}/>}
        <Select size="sm" icon="graduation-cap" value={pg} onChange={e=>setPg(e.target.value)} options={[{value:'all',label:lang==='en'?'All programs':'جميع البرامج'},...st.S.programs.map(p=>({value:p.id,label:lang==='en'?p.en:p.ar}))]} style={{width:220}}/>
        <Select size="sm" icon="lightbulb" value={fi} onChange={e=>setFi(e.target.value)} options={[{value:'all',label:lang==='en'?'All initiatives':'جميع المبادرات'},...st.initiatives.map(i=>({value:String(i.id),label:lang==='en'?i.en:i.ar})),{value:'none',label:lang==='en'?'Not linked':'غير مرتبطة بمبادرة'}]} style={{width:220}}/>
        <div style={{flex:1}}/><Button size="sm" variant="secondary" icon="download" onClick={()=>{st.audit(me.id,'section.exported','section',null,null,{rows:rows.length});notify(t('export'),rows.length+' '+t('rows'))}}>{t('export')}</Button>
      </div>
      <DataTable loading={loading} onRowClick={r=>go('section',r.id)} columns={[
        {key:'no',label:t('section_no'),numeric:true,render:r=><div style={{display:'flex',gap:8,alignItems:'center'}}><b>{r.no}</b>{st.isShared(me,r)&&<StatusBadge status="shared"/>}</div>},
        {key:'c',label:t('course'),render:r=>{const c=st.course(r.course);return <span><span style={{fontFamily:'var(--font-latin)'}}>{c.code}</span> <span style={{color:'var(--text-secondary)'}}>· {lang==='en'?c.en:c.ar}</span></span>}},
        {key:'p',label:lang==='en'?'Program':'البرنامج',render:r=>st.programName(r.program,lang)},
        {key:'ini',label:lang==='en'?'Initiative':'المبادرة',wrap:true,render:r=>{if(r.status&&r.status!=='active')return <SecStatus s={r.status}/>;const l=inisOf(r.id);return l.length?<div style={{display:'flex',flexWrap:'wrap',gap:4}}>{l.map(a=><span key={a.id} style={{display:'inline-flex',alignItems:'center',height:24,padding:'0 9px',borderRadius:12,background:'var(--green-50)',border:'1px solid var(--green-200)',font:'600 12px var(--font-sans)',color:'var(--green-700)',whiteSpace:'nowrap'}}>{st.iniName(a,lang)}</span>)}</div>:<StatusBadge status="warning">{lang==='en'?'Not linked':'غير مرتبطة'}</StatusBadge>}},
        {key:'b',label:t('branch'),render:r=>st.branchLabel(r.branch,lang)},
        ...(me.role==='INSTRUCTOR'?[]:[{key:'i',label:t('instructor'),render:r=>r.instructor?st.uname(r.instructor,lang):<span style={{color:'var(--text-muted)'}}>{t('no_instructor')}</span>}]),
        {key:'s',label:t('students_n'),numeric:true,render:r=>count(r.id)}]} rows={rows}
        empty={<EmptyState icon="users" title={lang==='en'?'No sections in this term':'لا توجد شعب في هذا الفصل'} action={st.can(me,'section.create')&&<Button size="sm" icon="plus" onClick={()=>setCreate(true)}>{t('new_section')}</Button>}/>}/>
    </Panel>
    {create&&<SectionDialog onClose={()=>setCreate(false)} notify={notify} term={term}/>}
    {req&&<SectionRequestDialog onClose={()=>setReq(false)} notify={notify}/>}
  </div>;
}
function SectionDialog({onClose,notify,term}){
  const {Dialog,Button,Input,Select,Alert}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();
  const [f,setF]=React.useState({term,branch:4,course:2,program:'',no:'214',instructor:'',ini:''});const [err,setErr]=React.useState(null);const [busy,setBusy]=React.useState(false);
  const save=()=>{if(!f.no){setErr(t('required'));return;}if(!f.program){setErr(lang==='en'?'Select the academic program for this section.':'اختر البرنامج الأكاديمي للشعبة.');return;}if(!f.ini){setErr(lang==='en'?'Select the initiative this section belongs to.':'اختر المبادرة التي تتبعها الشعبة.');return;}
    const dup=st.S.sections.find(s=>s.term===+f.term&&s.branch===+f.branch&&s.course===+f.course&&s.no===f.no.trim());
    if(dup){setErr(t('dup_section',{k:st.course(dup.course).code+' / '+dup.no+' / '+st.branchLabel(dup.branch,lang)}));return;}
    setBusy(true);setTimeout(()=>{const s={id:Date.now(),term:+f.term,branch:+f.branch,course:+f.course,no:f.no.trim(),program:+f.program,instructor:f.instructor?+f.instructor:null};st.S.sections.push(s);
      if(s.instructor)st.S.history.push({section:s.id,instructor:s.instructor,from:st.now().slice(0,10),to:null,by:me.id});
      st.S.assignments.push({id:Date.now()+2,section:s.id,ini:+f.ini,stages:(st.TPL[+f.ini]||['تسجيل الطالب','التوثيق','الانتهاء']).slice()});
      st.audit(me.id,'section.created','section',s.id,null,{course:st.course(s.course).code,no:s.no,branch:st.branchLabel(s.branch,'ar'),program:st.programName(s.program,'ar'),initiative:st.iniName({id:+f.ini},'ar')});st.emit();setBusy(false);onClose();notify(t('section_created'));go('section',s.id);},500);};
  const set=(k,v)=>{setF({...f,[k]:v});setErr(null)};
  return <Dialog title={t('new_section')} onClose={onClose} width={560} footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{t('create')}</Button></>}>
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
      {st.S.terms.length===0&&<Alert tone="warning" style={{gridColumn:'1 / -1'}}>{t('no_term_first')}</Alert>}
      <Select label={t('f_term')} value={f.term} onChange={e=>set('term',e.target.value)} options={st.S.terms.map(x=>({value:x.id,label:lang==='en'?x.en:x.ar}))}/>
      <Select label={t('branch')} value={f.branch} onChange={e=>set('branch',e.target.value)} options={st.S.branches.filter(b=>b.active).map(b=>({value:b.id,label:st.branchLabel(b.id,lang)}))}/>
      <Select label={t('course')} value={f.course} onChange={e=>set('course',e.target.value)} options={st.S.courses.map(c=>({value:c.id,label:c.code+' · '+(lang==='en'?c.en:c.ar)}))}/>
      <Input label={t('section_no')} required dir="ltr" value={f.no} onChange={e=>set('no',e.target.value)}/>
      <Select label={lang==='en'?'Academic program':'البرنامج الأكاديمي'} required value={f.program} onChange={e=>set('program',e.target.value)} options={[{value:'',label:lang==='en'?'Select program…':'اختر البرنامج…'},...st.S.programs.filter(p=>p.active).map(p=>({value:p.id,label:p.code+' · '+(lang==='en'?p.en:p.ar)}))]} style={{gridColumn:'1 / -1'}}/>
      <Select label={lang==='en'?'Initiative':'المبادرة'} required value={f.ini} onChange={e=>set('ini',e.target.value)} options={[{value:'',label:lang==='en'?'Select initiative…':'اختر المبادرة…'},...st.initiatives.filter(i=>i.status!=='closed').map(i=>({value:String(i.id),label:lang==='en'?i.en:i.ar}))]} style={{gridColumn:'1 / -1'}}/>
      <Select label={t('instructor')} value={f.instructor} onChange={e=>set('instructor',e.target.value)} options={[{value:'',label:t('no_instructor')},...st.S.users.filter(u=>u.role==='INSTRUCTOR'&&u.active).map(u=>({value:u.id,label:lang==='en'?u.en:u.ar}))]} style={{gridColumn:'1 / -1'}}/>
      {err&&<Alert tone="danger" style={{gridColumn:'1 / -1'}}>{err}</Alert>}
    </div>
  </Dialog>;
}

function SectionDetail({id,notify}){
  const {Button,DataTable,StatusBadge,Tabs,StatCard,Dialog,Input,Select,Alert}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();
  const sec=st.S.sections.find(s=>s.id===id);
  const [tab,setTab]=React.useState('ini');const [ini,setIni]=React.useState(null);const [rq,setRq]=React.useState('');const [rf,setRf]=React.useState('all');const [wd,setWd]=React.useState(null);const [reason,setReason]=React.useState('');const [rErr,setRErr]=React.useState(null);const [busy,setBusy]=React.useState(false);
  const [enroll,setEnroll]=React.useState(false);const [uid,setUid]=React.useState('');const [uErr,setUErr]=React.useState(null);
  const [chg,setChg]=React.useState(false);const [newI,setNewI]=React.useState('');const [grant,setGrant]=React.useState(false);const [gUser,setGUser]=React.useState('');const [review,setReview]=React.useState(false);const [resub,setResub]=React.useState(false);
  const denied=React.useRef(false);
  if(!sec||!st.canSee(me,sec)){if(!denied.current){denied.current=true;st.audit(me.id,'access.denied','section',id,null,{reason:'out_of_scope'});}return <RefusalScreen kind="404"/>;}
  const c=st.course(sec.course);const tm=st.term(sec.term);
  const enr=st.S.enrollments.filter(e=>e.section===id);
  const canWrite=me.role==='SUPER_ADMIN'||(me.role==='INSTRUCTOR');
  const listLabel=me.role==='INSTRUCTOR'?t('nav_mysections'):t('nav_sections');
  const doWithdraw=()=>{if(!reason.trim()){setRErr(t('required'));return;}setBusy(true);setTimeout(()=>{wd.status='withdrawn';wd.withdrawn=st.now().slice(0,10);wd.reason=reason;st.audit(me.id,'enrollment.withdrawn','enrollment',wd.id,{status:'active'},{status:'withdrawn',reason});st.emit();setBusy(false);setWd(null);setReason('');notify(t('withdrawn_ok'));},500);};
  const doEnroll=()=>{if(!/^\d{9}$/.test(uid)){setUErr(t('uid_bad'));return;}let s=st.S.students.find(x=>x.uid===uid);
    if(s&&enr.some(e=>e.student===s.id&&e.status==='active')){setUErr(t('already_enrolled'));return;}
    setBusy(true);setTimeout(()=>{if(!s){s={id:Date.now(),uid,ar:lang==='en'?'طالب جديد':'طالب جديد',en:'New student'};st.S.students.push(s);st.audit(me.id,'student.created','student',s.id,null,{uid});}
      const ex=enr.find(e=>e.student===s.id);if(ex){ex.status='active';ex.withdrawn=null;ex.reason=null;}else st.S.enrollments.push({id:Date.now()+1,student:s.id,section:id,status:'active',enrolled:st.now().slice(0,10)});
      st.audit(me.id,'enrollment.created','enrollment',id,null,{uid,section:sec.no});st.emit();setBusy(false);setEnroll(false);setUid('');notify(t('enrolled_ok'));},500);};
  const doChange=()=>{setBusy(true);setTimeout(()=>{const old=sec.instructor;const h=st.S.history.find(x=>x.section===id&&!x.to);if(h)h.to=st.now().slice(0,10);sec.instructor=+newI;st.S.history.push({section:id,instructor:+newI,from:st.now().slice(0,10),to:null,by:me.id});
    st.audit(me.id,'section.instructor_changed','section',id,{instructor:st.uname(old,'ar')},{instructor:st.uname(+newI,'ar')});st.emit();setBusy(false);setChg(false);notify(t('instr_changed'));},500);};
  const doGrant=()=>{if(!gUser)return;st.S.grants.push({id:Date.now(),section:id,user:+gUser,by:me.id,at:st.now().slice(0,19)});st.audit(me.id,'section.access_granted','section',id,null,{user:st.uname(+gUser,'ar')});st.emit();setGrant(false);setGUser('');notify(t('grant_ok'));};
  const hist=st.S.history.filter(h=>h.section===id).slice().reverse();
  const grants=st.S.grants.filter(g=>g.section===id);
  const acts=st.S.audit.filter(a=>(a.type==='section'&&a.eid===id)||(a.type==='enrollment'&&enr.some(e=>e.id===a.eid)));
  const inis=st.S.assignments.filter(a=>a.section===id);const iniObj=ini&&inis.find(a=>a.id===ini);const [asg,setAsg]=React.useState(undefined);
  const tabs=[{id:'ini',label:lang==='en'?'Initiatives':'المبادرات',count:inis.length},{id:'roster',label:t('tab_roster'),count:enr.filter(e=>e.status==='active').length}];
  if(me.role==='SUPER_ADMIN')tabs.push({id:'access',label:t('tab_access'),count:grants.length});
  tabs.push({id:'support',label:lang==='en'?'Support requests':'طلبات الدعم الفني',count:st.S.tickets.filter(x=>x.section===id).length});
  tabs.push({id:'activity',label:t('tab_activity'),count:acts.length});
  return <div>
    <PageHeader crumbs={[{label:listLabel,onClick:()=>go(me.role==='INSTRUCTOR'?'mysections':'sections')},{label:c.code+' / '+sec.no,onClick:iniObj&&tab==='ini'?()=>setIni(null):undefined},...(iniObj&&tab==='ini'?[{label:st.iniName(iniObj,lang)}]:[])]}
      title={<span style={{display:'inline-flex',alignItems:'center',gap:10,flexWrap:'wrap'}}>{(lang==='en'?'Section ':'الشعبة ')+sec.no}<span style={{font:'500 18px var(--font-sans)',color:'var(--text-secondary)'}}>{c.code} · {lang==='en'?c.en:c.ar}</span>{st.isShared(me,sec)&&<StatusBadge status="shared"/>}</span>}
      subtitle={st.programName(sec.program,lang)+' · '+st.branchLabel(sec.branch,lang)+' · '+(lang==='en'?tm.en:tm.ar)+(me.role==='INSTRUCTOR'?'':' · '+t('instructor')+': '+(sec.instructor?st.uname(sec.instructor,lang):t('no_instructor')))}
      actions={<>{st.can(me,'import.section.run')&&(me.role==='SUPER_ADMIN'||sec.instructor===me.id||st.isShared(me,sec))&&<Button variant="secondary" icon="file-up" onClick={()=>go('wizard',{mode:'section',section:id})}>{t('import_list')}</Button>}
        {st.can(me,'enrollment.create')&&<Button icon="user-plus" onClick={()=>{setEnroll(true);setUErr(null)}}>{t('enroll')}</Button>}
        {st.can(me,'section.change_instructor')&&<Button variant="secondary" icon="repeat" onClick={()=>{setNewI('');setChg(true)}}>{t('change_instr')}</Button>}</>}/>
    <SectionMeta sec={sec}/>
    {sec.status&&sec.status!=='active'&&(()=>{const {Alert}=window.DS;const en=lang==='en';const canApprove=st.approvalScope(me).some(x=>x.id===sec.id);const d=sec.decision;
      return <div style={{marginBottom:16}}><Alert tone={sec.status==='rejected'?'danger':sec.status==='returned'?'warning':'info'} title={SEC_ST[sec.status][en?2:1]}>
        <div>{sec.status==='pending'?(en?'Sent by ':'أرسلها ')+st.uname(sec.req.by,lang)+' · '+fmtDateTime(sec.req.at)+(en?'. Stage tracking opens after the coordinator or Super Admin links it to an initiative.':'. يُفتح تسجيل المراحل بعد أن يربطها المنسق أو السوبر أدمن بمبادرة.'):d&&(st.uname(d.by,lang)+' · '+fmtDateTime(d.at)+': '+d.reason)}</div>
        <div style={{display:'flex',gap:8,marginTop:10}}>{canApprove&&sec.status==='pending'&&<Button size="sm" icon="clipboard-check" onClick={()=>setReview(true)}>{en?'Review request':'مراجعة الطلب'}</Button>}
          {sec.status==='returned'&&sec.instructor===me.id&&<Button size="sm" icon="pencil" onClick={()=>setResub(true)}>{en?'Edit and resubmit':'تعديل وإعادة الإرسال'}</Button>}</div>
      </Alert></div>;})()}
    {review&&<ApprovalDialog sec={sec} onClose={()=>setReview(false)} notify={notify}/>}
    {resub&&<SectionRequestDialog sec={sec} onClose={()=>setResub(false)} notify={notify}/>}
    {asg!==undefined&&<AssignDialog sec={sec} assign={asg} onClose={()=>setAsg(undefined)} notify={notify}/>}
    <Panel>
      <div style={{padding:'8px 20px 0'}}><Tabs value={tab} onChange={v=>{setTab(v);if(v!=='ini')setIni(null)}} tabs={tabs}/></div>
      <div style={{padding:20}}>
        {tab==='ini'&&(iniObj?<InitiativeRoster sec={sec} enr={enr} assign={iniObj} onBack={()=>setIni(null)} notify={notify}/>:<InitiativeCards sec={sec} enr={enr} list={inis} onOpen={setIni} onAssign={a=>setAsg(a)}/>)}
        {tab==='roster'&&<RosterTable enr={enr} sec={sec} q={rq} setQ={setRq} f={rf} setF={setRf} onWithdraw={r=>{setWd(r);setReason('');setRErr(null)}}/>}
        {tab==='access'&&<div style={{display:'flex',flexDirection:'column',gap:12}}>
          <div><Button size="sm" icon="share-2" onClick={()=>setGrant(true)}>{t('grant')}</Button></div>
          <DataTable columns={[{key:'u',label:t('grant_to'),render:r=><div style={{display:'flex',gap:8,alignItems:'center'}}><b>{st.uname(r.user,lang)}</b><StatusBadge status="shared"/></div>},{key:'b',label:t('granted_by'),render:r=>st.uname(r.by,lang)},{key:'a',label:t('granted_at'),numeric:true,render:r=>fmtDateTime(r.at)}]} rows={grants}/>
        </div>}
        {tab==='support'&&<TicketList rows={st.S.tickets.filter(x=>x.section===id)} notify={notify} showSection={false}/>}
        {tab==='activity'&&<AuditTable rows={acts}/>}
      </div>
    </Panel>
    <ConfirmDialog open={!!wd} busy={busy} onClose={()=>setWd(null)} onConfirm={doWithdraw} title={wd&&t('withdraw_q',{n:lang==='en'?st.student(wd.student).en:st.student(wd.student).ar})} impact={t('withdraw_impact')} confirmLabel={t('withdraw')}>
      <Input label={t('wd_date')} type="date" defaultValue={st.now().slice(0,10)}/>
      <Input label={t('reason')} required value={reason} onChange={e=>{setReason(e.target.value);setRErr(null)}} onBlur={()=>!reason.trim()&&setRErr(t('required'))} error={rErr}/>
    </ConfirmDialog>
    <Dialog open={enroll} title={t('enroll')} onClose={()=>setEnroll(false)} width={460} footer={<><Button variant="ghost" onClick={()=>setEnroll(false)}>{t('cancel')}</Button><Button loading={busy} onClick={doEnroll}>{t('enroll')}</Button></>}>
      <Input label={t('uid')} required dir="ltr" inputMode="numeric" placeholder="441002315" value={uid} onChange={e=>{setUid(e.target.value);setUErr(null)}} onBlur={()=>uid&&!/^\d{9}$/.test(uid)&&setUErr(t('uid_bad'))} error={uErr} hint={lang==='en'?'9 digits':'9 أرقام'}/>
    </Dialog>
    <ConfirmDialog open={chg} busy={busy} onClose={()=>setChg(false)} onConfirm={()=>newI&&doChange()} title={t('change_instr')} impact={t('instr_impact')} confirmLabel={t('change_instr')}>
      <Select label={t('instructor')} value={newI} onChange={e=>setNewI(e.target.value)} options={[{value:'',label:'—'},...st.S.users.filter(u=>u.role==='INSTRUCTOR'&&u.active&&u.id!==sec.instructor).map(u=>({value:u.id,label:lang==='en'?u.en:u.ar}))]}/>
    </ConfirmDialog>
    <Dialog open={grant} title={t('grant')} onClose={()=>setGrant(false)} width={460} footer={<><Button variant="ghost" onClick={()=>setGrant(false)}>{t('cancel')}</Button><Button onClick={doGrant}>{t('grant')}</Button></>}>
      <Select label={t('grant_to')} value={gUser} onChange={e=>setGUser(e.target.value)} options={[{value:'',label:'—'},...st.S.users.filter(u=>u.role==='INSTRUCTOR'&&u.active&&u.id!==sec.instructor&&!grants.some(g=>g.user===u.id)).map(u=>({value:u.id,label:lang==='en'?u.en:u.ar}))]}/>
    </Dialog>
  </div>;
}

function StudentsScreen(){
  const {DataTable,Input,Select,Icon}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();const [q,setQ]=React.useState('');const [ini,setIni]=React.useState(1);
  const loading=useFakeLoad(ini);
  const rows=st.studentScope(me).filter(s=>(s.uid+s.ar+s.en).toLowerCase().includes(q.toLowerCase()));
  const en=lang==='en';
  const flag=(on,yes,no)=><span style={{display:'inline-flex',alignItems:'center',gap:6,font:'600 12px var(--font-sans)',color:on?'var(--green-700)':'var(--text-muted)'}}><span style={{width:22,height:22,borderRadius:99,display:'inline-flex',alignItems:'center',justifyContent:'center',background:on?'var(--green-100)':'var(--gray-100)'}}><Icon name={on?'check':'minus'} size={13} strokeWidth={3}/></span>{on?yes:no}</span>;
  const P=r=>st.progress(r.id,ini);
  const tot=rows.length,cR=rows.filter(r=>P(r).registered).length,cD=rows.filter(r=>P(r).documented).length,cC=rows.filter(r=>P(r).completed).length;
  return <div>
    <PageHeader title={t('nav_students')} subtitle={t('students_sub')}/>
    <Panel style={{padding:20}}>
      <div style={{display:'flex',gap:12,marginBottom:16,flexWrap:'wrap',alignItems:'flex-end'}}>
        <Input size="sm" icon="search" placeholder={t('uid')+' / '+t('student')} value={q} onChange={e=>setQ(e.target.value)} style={{width:260}}/>
        <Select size="sm" icon="lightbulb" value={ini} onChange={e=>setIni(+e.target.value)} options={st.initiatives.map(i=>({value:i.id,label:en?i.en:i.ar}))} style={{width:240}}/>
        <div style={{flex:1}}/>
        <div style={{display:'flex',gap:16,font:'500 12px var(--font-sans)',color:'var(--text-secondary)'}}>
          <span>{en?'Registered':'سجّلوا'} <b style={{fontFamily:'var(--font-numeric)',color:'var(--text-primary)'}}>{cR}/{tot}</b></span>
          <span>{en?'Documented':'وثّقوا'} <b style={{fontFamily:'var(--font-numeric)',color:'var(--text-primary)'}}>{cD}/{tot}</b></span>
          <span>{en?'Completed':'أنهوا'} <b style={{fontFamily:'var(--font-numeric)',color:'var(--green-700)'}}>{cC}/{tot}</b></span>
        </div>
      </div>
      <DataTable loading={loading} onRowClick={r=>go('student',r.id)} columns={[
        {key:'uid',label:t('uid'),numeric:true,render:r=><b>{r.uid}</b>},{key:'ar',label:t('name_ar')},{key:'en',label:t('name_en')},
        {key:'reg',label:en?'Registered':'قام بالتسجيل',render:r=>flag(P(r).registered,en?'Yes':'نعم',en?'No':'لا')},
        {key:'doc',label:en?'Documented':'قام بالتوثيق',render:r=>flag(P(r).documented,en?'Yes':'نعم',en?'No':'لا')},
        {key:'done',label:en?'Completed':'أنهى المبادرة',render:r=>flag(P(r).completed,en?'Completed':'مكتمل',en?'Not yet':'لم يُنهِ')}]} rows={rows}/>
    </Panel>
  </div>;
}
function StudentDetail({id,notify}){
  const {DataTable,StatusBadge,Card,Tabs,Button}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();const en=lang==='en';
  const s=st.student(id);const vis=s&&st.studentScope(me).some(x=>x.id===id);const [tab,setTab]=React.useState('ini');
  if(!s||!vis)return <RefusalScreen kind="404"/>;
  const scope=new Set(st.sectionScope(me).map(x=>x.id));
  const enr=st.S.enrollments.filter(e=>e.student===id&&scope.has(e.section));
  const act=enr.filter(e=>e.status==='active').map(e=>st.S.sections.find(y=>y.id===e.section));
  const asg=act.flatMap(sec=>st.S.assignments.filter(a=>a.section===sec.id).map(a=>({id:a.id,a,sec,m:st.marks(a,id)})));
  const tks=st.S.tickets.filter(x=>x.student===id&&(me.role==='SUPER_ADMIN'||(x.section?scope.has(x.section):true)));
  const acts=st.S.audit.filter(a=>(a.type==='student'&&a.eid===id)||(a.type==='enrollment'&&enr.some(e=>e.id===a.eid)));
  const facts=[[en?'University ID':'الرقم الجامعي',s.uid,1],[en?'University email':'البريد الجامعي',s.email||'—',1],[en?'Phone':'الجوال',s.phone||'—',1],[en?'Program':'البرنامج',s.program?st.programName(s.program,lang):'—'],[en?'Last sign-in':'آخر دخول',s.lastLogin?fmtDateTime(s.lastLogin):t('never')]];
  return <div>
    <PageHeader crumbs={[{label:t('nav_studentsadmin'),onClick:()=>go('studentsadmin')},{label:s.uid}]} title={<span style={{display:'inline-flex',alignItems:'center',gap:10}}>{s.ar}<StatusBadge status={s.active?'active':'inactive'}/></span>}/>
    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(170px,1fr))',background:'#fff',border:'1px solid var(--border-default)',borderRadius:'var(--radius-lg)',marginBottom:16,overflow:'hidden'}}>
      {facts.map(([l,v,lat],i)=><div key={l} style={{padding:'12px 16px',borderInlineStart:i?'1px solid var(--gray-100)':'none',minWidth:0}}><div style={{font:'500 12px var(--font-sans)',color:'var(--text-secondary)'}}>{l}</div><div style={{font:'700 14px var(--font-sans)',fontFamily:lat?'var(--font-latin)':undefined,marginTop:2,overflow:'hidden',textOverflow:'ellipsis'}}>{v}</div></div>)}
    </div>
    <Panel>
      <div style={{padding:'8px 20px 0'}}><Tabs value={tab} onChange={setTab} tabs={[{id:'ini',label:en?'Initiatives':'المبادرات',count:asg.length},{id:'sec',label:t('enrollments'),count:enr.length},{id:'support',label:en?'Support requests':'طلبات الدعم الفني',count:tks.length},{id:'activity',label:t('tab_activity'),count:acts.length}]}/></div>
      <div style={{padding:20}}>
        {tab==='ini'&&<DataTable columns={[{key:'i',label:en?'Initiative':'المبادرة',render:r=><a href="#" onClick={e=>{e.preventDefault();go('initiative',r.a.ini)}} style={{fontWeight:700,color:'var(--text-primary)'}}>{st.iniName(r.a,lang)}</a>},{key:'s',label:t('course'),render:r=><a href="#" onClick={e=>{e.preventDefault();go('section',r.sec.id)}} style={{color:'var(--track-blue)'}}>{st.course(r.sec.course).code+' / '+r.sec.no}</a>},{key:'st',label:en?'Stages':'المراحل',wrap:true,render:r=><StageTrack stages={r.a.stages} marks={r.m}/>},{key:'p',label:en?'Done':'المنجز',numeric:true,render:r=>r.m.filter(Boolean).length+'/'+r.a.stages.length}]} rows={asg} empty={en?'Not in any initiative':'غير مسجل في أي مبادرة'}/>}
        {tab==='sec'&&<DataTable onRowClick={r=>go('section',r.section)} columns={[{key:'s',label:t('course'),render:r=>{const x=st.S.sections.find(y=>y.id===r.section);return st.course(x.course).code+' / '+x.no}},{key:'b',label:t('branch'),render:r=>st.branchLabel(st.S.sections.find(y=>y.id===r.section).branch,lang)},{key:'i',label:t('instructor'),render:r=>{const x=st.S.sections.find(y=>y.id===r.section);return x.instructor?st.uname(x.instructor,lang):'—'}},{key:'d',label:t('enrolled_on'),numeric:true,render:r=>fmtDate(r.enrolled)},{key:'st',label:t('status'),render:r=><StatusBadge status={r.status==='active'?'active':'withdrawn'}/>}]} rows={enr}/>}
        {tab==='support'&&<TicketList rows={tks} notify={notify}/>}
        {tab==='activity'&&<AuditTable rows={acts} dense/>}
      </div>
    </Panel>
  </div>;
}
const stageFlag=(on,label)=><span style={{display:'inline-flex',alignItems:'center',gap:6,font:'600 12px var(--font-sans)',color:on?'var(--green-700)':'var(--text-muted)',whiteSpace:'nowrap'}}><span style={{width:22,height:22,borderRadius:99,display:'inline-flex',alignItems:'center',justifyContent:'center',background:on?'var(--green-100)':'var(--gray-100)',flex:'none'}}><window.DS.Icon name={on?'check':'minus'} size={13} strokeWidth={3}/></span>{label}</span>;
const acadTone=a=>a==='منتظم'?'active':a==='منسحب'?'withdrawn':'warning';
function SectionMeta({sec}){
  const {lang}=useApp();const st=useStore();const en=lang==='en';const c=st.course(sec.course);const b=st.S.branches.find(x=>x.id===sec.branch);
  const items=[[en?'Campus':'المقر',en?b.en+' ('+(b.gender==='male'?'Male':'Female')+')':b.ar+' ('+(b.gender==='male'?'طلاب':'طالبات')+')'],[en?'Degree':'الدرجة',sec.degree],[en?'Course':'اسم المقرر',en?c.en:c.ar],[en?'Activity':'النشاط',sec.activity],[en?'Section':'الشعبة',sec.no]];
  return <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(150px,1fr))',gap:0,background:'#fff',border:'1px solid var(--border-default)',borderRadius:'var(--radius-lg)',marginBottom:16,overflow:'hidden'}}>
    {items.map(([l,v],i)=><div key={l} style={{padding:'12px 16px',borderInlineStart:i?'1px solid var(--gray-100)':'none'}}><div style={{font:'500 12px var(--font-sans)',color:'var(--text-secondary)'}}>{l}</div><div style={{font:'700 14px var(--font-sans)',color:'var(--text-primary)',marginTop:2,fontFamily:i===4?'var(--font-numeric)':undefined}}>{v}</div></div>)}
  </div>;
}
function RosterTable({enr,sec,q,setQ,f,setF,onWithdraw}){
  const {DataTable,StatusBadge,Button,Input,Tabs}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();const en=lang==='en';
  const rows=enr.map((e,i)=>({...e,_i:e.seq||i+1,_s:st.student(e.student)})).sort((a,b)=>a._i-b._i).filter(r=>(f==='all'||r.status===f)&&(r._s.uid+r._s.ar+r._s.en).toLowerCase().includes(q.toLowerCase()));
  const nA=enr.filter(e=>e.status==='active').length,nW=enr.length-nA;
  return <div style={{display:'flex',flexDirection:'column',gap:14}}>
    <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:12,flexWrap:'wrap'}}>
      <Tabs variant="segmented" value={f} onChange={setF} tabs={[{id:'all',label:t('all'),count:enr.length},{id:'active',label:en?'Enrolled':'مسجّل',count:nA},{id:'withdrawn',label:en?'Withdrawn':'مسحوب',count:nW}]}/>
      <div style={{display:'flex',gap:8,alignItems:'center'}}><Input size="sm" icon="search" placeholder={en?'Student no. / name':'رقم الطالب / الاسم'} value={q} onChange={e=>setQ(e.target.value)} style={{width:240}}/><Button size="sm" variant="secondary" icon="download">{t('export')}</Button></div>
    </div>
    <DataTable dense columns={[
      {key:'_i',label:en?'Seq.':'تسلسل',numeric:true,width:60,render:r=><span style={{color:'var(--text-secondary)'}}>{r._i}</span>},
      {key:'uid',label:en?'Student no.':'رقم الطالب',numeric:true,render:r=><b>{r._s.uid}</b>},
      {key:'ar',label:en?'Student name':'اسم الطالب',wrap:true,render:r=><a href="#" onClick={e=>{e.preventDefault();go('student',r._s.id)}} style={{fontWeight:600,color:'var(--text-primary)'}}>{r._s.ar}</a>},
      {key:'acad',label:en?'Status':'الحالة',render:r=>r.status==='withdrawn'?<div><StatusBadge status="withdrawn">{en?'Withdrawn':'مسحوب من الشعبة'}</StatusBadge><div style={{font:'400 11px var(--font-sans)',color:'var(--text-secondary)',marginTop:4,whiteSpace:'normal'}}>{fmtDate(r.withdrawn)} · {r.reason}</div></div>:<StatusBadge status={acadTone(r.acad||'منتظم')}>{r.acad||'منتظم'}</StatusBadge>},
      {key:'x',label:'',render:r=>r.status==='active'&&st.can(me,'enrollment.withdraw')&&<Button size="sm" variant="ghost" icon="user-minus" onClick={()=>onWithdraw(r)}>{t('withdraw')}</Button>}]} rows={rows} empty={en?'No students match':'لا يوجد طلبة مطابقون'}/>
  </div>;
}
function InitiativeCards({sec,enr,list,onOpen,onAssign}){
  const {Icon,ProgressBar,EmptyState,Button}=window.DS;const {lang,me}=useApp();const st=useStore();const en=lang==='en';
  const act=enr.filter(e=>e.status==='active');
  const add=me.role==='SUPER_ADMIN'&&<Button size="sm" icon="plus" onClick={()=>onAssign(null)}>{en?'Assign initiative':'إسناد مبادرة'}</Button>;
  if(list.length===0)return <EmptyState icon="lightbulb" title={en?'This section is not part of any initiative':'هذه الشعبة غير مسندة لأي مبادرة'} message={en?'The Super Admin assigns initiatives to sections and defines their statuses.':'يُسند مسؤول النظام المبادرات إلى الشعب ويحدد حالاتها.'} action={add}/>;
  return <div style={{display:'flex',flexDirection:'column',gap:14}}>
    {add&&<div>{add}</div>}
    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(300px,1fr))',gap:14}}>
    {list.map(a=>{const n=act.length||1;const M=act.map(e=>st.marks(a,e.student));const last=a.stages.length-1;const done=M.filter(m=>m[last]).length;
      return <div key={a.id} style={{position:'relative'}}><button onClick={()=>onOpen(a.id)} style={{width:'100%',textAlign:'start',font:'inherit',cursor:'pointer',background:'#fff',border:'1px solid var(--border-default)',borderRadius:'var(--radius-lg)',padding:18,display:'flex',flexDirection:'column',gap:14,boxShadow:'var(--shadow-card)'}}>
        <div style={{display:'flex',alignItems:'center',gap:12}}>
          <span style={{width:40,height:40,borderRadius:10,background:'var(--green-50)',display:'flex',alignItems:'center',justifyContent:'center'}}><Icon name="lightbulb" size={20} color="var(--track-green)"/></span>
          <div style={{flex:1,minWidth:0}}><div style={{font:'700 16px var(--font-sans)',color:'var(--text-primary)'}}>{st.iniName(a,lang)}</div><div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{act.length} {en?'students':'طالب'} · {a.stages.length} {en?'statuses':'حالات'}</div></div>
          <Icon name={en?'chevron-right':'chevron-left'} size={18} color="var(--text-muted)"/>
        </div>
        <div style={{display:'grid',gridTemplateColumns:'repeat('+a.stages.length+',1fr)',gap:8}}>
          {a.stages.map((l,i)=>{const v=M.filter(m=>m[i]).length;return <div key={i} style={{background:'var(--gray-50)',borderRadius:8,padding:'8px 10px',minWidth:0}}><div style={{font:'700 17px var(--font-numeric)',color:i===last?'var(--green-700)':'var(--track-blue)'}}>{v}<span style={{font:'500 12px var(--font-numeric)',color:'var(--text-muted)'}}>/{act.length}</span></div><div style={{font:'500 12px var(--font-sans)',color:'var(--text-secondary)',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{l}</div></div>;})}
        </div>
        <ProgressBar value={Math.round(done/n*100)}/>
      </button>
      {me.role==='SUPER_ADMIN'&&<button onClick={()=>onAssign(a)} aria-label={en?'Edit statuses':'تعديل الحالات'} title={en?'Edit statuses':'تعديل الحالات'} style={{position:'absolute',top:14,insetInlineEnd:40,width:30,height:30,border:'none',borderRadius:8,background:'var(--gray-100)',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',color:'var(--track-blue)'}}><Icon name="settings-2" size={16}/></button>}
      </div>;})}
    </div>
  </div>;
}
function AssignDialog({sec,assign,onClose,notify}){
  const {Dialog,Button,Select,Input,Icon,Alert}=window.DS;const {lang,me}=useApp();const st=useStore();const en=lang==='en';
  const used=st.S.assignments.filter(a=>a.section===sec.id).map(a=>a.ini);
  const avail=st.initiatives.filter(i=>assign?i.id===assign.ini:!used.includes(i.id));
  const [ini,setIni]=React.useState(assign?assign.ini:(avail[0]&&avail[0].id));
  const [stages,setStages]=React.useState(assign?assign.stages.slice():(st.TPL[avail[0]&&avail[0].id]||['تسجيل الطالب','التوثيق','الانتهاء']).slice());
  const [err,setErr]=React.useState(null);
  const pick=v=>{setIni(+v);if(!assign)setStages((st.TPL[+v]||['تسجيل الطالب','التوثيق','الانتهاء']).slice());};
  const save=()=>{const clean=stages.map(x=>x.trim()).filter(Boolean);if(clean.length===0){setErr(en?'Add at least one status.':'أضف حالة واحدة على الأقل.');return;}
    if(assign){const old=assign.stages.slice();assign.stages=clean;st.audit(me.id,'section.initiative_updated','section',sec.id,{stages:old},{stages:clean});}
    else{const a={id:Date.now(),section:sec.id,ini,stages:clean};st.S.assignments.push(a);st.audit(me.id,'section.initiative_assigned','section',sec.id,null,{initiative:st.iniName(a,'ar'),stages:clean});}
    st.emit();onClose();notify(en?'Saved':'تم الحفظ');};
  const mv=(i,d)=>{const n=stages.slice();const j=i+d;if(j<0||j>=n.length)return;[n[i],n[j]]=[n[j],n[i]];setStages(n);};
  return <Dialog title={assign?(en?'Edit initiative statuses':'تعديل حالات المبادرة'):(en?'Assign initiative to section':'إسناد مبادرة للشعبة')} subtitle={(en?'Section ':'الشعبة ')+sec.no+' · '+(en?st.course(sec.course).en:st.course(sec.course).ar)} onClose={onClose} width={560}
    footer={<><Button variant="ghost" onClick={onClose}>{en?'Cancel':'إلغاء'}</Button><Button onClick={save} disabled={!ini}>{en?'Save':'حفظ'}</Button></>}>
    <div style={{display:'flex',flexDirection:'column',gap:14}}>
      {avail.length===0?<Alert tone="info">{en?'All initiatives are already assigned to this section.':'جميع المبادرات مسندة لهذه الشعبة.'}</Alert>:
      <Select label={en?'Initiative':'المبادرة'} value={ini} onChange={e=>pick(e.target.value)} options={avail.map(i=>({value:i.id,label:en?i.en:i.ar}))}/>}
      <div>
        <div style={{font:'600 13px var(--font-sans)',marginBottom:4}}>{en?'Student statuses in this initiative':'حالات الطالب في هذه المبادرة'}</div>
        <div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)',marginBottom:10}}>{en?'Each status becomes a column the instructor ticks for every student, in order. The last one means completion.':'كل حالة تصبح عمودًا يؤشّر عليه المدرس لكل طالب بالترتيب، والحالة الأخيرة تعني إنهاء المبادرة.'}</div>
        <div style={{display:'flex',flexDirection:'column',gap:8}}>
          {stages.map((x,i)=><div key={i} style={{display:'flex',gap:8,alignItems:'center'}}>
            <span style={{width:26,height:26,borderRadius:99,background:i===stages.length-1?'var(--green-100)':'var(--blue-50)',color:i===stages.length-1?'var(--green-700)':'var(--track-blue)',display:'flex',alignItems:'center',justifyContent:'center',font:'700 12px var(--font-numeric)',flex:'none'}}>{i+1}</span>
            <input aria-label={(en?'Status ':'الحالة ')+(i+1)} value={x} onChange={e=>{const n=stages.slice();n[i]=e.target.value;setStages(n);setErr(null);}} style={{...nativeStyle,flex:1}}/>
            <button onClick={()=>mv(i,-1)} aria-label="up" style={{border:'1px solid var(--border-default)',background:'#fff',borderRadius:8,width:32,height:32,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}><Icon name="arrow-up" size={15}/></button>
            <button onClick={()=>mv(i,1)} aria-label="down" style={{border:'1px solid var(--border-default)',background:'#fff',borderRadius:8,width:32,height:32,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}><Icon name="arrow-down" size={15}/></button>
            <button onClick={()=>setStages(stages.filter((_,j)=>j!==i))} aria-label={en?'Remove':'حذف'} style={{border:'none',background:'var(--red-50)',color:'var(--red-600)',borderRadius:8,width:32,height:32,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}><Icon name="trash-2" size={15}/></button>
          </div>)}
        </div>
        <div style={{marginTop:10}}><Button size="sm" variant="secondary" icon="plus" onClick={()=>setStages([...stages,''])}>{en?'Add status':'إضافة حالة'}</Button></div>
        {err&&<div role="alert" style={{font:'400 12px var(--font-sans)',color:'var(--red-600)',marginTop:8}}>{err}</div>}
      </div>
    </div>
  </Dialog>;
}
function InitiativeRoster({sec,enr,assign,onBack,notify}){
  const {DataTable,Input,Tabs,Button,StatusBadge,Alert}=window.DS;const {t,lang,go,me}=useApp();const st=useStore();const en=lang==='en';
  const [q,setQ]=React.useState('');const [f,setF]=React.useState('all');
  const canEdit=me.role==='INSTRUCTOR'&&(sec.instructor===me.id||st.isShared(me,sec));
  const all=enr.filter(e=>e.status==='active').map((e,i)=>({...e,_i:e.seq||i+1,_s:st.student(e.student),_m:st.marks(assign,e.student)})).sort((a,b)=>a._i-b._i);
  const lvl=r=>r._m.filter(Boolean).length;
  const rows=all.filter(r=>(f==='all'||lvl(r)===+f)&&(r._s.uid+r._s.ar).toLowerCase().includes(q.toLowerCase()));
  const toggle=(r,i)=>{const was=r._m.slice();const now=st.setMark(assign,r.student,i,!r._m[i]);st.audit(me.id,'student.stage_updated','student',r.student,{initiative:st.iniName(assign,'ar'),stages:assign.stages.filter((_,k)=>was[k])},{stages:assign.stages.filter((_,k)=>now[k])});st.emit();};
  const segs=[{id:'all',label:t('all'),count:all.length},{id:'0',label:en?'None yet':'لم يبدأ',count:all.filter(r=>lvl(r)===0).length},...assign.stages.map((s,i)=>({id:String(i+1),label:s,count:all.filter(r=>lvl(r)===i+1).length}))];
  return <div style={{display:'flex',flexDirection:'column',gap:14}}>
    <div style={{display:'flex',alignItems:'center',gap:10,flexWrap:'wrap'}}><Button size="sm" variant="ghost" icon={en?'arrow-left':'arrow-right'} onClick={onBack}>{en?'Initiatives':'المبادرات'}</Button><span style={{font:'700 16px var(--font-sans)'}}>{st.iniName(assign,lang)}</span>
      {!canEdit&&<span style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>· {en?'Read only — the section instructor updates statuses':'للعرض فقط — يحدّث مدرس الشعبة الحالات'}</span>}</div>
    <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:12,flexWrap:'wrap'}}>
      <Tabs variant="segmented" value={f} onChange={setF} tabs={segs}/>
      <div style={{display:'flex',gap:8}}><Input size="sm" icon="search" placeholder={en?'Student no. / name':'رقم الطالب / الاسم'} value={q} onChange={e=>setQ(e.target.value)} style={{width:220}}/><Button size="sm" variant="secondary" icon="download">{t('export')}</Button></div>
    </div>
    {canEdit&&<Alert tone="info" icon="mouse-pointer-click">{en?'Tick a status to record it. Ticking a later status fills the earlier ones; changes save automatically.':'اضغط على الحالة لتسجيلها. التأشير على حالة لاحقة يؤشّر ما قبلها تلقائيًا، والحفظ تلقائي.'}</Alert>}
    <DataTable dense columns={[
      {key:'_i',label:en?'Seq.':'تسلسل',numeric:true,width:60,render:r=><span style={{color:'var(--text-secondary)'}}>{r._i}</span>},
      {key:'uid',label:en?'Student no.':'رقم الطالب',numeric:true,render:r=><b>{r._s.uid}</b>},
      {key:'ar',label:en?'Student name':'اسم الطالب',wrap:true,render:r=><span style={{fontWeight:600}}>{r._s.ar}</span>},
      {key:'acad',label:en?'Status':'الحالة',render:r=><StatusBadge status={acadTone(r.acad||'منتظم')}>{r.acad||'منتظم'}</StatusBadge>},
      ...assign.stages.map((s,i)=>({key:'st'+i,label:s,render:r=>canEdit?<button role="checkbox" aria-checked={r._m[i]} aria-label={s+' — '+r._s.ar} onClick={()=>toggle(r,i)} style={{border:'none',background:'none',padding:0,cursor:'pointer'}}>{stageFlag(r._m[i],r._m[i]?(en?'Done':'تم'):(en?'Not yet':'لم يتم'))}</button>:stageFlag(r._m[i],r._m[i]?(en?'Done':'تم'):(en?'Not yet':'لم يتم'))}))]} rows={rows} empty={en?'No students match':'لا يوجد طلبة مطابقون'}/>
  </div>;
}
Object.assign(window,{stageFlag,SectionsScreen,SectionDetail,StudentsScreen,StudentDetail,RosterTable,InitiativeCards,InitiativeRoster,AssignDialog,SectionMeta});
