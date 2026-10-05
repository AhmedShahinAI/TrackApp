import React from 'react';
import {Icon} from './Icon.jsx';
export function IconButton({icon,label,variant='ghost',size=36,dot,onClick,style}){
  const [h,setH]=React.useState(false);
  const bg=variant==='filled'?(h?'var(--action-primary-hover)':'var(--action-primary)'):variant==='outline'?(h?'var(--gray-100)':'#fff'):(h?'var(--gray-100)':'transparent');
  return <button aria-label={label} title={label} onClick={onClick} onMouseEnter={()=>setH(true)} onMouseLeave={()=>setH(false)}
   style={{position:'relative',width:size,height:size,display:'inline-flex',alignItems:'center',justifyContent:'center',borderRadius:'var(--radius-md)',
   border:variant==='outline'?'1px solid var(--border-default)':'none',background:bg,color:variant==='filled'?'#fff':'var(--track-blue)',cursor:'pointer',transition:'background var(--dur-fast)',...style}}>
   <Icon name={icon} size={Math.round(size*0.56)}/>
   {dot&&<span style={{position:'absolute',top:6,insetInlineEnd:7,width:8,height:8,borderRadius:99,background:'var(--red-500)',border:'2px solid #fff'}}/>}
  </button>;
}