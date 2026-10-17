function StagesEditor({stages,setStages}){
  const {Icon,Button}=window.DS;const {lang}=useApp();const en=lang==='en';
  const mv=(i,d)=>{const n=stages.slice();const j=i+d;if(j<0||j>=n.length)return;[n[i],n[j]]=[n[j],n[i]];setStages(n);};
  const sq={border:'1px solid var(--border-default)',background:'#fff',borderRadius:8,width:32,height:32,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',flex:'none'};
  return <div>
    <div style={{display:'flex',flexDirection:'column',gap:8}}>
      {stages.map((x,i)=><div key={i} style={{display:'flex',gap:8,alignItems:'center'}}>
        <span style={{width:26,height:26,borderRadius:99,background:i===stages.length-1?'var(--green-100)':'var(--blue-50)',color:i===stages.length-1?'var(--green-700)':'var(--track-blue)',display:'flex',alignItems:'center',justifyContent:'center',font:'700 12px var(--font-numeric)',flex:'none'}}>{i+1}</span>
        <input aria-label={(en?'Status ':'الحالة ')+(i+1)} value={x} placeholder={en?'Status name':'اسم الحالة'} onChange={e=>{const n=stages.slice();n[i]=e.target.value;setStages(n);}} style={{...nativeStyle,flex:1,minWidth:0}}/>
        <button onClick={()=>mv(i,-1)} aria-label={en?'Move up':'تحريك لأعلى'} style={sq}><Icon name="arrow-up" size={15}/></button>
        <button onClick={()=>mv(i,1)} aria-label={en?'Move down':'تحريك لأسفل'} style={sq}><Icon name="arrow-down" size={15}/></button>
        <button onClick={()=>setStages(stages.filter((_,j)=>j!==i))} aria-label={en?'Remove':'حذف'} style={{...sq,border:'none',background:'var(--red-50)',color:'var(--red-600)'}}><Icon name="trash-2" size={15}/></button>
      </div>)}
    </div>
    <div style={{marginTop:10}}><Button size="sm" variant="secondary" icon="plus" onClick={()=>setStages([...stages,''])}>{en?'Add status':'إضافة حالة'}</Button></div>
  </div>;
}

function SectionFilePicker({term,termSecs,onAdd}){
  const {Button,Alert,DataTable,StatusBadge,Icon}=window.DS;const {lang}=useApp();const st=useStore();const en=lang==='en';
  const ref=React.useRef(null);const [res,setRes]=React.useState(null);const [err,setErr]=React.useState(null);const [name,setName]=React.useState('');
  const match=p=>{const H=p.headers.map(h=>String(h||''));const iNo=H.findIndex(h=>/رقم الشعبة|^الشعبة$|section/i.test(h));const iC=H.findIndex(h=>/رمز المقرر|course code|^code$/i.test(h));const iB=H.findIndex(h=>/الفرع|المقر|branch|campus/i.test(h));
    let items=[];
    if(p.meta&&p.meta.section&&iNo<0)items=[{n:1,no:p.meta.section,code:'',course:p.meta.course||'',branch:p.meta.campus||''}];
    else{if(iNo<0)throw new Error('cols');items=p.rows.map((r,k)=>({n:k+2,no:String(r[iNo]||'').trim(),code:iC>=0?String(r[iC]||'').trim():'',branch:iB>=0?String(r[iB]||'').trim():''}));}
    const seen={};return items.map(it=>{let c=termSecs.filter(s=>s.no===it.no);
      if(it.code)c=c.filter(s=>st.course(s.course).code.toUpperCase()===it.code.toUpperCase());
      if(it.course)c=c.filter(s=>{const x=st.course(s.course);return x.ar===it.course||x.en===it.course;});
      if(it.branch&&c.length>1)c=c.filter(s=>{const b=st.S.branches.find(x=>x.id===s.branch);return it.branch.includes(b.ar)||it.branch.toLowerCase().includes(b.en.toLowerCase());});
      const key=c.length===1?c[0].id:null;let status,reason;
      if(!it.no){status='error';reason=en?'Missing section number':'رقم الشعبة مفقود';}
      else if(c.length===0){status='error';reason=en?'Section not found in this term':'الشعبة غير مسجلة في هذا الفصل';}
      else if(c.length>1){status='warning';reason=en?'Matches several sections — add course code or branch':'تطابق أكثر من شعبة — أضف رمز المقرر أو الفرع';}
      else if(seen[key]){status='warning';reason=(en?'Duplicate of row ':'مكرر مع الصف ')+seen[key];}
      else{seen[key]=it.n;status='valid';reason=st.course(c[0].course).code+' / '+c[0].no+' · '+st.branchLabel(c[0].branch,lang);}
      return {...it,id:it.n,status,reason,sid:status==='valid'?key:null};});};
  const onFile=async ev=>{const f=ev.target.files&&ev.target.files[0];ev.target.value='';if(!f)return;try{const p=await window.parseRosterFile(f);setName(p.name);setErr(null);setRes(match(p));}catch(x){setRes(null);setErr(x.message==='cols'?(en?'The file needs a “section number” column.':'يجب أن يحتوي الملف على عمود «رقم الشعبة».'):x.message==='size'?(en?'File exceeds 20 MB.':'الملف يتجاوز 20 ميجابايت.'):(en?'Unsupported file. Use xlsx or csv.':'نوع الملف غير مدعوم. استخدم xlsx أو csv.'));}};
  const sample=()=>{setName('sections_list.xlsx');setErr(null);setRes(match({headers:['الفرع','رمز المقرر','رقم الشعبة'],rows:[['المجمعة','CS212','2298'],['الزلفي','ENG102','214'],['','MATH101','123'],['الزلفي','MATH101','215'],['رماح','ISLM101','157'],['المجمعة','CS101','999'],['الزلفي','ENG102','214']]}));};
  const ok=res?res.filter(r=>r.sid):[];
  return <div style={{display:'flex',flexDirection:'column',gap:12}}>
    <div style={{border:'2px dashed var(--border-strong)',borderRadius:'var(--radius-lg)',padding:'22px 16px',textAlign:'center',background:'var(--gray-50)'}}>
      <Icon name="file-spreadsheet" size={30} color="var(--track-green)" style={{margin:'0 auto 8px'}}/>
      <div style={{font:'700 14px var(--font-sans)'}}>{en?'Upload a list of sections':'ارفع قائمة الشعب'}</div>
      <div style={{font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)',marginTop:4}}>{en?'Columns: section number (required), course code and branch (recommended). A section roster file with a header block also works.':'الأعمدة: رقم الشعبة (إلزامي)، رمز المقرر والفرع (يُنصح بهما). ويُقبل أيضًا كشف الشعبة بصيغة ملف الطلبة.'}</div>
      <input ref={ref} type="file" accept=".xlsx,.csv" onChange={onFile} style={{display:'none'}}/>
      <div style={{display:'flex',gap:8,justifyContent:'center',marginTop:12,flexWrap:'wrap'}}><Button size="sm" variant="secondary" icon="download" onClick={()=>downloadTemplate('track_sections_list_template.csv',[['الفرع','رمز المقرر','رقم الشعبة'],['المجمعة','CS212','2298'],['الزلفي','ENG102','214']])}>{en?'Download template':'تنزيل القالب'}</Button><Button size="sm" variant="secondary" icon="folder-open" onClick={()=>ref.current&&ref.current.click()}>{en?'Choose file':'اختيار ملف'}</Button><Button size="sm" variant="ghost" onClick={sample}>{en?'Use sample file':'استخدام ملف تجريبي'}</Button></div>
    </div>
    {err&&<Alert tone="danger">{err}</Alert>}
    {res&&<>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:8,flexWrap:'wrap'}}>
        <span style={{font:'600 13px var(--font-sans)'}}>{name} · <span style={{color:'var(--green-700)'}}>{ok.length} {en?'matched':'مطابقة'}</span> · <span style={{color:'var(--red-600)'}}>{res.length-ok.length} {en?'not added':'لن تُضاف'}</span></span>
        <Button size="sm" icon="check" disabled={!ok.length} onClick={()=>onAdd(ok.map(r=>r.sid))}>{en?'Add matched sections':'إضافة الشعب المطابقة'}</Button>
      </div>
      <DataTable dense columns={[{key:'n',label:en?'Row':'الصف',numeric:true},{key:'b',label:en?'Branch':'الفرع',render:r=>r.branch||'—'},{key:'code',label:en?'Course':'المقرر',numeric:true,render:r=>r.code||r.course||'—'},{key:'no',label:en?'Section':'الشعبة',numeric:true,render:r=><b>{r.no||'—'}</b>},{key:'s',label:en?'Result':'النتيجة',render:r=><StatusBadge status={r.status}/>},{key:'r',label:en?'Details':'التفاصيل',wrap:true,render:r=><span style={{font:'400 12px var(--font-sans)',color:r.status==='error'?'var(--red-600)':'var(--text-secondary)'}}>{r.reason}</span>}]} rows={res}/>
    </>}
  </div>;
}
function InitiativeDialog({ini,onClose,notify}){
  const {Dialog,Button,Input,Select,Checkbox,Tabs,Alert}=window.DS;const {lang,me,t}=useApp();const st=useStore();const en=lang==='en';const isNew=!ini.id;
  const [step,setStep]=React.useState('info');
  const [f,setF]=React.useState({ar:ini.ar||'',en:ini.en||'',term:ini.term||st.currentTerm().id,start:ini.start||'2026-10-15',end:ini.end||'2027-01-05',owner:ini.owner||'عمادة التطوير الأكاديمي',desc:ini.desc||'',status:ini.status||'draft'});
  const [stages,setStages]=React.useState(ini.id?(st.TPL[ini.id]||[]).slice():['تسجيل الطالب','التوثيق','الانتهاء']);
  const assigned=ini.id?st.S.assignments.filter(a=>a.ini===ini.id).map(a=>a.section):[];
  const [secs,setSecs]=React.useState(assigned);
  const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);const [src,setSrc]=React.useState('list');
  const termSecs=st.S.sections.filter(s=>s.term===+f.term);
  const byBranch=st.S.branches.map(b=>({b,list:termSecs.filter(s=>s.branch===b.id)})).filter(x=>x.list.length);
  const set=(k,v)=>setF({...f,[k]:v});
  const save=()=>{const clean=stages.map(x=>x.trim()).filter(Boolean);
    const n={ar:f.ar.trim()?null:t('required'),en:f.en.trim()?null:t('required'),end:f.end<=f.start?t('end_after'):null,stages:clean.length?null:(en?'Add at least one status.':'أضف حالة واحدة على الأقل.')};setE(n);
    if(n.ar||n.en||n.end){setStep('info');return;}if(n.stages){setStep('stages');return;}
    setBusy(true);setTimeout(()=>{let obj=ini;
      if(isNew){obj={id:Date.now(),...f,term:+f.term};st.initiatives.push(obj);st.audit(me.id,'initiative.created','initiative',obj.id,null,{name_ar:f.ar,stages:clean,sections:secs.length});}
      else{const old={ar:ini.ar,start:ini.start,end:ini.end,status:ini.status};Object.assign(ini,f,{term:+f.term});st.audit(me.id,'initiative.updated','initiative',ini.id,old,{ar:f.ar,start:f.start,end:f.end,status:f.status});}
      st.TPL[obj.id]=clean;
      secs.forEach(sid=>{if(!st.S.assignments.some(a=>a.ini===obj.id&&a.section===sid)){st.S.assignments.push({id:Date.now()+sid,section:sid,ini:obj.id,stages:clean.slice()});st.audit(me.id,'section.initiative_assigned','section',sid,null,{initiative:obj.ar,stages:clean});}});
      st.emit();setBusy(false);onClose();notify(isNew?(en?'Initiative created':'تم إنشاء المبادرة'):(en?'Initiative saved':'تم حفظ المبادرة'),secs.length+' '+(en?'sections':'شعبة'));},600);};
  const toggle=(id,on)=>setSecs(on?[...secs,id]:secs.filter(x=>x!==id));
  return <Dialog title={isNew?(en?'Create initiative':'إنشاء مبادرة'):(en?'Edit initiative':'تعديل المبادرة')} onClose={onClose} width={680}
    footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{isNew?(en?'Create initiative':'إنشاء المبادرة'):t('save')}</Button></>}>
    <div style={{marginTop:-8,marginBottom:16}}><Tabs value={step} onChange={setStep} tabs={[{id:'info',label:en?'Details':'البيانات'},{id:'stages',label:en?'Student statuses':'حالات الطالب',count:stages.filter(x=>x.trim()).length},{id:'sections',label:en?'Target sections':'الشعب المستهدفة',count:secs.length}]}/></div>
    {step==='info'&&<div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
      <Input label={t('name_ar')} required value={f.ar} onChange={x=>set('ar',x.target.value)} onBlur={()=>setE({...e,ar:f.ar.trim()?null:t('required')})} error={e.ar} placeholder="مثال: مهارات المستقبل"/>
      <Input label={t('name_en')} required dir="ltr" value={f.en} onChange={x=>set('en',x.target.value)} onBlur={()=>setE({...e,en:f.en.trim()?null:t('required')})} error={e.en} placeholder="e.g. Future Skills"/>
      <Select label={t('f_term')} value={f.term} onChange={x=>{set('term',x.target.value);setSecs([]);}} options={st.S.terms.filter(x=>x.status!=='archived').map(x=>({value:x.id,label:en?x.en:x.ar}))}/>
      <Input label={en?'Owner':'الجهة المالكة'} value={f.owner} onChange={x=>set('owner',x.target.value)} placeholder={en?'e.g. Deanship of Academic Development':'مثال: عمادة التطوير الأكاديمي'}/>
      <Input label={t('start')} type="date" value={f.start} onChange={x=>set('start',x.target.value)}/>
      <Input label={t('end')} type="date" value={f.end} onChange={x=>set('end',x.target.value)} error={e.end}/>
      <label style={{gridColumn:'1 / -1',display:'flex',flexDirection:'column',gap:6}}><span style={{font:'600 13px var(--font-sans)'}}>{en?'Description':'الوصف'}</span>
        <textarea value={f.desc} onChange={x=>set('desc',x.target.value)} rows={3} style={{...nativeStyle,height:'auto',padding:'10px 12px',resize:'vertical',font:'400 14px/1.6 var(--font-sans)'}}/></label>
      <Select label={t('status')} value={f.status} onChange={x=>set('status',x.target.value)} options={[{value:'draft',label:en?'Draft':'مسودة'},{value:'active',label:en?'Active':'فعّالة'},{value:'closed',label:en?'Closed':'مغلقة'}]}/>
    </div>}
    {step==='stages'&&<div style={{display:'flex',flexDirection:'column',gap:12}}>
      <Alert tone="info">{en?'These statuses become columns in every target section. The instructor ticks them for each student, in order; the last one means the student completed the initiative. You can still adjust them per section later.':'تصبح هذه الحالات أعمدة في كل شعبة مستهدفة، يؤشّر عليها المدرس لكل طالب بالترتيب، والحالة الأخيرة تعني إنهاء الطالب للمبادرة. يمكن تعديلها لكل شعبة لاحقًا.'}</Alert>
      <StagesEditor stages={stages} setStages={x=>{setStages(x);setE({...e,stages:null});}}/>
      {e.stages&&<div role="alert" style={{font:'400 12px var(--font-sans)',color:'var(--red-600)'}}>{e.stages}</div>}
    </div>}
    {step==='sections'&&<div style={{display:'flex',flexDirection:'column',gap:14}}>
      <Tabs variant="segmented" value={src} onChange={setSrc} tabs={[{id:'list',label:en?'From registered sections':'من الشعب المسجلة'},{id:'file',label:en?'Upload Excel file':'رفع ملف Excel'}]}/>
      {src==='file'&&<SectionFilePicker term={+f.term} termSecs={termSecs} onAdd={ids=>{setSecs([...new Set([...secs,...ids])]);setSrc('list');}}/>}
      {src==='list'&&<>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}><span style={{font:'400 13px var(--font-sans)',color:'var(--text-secondary)'}}>{en?'Sections registered in':'الشعب المسجلة في'} {(()=>{const x=st.term(+f.term);return x?(en?x.en:x.ar):'';})()} · {secs.length}/{termSecs.length}</span>
        <div style={{display:'flex',gap:6}}><Button size="sm" variant="ghost" onClick={()=>setSecs(termSecs.map(s=>s.id))}>{en?'Select all':'تحديد الكل'}</Button><Button size="sm" variant="ghost" onClick={()=>setSecs(assigned)}>{en?'Clear':'إلغاء التحديد'}</Button></div></div>
      {byBranch.length===0&&<Alert tone="warning">{en?'No sections in this term yet.':'لا توجد شعب في هذا الفصل بعد.'}</Alert>}
      {byBranch.map(({b,list})=><div key={b.id} style={{border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',padding:12}}>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:10}}>
          <b style={{font:'700 13px var(--font-sans)'}}>{st.branchLabel(b.id,lang)}</b>
          <Checkbox label={en?'All':'الكل'} checked={list.every(s=>secs.includes(s.id))} onChange={v=>setSecs(v?[...new Set([...secs,...list.map(s=>s.id)])]:secs.filter(x=>!list.some(s=>s.id===x)||assigned.includes(x)))}/>
        </div>
        <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(190px,1fr))',gap:8}}>
          {list.map(s=>{const c=st.course(s.course);const locked=assigned.includes(s.id);return <Checkbox key={s.id} disabled={locked} checked={secs.includes(s.id)} onChange={v=>toggle(s.id,v)} label={<span><span style={{fontFamily:'var(--font-latin)'}}>{c.code}/{s.no}</span> <span style={{color:'var(--text-secondary)'}}>· {en?c.en:c.ar}</span></span>}/>;})}
        </div>
      </div>)}
      {assigned.length>0&&<div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{en?'Already-assigned sections are locked here; manage them from the section page.':'الشعب المسندة سابقًا مثبّتة هنا، وتُدار من صفحة الشعبة.'}</div>}
      </>}
    </div>}
  </Dialog>;
}

