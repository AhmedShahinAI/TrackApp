const SEC_ST={pending:['pending','بانتظار الاعتماد','Pending approval'],returned:['warning','معادة للتعديل','Returned'],rejected:['error','مرفوضة','Rejected'],active:['active','معتمدة','Approved']};
function SecStatus({s}){const {StatusBadge}=window.DS;const {lang}=useApp();const x=SEC_ST[s||'active'];return <StatusBadge status={x[0]}>{lang==='en'?x[2]:x[1]}</StatusBadge>;}
const secDup=(st,f,selfId)=>st.S.sections.find(s=>s.id!==selfId&&s.status!=='rejected'&&s.term===+f.term&&s.branch===+f.branch&&s.course===+f.course&&s.no===String(f.no).trim());

function SectionRequestDialog({sec,onClose,notify}){
  const {Dialog,Button,Input,Select,Alert,Checkbox}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();const en=lang==='en';const isNew=!sec;
  const [f,setF]=React.useState(sec?{term:sec.term,branch:sec.branch,course:sec.course,no:sec.no,program:String(sec.program),suggested:sec.suggested||[],note:sec.note||''}:{term:st.currentTerm().id,branch:'',course:'',no:'',program:'',suggested:[],note:''});
  const [err,setErr]=React.useState(null);const [busy,setBusy]=React.useState(false);
  const fref=React.useRef(null);const [file,setFile]=React.useState(null);const [fErr,setFErr]=React.useState(null);
  const readRoster=p=>{const m=p.meta||{};const H=p.headers.map(h=>String(h||'').trim());const col=re=>H.findIndex(h=>re.test(h));const c={seq:col(/تسلسل|seq/i),uid:col(/رقم الطالب|الرقم الجامعي|university id/i),ar:col(/اسم الطالب|name/i),acad:col(/^الحالة$|status/i)};
    if(c.uid<0||c.ar<0)throw new Error('cols');
    const g=/طالبات|بنات/.test(m.campus||'')?'female':/طلاب|بنين/.test(m.campus||'')?'male':null;
    const b=st.S.branches.find(x=>x.active&&(m.campus||'').includes(x.ar)&&(!g||x.gender===g));
    const co=st.S.courses.find(x=>x.ar===(m.course||'').trim()||x.code.toLowerCase()===(m.course||'').trim().toLowerCase()||x.en.toLowerCase()===(m.course||'').trim().toLowerCase());
    const seen={};const rows=p.rows.map((r,k)=>{const v=i=>i>=0?String(r[i]||'').trim():'';const x={seq:+v(c.seq)||k+1,uid:v(c.uid).replace(/\D/g,''),ar:v(c.ar),acad:v(c.acad)||'منتظم'};
      x.ok=/^\d{9}$/.test(x.uid)&&!!x.ar&&!seen[x.uid];if(x.ok)seen[x.uid]=1;x.exists=!!st.S.students.find(s=>s.uid===x.uid);return x;});
    return {name:p.name,meta:m,branch:b,course:co,rows};};
  const applyFile=r=>{setFile(r);setErr(null);const n={...f};if(r.branch)n.branch=r.branch.id;if(r.course)n.course=r.course.id;if(r.meta.section)n.no=r.meta.section;setF(n);
    const miss=[!r.branch&&(en?'campus':'المقر'),!r.course&&(en?'course':'المقرر'),!r.meta.section&&(en?'section number':'رقم الشعبة')].filter(Boolean);
    setFErr(miss.length?(en?'Could not match from the file: ':'تعذّر تحديد من الملف: ')+miss.join('، ')+(en?'. Select it manually.':'. اختره يدويًا.'):null);};
  const onFile=async ev=>{const x=ev.target.files&&ev.target.files[0];ev.target.value='';if(!x)return;try{applyFile(readRoster(await window.parseRosterFile(x)));}catch(e){setFile(null);setFErr(e.message==='cols'?(en?'The file must contain “Student no.” and “Student name” columns. Download the guide template.':'يجب أن يحتوي الملف على عمودي «رقم الطالب» و«اسم الطالب». حمّل النموذج الاسترشادي.'):(en?'Unsupported file. Use the xlsx exported from the academic system.':'نوع الملف غير مدعوم. استخدم ملف xlsx المصدّر من النظام الأكاديمي.'));}};
  const sampleFile=()=>{const R=st.REAL_FILE;applyFile(readRoster({name:'filedownloadservlet(22).xlsx',meta:{...R.meta,section:'2305'},headers:R.headers,rows:R.rows}));};
  const guide=()=>downloadTemplate('نموذج_ملف_الشعبة.csv',[['المقر','المجمعة (طلاب)'],['الدرجة','الدبلوم المتوسط'],['اسم المقرر','برمجة 2'],['النشاط','محاضرة'],['الشعبة','2298'],[''],['تسلسل','رقم الطالب','اسم الطالب','الحالة'],['1','442104959','سعود بن محمد بن ابراهيم الشدوخي','منتظم'],['2','462103556','علي بن ابراهيم بن علي حدادي','منتظم'],['3','462109105','عبدالعزيز بن عبدالرحمن بن عبدالعزيز الجويعي','منتظم']]);
  const set=(k,v)=>{setF({...f,[k]:v});setErr(null);};
  const save=()=>{if(!f.branch||!f.course||!String(f.no).trim()||!f.program){setErr(en?'Branch, course, section number and program are required.':'الفرع والمقرر ورقم الشعبة والبرنامج حقول مطلوبة.');return;}
    const d=secDup(st,f,sec&&sec.id);if(d){setErr(t('dup_section',{k:st.course(d.course).code+' / '+d.no+' / '+st.branchLabel(d.branch,lang)}));return;}
    setBusy(true);setTimeout(()=>{const data={term:+f.term,branch:+f.branch,course:+f.course,no:String(f.no).trim(),program:+f.program,suggested:f.suggested,note:f.note.trim()};
      if(isNew){const s={id:Date.now(),...data,instructor:me.id,status:'pending',req:{by:me.id,at:st.now().slice(0,19),file:file&&file.name},degree:(file&&file.meta.degree)||'البكالوريوس',activity:(file&&file.meta.activity)||'محاضرة'};st.S.sections.push(s);st.S.history.push({section:s.id,instructor:me.id,from:st.now().slice(0,10),to:null,by:me.id});
        let nNew=0,nEnr=0;if(file)file.rows.filter(r=>r.ok).forEach((r,i)=>{let stu=st.S.students.find(x=>x.uid===r.uid);if(!stu){stu={id:Date.now()+10+i,uid:r.uid,ar:r.ar,en:'',email:r.uid+'@s.mu.edu.sa',phone:'',program:s.program,pw:r.uid,mustChange:true,active:true,firstLogin:null,lastLogin:null,role:'STUDENT',branches:[]};st.S.students.push(stu);nNew++;}
          st.S.enrollments.push({id:Date.now()+500+i,student:stu.id,section:s.id,status:'active',enrolled:st.now().slice(0,10),acad:r.acad,seq:r.seq});nEnr++;});
        if(file)st.S.imports.unshift({id:Date.now()+3,mode:'section',term:s.term,section:s.id,file:file.name,hash:'',by:me.id,at:st.now().slice(0,19),status:'completed',total:file.rows.length,processed:file.rows.length,imported:nEnr,duplicates:0,errors:file.rows.length-nEnr});
        st.audit(me.id,'section.requested','section',s.id,null,{course:st.course(s.course).code,no:s.no,branch:st.branchLabel(s.branch,'ar')});st.emit();setBusy(false);onClose();notify(en?'Sent for approval':'أُرسلت للاعتماد',en?'The branch coordinator or Super Admin will link it to an initiative.':'سيربطها منسق الفرع أو السوبر أدمن بالمبادرة.');go('section',s.id);}
      else{Object.assign(sec,data,{status:'pending',req:{by:me.id,at:st.now().slice(0,19)}});st.audit(me.id,'section.resubmitted','section',sec.id,null,{no:sec.no});st.emit();setBusy(false);onClose();notify(en?'Resubmitted':'أُعيد الإرسال');}},500);};
  return <Dialog title={isNew?(en?'Add a section':'إضافة شعبة'):(en?'Edit and resubmit':'تعديل وإعادة الإرسال')} onClose={onClose} width={680}
    footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button icon="send" loading={busy} onClick={save}>{en?'Send for approval':'إرسال للاعتماد'}</Button></>}>
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
      {isNew&&<div style={{gridColumn:'1 / -1',border:'2px dashed var(--border-strong)',borderRadius:'var(--radius-lg)',padding:'16px',background:'var(--gray-50)'}}>
        <div style={{display:'flex',alignItems:'flex-start',gap:12}}>
          <window.DS.Icon name="file-spreadsheet" size={28} color="var(--track-green)"/>
          <div style={{flex:1,minWidth:0}}>
            <div style={{font:'700 14px var(--font-sans)'}}>{en?'Section file from the academic system':'ملف الشعبة من النظام الأكاديمي'}</div>
            <div style={{font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)',marginTop:2}}>{en?'Upload the section list exported from the academic system. Campus, course and section number fill in automatically, and the students are added to the section.':'ارفع كشف الشعبة المصدّر من النظام الأكاديمي، وستُعبأ بيانات المقر والمقرر ورقم الشعبة تلقائيًا ويُضاف الطلبة للشعبة.'}</div>
            <input ref={fref} type="file" accept=".xlsx,.csv" onChange={onFile} style={{display:'none'}}/>
            <div style={{display:'flex',gap:8,marginTop:10,flexWrap:'wrap'}}><Button size="sm" icon="upload" onClick={()=>fref.current&&fref.current.click()}>{file?(en?'Replace file':'استبدال الملف'):(en?'Upload section file':'رفع ملف الشعبة')}</Button><Button size="sm" variant="secondary" icon="download" onClick={guide}>{en?'Guide template':'النموذج الاسترشادي'}</Button><Button size="sm" variant="ghost" onClick={sampleFile}>{en?'Use sample file':'استخدام ملف تجريبي'}</Button></div>
          </div>
        </div>
        {file&&(()=>{const ok=file.rows.filter(r=>r.ok);const nw=ok.filter(r=>!r.exists).length;const bad=file.rows.length-ok.length;
          return <div style={{marginTop:12,paddingTop:12,borderTop:'1px solid var(--border-default)'}}>
            <div style={{display:'flex',flexWrap:'wrap',gap:'4px 14px',font:'600 12px var(--font-sans)'}}><span>{file.name}</span><span style={{color:'var(--text-secondary)'}}>{[file.meta.campus,file.meta.degree,file.meta.course,file.meta.activity,(en?'Section ':'شعبة ')+(file.meta.section||'—')].filter(Boolean).join(' · ')}</span></div>
            <div style={{display:'flex',flexWrap:'wrap',gap:14,marginTop:6,font:'500 12px var(--font-sans)'}}><span style={{color:'var(--green-700)'}}>{ok.length} {en?'students to enroll':'طالب سيُسجّل'}</span><span style={{color:'var(--text-secondary)'}}>{nw} {en?'new accounts':'حساب جديد'}</span>{bad>0&&<span style={{color:'var(--red-600)'}}>{bad} {en?'rows skipped (invalid or duplicate ID)':'صف متجاهَل (رقم غير صالح أو مكرر)'}</span>}</div>
            <div style={{maxHeight:150,overflow:'auto',marginTop:8,border:'1px solid var(--border-default)',borderRadius:8,background:'#fff'}}>{file.rows.map((r,i)=><div key={i} style={{display:'grid',gridTemplateColumns:'36px 96px 1fr auto',gap:8,padding:'5px 10px',borderBottom:'1px solid var(--gray-100)',font:'400 12px var(--font-sans)',color:r.ok?'var(--text-primary)':'var(--red-600)'}}><span style={{color:'var(--text-secondary)'}}>{r.seq}</span><span style={{fontFamily:'var(--font-latin)'}}>{r.uid||'—'}</span><span style={{overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{r.ar}</span><span style={{color:'var(--text-secondary)'}}>{r.acad}</span></div>)}</div>
          </div>;})()}
        {fErr&&<div style={{marginTop:10}}><Alert tone="warning">{fErr}</Alert></div>}
      </div>}
      <Select label={t('f_term')} value={f.term} onChange={e=>set('term',e.target.value)} options={st.S.terms.filter(x=>x.status!=='closed').map(x=>({value:x.id,label:en?x.en:x.ar}))}/>
      <Select label={t('branch')} required value={f.branch} onChange={e=>set('branch',e.target.value)} options={[{value:'',label:en?'Select…':'اختر…'},...st.S.branches.filter(b=>b.active).map(b=>({value:b.id,label:st.branchLabel(b.id,lang)}))]}/>
      <Select label={t('course')} required value={f.course} onChange={e=>set('course',e.target.value)} options={[{value:'',label:en?'Select…':'اختر…'},...st.S.courses.map(c=>({value:c.id,label:c.code+' · '+(en?c.en:c.ar)}))]}/>
      <Input label={t('section_no')} required dir="ltr" value={f.no} onChange={e=>set('no',e.target.value)}/>
      <Select label={en?'Academic program':'البرنامج الأكاديمي'} required value={f.program} onChange={e=>set('program',e.target.value)} options={[{value:'',label:en?'Select program…':'اختر البرنامج…'},...st.S.programs.filter(p=>p.active).map(p=>({value:p.id,label:p.code+' · '+(en?p.en:p.ar)}))]} style={{gridColumn:'1 / -1'}}/>
      <div style={{gridColumn:'1 / -1'}}>
        <div style={{font:'600 13px var(--font-sans)',marginBottom:4}}>{en?'Suggested initiative (optional)':'المبادرة المقترحة (اختياري)'}</div>
        <div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)',marginBottom:8}}>{en?'A suggestion only. The coordinator or Super Admin decides the final link.':'اقتراح فقط، ويحدد منسق الفرع أو السوبر أدمن الربط النهائي.'}</div>
        <div style={{display:'flex',flexWrap:'wrap',gap:16}}>{st.initiatives.filter(i=>i.status!=='closed').map(i=><Checkbox key={i.id} label={en?i.en:i.ar} checked={f.suggested.includes(i.id)} onChange={v=>set('suggested',v?[...f.suggested,i.id]:f.suggested.filter(x=>x!==i.id))}/>)}</div>
      </div>
      <div style={{gridColumn:'1 / -1'}}><Field label={en?'Note to approver':'ملاحظة لجهة الاعتماد'}><textarea value={f.note} onChange={e=>set('note',e.target.value)} style={{...nativeStyle,height:70,padding:'8px 12px',resize:'vertical'}}></textarea></Field></div>
      {err&&<Alert tone="danger" style={{gridColumn:'1 / -1'}}>{err}</Alert>}
    </div>
    <p style={{margin:'12px 0 0',font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)'}}>{en?'You are set as the instructor. You can upload the student list now; stage tracking opens after approval.':'ستُسجَّل مدرسًا للشعبة. يمكنك رفع قائمة الطلبة الآن، ويُفتح تسجيل المراحل بعد الاعتماد.'}</p>
  </Dialog>;
}

function ApprovalDialog({sec,onClose,notify}){
  const {Dialog,Button,Alert,Checkbox,Tabs}=window.DS;const {t,me,lang}=useApp();const st=useStore();const en=lang==='en';
  const [pick,setPick]=React.useState((sec.suggested||[]).slice());const [mode,setMode]=React.useState('approve');const [reason,setReason]=React.useState('');const [err,setErr]=React.useState(null);const [busy,setBusy]=React.useState(false);
  const c=st.course(sec.course);const n=st.S.enrollments.filter(e=>e.section===sec.id&&e.status==='active').length;const dup=secDup(st,sec,sec.id);const open=sec.status==='pending';
  const go_=()=>{if(mode==='approve'&&!pick.length){setErr(en?'Select at least one initiative.':'اختر مبادرة واحدة على الأقل.');return;}if(mode!=='approve'&&!reason.trim()){setErr(en?'A reason is required.':'السبب مطلوب.');return;}
    setBusy(true);setTimeout(()=>{const at=st.now().slice(0,19);
      if(mode==='approve'){sec.status='active';pick.forEach((ini,i)=>{if(!st.S.assignments.some(a=>a.section===sec.id&&a.ini===ini))st.S.assignments.push({id:Date.now()+i,section:sec.id,ini,stages:(st.TPL[ini]||['تسجيل الطالب','التوثيق','الانتهاء']).slice()});});sec.decision={kind:'approved',by:me.id,at,inis:pick};
        st.audit(me.id,'section.approved','section',sec.id,{status:'pending'},{status:'active',initiatives:pick.map(i=>st.iniName({id:i},'ar'))});notify(en?'Section approved':'تم اعتماد الشعبة',pick.map(i=>st.iniName({id:i},lang)).join('، '));}
      else{sec.status=mode==='return'?'returned':'rejected';sec.decision={kind:sec.status,by:me.id,at,reason:reason.trim()};st.audit(me.id,mode==='return'?'section.returned':'section.rejected','section',sec.id,{status:'pending'},{status:sec.status,reason:reason.trim()});notify(mode==='return'?(en?'Returned to instructor':'أُعيدت للمدرس'):(en?'Request rejected':'تم رفض الطلب'));}
      st.emit();setBusy(false);onClose();},500);};
  const facts=[[t('course'),c.code+' · '+(en?c.en:c.ar)],[t('section_no'),sec.no],[t('branch'),st.branchLabel(sec.branch,lang)],[en?'Program':'البرنامج',st.programName(sec.program,lang)],[t('instructor'),st.uname(sec.instructor,lang)],[en?'Students uploaded':'الطلبة المرفوعون',n],[en?'Submitted':'تاريخ الطلب',fmtDateTime(sec.req.at)]];
  return <Dialog title={en?'Review section request':'مراجعة طلب إضافة شعبة'} onClose={onClose} width={680}
    footer={open?<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button variant={mode==='reject'?'danger':'primary'} icon={mode==='approve'?'circle-check':mode==='return'?'undo-2':'circle-x'} loading={busy} onClick={go_}>{mode==='approve'?(en?'Approve and link':'اعتماد وربط'):mode==='return'?(en?'Return to instructor':'إعادة للمدرس'):(en?'Reject':'رفض الطلب')}</Button></>:<Button onClick={onClose}>{t('close')}</Button>}>
    <div style={{display:'flex',flexDirection:'column',gap:14}}>
      <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(180px,1fr))',border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',overflow:'hidden'}}>
        {facts.map(([l,v])=><div key={l} style={{padding:'10px 14px',borderBottom:'1px solid var(--gray-100)',borderInlineEnd:'1px solid var(--gray-100)'}}><div style={{font:'500 12px var(--font-sans)',color:'var(--text-secondary)'}}>{l}</div><div style={{font:'700 14px var(--font-sans)',marginTop:2}}>{v}</div></div>)}
      </div>
      {sec.note&&<Alert tone="info" title={en?'Instructor note':'ملاحظة المدرس'}>{sec.note}</Alert>}
      {dup&&<Alert tone="warning">{en?'A matching section already exists: ':'توجد شعبة مطابقة مسبقًا: '}{st.course(dup.course).code+' / '+dup.no+' / '+st.branchLabel(dup.branch,lang)}</Alert>}
      {!open&&sec.decision&&<Alert tone={sec.status==='rejected'?'danger':'warning'} title={SEC_ST[sec.status][en?2:1]+' · '+st.uname(sec.decision.by,lang)+' · '+fmtDateTime(sec.decision.at)}>{sec.decision.reason}</Alert>}
      {open&&<>
        <Tabs variant="segmented" value={mode} onChange={v=>{setMode(v);setErr(null);}} tabs={[{id:'approve',label:en?'Approve':'اعتماد'},{id:'return',label:en?'Return for edits':'إعادة للتعديل'},{id:'reject',label:en?'Reject':'رفض'}]}/>
        {mode==='approve'?<div>
          <div style={{font:'600 13px var(--font-sans)',marginBottom:8}}>{en?'Link to initiatives':'ربط الشعبة بالمبادرات'}<span style={{color:'var(--red-600)'}}> *</span></div>
          <div style={{display:'flex',flexDirection:'column',gap:8}}>{st.initiatives.filter(i=>i.status!=='closed').map(i=><div key={i.id} style={{display:'flex',alignItems:'center',justifyContent:'space-between',gap:10,padding:'10px 12px',border:'1px solid '+(pick.includes(i.id)?'var(--green-200)':'var(--border-default)'),borderRadius:'var(--radius-md)',background:pick.includes(i.id)?'var(--green-50)':'#fff'}}>
            <Checkbox label={en?i.en:i.ar} checked={pick.includes(i.id)} onChange={v=>{setPick(v?[...pick,i.id]:pick.filter(x=>x!==i.id));setErr(null);}}/>
            <span style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{(sec.suggested||[]).includes(i.id)&&<b style={{color:'var(--track-blue)',marginInlineEnd:8}}>{en?'Suggested':'مقترحة من المدرس'}</b>}{(st.TPL[i.id]||[]).join(' ← ')}</span></div>)}</div>
          <div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)',marginTop:8}}>{en?'Default stages are applied; you can edit them later from the section page.':'تُطبَّق المراحل الافتراضية، ويمكن تعديلها لاحقًا من صفحة الشعبة.'}</div>
        </div>:<Field label={en?'Reason (shown to the instructor)':'السبب (يظهر للمدرس)'} required><textarea value={reason} onChange={e=>{setReason(e.target.value);setErr(null);}} style={{...nativeStyle,height:80,padding:'8px 12px',resize:'vertical'}}></textarea></Field>}
        {err&&<Alert tone="danger">{err}</Alert>}
      </>}
    </div>
  </Dialog>;
}

