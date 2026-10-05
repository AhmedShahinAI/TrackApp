import React from 'react';
export function ProgressBar({value=0,tone,showLabel=true,height=6}){
  const c=tone||(value>=70?'var(--track-green)':value>=40?'var(--blue-500)':'var(--red-500)');
  return <div style={{display:'flex',alignItems:'center',gap:10,minWidth:90}}>
    <div style={{flex:1,height,borderRadius:99,background:'var(--gray-100)',overflow:'hidden'}}><div style={{width:Math.max(0,Math.min(100,value))+'%',height:'100%',background:c,borderRadius:99,transition:'width var(--dur-slow) var(--ease-standard)'}}/></div>
    {showLabel&&<span style={{font:'600 12px var(--font-numeric)',color:'var(--text-primary)',minWidth:32}}>{value}%</span>}
  </div>;
}