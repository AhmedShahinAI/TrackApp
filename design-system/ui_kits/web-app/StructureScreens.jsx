function TermsScreen({notify}){
  const {Button,DataTable,StatusBadge,Dialog,Input,Select,Alert}=window.DS;const {t,me,lang}=useApp();const st=useStore();
  const [edit,setEdit]=React.useState(null);const [conf,setConf]=React.useState(null);const [busy,setBusy]=React.useState(false);const [newState,setNewState]=React.useState('active');
  const cur=st.currentTerm();
  const doConf=()=>{setBusy(true);setTimeout(()=>{const tm=conf.term;
    if(conf.kind==='current'){const o=st.currentTerm();if(o)o.current=false;tm.current=true;st.audit(me.id,'term.current_set','term',tm.id,{current:o&&o.ar},{current:tm.ar});}
    else{const old=tm.status;tm.status=newState;st.audit(me.id,'term.state_changed','term',tm.id,{status:old},{status:newState});}
    st.emit();setBusy(false);setConf(null);notify(t('term_saved'));},500);};
  const next={draft:['active'],active:['closed','draft'],closed:['archived','active'],archived:[]};
  return <div>
    <PageHeader title={t('nav_terms')} subtitle={t('terms_sub')} actions={<Button icon="calendar-plus" onClick={()=>setEdit({})}>{t('new_term')}</Button>}/>
    <Panel style={{padding:20}}>
      <DataTable columns={[
        {key:'n',label:t('nav_terms'),render:r=><div style={{display:'flex',alignItems:'center',gap:8}}><b>{lang==='en'?r.en:r.ar}</b>{r.current&&<StatusBadge status="current"/>}</div>},
        {key:'year',label:t('year'),numeric:true},{key:'sem',label:t('semester'),render:r=>t('sem_'+r.semester)},
        {key:'start',label:t('start'),numeric:true,render:r=>fmtDate(r.start)},{key:'end',label:t('end'),numeric:true,render:r=>fmtDate(r.end)},
        {key:'sc',label:t('sections_n'),numeric:true,render:r=>st.S.sections.filter(s=>s.term===r.id).length},
        {key:'status',label:t('status'),render:r=><StatusBadge status={r.status}/>},
        {key:'x',label:'',render:r=><div style={{display:'flex',gap:6}}>
          <Button size="sm" variant="ghost" icon="pencil" onClick={()=>setEdit(r)}>{t('edit')}</Button>
          {!r.current&&r.status!=='archived'&&<Button size="sm" variant="ghost" icon="star" onClick={()=>setConf({kind:'current',term:r})}>{t('set_current')}</Button>}
          {next[r.status].length>0&&<Button size="sm" variant="ghost" icon="repeat" onClick={()=>{setNewState(next[r.status][0]);setConf({kind:'state',term:r})}}>{t('change_state')}</Button>}
        </div>}]} rows={st.S.terms}/>
    </Panel>
    {edit&&<TermDialog term={edit} onClose={()=>setEdit(null)} notify={notify}/>}
    <ConfirmDialog open={!!conf} busy={busy} onClose={()=>setConf(null)} onConfirm={doConf} tone={conf&&conf.kind==='state'?'danger':'info'}
      title={conf&&(conf.kind==='current'?t('current_q',{n:lang==='en'?conf.term.en:conf.term.ar}):t('state_q',{s:t('st_'+newState)}))}
      impact={conf&&(conf.kind==='current'?t('current_impact',{o:cur?(lang==='en'?cur.en:cur.ar):'—'}):t('state_impact'))}>
      {conf&&conf.kind==='state'&&<Select label={t('status')} value={newState} onChange={e=>setNewState(e.target.value)} options={next[conf.term.status].map(s=>({value:s,label:t('st_'+s)}))}/>}
    </ConfirmDialog>
  </div>;
}
function TermDialog({term,onClose,notify}){
  const {Dialog,Button,Input,Select}=window.DS;const {t,me}=useApp();const st=useStore();const isNew=!term.id;
  const [f,setF]=React.useState({ar:term.ar||'',en:term.en||'',year:term.year||'2027/2028',semester:term.semester||'first',start:term.start||'2027-08-29',end:term.end||'2028-01-06'});const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);
  const set=(k,v)=>setF({...f,[k]:v});
  const save=()=>{const n={ar:f.ar?null:t('required'),en:f.en?null:t('required'),end:f.end<=f.start?t('end_after'):null};setE(n);if(Object.values(n).some(Boolean))return;setBusy(true);setTimeout(()=>{
    if(isNew){const x={id:Date.now(),...f,status:'draft',current:false};st.S.terms.push(x);st.audit(me.id,'term.created','term',x.id,null,f);}else{const old={ar:term.ar,start:term.start,end:term.end};Object.assign(term,f);st.audit(me.id,'term.updated','term',term.id,old,{ar:f.ar,start:f.start,end:f.end});}
    st.emit();setBusy(false);onClose();notify(t('term_saved'));},500);};
  return <Dialog title={isNew?t('new_term'):t('edit')} onClose={onClose} width={560} footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{t('save')}</Button></>}>
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
      <Input label={t('name_ar')} required value={f.ar} onChange={x=>set('ar',x.target.value)} error={e.ar}/>
      <Input label={t('name_en')} required dir="ltr" value={f.en} onChange={x=>set('en',x.target.value)} error={e.en}/>
      <Input label={t('year')} dir="ltr" value={f.year} onChange={x=>set('year',x.target.value)}/>
      <Select label={t('semester')} value={f.semester} onChange={x=>set('semester',x.target.value)} options={['first','second','summer'].map(s=>({value:s,label:t('sem_'+s)}))}/>
      <Input label={t('start')} type="date" value={f.start} onChange={x=>set('start',x.target.value)}/>
      <Input label={t('end')} type="date" value={f.end} onChange={x=>set('end',x.target.value)} error={e.end}/>
    </div>
  </Dialog>;
}