function ApprovalsScreen({notify}){
  const {DataTable,Tabs,Input}=window.DS;const {t,me,lang}=useApp();const st=useStore();const en=lang==='en';
  const [tab,setTab]=React.useState('pending');const [q,setQ]=React.useState('');const [open,setOpen]=React.useState(null);
  const all=st.approvalScope(me).slice().sort((a,b)=>b.req.at.localeCompare(a.req.at));
  const rows=all.filter(s=>(tab==='all'||s.status===tab)&&(st.course(s.course).code+s.no+st.uname(s.instructor,lang)).toLowerCase().includes(q.toLowerCase()));
  const cnt=k=>all.filter(s=>s.status===k).length;
  return <div>
    <PageHeader title={t('nav_approvals')} subtitle={me.role==='SUPER_ADMIN'?(en?'All section requests from instructors across every branch. Approve and link to initiatives, return for edits, or reject.':'جميع طلبات إضافة الشعب من المدرسين في كل الفروع. اعتمد واربط بالمبادرات، أو أعد للتعديل، أو ارفض.'):(en?'Section requests from instructors in your branches.':'طلبات إضافة الشعب من المدرسين في فروعك.')}/>
    <Panel>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:16,padding:'8px 20px 0',flexWrap:'wrap'}}>
        <Tabs value={tab} onChange={setTab} tabs={[...['pending','returned','rejected','active'].map(k=>({id:k,label:SEC_ST[k][en?2:1],count:cnt(k)})),{id:'all',label:t('all'),count:all.length}]}/>
        <Input size="sm" icon="search" placeholder={en?'Course, section, instructor':'المقرر، الشعبة، المدرس'} value={q} onChange={e=>setQ(e.target.value)} style={{width:240}}/>
      </div>
      <div style={{padding:20}}><DataTable onRowClick={setOpen} columns={[
        {key:'c',label:t('course'),render:r=>{const c=st.course(r.course);return <span><span style={{fontFamily:'var(--font-latin)',fontWeight:700}}>{c.code}</span> <span style={{color:'var(--text-secondary)'}}>· {en?c.en:c.ar}</span></span>;}},
        {key:'no',label:t('section_no'),numeric:true,render:r=><b>{r.no}</b>},
        {key:'b',label:t('branch'),render:r=>st.branchLabel(r.branch,lang)},
        {key:'i',label:t('instructor'),render:r=>st.uname(r.instructor,lang)},
        {key:'sg',label:en?'Initiative':'المبادرة',wrap:true,render:r=>r.status==='active'?st.S.assignments.filter(a=>a.section===r.id).map(a=>st.iniName(a,lang)).join('، '):(r.suggested||[]).length?<span style={{color:'var(--text-secondary)'}}>{(en?'Suggested: ':'مقترحة: ')+r.suggested.map(i=>st.iniName({id:i},lang)).join('، ')}</span>:'—'},
        {key:'n',label:t('students_n'),numeric:true,render:r=>st.S.enrollments.filter(e=>e.section===r.id&&e.status==='active').length},
        {key:'at',label:en?'Submitted':'تاريخ الطلب',numeric:true,render:r=>fmtDateTime(r.req.at)},
        {key:'s',label:t('status'),render:r=><SecStatus s={r.status}/>}]} rows={rows} empty={en?'No requests in this tab':'لا توجد طلبات في هذا التبويب'}/></div>
    </Panel>
    {open&&<ApprovalDialog sec={open} onClose={()=>setOpen(null)} notify={notify}/>}
  </div>;
}
Object.assign(window,{SEC_ST,SecStatus,SectionRequestDialog,ApprovalDialog,ApprovalsScreen});
