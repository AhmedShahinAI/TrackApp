import React from 'react';
import {Icon} from '../core/Icon.jsx';
const K={success:['circle-check','var(--track-green)'],error:['circle-alert','var(--red-500)'],info:['info','var(--blue-500)']};
export function Toast({tone='success',title,message,onClose}){
  const [i,c]=K[tone]||K.success;
  return <div role="status" style={{display:'flex',gap:12,alignItems:'flex-start',width:340,padding:'14px 16px',background:'#fff',borderRadius:'var(--radius-lg)',boxShadow:'var(--shadow-overlay)',border:'1px solid var(--border-default)'}}>
    <Icon name={i} size={20} color={c}/>
    <div style={{flex:1}}><div style={{font:'700 14px var(--font-sans)',color:'var(--text-primary)'}}>{title}</div>{message&&<div style={{font:'400 13px/1.5 var(--font-sans)',color:'var(--text-secondary)',marginTop:2}}>{message}</div>}</div>
    {onClose&&<button onClick={onClose} aria-label="إغلاق" style={{border:'none',background:'none',cursor:'pointer',color:'var(--text-muted)',padding:0}}><Icon name="x" size={16}/></button>}
  </div>;
}