const STU_HDR=['اسم الطالب','الرقم الجامعي','البريد الإلكتروني الجامعي','رقم الجوال','البرنامج'];
const stuEmailOk=v=>/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v);
const stuPhoneNorm=v=>{v=String(v||'').replace(/[\s-]/g,'').replace(/^\+?966/,'0');if(/^5\d{8}$/.test(v))v='0'+v;return v;};
const stuPhoneOk=v=>/^05\d{8}$/.test(v);
function stuProgramOf(st,v){v=String(v||'').trim();if(!v)return null;const l=v.toLowerCase();const p=st.S.programs.find(p=>p.code.toLowerCase()===l||p.ar===v||p.en.toLowerCase()===l)||st.S.programs.find(p=>v.includes(p.ar)||p.ar.includes(v));return p?p.id:'bad';}

function StudentsAdminScreen({notify}){
  const {Button,DataTable,StatusBadge,Input,IconButton,Tooltip,Select}=window.DS;const {t,me,lang,go}=useApp();const st=useStore();const en=lang==='en';const admin=me.role==='SUPER_ADMIN';
  const [q,setQ]=React.useState('');const [prog,setProg]=React.useState('');const [ini,setIni]=React.useState('');const [sec,setSec]=React.useState('');const [br,setBr]=React.useState('');const [edit,setEdit]=React.useState(null);const [imp,setImp]=React.useState(false);const [conf,setConf]=React.useState(null);const [busy,setBusy]=React.useState(false);
  const scopeSecs=st.sectionScope(me).filter(s=>s.term===st.currentTerm().id);const scopeIds=new Set(scopeSecs.map(s=>s.id));
  const info=s=>{const secs=st.studentEnrollments(s.id).filter(e=>scopeIds.has(e.section)).map(e=>e.section);const inis=[...new Set(st.S.assignments.filter(a=>secs.includes(a.section)).map(a=>a.ini))];const tk=st.S.tickets.filter(x=>x.student===s.id&&x.status!=='resolved').length;return {secs,inis,tk};};
  const base=st.studentScope(me);
  const rows=base.map(s=>({...s,_o:s,_x:info(s)})).filter(s=>(!prog||String(s.program)===prog)&&(!ini||s._x.inis.includes(+ini))&&(!sec||s._x.secs.includes(+sec))&&(!br||s._x.secs.some(id=>st.S.sections.find(x=>x.id===id).branch===+br))&&(s.ar+s.uid+(s.email||'')+(s.phone||'')).toLowerCase().includes(q.toLowerCase()));
  const visBr=admin?st.S.branches:st.S.branches.filter(b=>scopeSecs.some(s=>s.branch===b.id));
  const doConf=()=>{setBusy(true);setTimeout(()=>{const s=conf.s._o||conf.s;
    if(conf.kind==='deact'){s.active=!s.active;st.audit(me.id,s.active?'student.reactivated':'student.deactivated','student',s.id,{is_active:!s.active},{is_active:s.active});notify(t('user_saved'));}
    else{s.pw=s.uid;s.mustChange=true;st.audit(me.id,'student.password_reset','student',s.id,null,{to:'university_id'});notify(t('reset_done'),en?'Password is now the university ID':'كلمة المرور الآن هي الرقم الجامعي');}
    st.emit();setBusy(false);setConf(null);},500);};
  return <div>
    <PageHeader title={t('nav_studentsadmin')} subtitle={admin?(en?'All students. Accounts sign in with the university email; the first password is the university ID.':'جميع الطلبة. يدخل الطالب ببريده الجامعي، وكلمة المرور الأولى هي رقمه الجامعي.'):(en?'Students in the sections within your scope this term.':'الطلبة المسجلون في الشعب ضمن نطاق صلاحيتك للفصل الحالي.')}
      actions={admin&&<><Button variant="secondary" icon="file-spreadsheet" onClick={()=>setImp(true)}>{en?'Import from Excel':'استيراد من Excel'}</Button><Button icon="user-plus" onClick={()=>setEdit({})}>{en?'New student':'طالب جديد'}</Button></>}/>
    <Panel>
      <div style={{display:'flex',gap:10,padding:'16px 20px 0',flexWrap:'wrap',alignItems:'center'}}>
        <Input size="sm" icon="search" placeholder={en?'Name, ID, email, phone':'الاسم، الرقم الجامعي، البريد، الجوال'} value={q} onChange={e=>setQ(e.target.value)} style={{width:240}}/>
        <Select size="sm" icon="lightbulb" value={ini} onChange={e=>setIni(e.target.value)} options={[{value:'',label:en?'All initiatives':'كل المبادرات'},...st.initiatives.map(i=>({value:String(i.id),label:en?i.en:i.ar}))]} style={{width:200}}/>
        <Select size="sm" icon="users" value={sec} onChange={e=>setSec(e.target.value)} options={[{value:'',label:en?'All sections':'كل الشعب'},...scopeSecs.map(s=>({value:String(s.id),label:st.course(s.course).code+' · '+s.no}))]} style={{width:180}}/>
        {visBr.length>1&&<Select size="sm" icon="building-2" value={br} onChange={e=>setBr(e.target.value)} options={[{value:'',label:en?'All branches':'كل الفروع'},...visBr.map(b=>({value:String(b.id),label:st.branchLabel(b.id,lang)}))]} style={{width:190}}/>}
        <Select size="sm" icon="graduation-cap" value={prog} onChange={e=>setProg(e.target.value)} options={[{value:'',label:en?'All programs':'كل البرامج'},...st.S.programs.map(p=>({value:String(p.id),label:en?p.en:p.ar}))]} style={{width:200}}/>
        <span style={{marginInlineStart:'auto',font:'600 13px var(--font-sans)',color:'var(--text-secondary)'}}>{rows.length} {en?'students':'طالب'}</span>
      </div>
      <div style={{padding:20}}><DataTable onRowClick={r=>go('student',r.id)} columns={[
        {key:'ar',label:en?'Student':'اسم الطالب',wrap:true,render:r=><b>{r.ar}</b>},
        {key:'uid',label:en?'University ID':'الرقم الجامعي',numeric:true},
        {key:'email',label:en?'University email':'البريد الجامعي',numeric:true,render:r=>r.email||'—'},
        {key:'phone',label:en?'Phone':'الجوال',numeric:true,render:r=>r.phone||'—'},
        {key:'p',label:en?'Program':'البرنامج',render:r=>r.program?st.programName(r.program,lang):'—'},
        {key:'sn',label:en?'Sections':'الشعب',numeric:true,render:r=>r._x.secs.length},
        {key:'in',label:en?'Initiatives':'المبادرات',wrap:true,render:r=>r._x.inis.map(i=>st.iniName({id:i},lang)).join('، ')||'—'},
        {key:'tk',label:en?'Open requests':'طلبات مفتوحة',numeric:true,render:r=>r._x.tk?<span style={{font:'700 12px var(--font-numeric)',color:'var(--amber-600)'}}>{r._x.tk}</span>:'—'},
        ...(admin?[{key:'fl',label:en?'Password':'كلمة المرور',render:r=>r.mustChange?<span style={{font:'500 12px var(--font-sans)',color:'var(--amber-600)'}}>{en?'Temporary (ID)':'مؤقتة (الرقم الجامعي)'}</span>:<span style={{font:'500 12px var(--font-sans)',color:'var(--green-700)'}}>{en?'Changed':'تم تغييرها'}</span>}]:[]),
        {key:'s',label:t('status'),render:r=><StatusBadge status={r.active?'active':'inactive'}/>},
        ...(admin?[{key:'x',label:'',render:r=><div style={{display:'flex',gap:4}} onClick={e=>e.stopPropagation()}>
          <Tooltip content={t('edit')}><IconButton icon="pencil" label={t('edit')} size={32} onClick={()=>setEdit(r._o)}/></Tooltip>
          <Tooltip content={t('reset_pw')}><IconButton icon="key-round" label={t('reset_pw')} size={32} onClick={()=>setConf({kind:'reset',s:r})}/></Tooltip>
          <Tooltip content={r.active?t('deactivate'):t('reactivate')}><IconButton icon={r.active?'user-x':'user-check'} label={r.active?t('deactivate'):t('reactivate')} size={32} onClick={()=>setConf({kind:'deact',s:r})}/></Tooltip>
        </div>}]:[])]} rows={rows}/></div>
    </Panel>
    {edit&&<StudentDialog stu={edit} onClose={()=>setEdit(null)} notify={notify}/>}
    {imp&&<StudentImportDialog onClose={()=>setImp(false)} notify={notify}/>}
    <ConfirmDialog open={!!conf} busy={busy} onClose={()=>setConf(null)} onConfirm={doConf}
      title={conf&&(conf.kind==='deact'?(conf.s.active?t('deact_q',{n:conf.s.ar}):t('reactivate')):t('reset_q',{n:conf.s.ar}))}
      impact={conf&&(conf.kind==='deact'?t('deact_impact'):(en?'The password returns to the university ID and the student must change it at next sign-in.':'تعود كلمة المرور إلى الرقم الجامعي، ويُطلب من الطالب تغييرها عند الدخول التالي.'))}
      confirmLabel={conf&&(conf.kind==='deact'?(conf.s.active?t('deactivate'):t('reactivate')):t('reset_pw'))} tone={conf&&conf.kind==='deact'&&conf.s.active?'danger':'info'}/>
  </div>;
}

