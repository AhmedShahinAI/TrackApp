import React from 'react';
export function LineChart({data=[],height=150,max=100,color='var(--track-green)'}){
  const w=100,n=data.length;
  const pts=data.map((d,i)=>[n>1?(i/(n-1))*w:w/2,height-(d.value/max)*height]);
  const line=pts.map(p=>p.join(',')).join(' ');
  const area='0,'+height+' '+line+' '+w+','+height;
  return <div dir="ltr">
    <svg viewBox={'0 -6 '+w+' '+(height+12)} preserveAspectRatio="none" style={{width:'100%',height:height+12,display:'block',overflow:'visible'}}>
      {[0,.25,.5,.75,1].map(g=><line key={g} x1="0" x2={w} y1={height*g} y2={height*g} stroke="var(--gray-100)" strokeWidth="1" vectorEffect="non-scaling-stroke"/>)}
      <polygon points={area} fill={color} opacity=".08"/>
      <polyline points={line} fill="none" stroke={color} strokeWidth="2.5" vectorEffect="non-scaling-stroke" strokeLinejoin="round"/>
    </svg>
    <div style={{position:'relative',height:0}}>{pts.map((p,i)=><span key={i} style={{position:'absolute',left:p[0]+'%',top:-(height+12)+p[1]+6,width:9,height:9,marginLeft:-4.5,marginTop:-4.5,borderRadius:99,background:'#fff',border:'2px solid '+color}}/>)}</div>
    <div style={{display:'flex',justifyContent:'space-between',marginTop:8}}>{data.map(d=><span key={d.label} style={{font:'500 10px/1.3 var(--font-sans)',color:'var(--text-secondary)',textAlign:'center',flex:1}}>{d.label}</span>)}</div>
  </div>;
}