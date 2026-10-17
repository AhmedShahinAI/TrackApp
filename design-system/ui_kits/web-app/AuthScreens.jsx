function LoginScreen({onLogin}){
  const {Input,Button,Logo,Alert}=window.DS;const {t,lang}=useApp();const st=useStore();
  const [email,setEmail]=React.useState('f.alotaibi@mu.edu.sa');const [pw,setPw]=React.useState('Track2026');
  const [mode,setMode]=React.useState('staff');const en=lang==='en';
  const [err,setErr]=React.useState(null);const [fails,setFails]=React.useState(0);const [lock,setLock]=React.useState(0);const [busy,setBusy]=React.useState(false);
  React.useEffect(()=>{if(lock<=0)return;const x=setTimeout(()=>setLock(lock-1),1000);return()=>clearTimeout(x)},[lock]);
  const submit=e=>{e.preventDefault();if(lock>0)return;setBusy(true);setTimeout(()=>{setBusy(false);
    const u=(mode==='student'?st.S.students:st.S.users).find(x=>(x.email||'').toLowerCase()===email.trim().toLowerCase());
    if(!u||u.pw!==pw){const f=fails+1;setFails(f);st.audit(null,'auth.failed','user',u?u.id:null,null,{email});if(f>=5){setLock(60);setFails(0);setErr('lock')}else setErr('generic');return;}
    if(!u.active){setErr('deactivated');return;}
    setErr(null);onLogin(u,mode);},500);};
  const demoSt=st.student(106);
  const demo=[['m.alsalem@mu.edu.sa','SUPER_ADMIN'],['n.aldosari@mu.edu.sa','BRANCH_COORDINATOR'],['f.alotaibi@mu.edu.sa','INSTRUCTOR'],['s.alrashidi@mu.edu.sa','inactive']];
  const pickMode=m=>{setMode(m);setErr(null);if(m==='student'){setEmail(demoSt.email);setPw(demoSt.pw===demoSt.uid?demoSt.uid:'');}else{setEmail('f.alotaibi@mu.edu.sa');setPw('Track2026');}};
  return <div style={{display:'flex',minHeight:'100vh',background:'var(--surface-page)'}}>
    <div style={{flex:'0 0 500px',maxWidth:'100%',display:'flex',flexDirection:'column',justifyContent:'center',padding:'40px 64px',background:'#fff'}}>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:32}}><Logo variant="lockup" height={88} assetBase="../../assets/"/><LangSwitch/></div>
      <h1 style={{margin:0,font:'var(--text-h2)'}}>{t('login_title')}</h1>
      <p style={{margin:'6px 0 18px',color:'var(--text-secondary)'}}>{mode==='student'?(en?'Sign in with your university email and password':'ادخل بريدك الجامعي وكلمة المرور'):t('login_sub')}</p>
      <div role="tablist" style={{display:'grid',gridTemplateColumns:'1fr 1fr',background:'var(--gray-100)',borderRadius:10,padding:4,gap:4,marginBottom:18}}>
        {[['staff','briefcase',en?'Faculty & staff':'منسوبو الكلية'],['student','graduation-cap',en?'Student':'طالب']].map(([k,ic,l])=><button key={k} role="tab" aria-selected={mode===k} onClick={()=>pickMode(k)} style={{height:38,border:'none',borderRadius:7,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:8,background:mode===k?'#fff':'transparent',boxShadow:mode===k?'var(--shadow-xs)':'none',font:(mode===k?'700':'500')+' 14px var(--font-sans)',color:mode===k?'var(--track-blue)':'var(--text-secondary)'}}><window.DS.Icon name={ic} size={16}/>{l}</button>)}
      </div>
      <form onSubmit={submit} noValidate style={{display:'flex',flexDirection:'column',gap:16}}>
        {err==='generic'&&<Alert tone="danger">{t('login_generic')}</Alert>}
        {err==='lock'&&lock>0&&<Alert tone="warning" icon="timer">{t('login_locked',{s:lock})}</Alert>}
        {err==='deactivated'&&<Alert tone="danger" icon="user-x">{t('login_deactivated')}</Alert>}
        <Input label={mode==='student'?(en?'University email':'البريد الإلكتروني الجامعي'):t('email')} icon="mail" type="email" dir="ltr" placeholder={mode==='student'?'4xxxxxxxx@s.mu.edu.sa':undefined} value={email} onChange={e=>setEmail(e.target.value)}/>
        <Input label={t('password')} icon="lock" type="password" value={pw} onChange={e=>setPw(e.target.value)} hint={mode==='student'?(en?'First sign-in: your password is your university ID.':'عند أول دخول: كلمة المرور هي رقمك الجامعي.'):undefined}/>
        <Button type="submit" size="lg" fullWidth loading={busy} disabled={lock>0}>{lock>0?lock+'s':t('sign_in')}</Button>
      </form>
      <p style={{margin:'16px 0 0',font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)'}}>{t('no_register')}</p>
      <div style={{marginTop:28,paddingTop:18,borderTop:'1px dashed var(--border-strong)'}}>
        <div style={{font:'600 12px var(--font-sans)',color:'var(--text-secondary)',marginBottom:8}}>{t('demo_accounts')} · {mode==='student'?(en?'password = university ID':'كلمة المرور = الرقم الجامعي'):'Track2026'}</div>
        {mode==='student'?<div style={{display:'flex',flexWrap:'wrap',gap:6}}><button onClick={()=>pickMode('student')} style={{height:28,padding:'0 10px',border:'1px solid var(--border-default)',borderRadius:6,background:'var(--blue-50)',cursor:'pointer',font:'500 12px var(--font-sans)',color:'var(--track-blue)'}}>{demoSt.ar} · {demoSt.uid}</button></div>:
        <div style={{display:'flex',flexWrap:'wrap',gap:6}}>{demo.map(([e,r])=><button key={e} onClick={()=>{setEmail(e);setPw('Track2026');setErr(null)}} style={{height:28,padding:'0 10px',border:'1px solid var(--border-default)',borderRadius:6,background:email===e?'var(--blue-50)':'#fff',cursor:'pointer',font:'500 12px var(--font-sans)',color:'var(--track-blue)'}}>{r==='inactive'?(lang==='en'?'Deactivated':'حساب معطّل'):t('role_'+r)}</button>)}</div>}
      </div>
    </div>
    <div style={{flex:1,display:'flex',flexDirection:'column',justifyContent:'center',gap:36,padding:'0 64px',background:'var(--surface-sidebar)',color:'#fff'}}>
      <div>
        <div style={{font:'700 34px/1.3 var(--font-arabic)'}}>معًا .. نحو أثر أكاديمي أكبر</div>
        <div dir="ltr" style={{font:'400 18px var(--font-latin)',opacity:.85,marginTop:8,textAlign:lang==='en'?'left':'right'}}>Track Today, a Brighter Tomorrow</div>
        <div style={{width:48,height:4,borderRadius:2,background:'var(--track-green)',marginTop:18}}/>
      </div>
      <img src="../../assets/banner-initiatives.png" alt="" style={{width:'100%',maxWidth:760,height:'auto',borderRadius:'var(--radius-xl)',boxShadow:'var(--shadow-overlay)',display:'block'}}/>
    </div>
  </div>;
}

