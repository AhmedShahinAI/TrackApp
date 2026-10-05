import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function Input({label,placeholder,value,defaultValue,onChange,onBlur,icon,type='text',hint,error,disabled,required,size='md',dir,inputMode,autoFocus,name,style}){
  const [f,setF]=React.useState(false);
  const id=React.useId?React.useId():undefined;
  const h=size==='sm'?34:size==='lg'?48:40;
  const bd=error?'var(--red-500)':f?'var(--border-focus)':'var(--border-default)';
  return <div style={{display:'flex',flexDirection:'column',gap:6,minWidth:0,...style}}>
    {label&&<label htmlFor={id} style={{font:'600 13px var(--font-sans)',color:'var(--text-primary)'}}>{label}{required&&<span aria-hidden="true" style={{color:'var(--red-600)',marginInlineStart:3}}>*</span>}</label>}
    <span style={{display:'flex',alignItems:'center',gap:8,height:h,padding:'0 12px',background:disabled?'var(--gray-100)':'#fff',border:'1px solid '+bd,borderRadius:'var(--radius-md)',boxShadow:f?(error?'0 0 0 3px rgba(239,68,68,.15)':'var(--ring-focus)'):'none',transition:'box-shadow var(--dur-fast),border-color var(--dur-fast)'}}>
      {icon&&<Icon name={icon} size={18} color="var(--text-muted)"/>}
      <input id={id} name={name} type={type} dir={dir} inputMode={inputMode} autoFocus={autoFocus} placeholder={placeholder} value={value} defaultValue={defaultValue} onChange={onChange} disabled={disabled} aria-invalid={!!error} aria-required={required||undefined}
        onFocus={()=>setF(true)} onBlur={e=>{setF(false);onBlur&&onBlur(e)}}
        style={{flex:1,minWidth:0,border:'none',outline:'none',background:'transparent',font:'400 14px var(--font-sans)',color:'var(--text-primary)'}}/>
    </span>
    {(hint||error)&&<span role={error?'alert':undefined} style={{display:'flex',gap:4,alignItems:'center',font:'400 12px/1.5 var(--font-sans)',color:error?'var(--red-600)':'var(--text-secondary)'}}>{error&&<Icon name="circle-alert" size={13}/>}{error||hint}</span>}
  </div>;
}
