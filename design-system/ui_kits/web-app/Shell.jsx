const {t:_t,fmtDate,fmtDateTime}=window.TRACK_I18N;
const AppCtx=React.createContext(null);
function useApp(){return React.useContext(AppCtx);}
function useStore(){const [,f]=React.useState(0);React.useEffect(()=>window.TRACK_STORE.subscribe(()=>f(x=>x+1)),[]);return window.TRACK_STORE;}

function PageHeader({title,subtitle,actions,crumbs}){
  const {Breadcrumbs}=window.DS;
  return <div style={{marginBottom:20}}>
    {crumbs&&<div style={{marginBottom:10}}><Breadcrumbs items={crumbs}/></div>}
    <div style={{display:'flex',alignItems:'flex-end',justifyContent:'space-between',gap:16,flexWrap:'wrap'}}>
      <div style={{minWidth:0}}><h1 style={{margin:0,font:'var(--text-h1)',color:'var(--text-primary)'}}>{title}</h1>
      {subtitle&&<p style={{margin:'6px 0 0',font:'400 14px/1.6 var(--font-sans)',color:'var(--text-secondary)'}}>{subtitle}</p>}</div>
      {actions&&<div style={{display:'flex',gap:10,alignItems:'flex-end',flexWrap:'wrap'}}>{actions}</div>}
    </div>
  </div>;
}

function Field({label,children,error,hint,required}){
  return <label style={{display:'flex',flexDirection:'column',gap:6}}>
    <span style={{font:'600 13px var(--font-sans)',color:'var(--text-primary)'}}>{label}{required&&<span style={{color:'var(--red-600)'}}> *</span>}</span>
    {children}
    {(error||hint)&&<span role={error?'alert':undefined} style={{font:'400 12px var(--font-sans)',color:error?'var(--red-600)':'var(--text-secondary)'}}>{error||hint}</span>}
  </label>;
}
const nativeStyle={height:40,padding:'0 12px',border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',font:'400 14px var(--font-sans)',background:'#fff',color:'var(--text-primary)',width:'100%'};

function ConfirmDialog({open,title,impact,confirmLabel,tone='danger',onConfirm,onClose,children,busy}){
  const {Dialog,Button,Alert}=window.DS;const {t}=useApp();
  return <Dialog open={open} title={title} onClose={onClose} width={480}
    footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button variant={tone==='danger'?'danger':'primary'} loading={busy} onClick={onConfirm}>{confirmLabel||t('confirm')}</Button></>}>
    <Alert tone={tone==='danger'?'warning':'info'}>{impact}</Alert>
    {children&&<div style={{marginTop:14,display:'flex',flexDirection:'column',gap:14}}>{children}</div>}
  </Dialog>;
}

function Panel({children,style}){return <section style={{background:'#fff',border:'1px solid var(--border-default)',borderRadius:'var(--radius-lg)',boxShadow:'var(--shadow-card)',minWidth:0,...style}}>{children}</section>;}

function useFakeLoad(dep){const [l,setL]=React.useState(true);React.useEffect(()=>{setL(true);const x=setTimeout(()=>setL(false),450);return()=>clearTimeout(x)},[dep]);return l;}

const NAV={
 SUPER_ADMIN:[['home','house'],['dashboard','layout-dashboard'],['initiatives','lightbulb'],['users','user-cog'],['studentsadmin','graduation-cap'],['terms','calendar-range'],['branches','building-2'],['programs','library'],['courses','book-open'],['sections','users'],['approvals','clipboard-check'],['imports','file-up'],['support','life-buoy'],['audit','shield-check']],
 BRANCH_COORDINATOR:[['home','house'],['initiatives','lightbulb'],['sections','users'],['approvals','clipboard-check'],['studentsadmin','graduation-cap'],['imports','file-up'],['support','life-buoy'],['audit','shield-check']],
 INSTRUCTOR:[['home','house'],['mysections','users'],['studentsadmin','graduation-cap'],['imports','file-up'],['support','life-buoy']],
 STUDENT:[['myinitiatives','lightbulb'],['support','life-buoy']],
};

function LangSwitch(){
  const {lang,setLang}=useApp();
  return <div role="group" aria-label="Language" style={{display:'flex',background:'var(--gray-100)',borderRadius:8,padding:3,gap:2}}>
    {[['ar','عربي'],['en','EN']].map(([k,l])=><button key={k} aria-pressed={lang===k} onClick={()=>setLang(k)} style={{height:28,padding:'0 10px',border:'none',borderRadius:6,cursor:'pointer',background:lang===k?'#fff':'transparent',boxShadow:lang===k?'var(--shadow-xs)':'none',font:(lang===k?'700':'500')+' 12px var(--font-sans)',color:lang===k?'var(--track-blue)':'var(--text-secondary)'}}>{l}</button>)}
  </div>;
}

function Shell({children}){
  const {Sidebar,Topbar,Icon}=window.DS;const {me,route,go,t,lang,logout}=useApp();
  const items=NAV[me.role].map(([id,icon])=>({id,icon,label:t('nav_'+id)}));
  const base=route.name==='initiative'?'initiatives':route.name==='students'||route.name==='section'?(me.role==='INSTRUCTOR'?'mysections':'sections'):route.name==='student'?'studentsadmin':route.name==='wizard'?'imports':route.name;
  return <div style={{display:'flex',height:'100vh',background:'var(--surface-page)'}}>
    <Sidebar items={items} active={base} onSelect={id=>go(id)} assetBase="../../assets/"
      footer={<button onClick={logout} style={{display:'flex',alignItems:'center',gap:12,height:42,padding:'0 14px',border:'none',borderRadius:8,background:'transparent',color:'rgba(255,255,255,.75)',font:'500 14px var(--font-sans)',cursor:'pointer'}}><Icon name="log-out" size={19}/>{t('logout')}</button>}/>
    <div style={{flex:1,minWidth:0,display:'flex',flexDirection:'column'}}>
      <Topbar userName={lang==='en'?me.en:me.ar} userRole={t('role_'+me.role)} onUserClick={()=>go('profile')} notifications={false} end={<LangSwitch/>}/>
      <main style={{flex:1,overflow:'auto',padding:'28px 28px 48px'}}>{children}</main>
    </div>
  </div>;
}
function downloadTemplate(name,rows){const esc=v=>/[",\n]/.test(String(v))?'"'+String(v).replace(/"/g,'""')+'"':String(v);const csv='\uFEFF'+rows.map(r=>r.map(esc).join(',')).join('\n');const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8'}));a.download=name;document.body.appendChild(a);a.click();a.remove();}
Object.assign(window,{downloadTemplate,AppCtx,useApp,useStore,PageHeader,Field,nativeStyle,ConfirmDialog,Panel,useFakeLoad,Shell,LangSwitch,fmtDate,fmtDateTime});
