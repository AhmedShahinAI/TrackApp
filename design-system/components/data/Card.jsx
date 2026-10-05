import React from 'react';
export function Card({title,action,children,padding=20,style}){
  return <section style={{background:'var(--surface-card)',borderRadius:'var(--radius-lg)',boxShadow:'var(--shadow-card)',border:'1px solid var(--border-default)',padding,minWidth:0,...style}}>
    {(title||action)&&<header style={{display:'flex',alignItems:'center',justifyContent:'space-between',gap:12,marginBottom:16}}>
      <h3 style={{margin:0,font:'700 15px var(--font-sans)',color:'var(--text-primary)'}}>{title}</h3>{action}
    </header>}
    {children}
  </section>;
}