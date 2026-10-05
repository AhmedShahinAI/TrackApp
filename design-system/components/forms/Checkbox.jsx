import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function Checkbox({label,checked,defaultChecked,onChange,disabled}){
  const [c,setC]=React.useState(!!defaultChecked);const on=checked!==undefined?checked:c;
  return <label style={{display:'inline-flex',alignItems:'center',gap:8,cursor:disabled?'not-allowed':'pointer',opacity:disabled?.5:1,font:'500 13px var(--font-sans)',color:'var(--text-primary)'}}>
    <span onClick={()=>{if(disabled)return;setC(!on);onChange&&onChange(!on)}} style={{width:18,height:18,borderRadius:'var(--radius-xs)',border:'1.5px solid '+(on?'var(--track-blue)':'var(--border-strong)'),background:on?'var(--track-blue)':'#fff',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all var(--dur-fast)'}}>
      {on&&<Icon name="check" size={13} strokeWidth={3} color="#fff"/>}
    </span>{label}
  </label>;
}