function BranchesScreen({notify}){
  const {Button,DataTable,StatusBadge,Dialog,Input,Select,Switch,Alert}=window.DS;const {t,me,lang}=useApp();const st=useStore();
  const [edit,setEdit]=React.useState(null);const [f,setF]=React.useState({});const [busy,setBusy]=React.useState(false);
  const open=b=>{setEdit(b);setF({ar:b.ar||'',en:b.en||'',gender:b.gender||'male',active:b.active!==false});};
  const save=()=>{if(!f.ar||!f.en)return;setBusy(true);setTimeout(()=>{
    if(!edit.id){const b={id:Date.now(),...f};st.S.branches.push(b);st.audit(me.id,'branch.created','branch',b.id,null,f);}else{const old={ar:edit.ar,en:edit.en,active:edit.active};Object.assign(edit,f);st.audit(me.id,'branch.updated','branch',edit.id,old,f);}
    st.emit();setBusy(false);setEdit(null);notify(t('branch_saved'));},500);};
  return <div>
    <PageHeader title={t('nav_branches')} subtitle={t('branches_sub')} actions={<Button icon="plus" onClick={()=>open({})}>{t('new_branch')}</Button>}/>
    <Panel style={{padding:20}}>
      <DataTable columns={[
        {key:'n',label:t('branch'),render:r=><b>{st.branchLabel(r.id,lang)}</b>},
        {key:'ar',label:t('campus_ar')},{key:'en',label:t('campus_en')},
        {key:'g',label:t('gender'),render:r=>t(r.gender)},
        {key:'c',label:t('coordinators'),wrap:true,render:r=>st.S.users.filter(u=>u.branches.includes(r.id)).map(u=>st.uname(u.id,lang)).join('، ')||'—'},
        {key:'s',label:t('sections_n'),numeric:true,render:r=>st.S.sections.filter(s=>s.branch===r.id).length},
        {key:'a',label:t('status'),render:r=><StatusBadge status={r.active?'active':'inactive'}/>},
        {key:'x',label:'',render:r=><Button size="sm" variant="ghost" icon="pencil" onClick={()=>open(r)}>{t('edit')}</Button>}]} rows={st.S.branches}/>
    </Panel>
    <Dialog open={!!edit} title={edit&&edit.id?t('edit'):t('new_branch')} onClose={()=>setEdit(null)} width={520} footer={<><Button variant="ghost" onClick={()=>setEdit(null)}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{t('save')}</Button></>}>
      <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
        <Input label={t('campus_ar')} required value={f.ar} onChange={x=>setF({...f,ar:x.target.value})} error={f.ar===''?t('required'):null}/>
        <Input label={t('campus_en')} required dir="ltr" value={f.en} onChange={x=>setF({...f,en:x.target.value})} error={f.en===''?t('required'):null}/>
        <Select label={t('gender')} value={f.gender} onChange={x=>setF({...f,gender:x.target.value})} options={[{value:'male',label:t('male')},{value:'female',label:t('female')}]}/>
        <div style={{display:'flex',alignItems:'flex-end',paddingBottom:8}}><Switch label={t('active_flag')} checked={f.active} onChange={v=>setF({...f,active:v})}/></div>
        {edit&&edit.id&&<Alert tone="info" style={{gridColumn:'1 / -1'}}>{t('no_delete')}</Alert>}
      </div>
    </Dialog>
  </div>;
}