function InitiativesScreen({notify}){
  const {Button,DataTable,StatusBadge,Tabs,Input,ProgressBar}=window.DS;const {lang,me,go,t}=useApp();const st=useStore();const en=lang==='en';
  const [edit,setEdit]=React.useState(null);const [tab,setTab]=React.useState('all');const [q,setQ]=React.useState('');
  const scope=new Set(st.sectionScope(me).map(s=>s.id));
  const list=st.initiatives.filter(i=>(tab==='all'||i.status===tab)&&(i.ar+i.en).toLowerCase().includes(q.toLowerCase()));
  const secsOf=i=>st.S.assignments.filter(a=>a.ini===i.id&&scope.has(a.section));
  const pct=i=>{const as=secsOf(i);let tot=0,done=0;as.forEach(a=>{st.S.enrollments.filter(e=>e.section===a.section&&e.status==='active').forEach(e=>{tot++;const m=st.marks(a,e.student);if(m[m.length-1])done++;});});return tot?Math.round(done/tot*100):0;};
  const stTone=s=>s==='active'?'active':s==='draft'?'draft':'closed';
  return <div>
    <PageHeader title={t('nav_initiatives')} subtitle={en?'Initiatives run inside course sections. Each has its own student statuses.':'المبادرات التي تُنفَّذ داخل الشعب الدراسية، ولكل منها حالات الطالب الخاصة بها.'}
      actions={me.role==='SUPER_ADMIN'&&<Button icon="plus" onClick={()=>setEdit({})}>{en?'Create initiative':'إنشاء مبادرة'}</Button>}/>
    <Panel>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:16,padding:'8px 20px 0',flexWrap:'wrap'}}>
        <Tabs value={tab} onChange={setTab} tabs={[{id:'all',label:t('all'),count:st.initiatives.length},{id:'active',label:en?'Active':'فعّالة',count:st.initiatives.filter(i=>i.status==='active').length},{id:'draft',label:en?'Draft':'مسودة',count:st.initiatives.filter(i=>i.status==='draft').length},{id:'closed',label:en?'Closed':'مغلقة',count:st.initiatives.filter(i=>i.status==='closed').length}]}/>
        <Input size="sm" icon="search" placeholder={en?'Search initiatives':'بحث في المبادرات'} value={q} onChange={e=>setQ(e.target.value)} style={{width:240}}/>
      </div>
      <div style={{padding:20}}>
        <DataTable onRowClick={r=>go('initiative',r.id)} columns={[
          {key:'n',label:en?'Initiative':'المبادرة',render:r=><div><div style={{fontWeight:700}}>{en?r.en:r.ar}</div><div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{r.owner}</div></div>},
          {key:'t',label:t('f_term'),render:r=>{const x=st.term(r.term);return x?(en?x.en:x.ar):'—';}},
          {key:'d',label:en?'Period':'الفترة',numeric:true,render:r=>fmtDate(r.start)+' — '+fmtDate(r.end)},
          {key:'s',label:en?'Student statuses':'حالات الطالب',wrap:true,render:r=><div style={{display:'flex',gap:4,flexWrap:'wrap'}}>{(st.TPL[r.id]||[]).map((x,i,a)=><span key={i} style={{font:'500 11px var(--font-sans)',padding:'3px 8px',borderRadius:6,background:i===a.length-1?'var(--green-50)':'var(--gray-100)',color:i===a.length-1?'var(--green-700)':'var(--text-secondary)'}}>{i+1}. {x}</span>)}</div>},
          {key:'c',label:t('sections_n'),numeric:true,render:r=>secsOf(r).length},
          {key:'p',label:en?'Completion':'نسبة الإنهاء',width:160,render:r=><ProgressBar value={pct(r)}/>},
          {key:'st',label:t('status'),render:r=><StatusBadge status={stTone(r.status)}>{r.status==='active'?(en?'Active':'فعّالة'):r.status==='draft'?(en?'Draft':'مسودة'):(en?'Closed':'مغلقة')}</StatusBadge>},
          ...(me.role==='SUPER_ADMIN'?[{key:'x',label:'',render:r=><Button size="sm" variant="ghost" icon="pencil" onClick={e=>{e.stopPropagation();setEdit(r);}}>{t('edit')}</Button>}]:[])]} rows={list} empty={en?'No initiatives':'لا توجد مبادرات'}/>
      </div>
    </Panel>
    {edit&&<InitiativeDialog ini={edit} onClose={()=>setEdit(null)} notify={notify}/>}
  </div>;
}

