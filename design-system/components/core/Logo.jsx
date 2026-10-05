import React from 'react';
const F={primary:'logo-primary.png',reversed:'logo-reversed.png',mono:'logo-mono.png',lockup:'logo-lockup-full.png',mark:'logo-mark.png','mark-reversed':'logo-mark-reversed.png'};
export function Logo({variant='primary',height=32,assetBase='assets/',style}){
  return <img src={assetBase+(F[variant]||F.primary)} alt="TRACK" style={{height,width:'auto',display:'block',...style}}/>;
}