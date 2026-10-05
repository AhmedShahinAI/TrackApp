import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function Select({label,options=[],value,defaultValue,onChange,icon,size='md',error,disabled,required,style}){
  const h=size==='sm'?34:40;
  const id=React.useId?React.useId():undefined;
  return <div style={{display:'flex',flexDirection:'column',gap:6,minWidth:0,...style}}>
    {label&&<label htmlFor={id} style={{font:'600 13px var(--font-sans)',color:'var(--text-primary)'}}>{label}{required&&<span aria-hidden="true" style={{color:'var(--red-600)',marginInlineStart:3}}>*</span>}</label>}
    <span style={{position:'relative',display:'flex',alignItems:'center',height:h,background:disabled?'var(--gray-100)':'#fff',border:'1px solid '+(error?'var(--red-500)':'var(--border-default)'),borderRadius:'var(--radius-md)'}}>
      {icon&&<span style={{position:'absolute',insetInlineStart:12,pointerEvents:'none'}}><Icon name={icon} size={16} color="var(--text-muted)"/></span>}
      <select id={id} value={value} defaultValue={defaultValue} onChange={onChange} disabled={disabled} aria-invalid={!!error} style={{appearance:'none',WebkitAppearance:'none',width:'100%',height:'100%',border:'none',outline:'none',background:'transparent',font:'500 14px var(--font-sans)',color:'var(--text-primary)',paddingInlineStart:icon?36:12,paddingInlineEnd:34,cursor:disabled?'not-allowed':'pointer'}}>
        {options.map(o=>typeof o==='string'?<option key={o}>{o}</option>:<option key={o.value} value={o.value} disabled={o.disabled}>{o.label}</option>)}
      </select>
      <span style={{position:'absolute',insetInlineEnd:10,pointerEvents:'none'}}><Icon name="chevron-down" size={16} color="var(--text-secondary)"/></span>
    </span>
    {error&&<span role="alert" style={{display:'flex',gap:4,alignItems:'center',font:'400 12px var(--font-sans)',color:'var(--red-600)'}}><Icon name="circle-alert" size={13}/>{error}</span>}
  </div>;
}
