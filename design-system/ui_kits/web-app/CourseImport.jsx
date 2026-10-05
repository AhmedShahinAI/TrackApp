function CourseImportDialog({onClose,notify}){
  const {Dialog,Button,Alert,DataTable,StatusBadge,Icon,Checkbox}=window.DS;const {t,me,lang}=useApp();const st=useStore();const en=lang==='en';
  const ref=React.useRef(null);const [res,setRes]=React.useState(null);const [err,setErr]=React.useState(null);const [name,setName]=React.useState('');const [busy,setBusy]=React.useState(false);const [done,setDone]=React.useState(null);const [mkProg,setMkProg]=React.useState(true);const [raw,setRaw]=React.useState(null);
  const HDR=['رمز المقرر','اسم المقرر بالعربية','اسم المقرر بالإنجليزية','البرنامج','رمز البرنامج'];
  const findProg=(nm,code)=>st.S.programs.find(p=>(code&&p.code.toUpperCase()===code.toUpperCase())||(nm&&(p.ar===nm||p.en.toLowerCase()===nm.toLowerCase())));
  const validate=(p,mk)=>{const H=p.headers.map(h=>String(h||'').trim());const col=re=>H.findIndex(h=>re.test(h));
    const c={code:col(/رمز المقرر|course.?code|^code$/i),ar:col(/اسم المقرر بالعربي|اسم المقرر$|course.?name.?ar|^اسم المقرر/i),en:col(/بالإنجليزي|الانجليزي|name.?en|english/i),pg:col(/^البرنامج|البرامج|program(?!.?code)/i),pc:col(/رمز البرنامج|program.?code/i)};
    if(c.code<0||c.ar<0||(c.pg<0&&c.pc<0))throw new Error('cols');
    const seen={};const newProgs={};
    return p.rows.map((r,k)=>{const n=k+2;const g=i=>i>=0?String(r[i]||'').trim():'';
      const u={id:n,n,code:g(c.code).toUpperCase(),ar:g(c.ar),en:g(c.en),pgRaw:g(c.pg),pcRaw:g(c.pc)};const errs=[],warns=[];
      if(!u.code)errs.push(en?'Course code missing':'رمز المقرر مفقود');else if(seen[u.code])errs.push((en?'Duplicate of row ':'مكرر مع الصف ')+seen[u.code]);else seen[u.code]=n;
      if(!u.ar)errs.push(en?'Arabic name missing':'اسم المقرر بالعربية مفقود');
      if(!u.en&&u.ar){u.en=u.ar;warns.push(en?'English name copied from Arabic':'نُسخ الاسم العربي للإنجليزي');}
      const names=u.pgRaw.split(/[،,;]+/).map(x=>x.trim()).filter(Boolean);const codes=u.pcRaw.split(/[،,;]+/).map(x=>x.trim()).filter(Boolean);
      const len=Math.max(names.length,codes.length);u.progs=[];u.create=[];
      if(!len)errs.push(en?'Program missing':'البرنامج مفقود');
      for(let i=0;i<len;i++){const nm=names[i]||'',cd=codes[i]||'';const pr=findProg(nm,cd);
        if(pr){if(!u.progs.includes(pr.id))u.progs.push(pr.id);}
        else{const key=(cd||nm).toUpperCase();if(mk&&nm){u.create.push({key,ar:nm,code:cd.toUpperCase()});if(!newProgs[key]){newProgs[key]=1;}warns.push((en?'New program will be created: ':'سيُنشأ برنامج جديد: ')+nm);}
          else errs.push((en?'Unknown program: ':'برنامج غير معروف: ')+(nm||cd));}}
      const ex=u.code&&st.S.courses.find(x=>x.code.toUpperCase()===u.code);u.exists=!!ex;
      if(ex&&!errs.length)warns.push(en?'Course exists — names and programs will be updated':'المقرر موجود — سيُحدَّث اسمه وبرامجه');
      u.status=errs.length?'error':warns.length?'warning':'valid';u.reason=[...errs,...warns].join(' · ')||(en?'New course':'مقرر جديد');return u;});};
  const load=(p,nm)=>{setName(nm);setErr(null);setRaw(p);setRes(validate(p,mkProg));};
  const onFile=async ev=>{const f=ev.target.files&&ev.target.files[0];ev.target.value='';if(!f)return;try{const p=await window.parseRosterFile(f);load(p,p.name);}catch(x){setRes(null);setErr(x.message==='cols'?(en?'The file needs “Course code”, “Course name (Arabic)” and “Program” columns. Download the template.':'يجب أن يحتوي الملف على أعمدة «رمز المقرر» و«اسم المقرر بالعربية» و«البرنامج». حمّل القالب.'):(en?'Unsupported file. Use xlsx or csv.':'نوع الملف غير مدعوم. استخدم xlsx أو csv.'));}};
  const sample=()=>load({headers:HDR,rows:[
    ['HR201','مبادئ إدارة الموارد البشرية','Principles of HRM','الموارد البشرية',''],
    ['ACC110','مبادئ المحاسبة','Principles of Accounting','المحاسبة والضرائب',''],
    ['CS212','برمجة 2','Programming 2','البرمجة وعلوم الحاسب، تطوير التطبيقات',''],
    ['MKT150','مبادئ التسويق','','التسويق',''],
    ['DS101','مقدمة في علم البيانات','Intro to Data Science','علم البيانات','DS'],
    ['LOG120','إدارة سلاسل الإمداد','Supply Chain Management','','LOG'],
    ['','مقرر بدون رمز','No code','التسويق',''],
    ['HR201','مبادئ إدارة الموارد البشرية','Principles of HRM','الموارد البشرية','']]},'college_courses.xlsx');
  React.useEffect(()=>{if(raw)setRes(validate(raw,mkProg));},[mkProg]);
  const ok=res?res.filter(r=>r.status!=='error'):[];
  const run=()=>{setBusy(true);setTimeout(()=>{const created={};let nc=0,uc=0,np=0;
    ok.forEach((r,i)=>{const ids=r.progs.slice();r.create.forEach(cp=>{if(!created[cp.key]){const code=cp.code||('P'+(st.S.programs.length+1));const p={id:Date.now()+1000+np,code,ar:cp.ar,en:cp.ar,unit:'admin',active:true};st.S.programs.push(p);created[cp.key]=p.id;np++;st.audit(me.id,'program.created','program',p.id,null,{code,name_ar:p.ar,source:name});}ids.push(created[cp.key]);});
      const ex=st.S.courses.find(x=>x.code.toUpperCase()===r.code);
      if(ex){const old={ar:ex.ar,programs:(ex.programs||[]).map(x=>st.programName(x,'ar'))};Object.assign(ex,{ar:r.ar,en:r.en,programs:[...new Set(ids)]});st.audit(me.id,'course.updated','course',ex.id,old,{ar:ex.ar,programs:ex.programs.map(x=>st.programName(x,'ar'))});uc++;}
      else{const c={id:Date.now()+i,code:r.code,ar:r.ar,en:r.en,programs:[...new Set(ids)]};st.S.courses.push(c);st.audit(me.id,'course.created','course',c.id,null,{code:c.code,ar:c.ar,programs:c.programs.map(x=>st.programName(x,'ar'))});nc++;}});
    st.audit(me.id,'course.bulk_imported','course',null,null,{file:name,rows:res.length,created:nc,updated:uc,programs_created:np,skipped:res.length-ok.length});
    st.emit();setBusy(false);setDone({nc,uc,np,sk:res.length-ok.length});notify(en?'Courses imported':'تم استيراد المقررات',(nc+uc)+(en?' courses':' مقرر'));},700);};
  const cnt=k=>res?res.filter(r=>r.status===k).length:0;
  return <Dialog title={en?'Import college courses from Excel':'استيراد مقررات الكلية من Excel'} onClose={onClose} width={1000}
    footer={done?<Button onClick={onClose}>{en?'Done':'تم'}</Button>:<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button icon="book-open" loading={busy} disabled={!ok.length} onClick={run}>{(en?'Import ':'استيراد ')+ok.length+(en?' courses':' مقرر')}</Button></>}>
    {done?<Alert tone="success" title={en?'Import completed':'اكتمل الاستيراد'}>{en?`${done.nc} new courses, ${done.uc} updated, ${done.np} new programs, ${done.sk} rows skipped.`:`${done.nc} مقرر جديد، ${done.uc} مقرر مُحدَّث، ${done.np} برنامج جديد، ${done.sk} صف مرفوض.`}</Alert>
    :<div style={{display:'flex',flexDirection:'column',gap:12}}>
      <div style={{border:'2px dashed var(--border-strong)',borderRadius:'var(--radius-lg)',padding:'22px 16px',textAlign:'center',background:'var(--gray-50)'}}>
        <Icon name="file-spreadsheet" size={30} color="var(--track-green)" style={{margin:'0 auto 8px'}}/>
        <div style={{font:'700 14px var(--font-sans)'}}>{en?'Upload the college courses file (xlsx or csv)':'ارفع ملف مقررات الكلية (xlsx أو csv)'}</div>
        <div style={{font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)',marginTop:4}}>{en?'Columns: course code, Arabic name, program (required), English name and program code (optional). A course can belong to several programs — separate them with a comma.':'الأعمدة: رمز المقرر، اسم المقرر بالعربية، البرنامج (إلزامية)، والاسم بالإنجليزية ورمز البرنامج (اختيارية). يمكن ربط المقرر بأكثر من برنامج بفصلها بفاصلة.'}</div>
        <input ref={ref} type="file" accept=".xlsx,.csv" onChange={onFile} style={{display:'none'}}/>
        <div style={{display:'flex',gap:8,justifyContent:'center',marginTop:12,flexWrap:'wrap'}}>
          <Button size="sm" variant="secondary" icon="download" onClick={()=>downloadTemplate('track_courses_template.csv',[HDR,['CS212','برمجة 2','Programming 2','البرمجة وعلوم الحاسب، تطوير التطبيقات',''],['HR201','مبادئ إدارة الموارد البشرية','Principles of HRM','الموارد البشرية','HR']])}>{en?'Download template':'تنزيل القالب'}</Button>
          <Button size="sm" variant="secondary" icon="folder-open" onClick={()=>ref.current&&ref.current.click()}>{en?'Choose file':'اختيار ملف'}</Button>
          <Button size="sm" variant="ghost" onClick={sample}>{en?'Use sample file':'استخدام ملف تجريبي'}</Button></div>
      </div>
      {err&&<Alert tone="danger">{err}</Alert>}
      {res&&<>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:10,flexWrap:'wrap'}}>
          <span style={{font:'600 13px var(--font-sans)'}}>{name} · {res.length} {en?'rows':'صف'} · <span style={{color:'var(--green-700)'}}>{cnt('valid')} {en?'new':'جديد'}</span> · <span style={{color:'var(--amber-600)'}}>{cnt('warning')} {en?'with notes':'بملاحظات'}</span> · <span style={{color:'var(--red-600)'}}>{cnt('error')} {en?'rejected':'مرفوض'}</span></span>
          <Checkbox label={en?'Create programs that don’t exist yet':'إنشاء البرامج غير الموجودة تلقائيًا'} checked={mkProg} onChange={setMkProg}/>
        </div>
        <div style={{maxHeight:360,overflow:'auto'}}><DataTable dense columns={[{key:'n',label:en?'Row':'الصف',numeric:true},{key:'code',label:t('code'),numeric:true,render:r=><b>{r.code||'—'}</b>},{key:'ar',label:en?'Course':'المقرر',render:r=>r.ar||'—'},{key:'p',label:en?'Programs':'البرامج',wrap:true,render:r=>[...r.progs.map(x=>st.programName(x,lang)),...r.create.map(c=>c.ar+' ✚')].join('، ')||r.pgRaw||r.pcRaw||'—'},{key:'s',label:en?'Result':'النتيجة',render:r=><StatusBadge status={r.status}/>},{key:'r',label:en?'Details':'التفاصيل',wrap:true,render:r=><span style={{font:'400 12px var(--font-sans)',color:r.status==='error'?'var(--red-600)':'var(--text-secondary)'}}>{r.reason}</span>}]} rows={res}/></div>
      </>}
    </div>}
  </Dialog>;
}
window.CourseImportDialog=CourseImportDialog;
