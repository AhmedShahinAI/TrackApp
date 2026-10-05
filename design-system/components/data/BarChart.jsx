import React from 'react';
const P=['var(--chart-1)','var(--chart-2)','var(--chart-3)','var(--chart-4)','var(--chart-5)'];
export function BarChart({data=[],height=150,max=100,suffix='%'}){
  return <div style={{display:'flex',alignItems:'flex-end',gap:14,height:height+44}}>
    {data.map((d,i)=><div key={d.label} style={{flex:1,display:'flex',flexDirection:'column',alignItems:'center',gap:6,minWidth:0}}>
      <span style={{font:'600 11px var(--font-numeric)',color:'var(--text-primary)'}}>{d.value}{suffix}</span>
      <div style={{width:'100%',maxWidth:34,height:(d.value/max)*height,background:d.color||P[i%P.length],borderRadius:'4px 4px 0 0'}}/>
      <span style={{font:'500 11px var(--font-sans)',color:'var(--text-secondary)',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',maxWidth:'100%'}}>{d.label}</span>
    </div>)}
  </div>;
}