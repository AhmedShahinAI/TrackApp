import React from 'react';
export function Switch({label,checked,defaultChecked,onChange,disabled}){
  const [c,setC]=React.useState(!!defaultChecked);const on=checked!==undefined?checked:c;
  return <label style={{display:'inline-flex',alignItems:'center',gap:10,cursor:'pointer',opacity:disabled?.5:1,font:'500 13px var(--font-sans)',color:'var(--text-primary)'}}>
    <span onClick={()=>{if(disabled)return;setC(!on);onChange&&onChange(!on)}} style={{position:'relative',width:38,height:22,borderRadius:99,background:on?'var(--track-green)':'var(--gray-300)',transition:'background var(--dur-base)'}}>
      <span style={{position:'absolute',top:2,insetInlineStart:on?18:2,width:18,height:18,borderRadius:99,background:'#fff',boxShadow:'0 1px 2px rgba(0,0,0,.2)',transition:'inset-inline-start var(--dur-base) var(--ease-standard)'}}/>
    </span>{label}
  </label>;
}