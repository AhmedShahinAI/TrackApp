import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function StatCard({value,label,tone='blue',icon,trend}){
  const c=tone==='green'?'var(--track-green)':tone==='dark'?'var(--track-dark)':'var(--track-blue)';
  return <div style={{background:'#fff',borderRadius:'var(--radius-lg)',border:'1px solid var(--border-default)',boxShadow:'var(--shadow-card)',padding:'18px 16px',display:'flex',flexDirection:'column',alignItems:'center',gap:10,textAlign:'center',minWidth:0}}>
    {icon&&<span style={{width:36,height:36,borderRadius:10,background:tone==='green'?'var(--green-50)':'var(--blue-50)',display:'flex',alignItems:'center',justifyContent:'center'}}><Icon name={icon} size={18} color={c}/></span>}
    <div style={{font:'var(--text-stat)',color:c}}>{value}</div>
    <div style={{font:'500 13px var(--font-sans)',color:'var(--text-secondary)'}}>{label}</div>
    {trend&&<div style={{font:'600 11px var(--font-numeric)',color:trend.startsWith('-')?'var(--red-600)':'var(--green-700)'}}>{trend}</div>}
  </div>;
}