function CoursesScreen({notify}){
  const {Button,DataTable,Dialog,Input}=window.DS;const {t,me,lang}=useApp();const st=useStore();
  const [edit,setEdit]=React.useState(null);const [f,setF]=React.useState({});const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);
  const open=c=>{setEdit(c);setF({code:c.code||'',ar:c.ar||'',en:c.en||''});setE({});};
  const save=()=>{const n={code:!f.code?t('required'):st.S.courses.some(c=>c.code.toUpperCase()===f.code.toUpperCase()&&c.id!==edit.id)?t('code_taken'):null,ar:f.ar?null:t('required'),en:f.en?null:t('required')};setE(n);if(Object.values(n).some(Boolean))return;
    setBusy(true);setTimeout(()=>{if(!edit.id){const c={id:Date.now(),...f,code:f.code.toUpperCase()};st.S.courses.push(c);st.audit(me.id,'course.created','course',c.id,null,f);}else{const old={...edit};Object.assign(edit,f);st.audit(me.id,'course.updated','course',edit.id,{code:old.code,ar:old.ar},f);}
    st.emit();setBusy(false);setEdit(null);notify(t('course_saved'));},500);};
  return <div>
    <PageHeader title={t('nav_courses')} subtitle={t('courses_sub')} actions={<Button icon="plus" onClick={()=>open({})}>{t('new_course')}</Button>}/>
    <Panel style={{padding:20}}>
      <DataTable columns={[{key:'code',label:t('code'),numeric:true,render:r=><b>{r.code}</b>},{key:'ar',label:t('name_ar')},{key:'en',label:t('name_en')},{key:'p',label:lang==='en'?'Programs':'البرامج',wrap:true,render:r=>(r.programs||[]).map(x=>st.programName(x,lang)).join('، ')||'—'},{key:'s',label:t('sections_n'),numeric:true,render:r=>st.S.sections.filter(s=>s.course===r.id).length},{key:'x',label:'',render:r=><Button size="sm" variant="ghost" icon="pencil" onClick={()=>open(r)}>{t('edit')}</Button>}]} rows={st.S.courses}/>
    </Panel>
    <Dialog open={!!edit} title={edit&&edit.id?t('edit'):t('new_course')} onClose={()=>setEdit(null)} width={520} footer={<><Button variant="ghost" onClick={()=>setEdit(null)}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{t('save')}</Button></>}>
      <div style={{display:'grid',gridTemplateColumns:'1fr',gap:14}}>
        <Input label={t('code')} required dir="ltr" value={f.code} onChange={x=>setF({...f,code:x.target.value})} error={e.code}/>
        <Input label={t('name_ar')} required value={f.ar} onChange={x=>setF({...f,ar:x.target.value})} error={e.ar}/>
        <Input label={t('name_en')} required dir="ltr" value={f.en} onChange={x=>setF({...f,en:x.target.value})} error={e.en}/>
      </div>
    </Dialog>
  </div>;
}
function ProgramsScreen({notify}){
  const {Button,DataTable,StatusBadge,Dialog,Input,Select,Switch,Alert}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();const en=lang==='en';
  const [edit,setEdit]=React.useState(null);const [f,setF]=React.useState({});const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);const [imp,setImp]=React.useState(false);
  const open=p=>{setEdit(p);setF({code:p.code||'',ar:p.ar||'',en:p.en||'',unit:p.unit||'admin',active:p.active!==false});setE({});};
  const unitL=u=>u==='tech'?(en?'Technical':'البرامج التقنية'):(en?'Administrative':'البرامج الإدارية');
  const save=()=>{const n={code:!f.code?t('required'):st.S.programs.some(p=>p.code.toUpperCase()===f.code.toUpperCase()&&p.id!==edit.id)?t('code_taken'):null,ar:f.ar?null:t('required'),en:f.en?null:t('required')};setE(n);if(Object.values(n).some(Boolean))return;
    setBusy(true);setTimeout(()=>{if(!edit.id){const p={id:Date.now(),...f,code:f.code.toUpperCase()};st.S.programs.push(p);st.audit(me.id,'program.created','program',p.id,null,{code:p.code,name_ar:p.ar});}else{const old={code:edit.code,ar:edit.ar,active:edit.active};Object.assign(edit,f,{code:f.code.toUpperCase()});st.audit(me.id,'program.updated','program',edit.id,old,{code:edit.code,ar:edit.ar,active:edit.active});}
    st.emit();setBusy(false);setEdit(null);notify(en?'Program saved':'تم حفظ البرنامج');},500);};
  return <div>
    <PageHeader title={t('nav_programs')} subtitle={en?'Every section in the college belongs to one academic program.':'كل شعبة في الكلية مرتبطة ببرنامج أكاديمي واحد.'} actions={<><Button variant="secondary" icon="file-spreadsheet" onClick={()=>setImp(true)}>{en?'Import courses from Excel':'استيراد المقررات من Excel'}</Button><Button icon="plus" onClick={()=>open({})}>{en?'New program':'برنامج جديد'}</Button></>}/>
    <Panel style={{padding:20}}>
      <DataTable columns={[{key:'code',label:t('code'),numeric:true,render:r=><b>{r.code}</b>},{key:'ar',label:t('name_ar')},{key:'en',label:t('name_en')},
        {key:'u',label:en?'Specialty unit':'الوحدة التخصصية',render:r=>unitL(r.unit)},
        {key:'cc',label:en?'Courses':'المقررات',numeric:true,render:r=>st.S.courses.filter(c=>(c.programs||[]).includes(r.id)).length},
        {key:'s',label:t('sections_n'),numeric:true,render:r=>st.S.sections.filter(s=>s.program===r.id).length},
        {key:'a',label:t('status'),render:r=><StatusBadge status={r.active?'active':'inactive'}/>},
        {key:'x',label:'',render:r=><Button size="sm" variant="ghost" icon="pencil" onClick={()=>open(r)}>{t('edit')}</Button>}]} rows={st.S.programs}/>
    </Panel>
    <Dialog open={!!edit} title={edit&&edit.id?t('edit'):(en?'New program':'برنامج جديد')} onClose={()=>setEdit(null)} width={560} footer={<><Button variant="ghost" onClick={()=>setEdit(null)}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{t('save')}</Button></>}>
      <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
        <Input label={t('code')} required dir="ltr" value={f.code} onChange={x=>setF({...f,code:x.target.value})} error={e.code}/>
        <Select label={en?'Specialty unit':'الوحدة التخصصية'} value={f.unit} onChange={x=>setF({...f,unit:x.target.value})} options={[{value:'admin',label:unitL('admin')},{value:'tech',label:unitL('tech')}]}/>
        <Input label={t('name_ar')} required value={f.ar} onChange={x=>setF({...f,ar:x.target.value})} error={e.ar}/>
        <Input label={t('name_en')} required dir="ltr" value={f.en} onChange={x=>setF({...f,en:x.target.value})} error={e.en}/>
        <div style={{gridColumn:'1 / -1'}}><Switch label={t('active_flag')} checked={f.active} onChange={v=>setF({...f,active:v})}/></div>
        {edit&&edit.id&&<Alert tone="info" style={{gridColumn:'1 / -1'}}>{en?'Programs linked to sections cannot be deleted; deactivate instead.':'لا يمكن حذف برنامج مرتبط بشعب؛ يمكن إيقافه بدلاً من ذلك.'}</Alert>}
      </div>
    </Dialog>
    {imp&&<CourseImportDialog onClose={()=>setImp(false)} notify={notify}/>}
  </div>;
}
Object.assign(window,{TermsScreen,BranchesScreen,CoursesScreen,ProgramsScreen});