function InitiativeDetailScreen({id,notify}){
  const {Button,DataTable,StatusBadge,StatCard,ProgressBar,Tabs,Input,Select}=window.DS;const {lang,me,go,t}=useApp();const st=useStore();const en=lang==='en';
  const ini=st.initiatives.find(i=>i.id===id);const [edit,setEdit]=React.useState(false);const [tab,setTab]=React.useState('sections');const [q,setQ]=React.useState('');const [fs,setFs]=React.useState('');const [fl,setFl]=React.useState('');
  if(!ini)return <RefusalScreen kind="404"/>;
  const scope=new Set(st.sectionScope(me).map(s=>s.id));
  const as=st.S.assignments.filter(a=>a.ini===id&&scope.has(a.section));
  const rows=as.map(a=>{const sec=st.S.sections.find(s=>s.id===a.section);const enr=st.S.enrollments.filter(e=>e.section===a.section&&e.status==='active');const M=enr.map(e=>st.marks(a,e.student));return {id:a.id,a,sec,n:enr.length,counts:a.stages.map((_,i)=>M.filter(m=>m[i]).length),done:M.filter(m=>m[m.length-1]).length};});
  const totN=rows.reduce((x,r)=>x+r.n,0),totD=rows.reduce((x,r)=>x+r.done,0);
  const stages=st.TPL[id]||[];
  const studs=as.flatMap(a=>{const sec=st.S.sections.find(s=>s.id===a.section);return st.S.enrollments.filter(e=>e.section===a.section&&e.status==='active').map(e=>({id:a.id+'-'+e.student,a,sec,s:st.student(e.student),m:st.marks(a,e.student)}));});
  const lvl=r=>r.m.filter(Boolean).length;
  const srows=studs.filter(r=>(!fs||String(r.sec.id)===fs)&&(fl===''||(fl==='done'?r.m[r.m.length-1]:lvl(r)===+fl&&!r.m[r.m.length-1]))&&(r.s.uid+r.s.ar).toLowerCase().includes(q.toLowerCase()));
  const secIds=new Set(as.map(a=>a.section));const tks=st.ticketScope(me).filter(x=>x.section&&secIds.has(x.section));
  return <div>
    <PageHeader crumbs={[{label:t('nav_initiatives'),onClick:()=>go('initiatives')},{label:en?ini.en:ini.ar}]} title={en?ini.en:ini.ar} subtitle={ini.desc}
      actions={me.role==='SUPER_ADMIN'&&<Button variant="secondary" icon="pencil" onClick={()=>setEdit(true)}>{t('edit')}</Button>}/>
    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(150px,1fr))',gap:14,marginBottom:16}}>
      <StatCard value={rows.length} label={t('sections_n')}/><StatCard value={new Set(rows.map(r=>r.sec.branch)).size} label={t('branches')}/>
      <StatCard value={totN} label={t('students_n')}/><StatCard value={totD} label={en?'Completed':'أنهوا المبادرة'} tone="green"/><StatCard value={(totN?Math.round(totD/totN*100):0)+'%'} label={en?'Completion':'نسبة الإنهاء'} tone="green"/>
    </div>
    <Panel>
      <div style={{padding:'8px 20px 0'}}><Tabs value={tab} onChange={setTab} tabs={[{id:'sections',label:en?'Target sections':'الشعب المستهدفة',count:rows.length},{id:'students',label:en?'Initiative students':'طلبة المبادرة',count:studs.length},{id:'support',label:en?'Support requests':'طلبات الدعم الفني',count:tks.length}]}/></div>
      <div style={{padding:20}}>
      {tab==='students'&&<div style={{display:'flex',flexDirection:'column',gap:14}}>
        <div style={{display:'flex',gap:10,flexWrap:'wrap',alignItems:'center'}}>
          <Input size="sm" icon="search" placeholder={en?'Student no. / name':'رقم الطالب / الاسم'} value={q} onChange={e=>setQ(e.target.value)} style={{width:230}}/>
          <Select size="sm" icon="users" value={fs} onChange={e=>setFs(e.target.value)} options={[{value:'',label:en?'All sections':'كل الشعب'},...rows.map(r=>({value:String(r.sec.id),label:st.course(r.sec.course).code+' · '+r.sec.no+' · '+st.branchLabel(r.sec.branch,lang)}))]} style={{width:260}}/>
          <Select size="sm" icon="list-checks" value={fl} onChange={e=>setFl(e.target.value)} options={[{value:'',label:en?'All progress':'كل الحالات'},{value:'0',label:en?'Not started':'لم يبدأ'},...stages.slice(0,-1).map((s,i)=>({value:String(i+1),label:(en?'Reached: ':'وصل إلى: ')+s})),{value:'done',label:en?'Completed':'أنهى المبادرة'}]} style={{width:220}}/>
          <span style={{marginInlineStart:'auto',font:'600 13px var(--font-sans)',color:'var(--text-secondary)'}}>{srows.length} {en?'students':'طالب'}</span>
          <Button size="sm" variant="secondary" icon="download" onClick={()=>downloadTemplate('initiative_'+id+'_students.csv',[['الرقم الجامعي','اسم الطالب','المقرر','الشعبة','الفرع',...stages],...srows.map(r=>[r.s.uid,r.s.ar,st.course(r.sec.course).code,r.sec.no,st.branchLabel(r.sec.branch,'ar'),...stages.map((_,i)=>r.m[i]?'تم':'لم يتم')])])}>{t('export')}</Button>
        </div>
        <DataTable dense onRowClick={r=>go('student',r.s.id)} columns={[
          {key:'uid',label:en?'Student no.':'رقم الطالب',numeric:true,render:r=><b>{r.s.uid}</b>},
          {key:'ar',label:en?'Student name':'اسم الطالب',wrap:true,render:r=><span style={{fontWeight:600}}>{r.s.ar}</span>},
          {key:'sec',label:en?'Section':'الشعبة',render:r=>st.course(r.sec.course).code+' · '+r.sec.no},
          {key:'b',label:t('branch'),render:r=>st.branchLabel(r.sec.branch,lang)},
          ...stages.map((s,i)=>({key:'st'+i,label:s,render:r=>window.stageFlag(!!r.m[i],r.m[i]?(en?'Done':'تم'):(en?'Not yet':'لم يتم'))}))]} rows={srows} empty={en?'No students match':'لا يوجد طلبة مطابقون'}/>
      </div>}
      {tab==='support'&&<TicketList rows={tks} notify={notify}/>}
      {tab==='sections'&&<>
      <DataTable onRowClick={r=>go('section',r.sec.id)} columns={[
        {key:'no',label:t('section_no'),numeric:true,render:r=><b>{r.sec.no}</b>},
        {key:'c',label:t('course'),render:r=>{const c=st.course(r.sec.course);return <span><span style={{fontFamily:'var(--font-latin)'}}>{c.code}</span> <span style={{color:'var(--text-secondary)'}}>· {en?c.en:c.ar}</span></span>;}},
        {key:'b',label:t('branch'),render:r=>st.branchLabel(r.sec.branch,lang)},
        {key:'i',label:t('instructor'),render:r=>r.sec.instructor?st.uname(r.sec.instructor,lang):'—'},
        {key:'n',label:t('students_n'),numeric:true,render:r=>r.n},
        ...stages.map((s,i)=>({key:'s'+i,label:s,numeric:true,render:r=>(r.counts[i]??0)+'/'+r.n})),
        {key:'p',label:en?'Completion':'نسبة الإنهاء',width:150,render:r=><ProgressBar value={r.n?Math.round(r.done/r.n*100):0}/>}]} rows={rows} empty={en?'No sections assigned yet':'لم تُسند لأي شعبة بعد'}/>
      </>}
      </div>
    </Panel>
    {edit&&<InitiativeDialog ini={ini} onClose={()=>setEdit(false)} notify={notify}/>}
  </div>;
}
Object.assign(window,{SectionFilePicker,StagesEditor,InitiativeDialog,InitiativesScreen,InitiativeDetailScreen});
