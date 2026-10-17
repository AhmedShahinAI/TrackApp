const CENTRAL_FIELDS=[['term',1],['branch',1],['gender',1],['course_code',1],['course_name',0],['section_no',1],['inst_name',0],['inst_email',1],['uid',1],['student_name',1]];
const SECTION_FIELDS=[['seq',0],['uid',1],['student_name',1],['acad_status',0]];
const CENTRAL_HEADERS=['الفصل','الفرع','النوع','رمز المقرر','اسم المقرر','رقم الشعبة','اسم المدرس','البريد','الرقم الجامعي','اسم الطالب','ملاحظات'];
const SECTION_HEADERS=['تسلسل','رقم الطالب','اسم الطالب','الحالة'];
const HDR_MATCH={seq:/تسلسل|seq/i,uid:/رقم الطالب|الرقم الجامعي|university id|student no/i,student_name:/اسم الطالب|student name|name/i,acad_status:/^الحالة$|status/i};
function autoMap(fields,headers){const m={};fields.forEach(([k])=>{const i=headers.findIndex(h=>HDR_MATCH[k]&&HDR_MATCH[k].test(h));m[k]=i>=0?i:'';});return m;}
function sampleCentral(st){
  const rows=[];const ok=st.S.students.slice(0,8);
  ok.forEach((s,i)=>rows.push(['1448-1','الزلفي','طالبات','ENG102','اللغة الإنجليزية 2','214','د. فيصل العتيبي','f.alotaibi@mu.edu.sa',s.uid,s.ar]));
  for(let i=0;i<6;i++)rows.push(['1448-1','المجمعة','طلاب','CS101','مقدمة في الحاسب','131','د. هند المطيري','h.almutairi@mu.edu.sa','44200'+(1100+i),['سلطان','مازن','عمر','زياد','راكان','حمد'][i]+' القحطاني']);
  rows.splice(4,0,rows[2].slice());
  rows.push(['1448-1','الزلفي','طالبات','ENG102','اللغة الإنجليزية 2','214','د. فيصل العتيبي','f.alotaibi@mu.edu.sa','44A10023','جنى الحربي']);
  rows.push(['1448-1','الزلفي','طالبات','ENG102','اللغة الإنجليزية 2','214','د. فيصل العتيبي','f.alotaibi@mu.edu.sa','','رزان المطيري']);
  rows.push(['1448-1','الدرعية','طلاب','CS101','مقدمة في الحاسب','131','د. هند المطيري','h.almutairi@mu.edu.sa','442001200','ماجد الشهري']);
  rows.push(['1448-1','رماح','طلاب','PHYS110','فيزياء عامة','401','أ. بدر السهلي','b.alsahli@mu.edu.sa','442001201','عبدالله السهلي']);
  rows.push(['1448-1','رماح','طلاب','PHYS110','فيزياء عامة','401','أ. بدر السهلي','b.alsahli@mu.edu.sa','442001202','صالح الدوسري']);
  rows.push(['1448-1','الزلفي','طالبات','ENG102','اللغة الإنجليزية 2','214','د. فيصل العتيبي','f.alotaibi@mu.edu.sa',ok[1].uid,'اسم مختلف تمامًا']);
  rows.push(['1448-1','الزلفي','طالبات','ENG102','اللغة الإنجليزية 2','','د. فيصل العتيبي','f.alotaibi@mu.edu.sa','442001203','نوف العنزي']);
  return rows.map(r=>[...r,'']);
}
function sampleSection(st){const ex=st.S.students[20];return [['442009001','لين الحربي'],[ex.uid,'اسم آخر'],['442009002','هديل الغامدي'],['442009001','لين الحربي'],['4420X9003','ترف السبيعي']];}

