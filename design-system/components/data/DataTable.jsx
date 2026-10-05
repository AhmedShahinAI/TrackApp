import React from 'react';
import {Skeleton} from '../feedback/Skeleton.jsx';
export function DataTable({columns=[],rows=[],onRowClick,dense,empty,loading,stackBelow=768}){
  const [hv,setHv]=React.useState(-1);
  const ref=React.useRef(null);const [w,setW]=React.useState(typeof window!=='undefined'?window.innerWidth:9999);
  React.useEffect(()=>{const f=()=>setW(window.innerWidth);window.addEventListener('resize',f);return()=>window.removeEventListener('resize',f);},[]);
  const en=typeof document!=='undefined'&&document.documentElement.lang==='en';
  const emptyTxt=empty||(en?'No data':'لا توجد بيانات');
  const pad=dense?'10px 12px':'13px 14px';
  const stacked=w<stackBelow;
  const cell=(c,r)=>c.render?c.render(r):r[c.key];
  if(stacked) return <div ref={ref} style={{display:'flex',flexDirection:'column',gap:10}}>
    {loading?[0,1,2].map(i=><div key={i} style={{border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',padding:14,display:'flex',flexDirection:'column',gap:8}}><Skeleton width="60%"/><Skeleton width="40%"/></div>)
    :rows.length===0?<div style={{padding:24,textAlign:'center',color:'var(--text-muted)',border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)'}}>{emptyTxt}</div>
    :rows.map((r,i)=><div key={r.id||i} onClick={onRowClick?()=>onRowClick(r):undefined} tabIndex={onRowClick?0:undefined} onKeyDown={onRowClick?e=>{if(e.key==='Enter')onRowClick(r)}:undefined}
      style={{border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',padding:14,background:'#fff',cursor:onRowClick?'pointer':'default',display:'grid',gridTemplateColumns:'minmax(0,auto) minmax(0,1fr)',gap:'6px 12px',font:'400 13px var(--font-sans)'}}>
      {columns.map(c=><React.Fragment key={c.key}><span style={{color:'var(--text-secondary)',font:'600 12px var(--font-sans)'}}>{c.label}</span><span style={{fontFamily:c.numeric?'var(--font-numeric)':undefined,minWidth:0}}>{cell(c,r)}</span></React.Fragment>)}
    </div>)}
  </div>;
  return <div ref={ref} style={{overflowX:'auto',border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)'}}>
    <table style={{width:'100%',borderCollapse:'collapse',font:'400 13px var(--font-sans)',color:'var(--text-primary)'}}>
      <thead><tr style={{background:'var(--gray-50)'}}>{columns.map(c=><th key={c.key} scope="col" style={{textAlign:'start',padding:pad,font:'600 12px var(--font-sans)',color:'var(--text-secondary)',borderBottom:'1px solid var(--border-default)',whiteSpace:'nowrap',width:c.width}}>{c.label}</th>)}</tr></thead>
      <tbody>{loading?[0,1,2,3].map(i=><tr key={i}>{columns.map(c=><td key={c.key} style={{padding:pad,borderBottom:'1px solid var(--gray-100)'}}><Skeleton width={c.numeric?48:'70%'}/></td>)}</tr>)
      :rows.length===0?<tr><td colSpan={columns.length} style={{padding:28,textAlign:'center',color:'var(--text-muted)'}}>{emptyTxt}</td></tr>
      :rows.map((r,i)=><tr key={r.id||i} onClick={onRowClick?()=>onRowClick(r):undefined} tabIndex={onRowClick?0:undefined} onKeyDown={onRowClick?e=>{if(e.key==='Enter')onRowClick(r)}:undefined}
        onMouseEnter={()=>setHv(i)} onMouseLeave={()=>setHv(-1)} style={{background:hv===i?'var(--blue-50)':'#fff',cursor:onRowClick?'pointer':'default',transition:'background var(--dur-fast)'}}>
        {columns.map(c=><td key={c.key} style={{padding:pad,borderBottom:i<rows.length-1?'1px solid var(--gray-100)':'none',whiteSpace:c.wrap?'normal':'nowrap',fontFamily:c.numeric?'var(--font-numeric)':undefined}}>{cell(c,r)}</td>)}
      </tr>)}</tbody>
    </table>
  </div>;
}
