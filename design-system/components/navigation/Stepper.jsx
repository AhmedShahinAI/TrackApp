import React from 'react';
import {Icon} from '../core/Icon.jsx';
export function Stepper({steps=[],current=0}){
  return <ol style={{display:'flex',alignItems:'flex-start',gap:0,listStyle:'none',margin:0,padding:0}}>
    {steps.map((s,i)=>{const done=i<current,on=i===current;
      return <li key={i} aria-current={on?'step':undefined} style={{flex:1,minWidth:0,display:'flex',flexDirection:'column',alignItems:'center',gap:8,position:'relative'}}>
        {i>0&&<span style={{position:'absolute',top:14,insetInlineEnd:'calc(50% + 20px)',insetInlineStart:'calc(-50% + 20px)',height:2,borderRadius:2,background:i<=current?'var(--track-green)':'var(--gray-200)'}}/>}
        <span style={{width:30,height:30,borderRadius:99,display:'flex',alignItems:'center',justifyContent:'center',font:'700 13px var(--font-numeric)',
          background:done?'var(--track-green)':on?'var(--track-blue)':'#fff',color:done||on?'#fff':'var(--text-secondary)',border:done||on?'none':'1.5px solid var(--border-strong)',boxShadow:on?'0 0 0 4px var(--blue-100)':'none'}}>
          {done?<Icon name="check" size={15} strokeWidth={3}/>:i+1}
        </span>
        <span style={{font:(on?'700':'500')+' 12px/1.4 var(--font-sans)',color:on?'var(--text-primary)':'var(--text-secondary)',textAlign:'center'}}>{s}</span>
      </li>;})}
  </ol>;
}
