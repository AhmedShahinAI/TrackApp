import React from 'react';
function ensureKeys(){if(typeof document==='undefined'||document.getElementById('track-kf'))return;const s=document.createElement('style');s.id='track-kf';s.textContent='@keyframes track-spin{to{transform:rotate(360deg)}}@keyframes track-pulse{0%,100%{opacity:1}50%{opacity:.45}}';document.head.appendChild(s);}
export function Skeleton({width='100%',height=14,radius=6,style}){
  ensureKeys();
  return <span aria-hidden="true" style={{display:'block',width,height,borderRadius:radius,background:'var(--gray-100)',animation:'track-pulse 1.4s ease-in-out infinite',...style}}/>;
}
