const TK_CATS={login:['الدخول والحساب','Sign-in & account'],progress:['مراحل المبادرة','Initiative stages'],enroll:['الشعب والتسجيل','Sections & enrollment'],other:['أخرى','Other']};
const TK_ST={open:['pending','مفتوح','Open'],in_progress:['progress','قيد المعالجة','In progress'],resolved:['completed','تم الحل','Resolved']};
function TicketBadge({s}){const {StatusBadge}=window.DS;const {lang}=useApp();const x=TK_ST[s];return <StatusBadge status={x[0]}>{lang==='en'?x[2]:x[1]}</StatusBadge>;}
const tkArea={minHeight:110,padding:'10px 12px',border:'1px solid var(--border-default)',borderRadius:'var(--radius-md)',font:'400 14px/1.7 var(--font-sans)',resize:'vertical',width:'100%',color:'var(--text-primary)'};

function NewTicketDialog({onClose,notify}){
  const {Dialog,Button,Input,Select}=window.DS;const {me,lang,t}=useApp();const st=useStore();const en=lang==='en';
  const secs=st.studentEnrollments(me.id).map(e=>st.S.sections.find(s=>s.id===e.section)).filter(Boolean);
  const [f,setF]=React.useState({cat:'progress',section:secs[0]?String(secs[0].id):'',subject:'',body:''});const [e,setE]=React.useState({});const [busy,setBusy]=React.useState(false);
  const save=()=>{const n={subject:f.subject.trim()?null:t('required'),body:f.body.trim().length<10?(en?'Describe the issue (10+ characters).':'اكتب وصفًا للمشكلة (10 أحرف على الأقل).'):null};setE(n);if(n.subject||n.body)return;setBusy(true);
    setTimeout(()=>{const id=Math.max(0,...st.S.tickets.map(x=>x.id))+1;const tk={id,no:'TK-'+(1000+id),student:me.id,section:f.section?+f.section:null,cat:f.cat,subject:f.subject.trim(),body:f.body.trim(),status:'open',at:st.now().slice(0,19),replies:[]};
      st.S.tickets.unshift(tk);st.audit(null,'support.created','ticket',id,null,{no:tk.no,student:me.uid,subject:tk.subject});st.emit();setBusy(false);notify(en?'Request submitted':'تم إرسال الطلب',tk.no);onClose();},500);};
  return <Dialog title={en?'New support request':'طلب دعم فني جديد'} onClose={onClose} width={560}
    footer={<><Button variant="ghost" onClick={onClose}>{t('cancel')}</Button><Button icon="send" loading={busy} onClick={save}>{en?'Submit':'إرسال الطلب'}</Button></>}>
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:14}}>
      <Select label={en?'Category':'نوع المشكلة'} value={f.cat} onChange={x=>setF({...f,cat:x.target.value})} options={Object.entries(TK_CATS).map(([k,v])=>({value:k,label:en?v[1]:v[0]}))}/>
      <Select label={en?'Related section':'الشعبة المرتبطة'} value={f.section} onChange={x=>setF({...f,section:x.target.value})} options={[{value:'',label:en?'Not section-specific':'غير مرتبط بشعبة'},...secs.map(s=>({value:String(s.id),label:(en?st.course(s.course).en:st.course(s.course).ar)+' · '+s.no}))]}/>
      <Input label={en?'Subject':'العنوان'} required value={f.subject} error={e.subject} onChange={x=>setF({...f,subject:x.target.value})} style={{gridColumn:'1 / -1'}}/>
      <div style={{gridColumn:'1 / -1'}}><Field label={en?'Description':'وصف المشكلة'} required error={e.body}><textarea value={f.body} onChange={x=>setF({...f,body:x.target.value})} style={tkArea}></textarea></Field></div>
    </div>
    <p style={{margin:'12px 0 0',font:'400 12px/1.6 var(--font-sans)',color:'var(--text-secondary)'}}>{en?'Your request is visible to the section instructor, the branch coordinator and the system administrator.':'يظهر طلبك لمدرس الشعبة ومنسق الفرع ومسؤول النظام.'}</p>
  </Dialog>;
}

