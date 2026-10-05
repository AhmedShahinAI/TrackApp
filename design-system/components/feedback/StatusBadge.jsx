import React from 'react';
const T={
 active:['active','فعّال','Active'],completed:['success','مكتمل','Completed'],progress:['progress','قيد التنفيذ','In progress'],
 late:['danger','متأخرة','Late'],pending:['warning','بانتظار الاعتماد','Pending'],notStarted:['neutral','لم تبدأ','Not started'],
 closed:['neutral','مغلق','Closed'],draft:['neutral','مسودة','Draft'],archived:['neutral','مؤرشف','Archived'],
 withdrawn:['danger','منسحب','Withdrawn'],inactive:['neutral','معطّل','Deactivated'],failed:['danger','فشل','Failed'],
 queued:['warning','في الانتظار','Queued'],running:['progress','قيد المعالجة','Processing'],warning:['warning','تنبيه','Warning'],
 error:['danger','خطأ','Error'],valid:['success','صالح','Valid'],shared:['progress','وصول مشترك','Shared access'],
 current:['active','الفصل الحالي','Current'],
};
export function StatusBadge({status='active',children,dot,lang}){
  const [tone,ar,en]=T[status]||T.active;
  const L=lang||(typeof document!=='undefined'&&document.documentElement.lang==='en'?'en':'ar');
  return <span style={{display:'inline-flex',alignItems:'center',gap:6,height:24,padding:'0 10px',borderRadius:'var(--radius-sm)',background:'var(--status-'+tone+'-bg)',color:'var(--status-'+tone+'-fg)',font:'600 12px/1 var(--font-sans)',whiteSpace:'nowrap'}}>
    {dot&&<span style={{width:6,height:6,borderRadius:99,background:'currentColor'}}/>}{children||(L==='en'?en:ar)}
  </span>;
}
