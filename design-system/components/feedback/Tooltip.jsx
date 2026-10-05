import React from 'react';
export function Tooltip({content,children}){
  const [o,setO]=React.useState(false);
  return <span style={{position:'relative',display:'inline-flex'}} onMouseEnter={()=>setO(true)} onMouseLeave={()=>setO(false)}>
    {children}
    {o&&<span role="tooltip" style={{position:'absolute',bottom:'calc(100% + 8px)',left:'50%',transform:'translateX(-50%)',background:'var(--track-dark)',color:'#fff',font:'500 12px/1.4 var(--font-sans)',padding:'6px 10px',borderRadius:'var(--radius-sm)',whiteSpace:'nowrap',zIndex:50,boxShadow:'var(--shadow-raised)'}}>{content}</span>}
  </span>;
}