function StudentDialog({stu,onClose,notify}){
  const {Dialog,Button,Input,Select,Alert}=window.DS;const {t,me,lang}=useApp();const st=useStore();const en=lang==='en';const isNew=!stu.id;
  const [f,setF]=React.useState({ar:stu.ar||'',uid:stu.uid||'',email:stu.email||'',phone:stu.phone||'',program:stu.program?String(stu.program):''});const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);
  const set=(k,v)=>{const n={...f,[k]:v};if(k==='uid'&&isNew&&(!f.email||f.email===f.uid+'@s.mu.edu.sa'))n.email=v?v+'@s.mu.edu.sa':'';setF(n);};
  const check=()=>{const n={};const ph=stuPhoneNorm(f.phone);
    n.ar=f.ar.trim()?null:t('required');
    n.uid=!f.uid?t('required'):!/^\d{9}$/.test(f.uid)?(en?'University ID is 9 digits.':'الرقم الجامعي 9 أرقام.'):st.S.students.some(s=>s.uid===f.uid&&s.id!==stu.id)?(en?'ID already registered.':'الرقم الجامعي مسجل مسبقًا.'):null;
    n.email=!f.email?t('required'):!stuEmailOk(f.email)?t('email_bad'):st.S.students.some(s=>(s.email||'').toLowerCase()===f.email.toLowerCase()&&s.id!==stu.id)?t('email_taken'):null;
    n.phone=!f.phone?t('required'):!stuPhoneOk(ph)?(en?'Use 05XXXXXXXX.':'استخدم الصيغة 05XXXXXXXX.'):null;
    n.program=f.program?null:t('required');setE(n);return n;};
  const save=()=>{const n=check();if(Object.values(n).some(Boolean))return;setBusy(true);setTimeout(()=>{const d={ar:f.ar.trim(),uid:f.uid,email:f.email.toLowerCase(),phone:stuPhoneNorm(f.phone),program:+f.program};
    if(isNew){const s={id:Date.now(),en:'',...d,pw:d.uid,mustChange:true,active:true,firstLogin:null,lastLogin:null,role:'STUDENT',branches:[]};st.S.students.push(s);st.audit(me.id,'student.created','student',s.id,null,{name:s.ar,uid:s.uid,email:s.email});notify(en?'Student added':'تمت إضافة الطالب',s.email);}
    else{const old={email:stu.email,phone:stu.phone,program:stu.program};Object.assign(stu,d);st.audit(me.id,'student.updated','student',stu.id,old,{email:d.email,phone:d.phone,program:d.program});notify(t('user_saved'));}
    st.emit();setBusy(false);onClose();},500);};
  return <Dialog title={isNew?(en?'New student':'إضافة طالب'):(en?'Edit student':'تعديل بيانات الطالب')} onClose={onClose} width={600}
    footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{isNew?t('create'):t('save')}</Button></>}>
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
      <Input label={en?'Student name':'اسم الطالب'} required value={f.ar} error={e.ar} onChange={x=>set('ar',x.target.value)} style={{gridColumn:'1 / -1'}}/>
      <Input label={en?'University ID':'الرقم الجامعي'} required dir="ltr" icon="id-card" value={f.uid} error={e.uid} onChange={x=>set('uid',x.target.value.replace(/\D/g,'').slice(0,9))}/>
      <Input label={en?'Phone':'رقم الجوال'} required dir="ltr" icon="phone" placeholder="05XXXXXXXX" value={f.phone} error={e.phone} onChange={x=>set('phone',x.target.value)}/>
      <Input label={en?'University email':'البريد الإلكتروني الجامعي'} required dir="ltr" icon="mail" value={f.email} error={e.email} onChange={x=>set('email',x.target.value)} style={{gridColumn:'1 / -1'}}/>
      <Select label={en?'Program':'البرنامج'} required value={f.program} error={e.program} onChange={x=>set('program',x.target.value)} options={[{value:'',label:en?'Select a program':'اختر البرنامج'},...st.S.programs.filter(p=>p.active||String(p.id)===f.program).map(p=>({value:String(p.id),label:(en?p.en:p.ar)+' ('+p.code+')'}))]} style={{gridColumn:'1 / -1'}}/>
    </div>
    {isNew&&<div style={{marginTop:14}}><Alert tone="info">{en?'The initial password is the university ID. The student is asked to change it at first sign-in.':'كلمة المرور الأولية هي الرقم الجامعي، ويُطلب من الطالب تغييرها عند أول دخول.'}</Alert></div>}
  </Dialog>;
}