function TicketDialog({tk,onClose,notify}){
  const {Dialog,Button,Icon}=window.DS;const {me,lang,t}=useApp();const st=useStore();const en=lang==='en';const isSt=me.role==='STUDENT';
  const [msg,setMsg]=React.useState('');const stu=st.student(tk.student);const sec=tk.section&&st.S.sections.find(s=>s.id===tk.section);
  const who=r=>r.kind==='student'?[stu?stu.ar:'—',en?'Student':'طالب']:[st.uname(r.by,lang),t('role_'+(st.user(r.by)||{}).role)];
  const setStatus=s=>{const old=tk.status;tk.status=s;st.audit(isSt?null:me.id,'support.status_changed','ticket',tk.id,{status:old},{status:s});st.emit();};
  const reply=()=>{if(!msg.trim())return;tk.replies.push({kind:isSt?'student':'user',by:me.id,body:msg.trim(),at:st.now().slice(0,19)});st.audit(isSt?null:me.id,'support.replied','ticket',tk.id,null,{no:tk.no});
    if(!isSt&&tk.status==='open')tk.status='in_progress';if(isSt&&tk.status==='resolved')tk.status='open';st.emit();setMsg('');notify(en?'Reply sent':'تم إرسال الرد',tk.no);};
  const bubble=(name,role,body,at,mine)=><div style={{alignSelf:mine?'flex-start':'flex-end',maxWidth:'85%',background:mine?'var(--blue-50)':'var(--gray-50)',border:'1px solid var(--border-default)',borderRadius:12,padding:'10px 14px'}}>
    <div style={{display:'flex',gap:8,alignItems:'baseline',flexWrap:'wrap',marginBottom:4}}><b style={{font:'700 13px var(--font-sans)'}}>{name}</b><span style={{font:'400 11px var(--font-sans)',color:'var(--text-secondary)'}}>{role} · {fmtDateTime(at)}</span></div>
    <div style={{font:'400 14px/1.7 var(--font-sans)',whiteSpace:'pre-wrap'}}>{body}</div></div>;
  return <Dialog title={tk.no+' · '+tk.subject} onClose={onClose} width={720}
    footer={<>{!isSt&&tk.status!=='resolved'&&<Button variant="secondary" icon="circle-check" onClick={()=>setStatus('resolved')}>{en?'Mark resolved':'تحديد كمحلول'}</Button>}
      {!isSt&&tk.status==='resolved'&&<Button variant="secondary" icon="rotate-ccw" onClick={()=>setStatus('open')}>{en?'Reopen':'إعادة فتح'}</Button>}
      <Button variant="ghost" onClick={onClose}>{t('close')}</Button></>}>
    <div style={{display:'flex',flexWrap:'wrap',gap:'8px 20px',alignItems:'center',font:'400 13px var(--font-sans)',color:'var(--text-secondary)',paddingBottom:14,borderBottom:'1px solid var(--border-default)'}}>
      <TicketBadge s={tk.status}/>
      <span><Icon name="tag" size={14}/> {en?TK_CATS[tk.cat][1]:TK_CATS[tk.cat][0]}</span>
      {stu&&<span><Icon name="user" size={14}/> {stu.ar} · <span style={{fontFamily:'var(--font-latin)'}}>{stu.uid}</span></span>}
      {sec&&<span><Icon name="users" size={14}/> {(en?st.course(sec.course).en:st.course(sec.course).ar)+' · '+sec.no+' · '+st.branchLabel(sec.branch,lang)}</span>}
    </div>
    <div style={{display:'flex',flexDirection:'column',gap:10,padding:'16px 0',maxHeight:340,overflow:'auto'}}>
      {bubble(stu?stu.ar:'—',en?'Student':'طالب',tk.body,tk.at,isSt)}
      {tk.replies.map((r,i)=>{const [n,ro]=who(r);return <React.Fragment key={i}>{bubble(n,ro,r.body,r.at,(r.kind==='student')===isSt)}</React.Fragment>;})}
    </div>
    <div style={{display:'flex',flexDirection:'column',gap:10}}>
      <textarea value={msg} onChange={e=>setMsg(e.target.value)} placeholder={en?'Write a reply…':'اكتب ردًا…'} style={{...tkArea,minHeight:80}}></textarea>
      <div><Button icon="send" disabled={!msg.trim()} onClick={reply}>{en?'Send reply':'إرسال الرد'}</Button></div>
    </div>
  </Dialog>;
}

