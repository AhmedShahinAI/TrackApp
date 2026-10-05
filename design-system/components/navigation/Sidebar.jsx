import React from 'react';
import {Icon} from '../core/Icon.jsx';
import {Logo} from '../core/Logo.jsx';
export function Sidebar({items=[],active,onSelect,assetBase='assets/',footer}){
  const [hv,setHv]=React.useState(null);
  return <aside style={{width:'var(--sidebar-width)',flex:'none',background:'var(--surface-sidebar)',color:'#fff',display:'flex',flexDirection:'column',padding:'20px 12px',gap:4,minHeight:'100%',boxSizing:'border-box'}}>
    <div style={{padding:'0 8px 20px'}}><Logo variant="reversed" height={32} assetBase={assetBase}/></div>
    {items.map(it=>{const on=it.id===active;return <button key={it.id} onClick={()=>onSelect&&onSelect(it.id)} onMouseEnter={()=>setHv(it.id)} onMouseLeave={()=>setHv(null)}
      style={{display:'flex',alignItems:'center',gap:12,height:42,padding:'0 14px',border:'none',borderRadius:'var(--radius-md)',cursor:'pointer',textAlign:'start',
      background:on?'var(--track-green)':hv===it.id?'rgba(255,255,255,.08)':'transparent',color:on?'#fff':'rgba(255,255,255,.82)',font:(on?'700':'500')+' 14px var(--font-sans)',transition:'background var(--dur-fast)'}}>
      <Icon name={it.icon} size={19}/><span style={{flex:1}}>{it.label}</span>
      {it.count!=null&&<span style={{font:'600 11px var(--font-numeric)',background:'rgba(255,255,255,.14)',borderRadius:99,padding:'2px 8px'}}>{it.count}</span>}
    </button>;})}
    <div style={{flex:1}}/>{footer}
  </aside>;
}