function validate(rows,map,mode,st,sectionId){
  const col=k=>map[k];const seen={};const out=[];
  const idx=Object.fromEntries(Object.entries(map).filter(([k,v])=>v!==''));
  rows.forEach((r,i)=>{const n=i+2;const v=k=>idx[k]!=null?String(r[idx[k]]||'').trim():'';const reasons=[];let status='valid';
    const uid=v('uid');const key=mode==='central'?[v('branch'),v('gender'),v('course_code'),v('section_no'),uid].join('|'):uid;
    if(seen[key]){reasons.push(['r_dup_file',{n:seen[key]}]);status='error';}else seen[key]=n;
    const req=(mode==='central'?CENTRAL_FIELDS:SECTION_FIELDS).filter(f=>f[1]).map(f=>f[0]);
    req.forEach(f=>{if(!v(f)){reasons.push(['r_missing',{c:'f_'+f}]);status='error';}});
    if(uid&&!/^\d{9}$/.test(uid)){reasons.push(['r_uid',{}]);status='error';}
    let flags={};
    if(mode==='central'&&status!=='error'){
      const b=st.S.branches.find(b=>b.ar===v('branch')&&(b.gender==='male'?/^(طلاب|بنين)$/:/^(طالبات|بنات)$/).test(v('gender')));
      if(!b){reasons.push(['r_branch',{v:v('branch')+' — '+v('gender')}]);status='error';}
      if(!st.S.courses.find(c=>c.code===v('course_code'))){reasons.push(['r_course',{v:v('course_code')}]);flags.course=true;if(status==='valid')status='warning';}
      if(!st.S.users.find(u=>u.email===v('inst_email'))){reasons.push(['r_inst',{v:v('inst_email')}]);flags.inst=true;if(status==='valid')status='warning';}
    }
    const ex=uid&&st.S.students.find(s=>s.uid===uid);
    if(ex&&status!=='error'){if(ex.ar!==v('student_name')){reasons.push(['r_name',{v:ex.ar}]);if(status==='valid')status='warning';}else reasons.push(['r_linked',{}]);flags.existing=true;}
    out.push({n,raw:r,uid,name:v('student_name'),acad:v('acad_status'),seq:v('seq'),status,reasons,flags,sec:mode==='central'?v('course_code')+'/'+v('section_no'):null});
  });
  return out;
}