function StudentImportDialog({onClose,notify}){
  const {Dialog,Button,Alert,DataTable,StatusBadge,Icon,Checkbox}=window.DS;const {t,me,lang}=useApp();const st=useStore();const en=lang==='en';
  const ref=React.useRef(null);const [res,setRes]=React.useState(null);const [err,setErr]=React.useState(null);const [name,setName]=React.useState('');const [busy,setBusy]=React.useState(false);const [done,setDone]=React.useState(null);const [skipWarn,setSkipWarn]=React.useState(false);
  const validate=p=>{const H=p.headers.map(h=>String(h||'').trim());const col=re=>H.findIndex(h=>re.test(h));
    const c={ar:col(/اسم الطالب|^الاسم$|student name|^name$/i),uid:col(/الرقم الجامعي|رقم الطالب|university id|student id|^id$/i),email:col(/البريد|email/i),phone:col(/الجوال|الهاتف|التليفون|phone|mobile/i),prog:col(/البرنامج|التخصص|program|major/i)};
    if(c.uid<0||c.ar<0)throw new Error('cols');const seen={};
    return p.rows.map((r,k)=>{const n=k+2;const g=i=>i>=0?String(r[i]||'').trim():'';const u={id:n,n,ar:g(c.ar),uid:g(c.uid).replace(/\D/g,''),email:g(c.email).toLowerCase(),phone:stuPhoneNorm(g(c.phone)),progRaw:g(c.prog)};const errs=[],warns=[];
      if(!u.ar)errs.push(en?'Name missing':'الاسم مفقود');
      if(!/^\d{9}$/.test(u.uid))errs.push(en?'ID must be 9 digits':'الرقم الجامعي يجب أن يكون 9 أرقام');else if(seen[u.uid])errs.push((en?'Duplicate of row ':'مكرر مع الصف ')+seen[u.uid]);else seen[u.uid]=n;
      u.existing=st.S.students.find(s=>s.uid===u.uid)||null;
      if(!u.email&&u.uid){u.email=u.uid+'@s.mu.edu.sa';warns.push(en?'Email generated from ID':'وُلّد البريد من الرقم الجامعي');}
      else if(u.email&&!stuEmailOk(u.email))errs.push(en?'Invalid email':'بريد غير صالح');
      else if(st.S.students.some(s=>(s.email||'').toLowerCase()===u.email&&s.uid!==u.uid))errs.push(en?'Email belongs to another student':'البريد مسجل لطالب آخر');
      if(u.phone&&!stuPhoneOk(u.phone))errs.push(en?'Invalid phone':'رقم جوال غير صالح');else if(!u.phone)warns.push(en?'Phone missing':'رقم الجوال فارغ');
      const pg=stuProgramOf(st,u.progRaw);if(pg==='bad'){errs.push((en?'Unknown program: ':'برنامج غير معروف: ')+u.progRaw);u.program=null;}else if(!pg){errs.push(en?'Program missing':'البرنامج مفقود');u.program=null;}else u.program=pg;
      if(u.existing&&!errs.length)warns.push(en?'Existing student — details will be updated':'طالب مسجل — ستُحدَّث بياناته');
      u.status=errs.length?'error':warns.length?'warning':'valid';u.reason=[...errs,...warns].join(' · ')||(en?'Ready':'جاهز للإضافة');return u;});};
  const onFile=async ev=>{const f=ev.target.files&&ev.target.files[0];ev.target.value='';if(!f)return;try{const p=await window.parseRosterFile(f);setName(p.name);setErr(null);setRes(validate(p));}catch(x){setRes(null);setErr(x.message==='cols'?(en?'The file needs “Student name” and “University ID” columns. Download the template.':'يجب أن يحتوي الملف على عمودي «اسم الطالب» و«الرقم الجامعي» على الأقل. حمّل القالب.'):x.message==='size'?(en?'File exceeds 20 MB.':'الملف يتجاوز 20 ميجابايت.'):(en?'Unsupported file. Use xlsx or csv.':'نوع الملف غير مدعوم. استخدم xlsx أو csv.'));}};
  const sample=()=>{const ex=st.S.students[3];setName('students_batch.xlsx');setErr(null);setRes(validate({headers:STU_HDR,rows:[
    ['ليان بنت عبدالله القحطاني','445100211','445100211@s.mu.edu.sa','0551234567','HR'],
    ['محمد بن سعد الحربي','445100212','','0509876543','البرمجة وعلوم الحاسب'],
    ['نوف بنت فهد العتيبي','445100213','445100213@s.mu.edu.sa','+966561112233','التسويق'],
    [ex.ar,ex.uid,ex.email,'0598887766','ACC'],
    ['راكان بن ماجد الشمري','44510021','44510021@s.mu.edu.sa','0544443322','CS'],
    ['جود بنت سلطان المطيري','445100215','445100215@s.mu.edu.sa','12345','GD'],
    ['تركي بن نايف الدوسري','445100216','445100216@s.mu.edu.sa','0533332211','الطب'],
    ['ليان بنت عبدالله القحطاني','445100211','445100211@s.mu.edu.sa','0551234567','HR']]}));};
  const template=()=>downloadTemplate('track_students_template.csv',[STU_HDR,['سعود بن محمد الشدوخي','442104959','442104959@s.mu.edu.sa','0551234567','CS'],['لمى بنت خالد العنزي','445100300','445100300@s.mu.edu.sa','0509998877','الموارد البشرية']]);
  const ok=res?res.filter(r=>r.status==='valid'||(r.status==='warning'&&!skipWarn)):[];
  const run=()=>{setBusy(true);setTimeout(()=>{let add=0,upd=0;const out=ok.map((r,i)=>{if(r.existing){Object.assign(r.existing,{ar:r.ar,email:r.email,phone:r.phone||r.existing.phone,program:r.program});upd++;st.audit(me.id,'student.updated','student',r.existing.id,null,{uid:r.uid,source:name});return {...r,act:'upd'};}
      const s={id:Date.now()+i,en:'',ar:r.ar,uid:r.uid,email:r.email,phone:r.phone,program:r.program,pw:r.uid,mustChange:true,active:true,firstLogin:null,lastLogin:null,role:'STUDENT',branches:[]};st.S.students.push(s);add++;st.audit(me.id,'student.created','student',s.id,null,{name:s.ar,uid:s.uid,source:name});return {...r,act:'add'};});
    st.audit(me.id,'student.bulk_imported','student',null,null,{file:name,rows:res.length,created:add,updated:upd,skipped:res.length-out.length});st.emit();setBusy(false);setDone({out,add,upd});notify(en?'Students imported':'تم استيراد الطلاب',add+(en?' added, ':' أُضيف، ')+upd+(en?' updated':' حُدّث'));},700);};
  const cnt=k=>res?res.filter(r=>r.status===k).length:0;
  const cols=[{key:'n',label:en?'Row':'الصف',numeric:true},{key:'ar',label:en?'Name':'الاسم',wrap:true,render:r=><b>{r.ar||'—'}</b>},{key:'uid',label:en?'ID':'الرقم الجامعي',numeric:true,render:r=>r.uid||'—'},{key:'email',label:en?'Email':'البريد',numeric:true},{key:'phone',label:en?'Phone':'الجوال',numeric:true,render:r=>r.phone||'—'},{key:'p',label:en?'Program':'البرنامج',render:r=>r.program?st.programName(r.program,lang):(r.progRaw||'—')}];
  return <Dialog title={en?'Import students from Excel':'استيراد الطلاب من Excel'} onClose={onClose} width={1000}
    footer={done?<Button onClick={onClose}>{en?'Done':'تم'}</Button>:<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button icon="users" loading={busy} disabled={!ok.length} onClick={run}>{(en?'Import ':'استيراد ')+ok.length+(en?' students':' طالب')}</Button></>}>
    {done?<div style={{display:'flex',flexDirection:'column',gap:14}}>
      <Alert tone="success" title={(en?'Added ':'أُضيف ')+done.add+(en?' · updated ':' · حُدّث ')+done.upd}>{en?'New students sign in with their university email; the first password is their university ID.':'يدخل الطلاب الجدد ببريدهم الجامعي، وكلمة المرور الأولى هي الرقم الجامعي.'}</Alert>
      <div style={{maxHeight:360,overflow:'auto'}}><DataTable dense columns={[...cols.slice(1),{key:'a',label:en?'Action':'الإجراء',render:r=>r.act==='add'?(en?'Added':'إضافة'):(en?'Updated':'تحديث')}]} rows={done.out}/></div>
    </div>:<div style={{display:'flex',flexDirection:'column',gap:12}}>
      <div style={{border:'2px dashed var(--border-strong)',borderRadius:'var(--radius-lg)',padding:'22px 16px',textAlign:'center',background:'var(--gray-50)'}}>
        <Icon name="file-spreadsheet" size={30} color="var(--track-green)" style={{margin:'0 auto 8px'}}/>
        <div style={{font:'700 14px var(--font-sans)'}}>{en?'Upload a students file (xlsx or csv)':'ارفع ملف الطلاب (xlsx أو csv)'}</div>
        <div style={{font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)',marginTop:4}}>{en?'Columns: student name, university ID, university email, phone, program (code or name). Existing IDs are updated.':'الأعمدة: اسم الطالب، الرقم الجامعي، البريد الإلكتروني الجامعي، رقم الجوال، البرنامج (الرمز أو الاسم). الأرقام الجامعية الموجودة تُحدَّث بياناتها.'}</div>
        <input ref={ref} type="file" accept=".xlsx,.csv" onChange={onFile} style={{display:'none'}}/>
        <div style={{display:'flex',gap:8,justifyContent:'center',marginTop:12,flexWrap:'wrap'}}><Button size="sm" variant="secondary" icon="download" onClick={template}>{en?'Download template':'تنزيل القالب'}</Button><Button size="sm" variant="secondary" icon="folder-open" onClick={()=>ref.current&&ref.current.click()}>{en?'Choose file':'اختيار ملف'}</Button><Button size="sm" variant="ghost" onClick={sample}>{en?'Use sample file':'استخدام ملف تجريبي'}</Button></div>
      </div>
      {err&&<Alert tone="danger">{err}</Alert>}
      {res&&<>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:10,flexWrap:'wrap'}}>
          <span style={{font:'600 13px var(--font-sans)'}}>{name} · {res.length} {en?'rows':'صف'} · <span style={{color:'var(--green-700)'}}>{cnt('valid')} {en?'ready':'جاهز'}</span> · <span style={{color:'var(--amber-600)'}}>{cnt('warning')} {en?'with notes':'بملاحظات'}</span> · <span style={{color:'var(--red-600)'}}>{cnt('error')} {en?'rejected':'مرفوض'}</span></span>
          {cnt('warning')>0&&<Checkbox label={en?'Skip rows with notes':'تجاهل الصفوف ذات الملاحظات'} checked={skipWarn} onChange={setSkipWarn}/>}
        </div>
        <div style={{maxHeight:360,overflow:'auto'}}><DataTable dense columns={[...cols,{key:'s',label:en?'Result':'النتيجة',render:r=><StatusBadge status={r.status}/>},{key:'r',label:en?'Details':'التفاصيل',wrap:true,render:r=><span style={{font:'400 12px var(--font-sans)',color:r.status==='error'?'var(--red-600)':'var(--text-secondary)'}}>{r.reason}</span>}]} rows={res}/></div>
      </>}
    </div>}
  </Dialog>;
}
Object.assign(window,{StudentsAdminScreen,StudentDialog,StudentImportDialog});
