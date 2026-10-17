function ForcePasswordScreen(){
  const {Input,Button,Logo,Alert}=window.DS;const {me,lang,t,logout}=useApp();const st=useStore();const en=lang==='en';
  const [f,setF]=React.useState({nw:'',cf:''});const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);
  const policy=v=>v.length>=8&&/[A-Za-z\u0600-\u06FF]/.test(v)&&/\d/.test(v);
  const submit=ev=>{ev.preventDefault();const n={nw:!f.nw?t('required'):!policy(f.nw)?t('pw_policy'):f.nw===me.uid?(en?'Must differ from your university ID.':'يجب أن تختلف عن رقمك الجامعي.'):null,cf:!f.cf?t('required'):f.cf!==f.nw?t('pw_mismatch'):null};setE(n);if(n.nw||n.cf)return;
    setBusy(true);setTimeout(()=>{me.pw=f.nw;me.mustChange=false;st.audit(null,'student.password_set','student',me.id,null,{uid:me.uid});st.emit();},600);};
  return <div style={{minHeight:'100vh',display:'flex',alignItems:'center',justifyContent:'center',background:'var(--surface-page)',padding:24}}>
    <div style={{width:'100%',maxWidth:460,background:'#fff',borderRadius:'var(--radius-xl)',boxShadow:'var(--shadow-overlay)',padding:'36px 40px'}}>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:24}}><Logo variant="lockup" height={64} assetBase="../../assets/"/><LangSwitch/></div>
      <h1 style={{margin:0,font:'var(--text-h2)'}}>{en?'Set a new password':'تعيين كلمة مرور جديدة'}</h1>
      <p style={{margin:'6px 0 20px',color:'var(--text-secondary)',font:'400 14px/1.7 var(--font-sans)'}}>{en?'Welcome, '+(me.en||me.ar)+'. This is your first sign-in, so replace the temporary password (your university ID) before continuing.':'مرحبًا '+me.ar+'. هذا أول دخول لك، لذا استبدل كلمة المرور المؤقتة (رقمك الجامعي) قبل المتابعة.'}</p>
      <form onSubmit={submit} noValidate style={{display:'flex',flexDirection:'column',gap:14}}>
        <Input label={t('new_pw')} type="password" icon="lock" value={f.nw} error={e.nw} hint={t('pw_rule')} onChange={x=>setF({...f,nw:x.target.value})}/>
        <Input label={t('confirm_pw')} type="password" icon="lock" value={f.cf} error={e.cf} onChange={x=>setF({...f,cf:x.target.value})}/>
        <Button type="submit" size="lg" fullWidth loading={busy} icon="key-round">{en?'Save and continue':'حفظ والمتابعة'}</Button>
      </form>
      <button onClick={logout} style={{marginTop:14,border:'none',background:'none',cursor:'pointer',font:'500 13px var(--font-sans)',color:'var(--text-secondary)'}}>{t('logout')}</button>
    </div>
  </div>;
}

function StageTrack({stages,marks}){
  const {Icon}=window.DS;
  return <div style={{display:'flex',flexWrap:'wrap',alignItems:'center',gap:6}}>{stages.map((s,i)=><React.Fragment key={i}>
    {i>0&&<span style={{width:14,height:2,background:marks[i]?'var(--track-green)':'var(--gray-200)'}}></span>}
    <span style={{display:'flex',alignItems:'center',gap:6,height:28,padding:'0 10px',borderRadius:14,background:marks[i]?'var(--green-50)':'var(--gray-50)',border:'1px solid '+(marks[i]?'var(--green-200)':'var(--border-default)'),font:'600 12px var(--font-sans)',color:marks[i]?'var(--green-700)':'var(--text-secondary)'}}>
      <Icon name={marks[i]?'circle-check':'circle-dashed'} size={14}/>{s}</span>
  </React.Fragment>)}</div>;
}