function FileMeta({meta,sec}){
  const {Icon}=window.DS;const {lang}=useApp();const st=useStore();const en=lang==='en';const c=st.course(sec.course);
  const rows=[[en?'Campus':'المقر',meta.campus,null],[en?'Degree':'الدرجة',meta.degree,null],[en?'Course':'اسم المقرر',meta.course,meta.course?meta.course===c.ar||meta.course===c.en:null],[en?'Activity':'النشاط',meta.activity,null],[en?'Section':'الشعبة',meta.section,meta.section?meta.section===sec.no:null]];
  const bad=rows.some(r=>r[2]===false);
  return <div style={{border:'1px solid '+(bad?'var(--red-100)':'var(--green-100)'),background:bad?'var(--red-50)':'var(--green-50)',borderRadius:'var(--radius-md)',padding:14}}>
    <div style={{display:'flex',alignItems:'center',gap:8,font:'700 13px var(--font-sans)',marginBottom:10,color:bad?'var(--red-600)':'var(--green-700)'}}><Icon name={bad?'circle-alert':'circle-check'} size={16}/>{bad?(en?'File header does not match this section':'بيانات رأس الملف لا تطابق هذه الشعبة'):(en?'File header matches this section':'بيانات رأس الملف مطابقة لهذه الشعبة')}</div>
    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(120px,1fr))',gap:10}}>{rows.map(([l,v,ok])=><div key={l}><div style={{font:'500 11px var(--font-sans)',color:'var(--text-secondary)'}}>{l}</div><div style={{display:'flex',alignItems:'center',gap:4,font:'700 13px var(--font-sans)'}}>{v||'—'}{ok===true&&<Icon name="check" size={13} color="var(--green-700)"/>}{ok===false&&<Icon name="x" size={13} color="var(--red-600)"/>}</div></div>)}</div>
    {bad&&<div style={{font:'400 12px var(--font-sans)',color:'var(--red-600)',marginTop:8}}>{en?'Expected: ':'المتوقع: '}{c.ar} · {sec.no}</div>}
  </div>;
}
function ImportsScreen(){
  const {Button,DataTable,StatusBadge}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();
  const rows=me.role==='SUPER_ADMIN'?st.S.imports:st.S.imports.filter(i=>i.by===me.id||(i.section&&st.S.sections.some(s=>s.id===i.section&&st.canSee(me,s))));
  return <div>
    <PageHeader title={t('nav_imports')} subtitle={t('imports_sub')} actions={<>
      {st.can(me,'import.central.run')&&<Button icon="file-up" onClick={()=>go('wizard',{mode:'central'})}>{t('central')}</Button>}
      {st.can(me,'import.section.run')&&me.role==='INSTRUCTOR'&&<Button icon="file-up" onClick={()=>go('mysections')}>{t('section_mode')}</Button>}</>}/>
    <Panel style={{padding:20}}>
      <DataTable columns={[
        {key:'file',label:t('file'),render:r=><div style={{display:'flex',gap:8,alignItems:'center'}}><window.DS.Icon name="file-spreadsheet" size={18} color="var(--track-green)"/><b style={{fontFamily:'var(--font-latin)',fontWeight:600}}>{r.file}</b></div>},
        {key:'m',label:lang==='en'?'Mode':'النوع',render:r=>r.mode==='central'?t('central'):t('section_mode')+(r.section?' · '+st.S.sections.find(s=>s.id===r.section).no:'')},
        {key:'by',label:t('ran_by'),render:r=>st.uname(r.by,lang)},{key:'at',label:t('date'),numeric:true,render:r=>fmtDateTime(r.at)},
        {key:'total',label:t('rows'),numeric:true,render:r=>r.total.toLocaleString('en')},{key:'imported',label:t('imported'),numeric:true,render:r=>r.imported.toLocaleString('en')},
        {key:'duplicates',label:t('duplicates'),numeric:true},{key:'errors',label:t('errors'),numeric:true},
        {key:'s',label:t('status'),render:r=><StatusBadge status={r.status==='completed'?'completed':r.status==='failed'?'failed':'running'}/>},
        {key:'x',label:'',render:r=>r.status==='failed'?<Button size="sm" variant="ghost" icon="rotate-cw">{lang==='en'?'Resume':'استئناف'}</Button>:r.errors>0?<Button size="sm" variant="ghost" icon="download">xlsx</Button>:null}]} rows={rows}/>
    </Panel>
  </div>;
}

