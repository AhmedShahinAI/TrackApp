import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function Dialog({open=true,title,subtitle,children,footer,onClose,width=520,inline}){
  if(!open)return null;
  const box=<div role="dialog" style={{width,maxWidth:'100%',background:'#fff',borderRadius:'var(--radius-xl)',boxShadow:'var(--shadow-overlay)',overflow:'hidden'}}>
    <div style={{display:'flex',alignItems:'flex-start',gap:12,padding:'20px 24px 0'}}>
      <div style={{flex:1}}><div style={{font:'var(--text-h3)',color:'var(--text-primary)'}}>{title}</div>{subtitle&&<div style={{font:'400 13px var(--font-sans)',color:'var(--text-secondary)',marginTop:4}}>{subtitle}</div>}</div>
      {onClose&&<button onClick={onClose} aria-label="إغلاق" style={{border:'none',background:'var(--gray-100)',width:32,height:32,borderRadius:8,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',color:'var(--text-secondary)'}}><Icon name="x" size={16}/></button>}
    </div>
    <div style={{padding:'20px 24px'}}>{children}</div>
    {footer&&<div style={{display:'flex',gap:10,justifyContent:'flex-end',padding:'14px 24px',background:'var(--gray-50)',borderTop:'1px solid var(--border-default)'}}>{footer}</div>}
  </div>;
  if(inline)return box;
  return <div onClick={e=>{if(e.target===e.currentTarget&&onClose)onClose()}} style={{position:'fixed',inset:0,background:'rgba(10,47,58,.45)',backdropFilter:'blur(2px)',display:'flex',alignItems:'center',justifyContent:'center',zIndex:100,padding:20}}>{box}</div>;
}