function AuditTable({rows,dense}){
  const {DataTable,Button}=window.DS;const {t,lang}=useApp();const st=useStore();const [open,setOpen]=React.useState(null);const L=(p,c)=>window.TRACK_I18N.lbl(lang,p,c);const V=x=>Array.isArray(x)?x.join('، '):L('val',String(x));
  const vals=v=>v?Object.entries(v).map(([k,x])=><div key={k} style={{font:'400 12px/1.6 var(--font-sans)'}}><span style={{color:'var(--text-secondary)'}}>{L('fld',k)}:</span> {V(x)}</div>):<span style={{color:'var(--text-muted)'}}>—</span>;
  return <>
    <DataTable dense={dense} columns={[
      {key:'at',label:t('when'),numeric:true,render:r=>fmtDateTime(r.at)},
      {key:'actor',label:t('actor'),render:r=>r.actor?st.uname(r.actor,lang):<span style={{color:'var(--text-muted)'}}>—</span>},
      {key:'action',label:t('action'),render:r=><span style={{font:'600 13px var(--font-sans)',color:r.action==='access.denied'||r.action==='auth.failed'?'var(--red-600)':'var(--track-blue)'}}>{L('act',r.action)}</span>},
      {key:'e',label:t('entity'),numeric:true,render:r=>r.type?L('ent',r.type)+(r.eid?' #'+r.eid:''):'—'},
      ...(dense?[]:[{key:'ip',label:t('ip'),numeric:true}]),
      {key:'x',label:'',render:r=><Button size="sm" variant="ghost" onClick={()=>setOpen(r)}>{t('details')}</Button>}]} rows={rows} empty={t('no_entries')}/>
    {open&&<window.DS.Dialog title={L('act',open.action)} subtitle={fmtDateTime(open.at)+' · '+(open.actor?st.uname(open.actor,lang):'—')} onClose={()=>setOpen(null)} width={560}>
      <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12,marginBottom:14}}>
        <div style={{padding:12,background:'var(--red-50)',borderRadius:8}}><div style={{font:'700 12px var(--font-sans)',marginBottom:6}}>{t('before')}</div>{vals(open.old)}</div>
        <div style={{padding:12,background:'var(--green-50)',borderRadius:8}}><div style={{font:'700 12px var(--font-sans)',marginBottom:6}}>{t('after')}</div>{vals(open.neu)}</div>
      </div>
      <dl style={{display:'grid',gridTemplateColumns:'auto 1fr',gap:'6px 14px',margin:0,font:'400 13px var(--font-sans)'}}>
        <dt style={{color:'var(--text-secondary)'}}>{t('entity')}</dt><dd style={{margin:0}}>{L('ent',open.type)} #{open.eid??'—'}</dd>
        <dt style={{color:'var(--text-secondary)'}}>{t('ip')}</dt><dd style={{margin:0,fontFamily:'var(--font-latin)'}}>{open.ip}</dd>
        <dt style={{color:'var(--text-secondary)'}}>{t('browser')}</dt><dd style={{margin:0,fontFamily:'var(--font-latin)'}}>{open.ua}</dd>
      </dl>
    </window.DS.Dialog>}
  </>;
}
function AuditScreen({notify}){
  const {Select,Input,Button,Alert}=window.DS;const {t,me,lang}=useApp();const st=useStore();
  const [actor,setActor]=React.useState('');const [action,setAction]=React.useState('');const [type,setType]=React.useState('');const [from,setFrom]=React.useState('');const [to,setTo]=React.useState('');
  const loading=useFakeLoad(actor+action+type+from+to);
  const base=me.role==='SUPER_ADMIN'?st.S.audit:st.S.audit.filter(a=>a.type!=='user'&&(a.type!=='section'||st.S.sections.some(s=>s.id===a.eid&&st.canSee(me,s))));
  const rows=base.filter(a=>(!actor||a.actor===+actor)&&(!action||a.action===action)&&(!type||a.type===type)&&(!from||a.at.slice(0,10)>=from)&&(!to||a.at.slice(0,10)<=to));
  const actions=[...new Set(st.S.audit.map(a=>a.action))].sort();const types=[...new Set(st.S.audit.map(a=>a.type).filter(Boolean))].sort();
  return <div>
    <PageHeader title={t('nav_audit')} subtitle={t('audit_sub')} actions={st.can(me,'audit.export')&&<Button variant="secondary" icon="download" onClick={()=>notify(t('export'),rows.length+' '+t('rows')+' · xlsx')}>{t('export')}</Button>}/>
    <Panel style={{padding:20}}>
      <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(170px,1fr))',gap:12,marginBottom:14}}>
        <Select size="sm" label={t('actor')} value={actor} onChange={e=>setActor(e.target.value)} options={[{value:'',label:t('all')},...st.S.users.map(u=>({value:u.id,label:lang==='en'?u.en:u.ar}))]}/>
        <Select size="sm" label={t('action')} value={action} onChange={e=>setAction(e.target.value)} options={[{value:'',label:t('all')},...actions.map(x=>({value:x,label:window.TRACK_I18N.lbl(lang,'act',x)}))]}/>
        <Select size="sm" label={t('entity')} value={type} onChange={e=>setType(e.target.value)} options={[{value:'',label:t('all')},...types.map(x=>({value:x,label:window.TRACK_I18N.lbl(lang,'ent',x)}))]}/>
        <Input size="sm" type="date" label={t('date_from')} value={from} onChange={e=>setFrom(e.target.value)}/>
        <Input size="sm" type="date" label={t('date_to')} value={to} onChange={e=>setTo(e.target.value)}/>
      </div>
      <Alert tone="info" icon="lock" style={{marginBottom:14}}>{t('immutable')} · {rows.length} {t('rows')}</Alert>
      {loading?<window.DS.DataTable loading columns={[{key:'a',label:t('when')},{key:'b',label:t('actor')},{key:'c',label:t('action')},{key:'d',label:t('entity')}]} rows={[]}/>:<AuditTable rows={rows}/>}
    </Panel>
  </div>;
}
Object.assign(window,{AuditTable,AuditScreen});
