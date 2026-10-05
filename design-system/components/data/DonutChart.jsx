import React from 'react';
export function DonutChart({segments=[],total,caption,size=130,thickness=16}){
  const r=(size-thickness)/2,C=2*Math.PI*r;let off=0;
  const sum=segments.reduce((a,s)=>a+s.value,0)||1;
  return <div style={{display:'flex',alignItems:'center',gap:20}}>
    <div style={{position:'relative',width:size,height:size,flex:'none'}}>
      <svg width={size} height={size} style={{transform:'rotate(-90deg)'}}>
        <circle cx={size/2} cy={size/2} r={r} fill="none" stroke="var(--gray-100)" strokeWidth={thickness}/>
        {segments.map((s,i)=>{const len=C*s.value/sum;const el=<circle key={i} cx={size/2} cy={size/2} r={r} fill="none" stroke={s.color} strokeWidth={thickness} strokeDasharray={len+' '+(C-len)} strokeDashoffset={-off}/>;off+=len;return el;})}
      </svg>
      <div style={{position:'absolute',inset:0,display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center'}}>
        <div style={{font:'700 22px var(--font-numeric)',color:'var(--track-dark)'}}>{total}</div>
        {caption&&<div style={{font:'500 11px var(--font-sans)',color:'var(--text-secondary)'}}>{caption}</div>}
      </div>
    </div>
    <div style={{display:'flex',flexDirection:'column',gap:12}}>{segments.map(s=><div key={s.label} style={{display:'flex',gap:8,alignItems:'flex-start'}}>
      <span style={{width:10,height:10,borderRadius:99,background:s.color,marginTop:5}}/>
      <div><div style={{font:'600 13px var(--font-sans)'}}>{s.label}</div><div style={{font:'500 12px var(--font-numeric)',color:'var(--text-secondary)'}}>{Math.round(s.value/sum*100)}%</div></div>
    </div>)}</div>
  </div>;
}