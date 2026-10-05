import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function EmptyState({icon='inbox',title,message,action,tone='brand',style}){
  const c=tone==='danger'?'var(--red-600)':'var(--track-blue)';
  const bg=tone==='danger'?'var(--red-50)':'var(--blue-50)';
  return <div style={{display:'flex',flexDirection:'column',alignItems:'center',textAlign:'center',gap:10,padding:'40px 20px',...style}}>
    <span style={{width:56,height:56,borderRadius:16,background:bg,display:'flex',alignItems:'center',justifyContent:'center',marginBottom:4}}><Icon name={icon} size={26} color={c}/></span>
    {title&&<div style={{font:'700 16px/1.5 var(--font-sans)',color:'var(--text-primary)'}}>{title}</div>}
    {message&&<div style={{font:'400 13px/1.7 var(--font-sans)',color:'var(--text-secondary)',maxWidth:420,textWrap:'pretty'}}>{message}</div>}
    {action&&<div style={{marginTop:6,display:'flex',gap:8}}>{action}</div>}
  </div>;
}