function ImportWizard({params,notify}){
  const {Stepper,Button,Select,Alert,DataTable,StatusBadge,StatCard,Checkbox,Icon,ProgressBar}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();
  const mode=params&&params.mode||'central';const sectionId=params&&params.section;
  const sec=sectionId&&st.S.sections.find(s=>s.id===sectionId);
  const [step,setStep]=React.useState(0);const [file,setFile]=React.useState(null);const [fErr,setFErr]=React.useState(null);const [term,setTerm]=React.useState(st.currentTerm().id);
  const [map,setMap]=React.useState({});const [prog,setProg]=React.useState(0);const [res,setRes]=React.useState(null);const [opt,setOpt]=React.useState({course:true,inst:false});const [report,setReport]=React.useState(null);
  const denied=React.useRef(false);
  if(mode==='section'&&(!sec||!(me.role==='SUPER_ADMIN'||sec.instructor===me.id||st.isShared(me,sec)))){if(!denied.current){denied.current=true;st.audit(me.id,'access.denied','import_job',sectionId,null,{reason:'section_not_taught'});}return <RefusalScreen kind="403"/>;}
  const fields=mode==='central'?CENTRAL_FIELDS:SECTION_FIELDS;const headers=file&&file.headers?file.headers:(mode==='central'?CENTRAL_HEADERS:SECTION_HEADERS);const fileRef=React.useRef(null);
  const onFile=async ev=>{const f=ev.target.files&&ev.target.files[0];ev.target.value='';if(!f)return;try{const p=await window.parseRosterFile(f);if(!p.rows.length){setFErr(lang==='en'?'The file contains no importable rows.':'الملف لا يحتوي على صفوف قابلة للاستيراد.');return;}setFErr(null);setFile({...p,prev:st.S.imports.some(i=>i.file===p.name&&(mode==='central'?i.term===term:i.section===sectionId))});setMap(mode==='central'&&!p.headers.some(h=>HDR_MATCH.uid.test(h))?{}:autoMap(fields,p.headers));}catch(e){setFErr(e.message==='size'?t('too_big'):e.message==='rows'?(lang==='en'?'File exceeds 50,000 rows.':'الملف يتجاوز 50,000 صف.'):t('bad_type'));}};
  const steps=['s_upload','s_map','s_check','s_preview','s_import','s_report'].map(k=>t(k));
  const pickSample=(bad)=>{if(bad==='big'){setFErr(t('too_big'));return;}if(bad==='type'){setFErr(t('bad_type'));return;}
    if(mode==='section'){const R=st.REAL_FILE;setFErr(null);setFile({...R,rows:R.rows.map(r=>r.slice()),prev:st.S.imports.some(i=>i.file===R.name&&i.section===sectionId)});setMap(autoMap(fields,R.headers));return;}
    const rows=sampleCentral(st);const name='roster_1448_first.xlsx';
    setFErr(null);setFile({name,rows,size:'412 KB',headers:CENTRAL_HEADERS,prev:st.S.imports.some(i=>i.file===name&&i.term===term)});
    const m={};fields.forEach(([k],i)=>{m[k]=mode==='central'?(k==='inst_name'?6:i<6?i:i<8?i:i):i;});if(mode==='central'){m.course_name='';}setMap(m);};
  const mapOk=fields.filter(f=>f[1]).every(([k])=>map[k]!==''&&map[k]!=null);
  const runCheck=()=>{setStep(2);setProg(0);let p=0;const x=setInterval(()=>{p+=20;setProg(p);if(p>=100){clearInterval(x);setRes(validate(file.rows,map,mode,st,sectionId));setStep(3);}},350);};
  const runImport=()=>{setStep(4);setProg(0);let p=0;const x=setInterval(()=>{p+=25;setProg(p);if(p>=100){clearInterval(x);
    const ok=res.filter(r=>r.status!=='error'&&!(r.flags.course&&!opt.course)&&!(r.flags.inst&&!opt.inst));
    let imported=0,dup=0;
    if(!file.prev){ok.forEach(r=>{let s=st.S.students.find(x=>x.uid===r.uid);if(!s){s={id:Date.now()+Math.random(),uid:r.uid,ar:r.name,en:''};st.S.students.push(s);imported++;}else dup++;
      if(mode==='section'&&!st.S.enrollments.some(e=>e.student===s.id&&e.section===sectionId))st.S.enrollments.push({id:Date.now()+Math.random(),student:s.id,section:sectionId,status:'active',acad:r.acad||'منتظم',seq:+r.seq||0,enrolled:st.now().slice(0,10)});});}
    else dup=ok.length;
    const errs=res.filter(r=>!ok.includes(r));
    const rep={processed:res.length,imported:file.prev?0:imported,duplicates:dup,errors:errs.length,errs};
    st.S.imports.unshift({id:Date.now(),mode,term,section:sectionId||null,file:file.name,by:me.id,at:st.now().slice(0,19),status:'completed',total:res.length,processed:res.length,imported:rep.imported,duplicates:dup,errors:errs.length});
    st.audit(me.id,'import.completed','import_job',null,null,{file:file.name,processed:res.length,imported:rep.imported,duplicates:dup,errors:errs.length});st.emit();
    setReport(rep);setStep(5);notify(t('import_done'),file.name);}},500);};
  const rsn=r=>r.reasons.map(([k,p],i)=><div key={i}>{t(k,p.c?{...p,c:t(p.c)}:p)}</div>);
  const cnt=s=>res?res.filter(r=>r.status===s).length:0;
  const crumbs=[{label:t('nav_imports'),onClick:()=>go('imports')},{label:mode==='central'?t('central'):t('section_mode')+' · '+sec.no}];
  return <div>
    <PageHeader crumbs={crumbs} title={mode==='central'?t('central'):t('section_mode')} subtitle={mode==='section'?(st.course(sec.course).code+' / '+sec.no+' · '+st.branchLabel(sec.branch,lang)):t('imports_sub')}/>
    <Panel style={{padding:'24px 24px 8px',marginBottom:16}}><Stepper steps={steps} current={step}/><div style={{height:16}}/></Panel>
    <Panel style={{padding:24}}>
    {step===0&&<div style={{display:'flex',flexDirection:'column',gap:16,maxWidth:720}}>
      {mode==='central'&&<Select label={t('target_term')} value={term} onChange={e=>setTerm(+e.target.value)} options={st.S.terms.map(x=>({value:x.id,label:lang==='en'?x.en:x.ar}))} style={{maxWidth:320}}/>}
      <div style={{border:'2px dashed '+(fErr?'var(--red-500)':'var(--border-strong)'),borderRadius:'var(--radius-lg)',padding:'36px 20px',textAlign:'center',background:file?'var(--green-50)':'var(--gray-50)'}}>
        <Icon name={file?'file-check-2':'upload-cloud'} size={36} color={file?'var(--track-green)':'var(--track-blue)'} style={{margin:'0 auto 10px'}}/>
        {file?<div><div style={{font:'700 15px var(--font-latin)'}}>{file.name}</div><div style={{font:'400 13px var(--font-sans)',color:'var(--text-secondary)'}}>{file.size} · {file.rows.length} {t('rows')}</div></div>
        :<><div style={{font:'700 15px var(--font-sans)'}}>{t('drop')}</div><div style={{font:'400 13px var(--font-sans)',color:'var(--text-secondary)',marginTop:4}}>{t('drop_hint')}</div></>}
        <div style={{display:'flex',gap:8,justifyContent:'center',marginTop:16,flexWrap:'wrap'}}>
          <input ref={fileRef} type="file" accept=".xlsx,.csv" onChange={onFile} style={{display:'none'}}/>
          <Button variant="secondary" icon="download" onClick={()=>downloadTemplate(mode==='central'?'track_central_roster_template.csv':'track_section_roster_template.csv',mode==='central'?[CENTRAL_HEADERS,sampleCentral(st)[0].slice(0,CENTRAL_HEADERS.length)]:[['تسلسل','رقم الطالب','اسم الطالب','الحالة'],['1','999000001','اسم الطالب الرباعي','منتظم']])}>{lang==='en'?'Download template':'تنزيل القالب'}</Button>
          <Button variant="secondary" icon="folder-open" onClick={()=>fileRef.current&&fileRef.current.click()}>{t('choose')}</Button>
          <Button variant="ghost" size="sm" icon="file-spreadsheet" onClick={()=>pickSample()}>{t('use_sample')}</Button>
          <Button variant="ghost" size="sm" onClick={()=>pickSample('big')}>{lang==='en'?'Try 25 MB file':'تجربة ملف 25MB'}</Button>
          <Button variant="ghost" size="sm" onClick={()=>pickSample('type')}>{lang==='en'?'Try .pdf':'تجربة ملف pdf'}</Button>
        </div>
      </div>
      {file&&file.meta&&Object.keys(file.meta).length>0&&mode==='section'&&<FileMeta meta={file.meta} sec={sec}/>}
      {fErr&&<Alert tone="danger">{fErr}</Alert>}
      {file&&file.prev&&<Alert tone="warning" icon="copy">{t('prev_imported')}</Alert>}
      <div style={{display:'flex',justifyContent:'flex-end'}}><Button disabled={!file} iconEnd={lang==='en'?'arrow-right':'arrow-left'} onClick={()=>setStep(1)}>{t('s_map')}</Button></div>
    </div>}
    {step===1&&<div style={{display:'flex',flexDirection:'column',gap:16}}>
      <Alert tone="info">{t('map_help')}</Alert>
      <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(300px,1fr))',gap:12}}>
        {fields.map(([k,req])=><div key={k} style={{display:'grid',gridTemplateColumns:'1fr',gap:6,padding:12,border:'1px solid '+(req&&(map[k]===''||map[k]==null)?'var(--red-500)':'var(--border-default)'),borderRadius:'var(--radius-md)'}}>
          <div style={{font:'700 13px var(--font-sans)'}}>{t('f_'+k)}{req?<span style={{color:'var(--red-600)'}}> *</span>:null}</div>
          <select aria-label={t('f_'+k)} value={map[k]??''} onChange={e=>setMap({...map,[k]:e.target.value===''?'':+e.target.value})} style={nativeStyle}>
            <option value="">{t('unmapped')}</option>{headers.map((h,i)=><option key={i} value={i}>{h}</option>)}
          </select>
          <div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{t('sample')}: {map[k]!==''&&map[k]!=null?String(file.rows[0][map[k]]||'—'):'—'}</div>
        </div>)}
      </div>
      {!mapOk&&<Alert tone="danger">{t('map_need')}</Alert>}
      <div style={{display:'flex',justifyContent:'space-between'}}><Button variant="ghost" onClick={()=>setStep(0)}>{t('back')}</Button><Button disabled={!mapOk} onClick={runCheck} icon="scan-search">{t('s_check')}</Button></div>
    </div>}
    {(step===2||step===4)&&<div style={{display:'flex',flexDirection:'column',alignItems:'center',gap:14,padding:'30px 0',textAlign:'center'}}>
      <Icon name="loader-circle" size={36} color="var(--track-green)" style={{animation:'track-spin 1s linear infinite'}}/>
      <div style={{font:'700 16px var(--font-sans)'}}>{step===2?t('checking'):t('importing')}</div>
      <div style={{width:'min(420px,100%)'}}><ProgressBar value={prog} tone="var(--track-green)"/></div>
      {step===4&&<div style={{font:'500 12px var(--font-numeric)',color:'var(--text-secondary)'}}>{t('chunk',{a:Math.max(1,Math.ceil(prog/25)),b:4})}</div>}
      <div style={{font:'400 13px var(--font-sans)',color:'var(--text-secondary)'}}>{t('checking_hint')}{step===2&&' '+t('nothing_saved')}</div>
      <Button variant="secondary" size="sm" icon="house" onClick={()=>go('home')}>{t('nav_home')}</Button>
    </div>}
    {step===3&&res&&<div style={{display:'flex',flexDirection:'column',gap:16}}>
      <Alert tone="info" icon="shield-check">{t('nothing_saved')}</Alert>
      <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(130px,1fr))',gap:12}}>
        {mode==='central'&&<StatCard value={new Set(res.filter(r=>r.status!=='error'&&r.flags.course).map(r=>r.sec)).size+1} label={t('p_new_sections')}/>}
        <StatCard value={res.filter(r=>r.status!=='error'&&!r.flags.existing).length} label={t('p_new_students')} tone="green"/>
        <StatCard value={res.filter(r=>r.status!=='error'&&!(mode==='section'&&st.S.enrollments.some(e=>e.section===sectionId&&e.status==='active'&&(st.student(e.student)||{}).uid===r.uid))).length} label={t('p_new_enroll')} tone="green"/>
        <StatCard value={res.filter(r=>r.flags.existing&&r.status!=='error').length} label={t('p_matched')}/>
        <StatCard value={cnt('warning')} label={t('p_warnings')} tone="dark"/>
        <StatCard value={cnt('error')} label={t('p_errors')} tone="dark"/>
      </div>
      {mode==='central'&&(res.some(r=>r.flags.course)||res.some(r=>r.flags.inst))&&<div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(300px,1fr))',gap:12}}>
        {res.some(r=>r.flags.course)&&<div style={{padding:14,border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',display:'flex',flexDirection:'column',gap:8}}><b style={{font:'700 13px var(--font-sans)'}}>{t('opt_courses')}: PHYS110</b><Checkbox label={t('opt_courses_q')} checked={opt.course} onChange={v=>setOpt({...opt,course:v})}/>{!opt.course&&<span style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{t('skip_rows')}</span>}</div>}
        {res.some(r=>r.flags.inst)&&<div style={{padding:14,border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',display:'flex',flexDirection:'column',gap:8}}><b style={{font:'700 13px var(--font-sans)'}}>{t('opt_inst')}: b.alsahli@mu.edu.sa</b><Checkbox label={t('opt_inst_q')} checked={opt.inst} onChange={v=>setOpt({...opt,inst:v})}/>{!opt.inst&&<span style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{t('skip_rows')}</span>}</div>}
      </div>}
      <div style={{font:'700 14px var(--font-sans)'}}>{t('first100')}</div>
      <DataTable dense columns={[{key:'n',label:t('row'),numeric:true},{key:'uid',label:t('uid'),numeric:true,render:r=>r.uid||'—'},{key:'name',label:t('student')},...(mode==='central'?[{key:'sec',label:t('course')+' / '+t('section_no'),numeric:true}]:[]),{key:'s',label:t('status'),render:r=><StatusBadge status={r.status}/>},{key:'r',label:t('reason'),wrap:true,render:r=><div style={{font:'400 12px/1.5 var(--font-sans)',color:r.status==='error'?'var(--red-600)':'var(--text-secondary)'}}>{rsn(r)}</div>}]} rows={res.slice(0,100)}/>
      <div style={{display:'flex',justifyContent:'space-between'}}><Button variant="ghost" onClick={()=>setStep(1)}>{t('back')}</Button><Button icon="check" onClick={runImport}>{t('confirm_import')}</Button></div>
    </div>}
    {step===5&&report&&<div style={{display:'flex',flexDirection:'column',gap:16}}>
      <Alert tone="success" title={t('import_done')}>{file.name}</Alert>
      <div style={{display:'grid',gridTemplateColumns:'repeat(4,minmax(0,1fr))',gap:12}}>
        <StatCard value={report.processed} label={t('processed')}/><StatCard value={report.imported} label={t('imported')} tone="green"/><StatCard value={report.duplicates} label={t('duplicates')}/><StatCard value={report.errors} label={t('errors')} tone="dark"/>
      </div>
      {report.errs.length>0&&<DataTable dense columns={[{key:'n',label:t('row'),numeric:true},{key:'c',label:lang==='en'?'Column':'العمود',render:r=>{const k=r.reasons[0];return k&&k[1].c?t(k[1].c):k&&k[0]==='r_uid'?t('f_uid'):k&&k[0]==='r_branch'?t('f_branch'):k&&k[0]==='r_course'?t('f_course_code'):k&&k[0]==='r_inst'?t('f_inst_email'):'—'}},{key:'v',label:lang==='en'?'Submitted value':'القيمة المُدخلة',numeric:true,render:r=>r.uid||'—'},{key:'r',label:t('reason'),wrap:true,render:r=><div style={{color:'var(--red-600)',font:'400 12px/1.5 var(--font-sans)'}}>{rsn(r)}</div>}]} rows={report.errs}/>}
      <div style={{display:'flex',gap:10,justifyContent:'flex-end',flexWrap:'wrap'}}>
        {report.errs.length>0&&<Button variant="secondary" icon="download" onClick={()=>notify(t('download_errors'),report.errs.length+' '+t('rows'))}>{t('download_errors')}</Button>}
        {mode==='section'&&<Button variant="secondary" onClick={()=>go('section',sectionId)}>{lang==='en'?'Open section':'فتح الشعبة'}</Button>}
        <Button icon="rotate-ccw" onClick={()=>{setStep(0);setFile(null);setRes(null);setReport(null)}}>{t('new_import2')}</Button>
      </div>
    </div>}
    </Panel>
  </div>;
}
Object.assign(window,{ImportsScreen,ImportWizard});
