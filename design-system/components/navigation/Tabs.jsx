import React from 'react';
export function Tabs({tabs=[],value,onChange,variant='underline'}){
  const [v,setV]=React.useState(value||(tabs[0]&&tabs[0].id));const cur=value!==undefined?value:v;
  const seg=variant==='segmented';
  return <div role="tablist" style={{display:'flex',gap:seg?4:24,borderBottom:seg?'none':'1px solid var(--border-default)',background:seg?'var(--gray-100)':'transparent',padding:seg?4:0,borderRadius:seg?'var(--radius-md)':0,width:seg?'fit-content':undefined}}>
    {tabs.map(t=>{const on=t.id===cur;return <button key={t.id} role="tab" aria-selected={on} onClick={()=>{setV(t.id);onChange&&onChange(t.id)}}
      style={seg?{height:32,padding:'0 14px',border:'none',borderRadius:6,cursor:'pointer',background:on?'#fff':'transparent',boxShadow:on?'var(--shadow-xs)':'none',color:on?'var(--track-blue)':'var(--text-secondary)',font:(on?'700':'500')+' 13px var(--font-sans)'}
      :{height:42,padding:0,border:'none',background:'none',cursor:'pointer',borderBottom:'2px solid '+(on?'var(--track-green)':'transparent'),marginBottom:-1,color:on?'var(--track-blue)':'var(--text-secondary)',font:(on?'700':'500')+' 14px var(--font-sans)'}}>
      {t.label}{t.count!=null&&<span style={{marginInlineStart:6,font:'600 11px var(--font-numeric)',color:on?'var(--track-green)':'var(--text-muted)'}}>{t.count}</span>}
    </button>;})}
  </div>;
}