function StudentHome(){
  const {Button,DataTable,StatusBadge,EmptyState,Icon}=window.DS;const {me,lang,go}=useApp();const st=useStore();const en=lang==='en';
  const secs=st.studentEnrollments(me.id).map(e=>st.S.sections.find(s=>s.id===e.section)).filter(Boolean);
  const groups=st.initiatives.map(ini=>({ini,items:secs.flatMap(s=>st.S.assignments.filter(a=>a.section===s.id&&a.ini===ini.id).map(a=>({s,a,m:st.marks(a,me.id)})))})).filter(g=>g.items.length);
  const cn=c=>c?(en?c.en:c.ar):'—';
  const meta=[me.uid,st.programName(me.program,lang),st.currentTerm()&&(en?st.currentTerm().en:st.currentTerm().ar)].filter(Boolean).join(' · ');
  return <div style={{maxWidth:1080}}>
    <PageHeader title={en?'Welcome, '+(me.en||me.ar):'مرحبًا، '+me.ar} subtitle={meta}
      actions={<Button variant="secondary" icon="life-buoy" onClick={()=>go('support',{new:true})}>{en?'New support request':'طلب دعم فني'}</Button>}/>
    <h2 style={{margin:'8px 0 12px',font:'700 17px var(--font-sans)'}}>{en?'My initiatives':'المبادرات المسجّل فيها'}</h2>
    {groups.length===0?<Panel><EmptyState icon="lightbulb" title={en?'No initiatives yet':'لا توجد مبادرات بعد'} message={en?'When your sections join an initiative it will appear here.':'عند ربط شعبك بمبادرة ستظهر هنا.'}/></Panel>:
    <div style={{display:'flex',flexDirection:'column',gap:14}}>{groups.map(({ini,items})=>{const done=items.reduce((a,x)=>a+x.m.filter(Boolean).length,0),tot=items.reduce((a,x)=>a+x.a.stages.length,0);
      return <Panel key={ini.id}>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',gap:16,padding:'18px 20px',borderBottom:'1px solid var(--border-default)',flexWrap:'wrap'}}>
          <div style={{minWidth:0}}><div style={{display:'flex',alignItems:'center',gap:10}}><span style={{font:'800 17px var(--font-sans)'}}>{en?ini.en:ini.ar}</span><StatusBadge status={ini.status}/></div>
            <div style={{font:'400 13px var(--font-sans)',color:'var(--text-secondary)',marginTop:4}}>{ini.owner} · {fmtDate(ini.start)} – {fmtDate(ini.end)}</div></div>
          <div style={{textAlign:'end'}}><div style={{font:'800 22px var(--font-numeric)',color:'var(--track-blue)'}}>{Math.round(done/(tot||1)*100)}%</div><div style={{font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{done}/{tot} {en?'stages done':'مراحل مكتملة'}</div></div>
        </div>
        {items.map(({s,a,m})=>{const c=st.course(s.course);return <div key={a.id} style={{display:'grid',gridTemplateColumns:'minmax(220px,1fr) minmax(0,2fr)',gap:16,alignItems:'center',padding:'14px 20px',borderBottom:'1px solid var(--gray-100)'}}>
          <div style={{minWidth:0}}><div style={{font:'700 14px var(--font-sans)'}}>{cn(c)} <span style={{fontFamily:'var(--font-latin)',color:'var(--text-secondary)',fontWeight:500}}>{c&&c.code}</span></div>
            <div style={{font:'400 12px/1.7 var(--font-sans)',color:'var(--text-secondary)'}}>{(en?'Section ':'شعبة ')+s.no} · {st.branchLabel(s.branch,lang)} · {s.instructor?st.uname(s.instructor,lang):(en?'No instructor':'بدون مدرس')}</div></div>
          <StageTrack stages={a.stages} marks={m}/>
        </div>;})}
      </Panel>;})}</div>}
    <h2 style={{margin:'28px 0 12px',font:'700 17px var(--font-sans)'}}>{en?'My sections':'شعبي الدراسية'}</h2>
    <Panel><div style={{padding:20}}><DataTable columns={[
      {key:'c',label:en?'Course':'المقرر',render:r=><b>{cn(st.course(r.course))}</b>},
      {key:'code',label:en?'Code':'الرمز',numeric:true,render:r=>st.course(r.course).code},
      {key:'no',label:en?'Section':'الشعبة',numeric:true},
      {key:'b',label:en?'Branch':'الفرع',render:r=>st.branchLabel(r.branch,lang)},
      {key:'i',label:en?'Instructor':'المدرس',render:r=>r.instructor?st.uname(r.instructor,lang):'—'},
      {key:'n',label:en?'Initiatives':'المبادرات',wrap:true,render:r=>st.S.assignments.filter(a=>a.section===r.id).map(a=>st.iniName(a,lang)).join('، ')||'—'}]} rows={secs}/></div></Panel>
  </div>;
}
Object.assign(window,{ForcePasswordScreen,StudentHome,StageTrack});
