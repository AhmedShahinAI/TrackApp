function DashboardScreen(){
  const {Icon}=window.DS;const {lang}=useApp();const st=useStore();const en=lang==='en';const DD=window.TRACK_DASH;
  const saved=JSON.parse(localStorage.getItem('track-dash')||'{}');
  const [ini,setIni]=React.useState(saved.ini||2);const [ti,setTi]=React.useState(saved.ti??null);const [br,setBr]=React.useState(saved.br||'all');const [view,setView]=React.useState(saved.view||'ov');
  React.useEffect(()=>{localStorage.setItem('track-dash',JSON.stringify({ini,ti,br,view}))},[ini,ti,br,view]);
  const I=st.initiatives.find(x=>x.id===ini);const recs=DD.byIni[ini]||[];
  const idx=ti==null||ti>=recs.length?recs.length-1:ti;const d=recs[idx];
  const B=DD.BR;const bName=b=>en?b.en:b.ar;const bSel=B.find(b=>b.k===br);
  const pick=k=>setBr(br===k?'all':k);
  const selSt={height:36,padding:'0 12px',background:'rgba(255,255,255,0.06)',color:DK.text,border:'1px solid '+DK.border,borderRadius:4,font:'600 13px '+DK.font,minWidth:170};
  const wrap={margin:'-28px -28px -48px',minHeight:'calc(100% + 76px)',paddingBottom:48,color:DK.text,fontFamily:DK.font,background:'var(--surface-page)'};
  const iniName=I?(en?I.en:I.ar):'';
  const hero=<div style={{padding:'28px 32px 20px',display:'flex',alignItems:'center',justifyContent:'space-between',flexWrap:'wrap',gap:16,borderBottom:'1px solid var(--border-default)'}}>
    <div><h1 style={{margin:0,fontSize:'clamp(22px,2.5vw,30px)',fontWeight:900,lineHeight:1.25,color:'var(--text-primary)'}}>{en?'Dashboard · ':'لوحة مؤشرات مبادرة '}<span style={{color:DK.teal2}}>{iniName}</span>{d&&<> – <span style={{color:'#b88c30'}}>{d.label}</span></>}</h1>
      <p style={{margin:'6px 0 0',fontSize:13,color:'var(--text-secondary)'}}>{I&&I.owner}{I&&I.status==='draft'&&(en?' · Draft':' · مسودة')}</p></div>
    {d&&<div style={{display:'flex',gap:12,flexWrap:'wrap'}}>{[[brD()?brD().v:d.kpi.total,en?'Certificates':'شهادة احترافية'],[d.kpi.days+(en?' d':' يوم'),en?'Duration':'مدة المبادرة'],[d.kpi.comp+'%',en?'Completion':'نسبة الإنجاز'],[d.kpi.fac,en?'Faculty':'عضو هيئة تدريس']].map(([v,l])=>
      <div key={l} style={{background:DK.panel2,border:'1px solid '+DK.border,borderRadius:8,padding:'10px 18px',textAlign:'center',minWidth:96}}><div style={{fontSize:24,fontWeight:900,color:DK.teal,lineHeight:1}}>{v}</div><div style={{fontSize:11,color:DK.text3,marginTop:4}}>{l}</div></div>)}</div>}
  </div>;
  function brD(){return bSel&&d?d.branches[br]:null;}
  const bar=<div style={{position:'sticky',top:-28,zIndex:20,background:'rgba(20,20,20,0.92)',backdropFilter:'blur(10px)',borderBottom:'1px solid '+DK.border2,padding:'10px 32px',display:'flex',alignItems:'center',gap:14,flexWrap:'wrap'}}>
    <label style={{display:'flex',alignItems:'center',gap:8,fontSize:13,color:DK.text3,fontWeight:600}}><Icon name="lightbulb" size={15} color={DK.text3}/>{en?'Initiative:':'المبادرة:'}
      <select value={String(ini)} onChange={e=>{setIni(+e.target.value);setTi(null);}} style={selSt}>{st.initiatives.map(i=><option key={i.id} value={String(i.id)} style={{background:'#2a2a2a'}}>{en?i.en:i.ar}</option>)}</select></label>
    <label style={{display:'flex',alignItems:'center',gap:8,fontSize:13,color:DK.text3,fontWeight:600}}><Icon name="map-pin" size={15} color={DK.text3}/>{en?'Branch:':'الفرع:'}
      <select value={br} onChange={e=>setBr(e.target.value)} style={selSt}><option value="all" style={{background:'#2a2a2a'}}>{en?'All branches':'جميع الفروع'}</option>{B.map(b=><option key={b.k} value={b.k} style={{background:'#2a2a2a'}}>{bName(b)}</option>)}</select></label>
    {recs.length>0&&<div style={{display:'flex',alignItems:'center',gap:8,flexWrap:'wrap'}}><span style={{display:'flex',alignItems:'center',gap:8,fontSize:13,color:DK.text3,fontWeight:600}}><Icon name="calendar-days" size={15} color={DK.text3}/>{en?'Term:':'الفصل الدراسي:'}</span>
      {recs.map((r,i)=>{const on=i===idx;const last=i===recs.length-1;return <button key={i} onClick={()=>setTi(i)} style={{padding:'5px 18px',border:'1px solid '+(on?DK.teal:last?DK.gold:DK.border),background:on?DK.teal:'transparent',color:on?'#fff':last?DK.gold:DK.text3,borderRadius:3,cursor:'pointer',font:'600 13px '+DK.font,transition:'all .18s'}}>{r.label}</button>})}</div>}
  </div>;
  const views=[['ov','نظرة عامة','Overview'],['br','الفروع','Branches'],['pg','البرامج','Programs'],['sc','الشعب','Sections'],['sa','رضا المستفيدين','Satisfaction'],['cmp','مقارنة الفصول','Compare terms']];
  const nav=<div style={{display:'flex',gap:0,padding:'0 32px',borderBottom:'1px solid '+DK.border2,background:'#262626',overflowX:'auto'}}>{views.map(([id,a,e])=>{const on=view===id;return <button key={id} onClick={()=>setView(id)} style={{padding:'0 16px',height:48,border:'none',borderBottom:'2px solid '+(on?DK.teal:'transparent'),background:'transparent',color:on?DK.teal:DK.text3,font:'600 14px '+DK.font,cursor:'pointer',whiteSpace:'nowrap'}}>{en?e:a}</button>})}</div>;

  if(!d)return <div style={wrap}>{hero}{bar}<div style={{padding:32}}><DkPanel><div style={{textAlign:'center',padding:'40px 0',color:DK.text2}}><Icon name="bar-chart-3" size={36} color={DK.text3}/><div style={{fontSize:18,fontWeight:800,marginTop:10,color:DK.text}}>{en?'No data yet':'لا توجد بيانات بعد'}</div><div style={{fontSize:13,color:DK.text3,marginTop:4}}>{en?'This initiative has no recorded term results.':'لم تُسجَّل نتائج فصلية لهذه المبادرة حتى الآن.'}</div></div></DkPanel></div></div>;

  const bd=brD();const total=bd?bd.v:d.kpi.total;
  const gM=bd?Math.round(bd.m/(bd.m+bd.f)*100):d.gender.m;
  const shutters=d.shutters.filter(s=>!bd||s.br===br).map((s,i)=>({...s,c:[DK.teal,DK.coral,DK.blue,DK.gold,DK.teal,DK.coral][d.shutters.indexOf(s)]}));
  const brTotal=B.reduce((a,b)=>a+d.branches[b.k].v,0);
  const secL=d.sec.st.map((_,i)=>String(i+1));
  const grid=(cols,gap=1)=>({display:'grid',gridTemplateColumns:cols,gap,background:gap===1?DK.border:'transparent'});
  const sec=(title,tag,children)=><div style={{padding:'28px 32px 0'}}><div style={{textAlign:'center',marginBottom:28}}><div style={{color:DK.teal2,fontSize:12,fontWeight:700,letterSpacing:'.12em',marginBottom:6}}>{tag}</div><h2 style={{margin:0,fontSize:'clamp(22px,2.5vw,28px)',fontWeight:900,color:'var(--text-primary)'}}>{title}</h2><div style={{width:50,height:2,background:`linear-gradient(90deg,${DK.teal},${DK.gold})`,margin:'8px auto 0',borderRadius:2}}></div></div>{children}</div>;
  const two={display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(360px,1fr))',gap:22,marginBottom:22};
  const kRow={display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(150px,1fr))',gap:16,marginBottom:26};
  const branchList=<div style={{width:'100%',textAlign:'start',padding:'0 8px'}}>{B.map(b=><div key={b.k} onClick={()=>pick(b.k)} style={{display:'flex',alignItems:'center',gap:10,marginBottom:12,fontSize:14,cursor:'pointer',opacity:bd&&br!==b.k?.4:1}}><span style={{width:9,height:9,borderRadius:'50%',background:DK_BR[b.k]}}></span><span style={{flex:1,color:DK.text2}}>{bName(b)}</span><b style={{fontSize:16}}>{d.branches[b.k].v}</b></div>)}</div>;

  let body;
  if(view==='ov')body=<div style={{padding:'24px 32px 0'}}><div style={{display:'flex',flexDirection:'column',gap:1,background:DK.border,border:'1px solid '+DK.border}}>
    <div style={grid('repeat(auto-fit,minmax(150px,1fr))')}>
      <DkKpi icon="award" label={bd?(en?'Certificates · ':'الشهادات المحققة · ')+bName(bSel):(en?'Total certificates':'إجمالي الشهادات المحققة')} value={total} sub={en?'accredited certificates':'شهادة تدريب مهني معتمدة'}/>
      <DkKpi icon="calendar-days" label={en?'Initiative days':'عدد أيام المبادرة'} value={d.kpi.days+(en?' d':' يوم')}/>
      <DkKpi icon="headphones" label={en?'Support requests':'طلب دعم فني'} value={d.kpi.supp}/>
      <DkKpi icon="graduation-cap" label={en?'Faculty members':'عضو هيئة تدريس'} value={d.kpi.fac}/>
      <DkKpi icon="clipboard-list" label={en?'Branch reports':'التقارير الدورية لأداء الفروع'} value={d.kpi.rep}/>
      <DkCell title={en?'By specialty unit':'نسبة الإنجاز حسب الوحدات التخصصية'} style={{gridColumn:'span 2'}}><div style={{display:'flex',gap:28}}>{[[en?'Administrative':'البرامج الإدارية',d.kpi.admin,DK.teal],[en?'Technical':'البرامج التقنية',d.kpi.tech,DK.gold]].map(([l,v,c])=><div key={l} style={{textAlign:'center'}}><DkDonut size={78} hole={.68} segs={[{v,c},{v:100-v,c:'rgba(255,255,255,0.08)'}]} center={<span style={{color:c,fontSize:14}}>{v}%</span>}/><div style={{fontSize:11,color:DK.text3,marginTop:4}}>{l}</div></div>)}</div></DkCell>
      <DkCell title={en?'By gender':'الحاصلون على الشهادات حسب النوع'} style={{gridColumn:'span 2'}}><DkDonut size={104} segs={[{v:gM,c:DK.teal},{v:100-gM,c:DK.coral}]} center={total}/><DkLegend items={[[gM+(en?'% male':'% طلاب'),DK.teal],[(100-gM)+(en?'% female':'% طالبات'),DK.coral]]}/></DkCell>
    </div>
    <div style={grid('repeat(auto-fit,minmax(240px,1fr))')}>
      <DkCell><div style={{fontSize:11,color:DK.text3,marginBottom:8}}>{en?'Actual completion':'نسب الإنجاز الفعلي'}</div><DkGauge pct={d.kpi.comp}/><div style={{fontSize:30,fontWeight:900,color:DK.red,lineHeight:1,marginTop:4}}>{d.kpi.comp}%</div></DkCell>
      <DkCell title={en?'Certificates per campus section':'عدد الشهادات الاحترافية حسب كل شطر'} style={{minHeight:200}}><DkShutters data={shutters}/></DkCell>
      <DkCell title={en?'Completion by program':'نسب الإنجاز حسب البرامج'} style={{minHeight:200,gridColumn:'span 2'}}><DkProg3D data={d.programs}/></DkCell>
      <DkCell title={en?'Beneficiary satisfaction':'رضا المستفيدين عن المبادرة'} style={{minHeight:200}}><div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12,width:'100%'}}>{(d.sat||[]).slice(0,4).map(s=>{const a=s.d[0]+s.d[1],n=s.d[2];return <div key={s.q} style={{display:'flex',flexDirection:'column',alignItems:'center',gap:4}}><DkDonut size={62} hole={.62} segs={[{v:a,c:DK.teal},{v:n,c:DK.gold},{v:100-a-n,c:DK.coral}]} center={<span style={{fontSize:11}}>{a}%</span>}/><div style={{fontSize:10,color:DK.text3,lineHeight:1.3,maxWidth:130}}>{s.q}</div></div>})}</div></DkCell>
    </div>
    <div style={grid('repeat(auto-fit,minmax(320px,1fr))')}>
      <DkCell title={en?'Branches – Riyadh region':'الشهادات حسب الفروع – منطقة الرياض'} style={{gridColumn:'span 2',minHeight:320}}><div style={{display:'grid',gridTemplateColumns:'minmax(0,2fr) minmax(130px,1fr)',gap:16,alignItems:'center',width:'100%'}}><DkMap d={d} br={br} onPick={pick} en={en}/>{branchList}</div></DkCell>
      <DkCell title={en?'Students vs registered per section':'الطلبة والمسجلون حسب الشعب'} style={{minHeight:320}}><DkLine labels={secL} series={[{label:en?'Students':'إجمالي الطلبة',data:d.sec.st,c:DK.gold},{label:en?'Registered':'إجمالي المسجلين',data:d.sec.rg,c:DK.teal}]}/></DkCell>
    </div>
  </div>
  {d.excellence&&<div style={{background:'linear-gradient(135deg,rgba(59,184,195,0.15),rgba(212,168,75,0.1))',border:'1px solid rgba(59,184,195,0.3)',borderRadius:12,padding:'1.6rem',textAlign:'center',marginTop:22}}><Icon name="medal" size={34} color={DK.gold}/><div style={{fontSize:44,fontWeight:900,color:DK.teal,lineHeight:1.1}}>{d.excellenceNum}</div><div style={{fontSize:16,fontWeight:700,margin:'4px 0 2px'}}>{en?'Excellence initiative participations':'مشاركة في مبادرة تميز'}</div><div style={{fontSize:13,color:DK.text3}}>Excellence Award – {d.label}</div></div>}
  </div>;
  else if(view==='br')body=sec(en?'Branches & geography':'الفروع والتوزيع الجغرافي','Branches · الفروع',<>
    <div style={kRow}>{B.map(b=><DkKcard key={b.k} icon={b.k==='majmaah'?'landmark':'school'} v={d.branches[b.k].v} l={bName(b)+' – '+b.sub} accent={DK_BR[b.k]}/>)}<DkKcard icon="bar-chart-3" v={brTotal} l={en?'Total':'إجمالي الشهادات'}/></div>
    <div style={two}><DkPanel title={en?'Interactive map – Riyadh region':'الخريطة التفاعلية – منطقة الرياض'}><DkMap d={d} br={br} onPick={pick} en={en}/></DkPanel>
      <DkPanel title={en?'Branch details':'تفاصيل الفروع'}>{B.map(b=>{const x=d.branches[b.k],c=DK_BR[b.k],p=Math.round(x.v/brTotal*100);return <div key={b.k} onClick={()=>pick(b.k)} style={{cursor:'pointer',background:'rgba(255,255,255,0.04)',border:'1px solid rgba(255,255,255,0.1)',borderInlineStart:'3px solid '+c,borderRadius:8,padding:'1rem',marginBottom:14,opacity:bd&&br!==b.k?.45:1}}>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:8}}><div><b style={{color:c,fontSize:15}}>{bName(b)}</b><span style={{fontSize:12,color:DK.text3,marginInlineStart:6}}>{b.sub}</span></div><div style={{fontSize:26,fontWeight:900,color:c}}>{x.v}</div></div>
        <div style={{display:'flex',gap:22,fontSize:13,marginBottom:8,color:DK.text2}}><span>{en?'Male':'طلاب'}: <b style={{color:DK.teal}}>{x.m}</b></span><span>{en?'Female':'طالبات'}: <b style={{color:DK.coral}}>{x.f}</b></span></div>
        <div style={{height:6,background:'rgba(255,255,255,0.08)',borderRadius:5,overflow:'hidden'}}><div style={{height:'100%',width:p+'%',background:c,borderRadius:5}}></div></div>
        <div style={{fontSize:11,color:DK.text3,textAlign:'end',marginTop:4}}>{p}% {en?'of total':'من الإجمالي'}</div></div>})}</DkPanel></div></>);
  else if(view==='pg')body=sec(en?'Completion by program':'نسب الإنجاز حسب البرامج','Programs · البرامج',<div style={two}>
    <DkPanel title={en?'Programs':'البرامج'}>{d.programs.map(p=><DkBar key={p.n} label={p.n} value={p.p} c={dkTone(p.p)}/>)}</DkPanel>
    <DkPanel title={en?'Branch comparison':'مقارنة الفروع'}><DkGroup labels={B.map(bName)} series={[{label:'',data:B.map(b=>d.branches[b.k].v),c:DK.gold}]}/></DkPanel></div>);
  else if(view==='sc'){const rows=d.sec.st.map((s,i)=>({n:(en?'Section ':'شعبة ')+(i+1),st:s,rg:d.sec.rg[i],ct:d.sec.ct[i],p:d.sec.rg[i]?Math.round(d.sec.ct[i]/d.sec.rg[i]*100):0}));
    body=sec(en?'Sections performance':'أداء الشعب','Sections · الشعب',<><div style={two}>
      <DkPanel title={en?'Students vs registered':'إجمالي الطلبة والمسجلين'}><DkLine labels={secL} series={[{label:en?'Students':'إجمالي الطلبة',data:d.sec.st,c:DK.gold},{label:en?'Registered':'إجمالي المسجلين',data:d.sec.rg,c:DK.teal}]}/></DkPanel>
      <DkPanel title={en?'Registered vs certified':'المسجلون والحاصلون على الشهادة'}><DkGroup labels={secL} series={[{label:en?'Registered':'المسجلين',data:d.sec.rg,c:DK.teal},{label:en?'Certified':'الحاصلين على الشهادة',data:d.sec.ct,c:DK.gold}]}/></DkPanel></div>
      <DkPanel><DkTable rows={rows} cols={[{k:'n',l:en?'Section':'الشعبة'},{k:'st',l:en?'Students':'إجمالي الطلبة',num:1},{k:'rg',l:en?'Registered':'المسجلون',num:1},{k:'ct',l:en?'Certified':'الحاصلون',num:1},{k:'p',l:en?'Rate':'النسبة',num:1,r:r=><b style={{color:dkTone(r.p)}}>{r.p}%</b>}]}/></DkPanel></>);}
  else if(view==='sa')body=sec(en?'Beneficiary satisfaction':'رضا المستفيدين عن المبادرة','Satisfaction · رضا المستفيدين',<div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(340px,1fr))',gap:20}}>{(d.sat||[]).map(s=><DkSat key={s.q} q={s.q} d={s.d}/>)}</div>);
  else{const C=[DK.teal,DK.blue,DK.gold,DK.coral];const tv=r=>bd?r.branches[br].v:r.kpi.total;
    body=sec((en?'Compare all terms':'مقارنة جميع الفصول الدراسية')+(bd?' · '+bName(bSel):''),'Compare · المقارنة',<>
      <div style={kRow}>{recs.map((r,i)=><div key={i} onClick={()=>setTi(i)} style={{cursor:'pointer'}}><DkKcard top={C[i%4]} accent={C[i%4]} v={tv(r)} l={r.label+' · '+(en?'completion ':'إنجاز ')+r.kpi.comp+'%'}/></div>)}</div>
      <div style={two}>
        <DkPanel title={en?'Total certificates':'إجمالي الشهادات'}><DkGroup labels={recs.map(r=>r.label)} series={[{label:'',data:recs.map(tv),c:DK.teal}]}/></DkPanel>
        <DkPanel title={en?'Completion rates':'نسب الإنجاز'}><DkLine labels={recs.map(r=>r.label)} series={[{label:en?'Completion %':'نسبة الإنجاز %',data:recs.map(r=>r.kpi.comp),c:DK.teal},{label:en?'Administrative %':'البرامج الإدارية %',data:recs.map(r=>r.kpi.admin),c:DK.gold},{label:en?'Technical %':'البرامج التقنية %',data:recs.map(r=>r.kpi.tech),c:DK.coral}]}/></DkPanel>
        <DkPanel title={en?'Branches by term':'الفروع حسب الفصل'}><DkGroup labels={recs.map(r=>r.label)} series={B.map(b=>({label:bName(b),data:recs.map(r=>r.branches[b.k].v),c:DK_BR[b.k]}))}/></DkPanel>
        <DkPanel title={en?'Support & faculty':'الدعم الفني وهيئة التدريس'}><DkGroup labels={recs.map(r=>r.label)} series={[{label:en?'Support requests':'طلبات الدعم الفني',data:recs.map(r=>r.kpi.supp),c:DK.coral},{label:en?'Faculty':'أعضاء هيئة التدريس',data:recs.map(r=>r.kpi.fac),c:DK.teal}]}/></DkPanel>
      </div>
      <DkPanel><DkTable hi={idx} rows={recs} cols={[{k:'label',l:en?'Term':'الفصل الدراسي',r:(r,i)=><b style={{color:C[i%4]}}>{r.label}</b>},{k:'t',l:en?'Certificates':'إجمالي الشهادات',num:1,r:r=><b style={{color:DK.text}}>{tv(r)}</b>},{k:'c',l:en?'Completion':'نسبة الإنجاز',num:1,r:r=><span style={{background:r.kpi.comp>=98?'rgba(59,184,195,0.2)':'rgba(212,168,75,0.2)',color:r.kpi.comp>=98?DK.teal:DK.gold,padding:'3px 10px',borderRadius:6,fontWeight:700}}>{r.kpi.comp}%</span>},{k:'d',l:en?'Days':'أيام المبادرة',num:1,r:r=>r.kpi.days},{k:'f',l:en?'Faculty':'هيئة التدريس',num:1,r:r=>r.kpi.fac},{k:'s',l:en?'Support':'طلبات الدعم',num:1,r:r=>r.kpi.supp},{k:'e',l:en?'Excellence':'مبادرة تميز',num:1,r:r=>r.excellence?<b style={{color:DK.gold}}>{r.excellenceNum}</b>:'—'}]}/></DkPanel></>);}
  return <div style={wrap}>{hero}{bar}{nav}{body}</div>;
}
window.DashboardScreen=DashboardScreen;
