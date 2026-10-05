import React from 'react';
import {Icon} from '../core/Icon.jsx';
const K={
 info:{i:'info',fg:'var(--track-blue)',bg:'var(--blue-50)',bd:'var(--blue-100)'},
 success:{i:'circle-check',fg:'var(--green-700)',bg:'var(--green-50)',bd:'var(--green-100)'},
 warning:{i:'triangle-alert',fg:'var(--amber-600)',bg:'#FFFBEB',bd:'#FDE68A'},
 danger:{i:'circle-alert',fg:'var(--red-600)',bg:'var(--red-50)',bd:'var(--red-100)'},
};
export function Alert({tone='info',title,children,icon,action,onClose,style}){
  const k=K[tone]||K.info;
  const en=typeof document!=='undefined'&&document.documentElement.lang==='en';
  return <div role={tone==='danger'?'alert':'status'} style={{display:'flex',gap:12,alignItems:'flex-start',padding:'12px 16px',background:k.bg,border:'1px solid '+k.bd,borderRadius:'var(--radius-md)',...style}}>
    <Icon name={icon||k.i} size={18} color={k.fg} style={{marginTop:2}}/>
    <div style={{flex:1,minWidth:0}}>
      {title&&<div style={{font:'700 14px/1.5 var(--font-sans)',color:'var(--text-primary)'}}>{title}</div>}
      {children&&<div style={{font:'400 13px/1.6 var(--font-sans)',color:'var(--text-secondary)'}}>{children}</div>}
    </div>
    {action}
    {onClose&&<button onClick={onClose} aria-label={en?'Dismiss':'إغلاق'} style={{border:'none',background:'none',cursor:'pointer',color:'var(--text-secondary)',padding:2}}><Icon name="x" size={16}/></button>}
  </div>;
}
