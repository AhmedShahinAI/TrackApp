const DK={panel:'rgba(30,30,30,0.82)',panel2:'rgba(40,40,40,0.9)',border:'rgba(255,255,255,0.13)',border2:'rgba(255,255,255,0.07)',teal:'#3bb8c3',teal2:'#2a9da8',coral:'#e07a6a',gold:'#d4a84b',blue:'#4a90c4',text:'#f0f0f0',text2:'#c0c0c0',text3:'#909090',red:'#e05555',font:"'Cairo','Tajawal',sans-serif"};
const DK_BR={majmaah:DK.gold,zulfi:DK.blue,rumah:DK.teal};
const DK_CYC=[DK.blue,DK.teal,DK.gold,DK.coral];
const dkTone=p=>p>=90?DK.teal:p>=70?DK.gold:DK.coral;

function DkCell({title,children,style}){
  return <div style={{background:DK.panel,padding:'1.1rem 1rem',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',textAlign:'center',minHeight:130,position:'relative',overflow:'hidden',...style}}>
    {title&&<div style={{position:'absolute',top:8,insetInlineStart:11,fontSize:11,color:DK.text3,fontWeight:600}}>{title}</div>}
    <div style={{marginTop:title?20:0,width:'100%',display:'flex',flexDirection:'column',alignItems:'center'}}>{children}</div></div>;
}
function DkKpi({icon,label,value,sub}){const {Icon}=window.DS;
  return <DkCell><div style={{width:54,height:54,border:'2px solid '+DK.border,borderRadius:8,background:'rgba(255,255,255,0.06)',display:'flex',alignItems:'center',justifyContent:'center',marginBottom:8}}><Icon name={icon} size={26} color={DK.text2}/></div>
    <div style={{fontSize:11,color:DK.text3,marginBottom:4}}>{label}</div>
    <div style={{fontSize:34,fontWeight:900,color:DK.red,lineHeight:1,marginTop:4}}>{value}</div>
    {sub&&<div style={{fontSize:11,color:DK.text3,marginTop:4}}>{sub}</div>}</DkCell>;
}
function DkDonut({segs,size=110,hole=.72,center}){
  const tot=segs.reduce((a,s)=>a+s.v,0)||1;let acc=0;
  const g=segs.map(s=>{const a=acc/tot*360;acc+=s.v;return `${s.c} ${a}deg ${acc/tot*360}deg`}).join(',');
  return <div style={{width:size,height:size,borderRadius:'50%',background:`conic-gradient(${g})`,display:'flex',alignItems:'center',justifyContent:'center'}}>
    <div style={{width:size*hole,height:size*hole,borderRadius:'50%',background:'#262626',display:'flex',alignItems:'center',justifyContent:'center',fontWeight:800,fontSize:size/6,color:DK.text}}>{center}</div></div>;
}
function DkGauge({pct}){
  return <svg viewBox="0 0 110 64" style={{width:130,height:76}}>
    <defs><linearGradient id="dkgg" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stopColor={DK.teal}></stop><stop offset="100%" stopColor={DK.gold}></stop></linearGradient></defs>
    <path d="M10 60 A45 45 0 0 1 100 60" fill="none" stroke="rgba(255,255,255,0.12)" strokeWidth="11" strokeLinecap="round"></path>
    <path d="M10 60 A45 45 0 0 1 100 60" fill="none" stroke="url(#dkgg)" strokeWidth="11" strokeLinecap="round" strokeDasharray="141" strokeDashoffset={141-pct/100*141} style={{transition:'stroke-dashoffset .8s'}}></path>
    <text x="55" y="54" textAnchor="middle" fill={DK.red} fontSize="16" fontWeight="900" fontFamily="Cairo">{pct}%</text></svg>;
}
function DkShutters({data,h=90}){const mx=Math.max(1,...data.map(s=>s.v));
  return <div style={{display:'flex',alignItems:'flex-end',justifyContent:'center',gap:8,height:h+60}}>{data.map(s=><div key={s.n} style={{display:'flex',flexDirection:'column',alignItems:'center',gap:3}}>
    <div style={{fontSize:11,fontWeight:700,color:DK.text2}}>{s.v}</div>
    <div style={{width:20,height:Math.round(s.v/mx*h),background:s.c,borderRadius:'2px 2px 0 0',transition:'height .8s'}}></div>
    <div style={{fontSize:10,color:DK.text3,writingMode:'vertical-rl',height:48,overflow:'hidden'}}>{s.n}</div></div>)}</div>;
}
function DkProg3D({data,h=88}){
  return <div style={{display:'flex',alignItems:'flex-end',justifyContent:'center',gap:9,height:h+50}}>{data.map((p,i)=>{const c=DK_CYC[i%4];return <div key={p.n} style={{display:'flex',flexDirection:'column',alignItems:'center',gap:3}}>
    <div style={{fontSize:11,fontWeight:700,color:DK.text2}}>{p.p}%</div>
    <div style={{width:22,height:Math.round(p.p/100*h),background:c,borderRadius:'2px 2px 0 0',position:'relative',transition:'height .8s'}}><div style={{position:'absolute',top:0,right:-4,width:4,height:'100%',background:c,filter:'brightness(.7)'}}></div></div>
    <div style={{fontSize:10,color:DK.text3,maxWidth:40,lineHeight:1.2,height:26,overflow:'hidden'}}>{p.n}</div></div>})}</div>;
}
function DkLine({labels,series,h=200}){
  const W=600,P=24,mx=Math.max(1,...series.flatMap(s=>s.data));const n=labels.length;
  const x=i=>P+(n<2?0:i*(W-2*P)/(n-1)),y=v=>h-P-(v/mx)*(h-2*P);
  return <div><svg viewBox={`0 0 ${W} ${h}`} style={{width:'100%',height:h,display:'block'}}>
    {[0,.25,.5,.75,1].map(t=><line key={t} x1={P} x2={W-P} y1={P+t*(h-2*P)} y2={P+t*(h-2*P)} stroke="rgba(255,255,255,0.06)"></line>)}
    {series.map(s=>{const pts=s.data.map((v,i)=>x(i)+','+y(v)).join(' ');return <g key={s.label}>
      <polygon points={`${x(0)},${h-P} ${pts} ${x(n-1)},${h-P}`} fill={s.c} opacity=".08"></polygon>
      <polyline points={pts} fill="none" stroke={s.c} strokeWidth="2.2" strokeLinejoin="round"></polyline>
      {s.data.map((v,i)=><circle key={i} cx={x(i)} cy={y(v)} r="3.2" fill={s.c}></circle>)}</g>})}
    {labels.map((l,i)=><text key={i} x={x(i)} y={h-6} textAnchor="middle" fontSize="10" fill="rgba(255,255,255,0.4)" fontFamily="Cairo">{l}</text>)}
  </svg><DkLegend items={series.map(s=>[s.label,s.c])}/></div>;
}
function DkGroup({labels,series,h=180,suffix=''}){const mx=Math.max(1,...series.flatMap(s=>s.data));
  return <div><div style={{display:'flex',alignItems:'flex-end',gap:6,height:h+34,overflowX:'auto',paddingTop:14}}>{labels.map((l,i)=><div key={i} style={{flex:'1 0 26px',display:'flex',flexDirection:'column',alignItems:'center',gap:4}}>
    <div style={{display:'flex',alignItems:'flex-end',gap:2,height:h}}>{series.map(s=><div key={s.label} title={s.label+': '+s.data[i]+suffix} style={{width:series.length>1?9:22,height:Math.max(2,s.data[i]/mx*h),background:s.c,opacity:s.op||.8,borderRadius:'3px 3px 0 0',position:'relative'}}>{series.length===1&&<span style={{position:'absolute',top:-16,left:'50%',transform:'translateX(-50%)',fontSize:10,fontWeight:700,color:DK.text2,whiteSpace:'nowrap'}}>{s.data[i]}{suffix}</span>}</div>)}</div>
    <div style={{fontSize:10,color:DK.text3,whiteSpace:'nowrap'}}>{l}</div></div>)}</div>
    {series.length>1&&<DkLegend items={series.map(s=>[s.label,s.c])}/>}</div>;
}
function DkLegend({items}){return <div style={{display:'flex',gap:14,justifyContent:'center',flexWrap:'wrap',marginTop:8}}>{items.map(([l,c])=><span key={l} style={{display:'flex',alignItems:'center',gap:5,fontSize:11,color:DK.text2}}><span style={{width:10,height:10,borderRadius:2,background:c}}></span>{l}</span>)}</div>;}
function DkPanel({title,children,style}){
  return <div style={{background:DK.panel2,border:'1px solid '+DK.border,borderRadius:10,padding:'1.3rem 1.2rem',minWidth:0,...style}}>
    {title&&<div style={{fontSize:15,fontWeight:800,color:DK.teal,marginBottom:16,display:'flex',alignItems:'center',gap:10}}><span style={{width:3,height:16,background:`linear-gradient(to bottom,${DK.teal},${DK.gold})`,borderRadius:2,flexShrink:0}}></span>{title}</div>}
    {children}</div>;
}
function DkKcard({icon,v,l,accent,top}){const {Icon}=window.DS;
  return <div style={{background:DK.panel2,border:'1px solid '+DK.border,borderTop:top?'3px solid '+top:undefined,borderRadius:10,padding:'1.2rem .9rem',textAlign:'center',position:'relative',overflow:'hidden'}}>
    {!top&&<div style={{position:'absolute',top:0,left:0,right:0,height:2,background:`linear-gradient(90deg,${DK.teal},${DK.gold})`}}></div>}
    {icon&&<div style={{marginBottom:8,display:'flex',justifyContent:'center'}}><Icon name={icon} size={24} color={accent||DK.text2}/></div>}
    <div style={{fontSize:30,fontWeight:900,color:accent||DK.text,lineHeight:1}}>{v}</div>
    <div style={{fontSize:12,color:DK.text3,marginTop:6}}>{l}</div></div>;
}
function DkBar({label,value,c}){
  return <div style={{marginBottom:14}}><div style={{display:'flex',justifyContent:'space-between',fontSize:13,marginBottom:6}}><span style={{color:DK.text2}}>{label}</span><b style={{color:c}}>{value}%</b></div>
    <div style={{height:7,borderRadius:7,background:'rgba(255,255,255,0.1)',overflow:'hidden'}}><div style={{height:'100%',width:value+'%',background:c,borderRadius:7,transition:'width 1s'}}></div></div></div>;
}
function DkSat({q,d}){const L=window.TRACK_DASH.SAT_SCALE;const C=[DK.teal,DK.blue,DK.gold,DK.coral,'#8B0000'];
  return <div style={{background:DK.panel2,border:'1px solid '+DK.border,borderRadius:10,padding:'1.2rem'}}>
    <div style={{fontSize:14,fontWeight:700,color:DK.text2,textAlign:'center',marginBottom:14,paddingBottom:11,borderBottom:'1px solid '+DK.border}}>{q}</div>
    {d.map((v,i)=><div key={i} style={{display:'flex',alignItems:'center',gap:10,marginBottom:8}}><div style={{fontSize:12,color:DK.text3,width:96,flexShrink:0}}>{L[i]}</div>
      <div style={{flex:1,height:6,background:'rgba(255,255,255,0.08)',borderRadius:5,overflow:'hidden'}}><div style={{height:'100%',width:v+'%',background:C[i],borderRadius:5}}></div></div><div style={{fontSize:12,color:DK.text3,width:34}}>{v}%</div></div>)}</div>;
}
function DkTable({cols,rows,hi}){
  return <div style={{overflowX:'auto'}}><table style={{width:'100%',borderCollapse:'collapse',fontSize:13}}>
    <thead><tr style={{background:'rgba(59,184,195,0.1)',color:DK.teal}}>{cols.map(c=><th key={c.k} style={{padding:'11px 16px',textAlign:c.num?'center':'start',borderBottom:'1px solid '+DK.border,fontWeight:700,whiteSpace:'nowrap'}}>{c.l}</th>)}</tr></thead>
    <tbody>{rows.map((r,i)=><tr key={i} style={{borderBottom:'1px solid rgba(255,255,255,0.06)',background:hi===i?'rgba(255,255,255,0.05)':'transparent'}}>{cols.map(c=><td key={c.k} style={{padding:'10px 16px',textAlign:c.num?'center':'start',color:c.num?DK.text2:DK.text}}>{c.r?c.r(r,i):r[c.k]}</td>)}</tr>)}</tbody></table></div>;
}
function DkMap({d,br,onPick,en}){
  const ref=React.useRef(null);const [tip,setTip]=React.useState(null);
  React.useEffect(()=>{if(!ref.current)return;ref.current.querySelectorAll('[data-br]').forEach(g=>{g.style.opacity=br!=='all'&&g.getAttribute('data-br')!==br?'.3':'1';g.style.transition='opacity .3s';});},[br]);
  const names={majmaah:en?'Majmaah – Main campus':'المجمعة – المقر الرئيسي',zulfi:en?'Zulfi – Branch':'الزلفي – فرع',rumah:en?'Rumah – Branch':'رماح – فرع'};
  const pos={majmaah:{top:'55%',right:'45%'},zulfi:{top:'36%',right:'58%'},rumah:{top:'65%',right:'28%'}};
  const click=e=>{const g=e.target.closest&&e.target.closest('[data-br]');if(!g)return;const k=g.getAttribute('data-br');setTip(k);clearTimeout(ref.current._t);ref.current._t=setTimeout(()=>setTip(null),3200);onPick&&onPick(k);};
  const b=tip&&d.branches[tip];
  return <div style={{width:'100%'}}><div style={{position:'relative',borderRadius:8,overflow:'hidden',background:'rgba(240,245,255,0.08)'}}>
    <div ref={ref} onClick={click} dangerouslySetInnerHTML={{__html:window.TRACK_DASH_MAP}}></div>
    {b&&<div style={{position:'absolute',...pos[tip],background:'rgba(20,20,20,0.95)',border:'1px solid '+DK.teal,borderRadius:8,padding:'.8rem 1rem',minWidth:170,pointerEvents:'none',zIndex:5,textAlign:'start'}}>
      <div style={{color:DK.teal,fontSize:14,fontWeight:700,marginBottom:8}}>{names[tip]}</div>
      {[[en?'Certificates':'الشهادات',b.v],[en?'Male':'طلاب',b.m],[en?'Female':'طالبات',b.f]].map(([l,v])=><div key={l} style={{display:'flex',justifyContent:'space-between',fontSize:12,marginBottom:4,color:DK.text2}}><span>{l}</span><b style={{color:DK.teal}}>{v}</b></div>)}</div>}
  </div>
  <div style={{display:'flex',gap:18,marginTop:12,flexWrap:'wrap',justifyContent:'center'}}>{Object.keys(names).map(k=><span key={k} style={{display:'flex',alignItems:'center',gap:6,fontSize:12,color:DK.text3}}><span style={{width:10,height:10,borderRadius:'50%',background:DK_BR[k]}}></span>{names[k]}</span>)}
    <span style={{fontSize:12,color:DK.text3}}>{en?'Click a branch to filter':'انقر على الفرع للتصفية'}</span></div></div>;
}
Object.assign(window,{DK,DK_BR,DK_CYC,dkTone,DkCell,DkKpi,DkDonut,DkGauge,DkShutters,DkProg3D,DkLine,DkGroup,DkLegend,DkPanel,DkKcard,DkBar,DkSat,DkTable,DkMap});