function SupportScreen({notify}){
  const {Button,DataTable,Tabs,Input,EmptyState}=window.DS;const {me,lang,t,route}=useApp();const st=useStore();const en=lang==='en';const isSt=me.role==='STUDENT';
  const [tab,setTab]=React.useState(isSt?'all':'open');const [q,setQ]=React.useState('');const [open,setOpen]=React.useState(null);const [nw,setNw]=React.useState(!!(route.param&&route.param.new));
  const all=st.ticketScope(me);
  const rows=all.filter(x=>(tab==='all'||x.status===tab)&&(!q||(x.no+x.subject+((st.student(x.student)||{}).ar||'')+((st.student(x.student)||{}).uid||'')).toLowerCase().includes(q.toLowerCase())));
  const secLbl=x=>{const s=x.section&&st.S.sections.find(y=>y.id===x.section);return s?(en?st.course(s.course).en:st.course(s.course).ar)+' · '+s.no:'—';};
  const cols=[{key:'no',label:en?'No.':'الرقم',numeric:true,render:r=><b>{r.no}</b>},
    ...(isSt?[]:[{key:'s',label:en?'Student':'الطالب',render:r=>{const s=st.student(r.student);return s?<div><div style={{fontWeight:700}}>{s.ar}</div><div style={{font:'400 12px var(--font-latin)',color:'var(--text-secondary)'}}>{s.uid}</div></div>:'—';}}]),
    {key:'sub',label:en?'Subject':'العنوان',wrap:true,render:r=><span style={{fontWeight:600}}>{r.subject}</span>},
    {key:'cat',label:en?'Category':'النوع',render:r=>en?TK_CATS[r.cat][1]:TK_CATS[r.cat][0]},
    {key:'sec',label:en?'Section':'الشعبة',render:secLbl},
    ...(isSt?[]:[{key:'b',label:en?'Branch':'الفرع',render:r=>{const s=r.section&&st.S.sections.find(y=>y.id===r.section);return s?st.branchLabel(s.branch,lang):'—';}}]),
    {key:'at',label:en?'Submitted':'تاريخ الطلب',numeric:true,render:r=>fmtDateTime(r.at)},
    {key:'r',label:en?'Replies':'الردود',numeric:true,render:r=>r.replies.length},
    {key:'st',label:t('status'),render:r=><TicketBadge s={r.status}/>}];
  const cnt=s=>all.filter(x=>x.status===s).length;
  return <div>
    <PageHeader title={t('nav_support')} subtitle={isSt?(en?'Track your requests and replies from the college team.':'تابع طلباتك وردود فريق الكلية.'):(en?'Requests from students in your scope. Replying moves a request to “In progress”.':'طلبات الطلاب ضمن نطاق صلاحيتك. الرد على الطلب ينقله إلى «قيد المعالجة».')}
      actions={isSt&&<Button icon="plus" onClick={()=>setNw(true)}>{en?'New request':'طلب دعم جديد'}</Button>}/>
    <Panel>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:16,padding:'8px 20px 0',flexWrap:'wrap'}}>
        <Tabs value={tab} onChange={setTab} tabs={[...['open','in_progress','resolved'].map(s=>({id:s,label:en?TK_ST[s][2]:TK_ST[s][1],count:cnt(s)})),{id:'all',label:t('all'),count:all.length}]}/>
        {!isSt&&<Input size="sm" icon="search" placeholder={en?'No., student, ID…':'رقم الطلب، الطالب، الرقم الجامعي…'} value={q} onChange={e=>setQ(e.target.value)} style={{width:260}}/>}
      </div>
      <div style={{padding:20}}>{rows.length?<DataTable columns={cols} rows={rows} onRowClick={r=>setOpen(r)}/>:<EmptyState icon="life-buoy" title={en?'No requests':'لا توجد طلبات'} message={isSt?(en?'Submit a request if something is wrong with your initiatives or sections.':'سجّل طلبًا إذا واجهت مشكلة في مبادراتك أو شعبك.'):(en?'Nothing in this tab.':'لا توجد طلبات في هذا التبويب.')}/>}</div>
    </Panel>
    {open&&<TicketDialog tk={open} onClose={()=>setOpen(null)} notify={notify}/>}
    {nw&&<NewTicketDialog onClose={()=>setNw(false)} notify={notify}/>}
  </div>;
}
function TicketList({rows,notify,showSection=true}){
  const {DataTable,EmptyState}=window.DS;const {lang,t}=useApp();const st=useStore();const en=lang==='en';const [open,setOpen]=React.useState(null);
  if(!rows.length)return <EmptyState icon="life-buoy" title={en?'No support requests':'لا توجد طلبات دعم'} message={en?'Requests submitted by students appear here.':'تظهر هنا الطلبات التي يرسلها الطلبة.'}/>;
  return <><DataTable onRowClick={r=>setOpen(r)} columns={[{key:'no',label:en?'No.':'الرقم',numeric:true,render:r=><b>{r.no}</b>},
    {key:'s',label:en?'Student':'الطالب',render:r=>{const s=st.student(r.student);return s?<div><div style={{fontWeight:700}}>{s.ar}</div><div style={{font:'400 12px var(--font-latin)',color:'var(--text-secondary)'}}>{s.uid}</div></div>:'—';}},
    {key:'sub',label:en?'Subject':'العنوان',wrap:true,render:r=><span style={{fontWeight:600}}>{r.subject}</span>},
    {key:'cat',label:en?'Category':'النوع',render:r=>en?TK_CATS[r.cat][1]:TK_CATS[r.cat][0]},
    ...(showSection?[{key:'sec',label:en?'Section':'الشعبة',render:r=>{const s=r.section&&st.S.sections.find(y=>y.id===r.section);return s?st.course(s.course).code+' · '+s.no:'—';}}]:[]),
    {key:'at',label:en?'Submitted':'تاريخ الطلب',numeric:true,render:r=>fmtDateTime(r.at)},
    {key:'st',label:t('status'),render:r=><TicketBadge s={r.status}/>}]} rows={rows}/>
    {open&&<TicketDialog tk={open} onClose={()=>setOpen(null)} notify={notify}/>}</>;
}
Object.assign(window,{SupportScreen,TicketDialog,NewTicketDialog,TicketBadge,TicketList});
