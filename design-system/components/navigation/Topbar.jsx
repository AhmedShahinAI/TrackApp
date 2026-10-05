import React from 'react';
import {Icon} from '../core/Icon.jsx';
import {IconButton} from '../core/IconButton.jsx';
export function Topbar({userName='محمد أحمد',userRole='سوبر أدمن',searchPlaceholder,onMenu,notifications=true,onUserClick,children,end}){
  const en=typeof document!=='undefined'&&document.documentElement.lang==='en';
  return <header style={{height:'var(--topbar-height)',flex:'none',display:'flex',alignItems:'center',gap:16,padding:'0 24px',background:'#fff',borderBottom:'1px solid var(--border-default)'}}>
    <IconButton icon="menu" label={en?'Menu':'القائمة'} onClick={onMenu}/>
    <div style={{display:'flex',alignItems:'center',gap:8,height:38,width:320,maxWidth:'40%',padding:'0 12px',background:'var(--gray-50)',border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)'}}>
      <Icon name="search" size={16} color="var(--text-muted)"/><input aria-label={searchPlaceholder||(en?'Search the platform':'بحث في المنصة')} placeholder={searchPlaceholder||(en?'Search the platform':'بحث في المنصة')} style={{flex:1,minWidth:0,border:'none',background:'transparent',outline:'none',font:'400 13px var(--font-sans)'}}/>
    </div>
    {children}<div style={{flex:1}}/>
    {end}
    <IconButton icon="bell" label={en?'Notifications':'الإشعارات'} dot={notifications}/>
    <IconButton icon="circle-help" label={en?'Help':'المساعدة'}/>
    <button onClick={onUserClick} style={{display:'flex',alignItems:'center',gap:10,paddingInlineStart:12,border:'none',borderInlineStart:'1px solid var(--border-default)',background:'none',cursor:onUserClick?'pointer':'default',textAlign:'start'}}>
      <span style={{width:38,height:38,borderRadius:99,background:'var(--blue-100)',color:'var(--track-blue)',display:'flex',alignItems:'center',justifyContent:'center'}}><Icon name="user" size={20}/></span>
      <span><span style={{display:'block',font:'700 13px var(--font-sans)',color:'var(--text-primary)'}}>{userName}</span><span style={{display:'block',font:'400 12px var(--font-sans)',color:'var(--text-secondary)'}}>{userRole}</span></span>
    </button>
  </header>;
}
