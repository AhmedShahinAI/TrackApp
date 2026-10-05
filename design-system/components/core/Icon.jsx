import React from 'react';
const pascal=(n)=>n.split('-').map(s=>s.charAt(0).toUpperCase()+s.slice(1)).join('');
export function Icon({name,size=20,strokeWidth=1.75,color='currentColor',style,...rest}){
  const lib=typeof window!=='undefined'&&window.lucide&&window.lucide.icons;
  const node=lib&&(lib[pascal(name)]||lib[name]);
  const kids=Array.isArray(node)?(Array.isArray(node[2])?node[2]:node):[];
  return React.createElement('svg',{width:size,height:size,viewBox:'0 0 24 24',fill:'none',stroke:color,strokeWidth,strokeLinecap:'round',strokeLinejoin:'round','aria-hidden':true,style:{flex:'none',display:'block',...style},...rest},
    kids.map(([tag,attrs],i)=>React.createElement(tag,{key:i,...attrs})));
}