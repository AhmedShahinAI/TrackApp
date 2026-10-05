function HomeScreen(){
  const {StatCard,Card,Alert,Button,DataTable,StatusBadge,EmptyState}=window.DS;const {t,me,lang,go,firstBanner,dismissBanner}=useApp();const st=useStore();
  const term=st.currentTerm();const secs=st.sectionScope(me).filter(s=>s.term===term.id);
  const ids=new Set(secs.map(s=>s.id));const enr=st.S.enrollments.filter(e=>ids.has(e.section)&&e.status==='active');
  const studs=new Set(enr.map(e=>e.student)).size;
  const recent=st.S.audit.filter(a=>me.role==='SUPER_ADMIN'||a.actor===me.id).slice(0,5);
  const quick={SUPER_ADMIN:[['lightbulb','nav_initiatives','initiatives'],['user-plus','new_user','users'],['file-up','new_import','imports'],['calendar-plus','new_term','terms'],['shield-check','nav_audit','audit']],
    BRANCH_COORDINATOR:[['users','nav_sections','sections'],['shield-check','nav_audit','audit']],
    INSTRUCTOR:[['users','nav_mysections','mysections'],['file-up','import_list','imports']]}[me.role];
  return <div>
    {firstBanner&&<Alert tone="warning" icon="key-round" title={t('first_login')} onClose={dismissBanner} style={{marginBottom:18}}
      action={<Button size="sm" variant="secondary" onClick={()=>go('profile')}>{t('change_now')}</Button>}>{t('first_login_msg')}</Alert>}
    <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-end',gap:16,marginBottom:22,flexWrap:'wrap'}}>
      <div><h1 style={{margin:0,font:'var(--text-h1)'}}>{t('welcome',{n:lang==='en'?me.en:me.ar})}</h1>
      <p style={{margin:'6px 0 0',color:'var(--text-secondary)'}}>{t('role_'+me.role)}{me.branches.length>0&&' · '+me.branches.map(b=>st.branchLabel(b,lang)).join('، ')}</p></div>
      <div style={{display:'flex',alignItems:'center',gap:8,padding:'8px 14px',background:'#fff',border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)'}}>
        <span style={{font:'500 12px var(--font-sans)',color:'var(--text-secondary)'}}>{t('current_term')}</span><b style={{font:'700 13px var(--font-sans)'}}>{lang==='en'?term.en:term.ar}</b><StatusBadge status="current"/>
      </div>
    </div>
    {me.role==='INSTRUCTOR'&&secs.length===0?<Panel><EmptyState icon="users" title={t('no_sections_inst')} message={t('no_sections_inst_msg')}/></Panel>:<>
    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(150px,1fr))',gap:14,marginBottom:18}}>
      {me.role==='SUPER_ADMIN'&&<StatCard value={st.S.users.filter(u=>u.active).length} label={t('k_users')}/>}
      {me.role!=='INSTRUCTOR'&&<StatCard value={me.role==='SUPER_ADMIN'?st.S.branches.filter(b=>b.active).length:me.branches.length} label={t('k_branches')}/>}
      <StatCard value={secs.length} label={t('k_sections')}/>
      <StatCard value={studs} label={t('k_students')} tone="green"/>
      <StatCard value={enr.length} label={t('k_enroll')} tone="green"/>
      {me.role==='SUPER_ADMIN'&&<StatCard value={st.S.imports.length} label={t('k_imports')}/>}
    </div>
    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(340px,1fr))',gap:14}}>
      <Card title={t('quick')}><div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:10}}>
        {quick.map(([i,l,r])=><Button key={l} variant="secondary" icon={i} onClick={()=>go(r)} style={{justifyContent:'flex-start',height:52}}>{t(l)}</Button>)}
      </div></Card>
      <Card title={t('recent_activity')} action={me.role!=='INSTRUCTOR'&&<a href="#" onClick={e=>{e.preventDefault();go('audit')}}>{t('view_all')}</a>}>
        <DataTable dense columns={[{key:'at',label:t('when'),numeric:true,render:r=>fmtDateTime(r.at)},{key:'action',label:t('action'),render:r=><span style={{font:'600 13px var(--font-sans)',color:'var(--track-blue)'}}>{window.TRACK_I18N.lbl(lang,'act',r.action)}</span>},{key:'actor',label:t('actor'),render:r=>r.actor?st.uname(r.actor,lang):'—'}]} rows={recent}/>
      </Card>
    </div></>}
  </div>;
}
window.HomeScreen=HomeScreen;
