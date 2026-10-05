import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function Breadcrumbs({items=[]}){
  const rtl=typeof document==='undefined'||document.documentElement.dir!=='ltr';
  return <nav aria-label="breadcrumb" style={{display:'flex',alignItems:'center',flexWrap:'wrap',gap:6,font:'500 13px var(--font-sans)'}}>
    {items.map((it,i)=>{const last=i===items.length-1;return <React.Fragment key={i}>
      {last||!it.onClick?<span aria-current={last?'page':undefined} style={{color:last?'var(--text-primary)':'var(--text-secondary)',fontWeight:last?700:500}}>{it.label}</span>
      :<button onClick={it.onClick} style={{border:'none',background:'none',padding:0,cursor:'pointer',font:'inherit',color:'var(--blue-500)'}}>{it.label}</button>}
      {!last&&<Icon name={rtl?'chevron-left':'chevron-right'} size={14} color="var(--text-muted)"/>}
    </React.Fragment>;})}
  </nav>;
}