function ProfileScreen({notify}){
  const {Input,Button,Card}=window.DS;const {t,me,lang,setLang,go}=useApp();const st=useStore();
  const [f,setF]=React.useState({cur:'',nw:'',cf:''});const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);
  const policy=v=>v.length>=8&&/[A-Za-z\u0600-\u06FF]/.test(v)&&/\d/.test(v);
  const validate=(k,all)=>{const n={...e};
    if(k==='cur'||all)n.cur=!f.cur?t('required'):null;
    if(k==='nw'||all)n.nw=!f.nw?t('required'):!policy(f.nw)?t('pw_policy'):f.nw===me.pw?t('pw_same'):null;
    if(k==='cf'||all)n.cf=!f.cf?t('required'):f.cf!==f.nw?t('pw_mismatch'):null;setE(n);return n;};
  const submit=ev=>{ev.preventDefault();const n=validate(null,true);if(Object.values(n).some(Boolean))return;
    if(f.cur!==me.pw){setE({...n,cur:t('pw_wrong')});return;}
    setBusy(true);setTimeout(()=>{me.pw=f.nw;st.audit(me.id,'user.password_changed','user',me.id,null,{sessions_revoked:true});st.emit();setBusy(false);setF({cur:'',nw:'',cf:''});notify(t('pw_changed'),t('pw_changed_msg'));},600);};
  return <div style={{maxWidth:880}}>
    <PageHeader title={t('profile')} crumbs={[{label:t('nav_home'),onClick:()=>go('home')},{label:t('profile')}]}/>
    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(320px,1fr))',gap:16}}>
      <Card title={lang==='en'?me.en:me.ar}>
        <dl style={{display:'grid',gridTemplateColumns:'auto 1fr',gap:'10px 16px',margin:0,font:'400 13px var(--font-sans)'}}>
          <dt style={{color:'var(--text-secondary)'}}>{t('name_ar')}</dt><dd style={{margin:0}}>{me.ar}</dd>
          <dt style={{color:'var(--text-secondary)'}}>{t('name_en')}</dt><dd style={{margin:0}} dir="ltr">{me.en}</dd>
          <dt style={{color:'var(--text-secondary)'}}>{t('email')}</dt><dd style={{margin:0,fontFamily:'var(--font-latin)'}}>{me.email}</dd>
          <dt style={{color:'var(--text-secondary)'}}>{t('role')}</dt><dd style={{margin:0}}>{t('role_'+me.role)}</dd>
          {me.uid&&<><dt style={{color:'var(--text-secondary)'}}>{lang==='en'?'University ID':'الرقم الجامعي'}</dt><dd style={{margin:0,fontFamily:'var(--font-latin)'}}>{me.uid}</dd></>}
          {(me.branches||[]).length>0&&<><dt style={{color:'var(--text-secondary)'}}>{t('branches')}</dt><dd style={{margin:0}}>{me.branches.map(b=>st.branchLabel(b,lang)).join('، ')}</dd></>}
          <dt style={{color:'var(--text-secondary)'}}>{t('language')}</dt><dd style={{margin:0}}><LangSwitch/></dd>
        </dl>
      </Card>
      <Card title={t('change_pw')}>
        <form onSubmit={submit} noValidate style={{display:'flex',flexDirection:'column',gap:14}}>
          {[['cur','current_pw'],['nw','new_pw'],['cf','confirm_pw']].map(([k,l])=><Input key={k} label={t(l)} type="password" icon="lock" value={f[k]} error={e[k]} hint={k==='nw'?t('pw_rule'):undefined}
            onChange={ev=>setF({...f,[k]:ev.target.value})} style={{}} />)}
          <div onBlur={()=>{}}/>
          <div><Button type="submit" loading={busy} icon="key-round">{t('change_pw')}</Button></div>
        </form>
      </Card>
    </div>
  </div>;
}

function RefusalScreen({kind}){
  const {EmptyState,Button}=window.DS;const {t,go}=useApp();
  const f=kind==='403';
  return <div style={{display:'flex',alignItems:'center',justifyContent:'center',minHeight:'60vh'}}>
    <Panel style={{maxWidth:560,width:'100%'}}><EmptyState tone={f?'danger':'brand'} icon={f?'shield-x':'file-question'} title={<span style={{font:'700 20px var(--font-sans)'}}>{f?t('forbidden'):t('notfound')}</span>} message={f?t('forbidden_msg'):t('notfound_msg')}
      action={<Button icon="house" onClick={()=>go('home')}>{t('back_home')}</Button>}/>
      <div style={{textAlign:'center',paddingBottom:20,font:'700 44px var(--font-numeric)',color:'var(--gray-200)'}}>{f?'403':'404'}</div>
    </Panel>
  </div>;
}
Object.assign(window,{LoginScreen,ProfileScreen,RefusalScreen});
