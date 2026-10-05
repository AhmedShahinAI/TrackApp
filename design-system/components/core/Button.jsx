import React from 'react';
import {Icon} from './Icon.jsx';
function ensureKeys(){if(typeof document==='undefined'||document.getElementById('track-kf'))return;const s=document.createElement('style');s.id='track-kf';s.textContent='@keyframes track-spin{to{transform:rotate(360deg)}}@keyframes track-pulse{0%,100%{opacity:1}50%{opacity:.45}}';document.head.appendChild(s);}
const V={
 primary:{bg:'var(--action-primary)',hover:'var(--action-primary-hover)',fg:'#fff',bd:'transparent'},
 accent:{bg:'var(--action-accent)',hover:'var(--action-accent-hover)',fg:'#fff',bd:'transparent'},
 secondary:{bg:'#fff',hover:'var(--gray-100)',fg:'var(--track-blue)',bd:'var(--border-strong)'},
 ghost:{bg:'transparent',hover:'var(--blue-50)',fg:'var(--track-blue)',bd:'transparent'},
 danger:{bg:'var(--red-600)',hover:'#B91C1C',fg:'#fff',bd:'transparent'},
};
const S={sm:{h:32,px:12,fs:13},md:{h:40,px:16,fs:14},lg:{h:48,px:20,fs:15}};
export function Button({variant='primary',size='md',icon,iconEnd,fullWidth,disabled,loading,children,onClick,type='button',style}){
  const [h,setH]=React.useState(false);const [p,setP]=React.useState(false);
  const v=V[variant]||V.primary,s=S[size]||S.md;const off=disabled||loading;
  if(loading)ensureKeys();
  return <button type={type} disabled={off} aria-busy={loading||undefined} onClick={onClick}
    onMouseEnter={()=>setH(true)} onMouseLeave={()=>{setH(false);setP(false)}} onMouseDown={()=>setP(true)} onMouseUp={()=>setP(false)}
    style={{display:'inline-flex',alignItems:'center',justifyContent:'center',gap:8,height:s.h,padding:'0 '+s.px+'px',width:fullWidth?'100%':undefined,
    font:'600 '+s.fs+'px/1 var(--font-sans)',color:v.fg,background:h&&!off?v.hover:v.bg,border:'1px solid '+v.bd,borderRadius:'var(--radius-md)',
    cursor:loading?'progress':disabled?'not-allowed':'pointer',opacity:disabled&&!loading?.45:1,transform:p&&!off?'scale(.98)':'none',transition:'background var(--dur-fast) var(--ease-standard),transform var(--dur-fast)',whiteSpace:'nowrap',...style}}>
    {loading?<Icon name="loader-circle" size={s.fs+4} style={{animation:'track-spin .8s linear infinite'}}/>:icon&&<Icon name={icon} size={s.fs+4}/>}
    {children}{iconEnd&&!loading&&<Icon name={iconEnd} size={s.fs+4}/>}
  </button>;
}
