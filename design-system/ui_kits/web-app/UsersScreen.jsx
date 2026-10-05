function UsersScreen({notify}){
  const {Button,DataTable,StatusBadge,Input,Tabs,Dialog,Checkbox,IconButton,Tooltip}=window.DS;const {t,me,lang}=useApp();const st=useStore();
  const [tab,setTab]=React.useState('all');const [q,setQ]=React.useState('');const [edit,setEdit]=React.useState(null);const [conf,setConf]=React.useState(null);const [busy,setBusy]=React.useState(false);const [imp,setImp]=React.useState(false);
  const loading=useFakeLoad(tab);
  const rows=st.S.users.filter(u=>(tab==='all'||u.role===tab)&&(u.ar+u.en+u.email).toLowerCase().includes(q.toLowerCase()));
  const doConf=()=>{setBusy(true);setTimeout(()=>{const u=conf.u;
    if(conf.kind==='deact'){const old=u.active;u.active=!u.active;st.audit(me.id,u.active?'user.reactivated':'user.deactivated','user',u.id,{is_active:old},{is_active:u.active});notify(t('user_saved'));}
    else{u.pw='Reset'+Math.floor(1000+Math.random()*9000);st.audit(me.id,'user.password_reset','user',u.id,null,{sessions_revoked:true});notify(t('reset_done'),u.pw);}
    st.emit();setBusy(false);setConf(null);},500);};
  return <div>
    <PageHeader title={t('nav_users')} subtitle={t('users_sub')} actions={<><Button variant="secondary" icon="file-spreadsheet" onClick={()=>setImp(true)}>{lang==='en'?'Import from Excel':'استيراد من Excel'}</Button><Button icon="user-plus" onClick={()=>setEdit({})}>{t('new_user')}</Button></>}/>
    <Panel>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:16,padding:'8px 20px 0',flexWrap:'wrap'}}>
        <Tabs value={tab} onChange={setTab} tabs={[{id:'all',label:t('all'),count:st.S.users.length},...['SUPER_ADMIN','BRANCH_COORDINATOR','INSTRUCTOR'].map(r=>({id:r,label:t('role_'+r),count:st.S.users.filter(u=>u.role===r).length}))]}/>
        <Input size="sm" icon="search" placeholder={t('search')} value={q} onChange={e=>setQ(e.target.value)} style={{width:240}}/>
      </div>
      <div style={{padding:20}}>
        <DataTable loading={loading} columns={[
          {key:'n',label:t('name_ar'),render:r=><div><div style={{fontWeight:700}}>{lang==='en'?r.en:r.ar}</div><div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{lang==='en'?r.ar:r.en}</div></div>},
          {key:'email',label:t('email'),numeric:true},
          {key:'role',label:t('role'),render:r=>t('role_'+r.role)},
          {key:'b',label:t('branches'),wrap:true,render:r=>r.role==='BRANCH_COORDINATOR'?r.branches.map(b=>st.branchLabel(b,lang)).join('، '):'—'},
          {key:'ll',label:t('last_login'),numeric:true,render:r=>r.lastLogin?fmtDateTime(r.lastLogin):<span style={{color:'var(--text-muted)'}}>{t('never')}</span>},
          {key:'s',label:t('status'),render:r=><StatusBadge status={r.active?'active':'inactive'}/>},
          {key:'x',label:'',render:r=><div style={{display:'flex',gap:4}}>
            <Tooltip content={t('edit')}><IconButton icon="pencil" label={t('edit')} size={32} onClick={()=>setEdit(r)}/></Tooltip>
            <Tooltip content={t('reset_pw')}><IconButton icon="key-round" label={t('reset_pw')} size={32} onClick={()=>setConf({kind:'reset',u:r})}/></Tooltip>
            {r.id!==me.id&&<Tooltip content={r.active?t('deactivate'):t('reactivate')}><IconButton icon={r.active?'user-x':'user-check'} label={r.active?t('deactivate'):t('reactivate')} size={32} onClick={()=>r.active?setConf({kind:'deact',u:r}):(setConf({kind:'deact',u:r}))}/></Tooltip>}
          </div>}]} rows={rows}/>
      </div>
    </Panel>
    {edit&&<UserDialog user={edit} onClose={()=>setEdit(null)} notify={notify}/>}
    {imp&&<UserImportDialog onClose={()=>setImp(false)} notify={notify}/>}
    <ConfirmDialog open={!!conf} busy={busy} onClose={()=>setConf(null)} onConfirm={doConf}
      title={conf&&(conf.kind==='deact'?(conf.u.active?t('deact_q',{n:lang==='en'?conf.u.en:conf.u.ar}):t('reactivate')):t('reset_q',{n:lang==='en'?conf.u.en:conf.u.ar}))}
      impact={conf&&(conf.kind==='deact'?t('deact_impact'):t('reset_impact'))} confirmLabel={conf&&(conf.kind==='deact'?(conf.u.active?t('deactivate'):t('reactivate')):t('reset_pw'))} tone={conf&&conf.kind==='deact'&&conf.u.active?'danger':'info'}/>
  </div>;
}
function UserDialog({user,onClose,notify}){
  const {Dialog,Button,Input,Select,Checkbox}=window.DS;const {t,me,lang}=useApp();const st=useStore();
  const isNew=!user.id;
  const [f,setF]=React.useState({ar:user.ar||'',en:user.en||'',email:user.email||'',pw:'',role:user.role||'INSTRUCTOR',branches:user.branches||[]});
  const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);
  const check=(k,all)=>{const n={...e};const v=f;
    if(all||k==='ar')n.ar=v.ar.trim()?null:t('required');
    if(all||k==='en')n.en=v.en.trim()?null:t('required');
    if(all||k==='email')n.email=!v.email?t('required'):!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v.email)?t('email_bad'):st.S.users.some(u=>u.email.toLowerCase()===v.email.toLowerCase()&&u.id!==user.id)?t('email_taken'):null;
    if(isNew&&(all||k==='pw'))n.pw=!v.pw?t('required'):!(v.pw.length>=8&&/[A-Za-z]/.test(v.pw)&&/\d/.test(v.pw))?t('pw_policy'):null;
    if(all||k==='branches')n.branches=v.role==='BRANCH_COORDINATOR'&&v.branches.length===0?t('coord_need'):null;
    setE(n);return n;};
  const save=()=>{const n=check(null,true);if(Object.values(n).some(Boolean))return;setBusy(true);setTimeout(()=>{
    if(isNew){const u={id:Date.now(),ar:f.ar,en:f.en,email:f.email,role:f.role,branches:f.role==='BRANCH_COORDINATOR'?f.branches:[],active:true,firstLogin:null,lastLogin:null,pw:f.pw};st.S.users.push(u);st.audit(me.id,'user.created','user',u.id,null,{name_ar:u.ar,email:u.email,role:u.role});notify(t('user_created'),u.email);}
    else{const old={role:user.role,branches:user.branches.map(b=>st.branchLabel(b,'ar'))};Object.assign(user,{ar:f.ar,en:f.en,email:f.email,role:f.role,branches:f.role==='BRANCH_COORDINATOR'?f.branches:[]});st.audit(me.id,old.role!==f.role||JSON.stringify(old.branches)!==JSON.stringify(user.branches.map(b=>st.branchLabel(b,'ar')))?'user.role_changed':'user.updated','user',user.id,old,{role:user.role,branches:user.branches.map(b=>st.branchLabel(b,'ar'))});notify(t('user_saved'));}
    st.emit();setBusy(false);onClose();},600);};
  const set=(k,v)=>setF({...f,[k]:v});
  return <Dialog title={isNew?t('new_user'):t('edit_user')} onClose={onClose} width={620}
    footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button loading={busy} onClick={save}>{isNew?t('create'):t('save')}</Button></>}>
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
      <Input label={t('name_ar')} required value={f.ar} onChange={x=>set('ar',x.target.value)} onBlur={()=>check('ar')} error={e.ar}/>
      <Input label={t('name_en')} required dir="ltr" value={f.en} onChange={x=>set('en',x.target.value)} onBlur={()=>check('en')} error={e.en}/>
      <Input label={t('email')} required icon="mail" dir="ltr" value={f.email} onChange={x=>set('email',x.target.value)} onBlur={()=>check('email')} error={e.email} style={{gridColumn:isNew?'auto':'1 / -1'}}/>
      {isNew&&<Input label={t('initial_pw')} required icon="lock" dir="ltr" value={f.pw} onChange={x=>set('pw',x.target.value)} onBlur={()=>check('pw')} error={e.pw} hint={t('pw_rule')}/>}
      <Select label={t('role')} value={f.role} onChange={x=>set('role',x.target.value)} options={['SUPER_ADMIN','BRANCH_COORDINATOR','INSTRUCTOR'].map(r=>({value:r,label:t('role_'+r)}))} style={{gridColumn:'1 / -1'}}/>
      {f.role==='BRANCH_COORDINATOR'&&<div style={{gridColumn:'1 / -1'}}>
        <div style={{font:'600 13px var(--font-sans)',marginBottom:8}}>{t('coord_branches')}<span style={{color:'var(--red-600)'}}> *</span></div>
        <div style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:10}}>{st.S.branches.filter(b=>b.active).map(b=><Checkbox key={b.id} label={st.branchLabel(b.id,lang)} checked={f.branches.includes(b.id)} onChange={v=>{const nb=v?[...f.branches,b.id]:f.branches.filter(x=>x!==b.id);setF({...f,branches:nb});setE({...e,branches:null});}}/>)}</div>
        {e.branches&&<div role="alert" style={{font:'400 12px var(--font-sans)',color:'var(--red-600)',marginTop:6}}>{e.branches}</div>}
      </div>}
      {f.role==='INSTRUCTOR'&&<div style={{gridColumn:'1 / -1',font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{t('sections_link')}: {t('optional_later')}</div>}
    </div>
  </Dialog>;
}
function UserImportDialog({onClose,notify}){
  const {Dialog,Button,Alert,DataTable,StatusBadge,Icon,Checkbox}=window.DS;const {t,me,lang}=useApp();const st=useStore();const en=lang==='en';
  const ref=React.useRef(null);const [res,setRes]=React.useState(null);const [err,setErr]=React.useState(null);const [name,setName]=React.useState('');const [busy,setBusy]=React.useState(false);const [done,setDone]=React.useState(null);const [skipWarn,setSkipWarn]=React.useState(false);
  const HDR=['الاسم بالعربية','الاسم بالإنجليزية','البريد الإلكتروني','الدور','الفروع','كلمة المرور'];
  const genPw=()=>'Track'+Math.floor(1000+Math.random()*9000)+String.fromCharCode(97+Math.floor(Math.random()*26));
  const roleOf=v=>{v=String(v||'').trim();if(!v)return null;if(/سوبر|super|admin|مدير/i.test(v))return 'SUPER_ADMIN';if(/منسق|coord/i.test(v))return 'BRANCH_COORDINATOR';if(/مدرس|تدريس|محاضر|instructor|teacher|faculty/i.test(v))return 'INSTRUCTOR';return 'bad';};
  const branchesOf=v=>{const out=[];let bad=[];String(v||'').split(/[،,;\n]+/).map(x=>x.trim()).filter(Boolean).forEach(p=>{const g=/طالبات|بنات|female/i.test(p)?'female':/طلاب|بنين|male/i.test(p)?'male':null;
    const m=st.S.branches.filter(b=>b.active&&(p.includes(b.ar)||p.toLowerCase().includes(b.en.toLowerCase()))&&(!g||b.gender===g));if(m.length)m.forEach(b=>{if(!out.includes(b.id))out.push(b.id)});else bad.push(p);});return {ids:out,bad};};
  const validate=p=>{const H=p.headers.map(h=>String(h||'').trim());const col=re=>H.findIndex(h=>re.test(h));
    const c={ar:col(/الاسم بالعربي|الاسم العربي|name.?ar|^الاسم$/i),en:col(/بالإنجليزي|الانجليزي|name.?en|english/i),email:col(/البريد|email|e-mail/i),role:col(/الدور|الصلاحية|role/i),br:col(/الفرع|الفروع|branch/i),pw:col(/كلمة المرور|password/i)};
    if(c.email<0||c.ar<0)throw new Error('cols');
    const seen={};return p.rows.map((r,k)=>{const n=k+2;const g=i=>i>=0?String(r[i]||'').trim():'';
      const u={id:n,n,ar:g(c.ar),en:g(c.en),email:g(c.email).toLowerCase(),roleRaw:g(c.role),brRaw:g(c.br),pw:g(c.pw)};const errs=[],warns=[];
      if(!u.ar)errs.push(en?'Arabic name missing':'الاسم بالعربية مفقود');
      if(!u.email)errs.push(en?'Email missing':'البريد مفقود');else if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(u.email))errs.push(en?'Invalid email':'بريد غير صالح');
      else if(st.S.users.some(x=>x.email.toLowerCase()===u.email))errs.push(en?'Email already registered':'البريد مسجل مسبقًا');
      else if(seen[u.email])errs.push((en?'Duplicate of row ':'مكرر مع الصف ')+seen[u.email]);else seen[u.email]=n;
      if(!u.en){u.en=u.ar;warns.push(en?'English name copied from Arabic':'نُسخ الاسم العربي للإنجليزي');}
      let role=roleOf(u.roleRaw);if(role==='bad'){errs.push(en?'Unknown role':'دور غير معروف');role=null;}else if(!role){role='INSTRUCTOR';warns.push(en?'Role empty — set to instructor':'الدور فارغ — عُيّن مدرسًا');}
      u.role=role;const b=branchesOf(u.brRaw);u.branches=role==='BRANCH_COORDINATOR'?b.ids:[];
      if(role==='BRANCH_COORDINATOR'&&!b.ids.length)errs.push(en?'Coordinator needs a branch':'منسق الفرع يحتاج فرعًا');
      if(b.bad.length)(role==='BRANCH_COORDINATOR'?errs:warns).push((en?'Unknown branch: ':'فرع غير معروف: ')+b.bad.join('، '));
      if(u.pw&&!(u.pw.length>=8&&/[A-Za-z\u0600-\u06FF]/.test(u.pw)&&/\d/.test(u.pw)))errs.push(t('pw_policy'));
      u.genPw=!u.pw;u.status=errs.length?'error':warns.length?'warning':'valid';u.reason=[...errs,...warns].join(' · ')||(en?'Ready':'جاهز للإضافة');return u;});};
  const onFile=async ev=>{const f=ev.target.files&&ev.target.files[0];ev.target.value='';if(!f)return;try{const p=await window.parseRosterFile(f);setName(p.name);setErr(null);setRes(validate(p));}catch(x){setRes(null);setErr(x.message==='cols'?(en?'The file needs at least “Arabic name” and “Email” columns. Download the template.':'يجب أن يحتوي الملف على عمودي «الاسم بالعربية» و«البريد الإلكتروني» على الأقل. حمّل القالب.'):x.message==='size'?(en?'File exceeds 20 MB.':'الملف يتجاوز 20 ميجابايت.'):(en?'Unsupported file. Use xlsx or csv.':'نوع الملف غير مدعوم. استخدم xlsx أو csv.'));}};
  const sample=()=>{setName('users_batch.xlsx');setErr(null);setRes(validate({headers:HDR,rows:[
    ['د. سارة القحطاني','Dr. Sarah Al-Qahtani','s.alqahtani@mu.edu.sa','مدرس','',''],
    ['أ. عبدالله الحربي','Abdullah Al-Harbi','a.alharbi@mu.edu.sa','عضو هيئة تدريس','','Welcome2026'],
    ['د. منيرة الشهري','Dr. Munira Al-Shehri','m.alshehri@mu.edu.sa','منسق فرع','الزلفي طالبات',''],
    ['د. تركي العنزي','','t.alanazi@mu.edu.sa','منسق فرع','المجمعة',''],
    ['أ. لمى الغامدي','Lama Al-Ghamdi','f.alotaibi@mu.edu.sa','مدرس','',''],
    ['أ. نايف السبيعي','Nayef Al-Subaie','n.alsubaie@mu','مدرس','',''],
    ['د. هيا الزهراني','Dr. Haya Al-Zahrani','h.alzahrani@mu.edu.sa','','',''],
    ['أ. بندر المطيري','Bandar Al-Mutairi','b.almutairi@mu.edu.sa','منسق فرع','الدمام','']]}));};
  const template=()=>{const csv='\uFEFF'+[HDR,['د. مثال الاسم','Dr. Example Name','example@mu.edu.sa','مدرس','',''],['د. منسق مثال','Dr. Coordinator','coord@mu.edu.sa','منسق فرع','المجمعة طلاب، الزلفي طالبات','']].map(r=>r.join(',')).join('\n');
    const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv'}));a.download='track_users_template.csv';a.click();};
  const ok=res?res.filter(r=>r.status==='valid'||(r.status==='warning'&&!skipWarn)):[];
  const run=()=>{setBusy(true);setTimeout(()=>{const made=ok.map((r,i)=>{const pw=r.pw||genPw();const u={id:Date.now()+i,ar:r.ar,en:r.en,email:r.email,role:r.role,branches:r.branches,active:true,firstLogin:null,lastLogin:null,pw};st.S.users.push(u);st.audit(me.id,'user.created','user',u.id,null,{name_ar:u.ar,email:u.email,role:u.role,source:name});return {...u,gen:r.genPw};});
    st.audit(me.id,'user.bulk_imported','user',null,null,{file:name,rows:res.length,created:made.length,skipped:res.length-made.length});st.emit();setBusy(false);setDone(made);notify(en?'Users imported':'تم استيراد المستخدمين',made.length+(en?' users added':' مستخدم أُضيف'));},700);};
  const creds=()=>{const csv='\uFEFF'+[['الاسم','البريد الإلكتروني','الدور','كلمة المرور المؤقتة'],...done.map(u=>[u.ar,u.email,t('role_'+u.role),u.gen?u.pw:'(من الملف)'])].map(r=>r.join(',')).join('\n');const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv'}));a.download='track_users_credentials.csv';a.click();};
  const cnt=k=>res?res.filter(r=>r.status===k).length:0;
  return <Dialog title={en?'Import users from Excel':'استيراد المستخدمين من Excel'} onClose={onClose} width={980}
    footer={done?<><Button variant="secondary" icon="download" onClick={creds}>{en?'Download credentials':'تنزيل بيانات الدخول'}</Button><Button onClick={onClose}>{en?'Done':'تم'}</Button></>
      :<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button icon="users" loading={busy} disabled={!ok.length} onClick={run}>{(en?'Add ':'إضافة ')+ok.length+(en?' users':' مستخدم')}</Button></>}>
    {done?<div style={{display:'flex',flexDirection:'column',gap:14}}>
      <Alert tone="success" title={(en?'Added ':'تمت إضافة ')+done.length+(en?' users':' مستخدم')}>{en?'Generated temporary passwords are in the credentials file. Users must change them at first sign-in.':'كلمات المرور المؤقتة المولّدة موجودة في ملف بيانات الدخول، ويُطلب من كل مستخدم تغييرها عند أول تسجيل دخول.'}</Alert>
      <DataTable dense columns={[{key:'ar',label:t('name_ar'),render:r=><b>{r.ar}</b>},{key:'email',label:t('email'),numeric:true},{key:'role',label:t('role'),render:r=>t('role_'+r.role)},{key:'b',label:t('branches'),wrap:true,render:r=>r.branches.map(b=>st.branchLabel(b,lang)).join('، ')||'—'},{key:'pw',label:t('initial_pw'),numeric:true,render:r=>r.gen?<code>{r.pw}</code>:<span style={{color:'var(--text-secondary)'}}>{en?'from file':'من الملف'}</span>}]} rows={done}/>
    </div>:<div style={{display:'flex',flexDirection:'column',gap:12}}>
      <div style={{border:'2px dashed var(--border-strong)',borderRadius:'var(--radius-lg)',padding:'22px 16px',textAlign:'center',background:'var(--gray-50)'}}>
        <Icon name="file-spreadsheet" size={30} color="var(--track-green)" style={{margin:'0 auto 8px'}}/>
        <div style={{font:'700 14px var(--font-sans)'}}>{en?'Upload a users file (xlsx or csv)':'ارفع ملف المستخدمين (xlsx أو csv)'}</div>
        <div style={{font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)',marginTop:4}}>{en?'Columns: Arabic name and email (required), English name, role, branches, password. Empty passwords are generated automatically.':'الأعمدة: الاسم بالعربية والبريد الإلكتروني (إلزامية)، الاسم بالإنجليزية، الدور، الفروع، كلمة المرور. تُولَّد كلمة مرور مؤقتة تلقائيًا إذا تُركت فارغة.'}</div>
        <input ref={ref} type="file" accept=".xlsx,.csv" onChange={onFile} style={{display:'none'}}/>
        <div style={{display:'flex',gap:8,justifyContent:'center',marginTop:12,flexWrap:'wrap'}}><Button size="sm" variant="secondary" icon="download" onClick={template}>{en?'Download template':'تنزيل القالب'}</Button><Button size="sm" variant="secondary" icon="folder-open" onClick={()=>ref.current&&ref.current.click()}>{en?'Choose file':'اختيار ملف'}</Button><Button size="sm" variant="ghost" onClick={sample}>{en?'Use sample file':'استخدام ملف تجريبي'}</Button></div>
      </div>
      {err&&<Alert tone="danger">{err}</Alert>}
      {res&&<>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:10,flexWrap:'wrap'}}>
          <span style={{font:'600 13px var(--font-sans)'}}>{name} · {res.length} {en?'rows':'صف'} · <span style={{color:'var(--green-700)'}}>{cnt('valid')} {en?'ready':'جاهز'}</span> · <span style={{color:'var(--amber-600)'}}>{cnt('warning')} {en?'with notes':'بملاحظات'}</span> · <span style={{color:'var(--red-600)'}}>{cnt('error')} {en?'rejected':'مرفوض'}</span></span>
          {cnt('warning')>0&&<Checkbox label={en?'Skip rows with notes':'تجاهل الصفوف ذات الملاحظات'} checked={skipWarn} onChange={setSkipWarn}/>}
        </div>
        <div style={{maxHeight:360,overflow:'auto'}}><DataTable dense columns={[{key:'n',label:en?'Row':'الصف',numeric:true},{key:'ar',label:t('name_ar'),render:r=><b>{r.ar||'—'}</b>},{key:'email',label:t('email'),numeric:true,render:r=>r.email||'—'},{key:'role',label:t('role'),render:r=>r.role?t('role_'+r.role):(r.roleRaw||'—')},{key:'b',label:t('branches'),wrap:true,render:r=>r.branches.length?r.branches.map(b=>st.branchLabel(b,lang)).join('، '):'—'},{key:'s',label:en?'Result':'النتيجة',render:r=><StatusBadge status={r.status}/>},{key:'r',label:en?'Details':'التفاصيل',wrap:true,render:r=><span style={{font:'400 12px var(--font-sans)',color:r.status==='error'?'var(--red-600)':'var(--text-secondary)'}}>{r.reason}</span>}]} rows={res}/></div>
      </>}
    </div>}
  </Dialog>;
}
window.UsersScreen=UsersScreen;
