(function(){
const now=()=>new Date().toISOString();
const S={
 branches:[
  {id:1,ar:'المجمعة',en:'Majmaah',gender:'male',active:true},
  {id:2,ar:'المجمعة',en:'Majmaah',gender:'female',active:true},
  {id:3,ar:'الزلفي',en:'Zulfi',gender:'male',active:true},
  {id:4,ar:'الزلفي',en:'Zulfi',gender:'female',active:true},
  {id:5,ar:'رماح',en:'Rumah',gender:'male',active:true},
  {id:6,ar:'رماح',en:'Rumah',gender:'female',active:false},
 ],
 terms:[
  {id:1,ar:'الفصل الأول 1448',en:'First Semester 1448',year:'2026/2027',semester:'first',start:'2026-08-30',end:'2027-01-07',status:'active',current:true},
  {id:2,ar:'الفصل الثاني 1447',en:'Second Semester 1447',year:'2025/2026',semester:'second',start:'2026-01-18',end:'2026-05-28',status:'closed',current:false},
  {id:3,ar:'الفصل الثاني 1448',en:'Second Semester 1448',year:'2026/2027',semester:'second',start:'2027-01-17',end:'2027-05-27',status:'draft',current:false},
 ],
 courses:[
  {id:1,code:'MATH101',ar:'مبادئ الرياضيات',en:'Principles of Mathematics'},
  {id:2,code:'ENG102',ar:'اللغة الإنجليزية 2',en:'English Language 2'},
  {id:3,code:'CS101',ar:'مقدمة في الحاسب',en:'Introduction to Computing'},
  {id:4,code:'STAT101',ar:'مبادئ الإحصاء',en:'Principles of Statistics'},
  {id:5,code:'ISLM101',ar:'الثقافة الإسلامية',en:'Islamic Culture'},
  {id:6,code:'CS212',ar:'برمجة 2',en:'Programming 2'},
 ],
 users:[
  {id:1,ar:'مها السالم',en:'Maha Al-Salem',email:'m.alsalem@mu.edu.sa',role:'SUPER_ADMIN',branches:[],active:true,firstLogin:'2026-08-20T08:10:00',lastLogin:'2026-09-26T07:55:00',pw:'Track2026'},
  {id:2,ar:'د. نورة الدوسري',en:'Dr. Noura Al-Dosari',email:'n.aldosari@mu.edu.sa',role:'BRANCH_COORDINATOR',branches:[4],active:true,firstLogin:'2026-09-01T09:00:00',lastLogin:'2026-09-25T12:30:00',pw:'Track2026'},
  {id:3,ar:'د. فيصل العتيبي',en:'Dr. Faisal Al-Otaibi',email:'f.alotaibi@mu.edu.sa',role:'INSTRUCTOR',branches:[],active:true,firstLogin:null,lastLogin:null,pw:'Track2026'},
  {id:4,ar:'د. خالد الشمري',en:'Dr. Khalid Al-Shammari',email:'k.alshammari@mu.edu.sa',role:'BRANCH_COORDINATOR',branches:[1,2],active:true,firstLogin:'2026-09-02T10:00:00',lastLogin:'2026-09-24T09:10:00',pw:'Track2026'},
  {id:5,ar:'أ. ريم العنزي',en:'Reem Al-Anazi',email:'r.alanazi@mu.edu.sa',role:'INSTRUCTOR',branches:[],active:true,firstLogin:'2026-09-03T11:00:00',lastLogin:'2026-09-22T13:00:00',pw:'Track2026'},
  {id:6,ar:'أ. سلمان الرشيدي',en:'Salman Al-Rashidi',email:'s.alrashidi@mu.edu.sa',role:'INSTRUCTOR',branches:[],active:false,firstLogin:'2026-01-20T08:00:00',lastLogin:'2026-05-10T08:00:00',pw:'Track2026'},
  {id:7,ar:'د. هند المطيري',en:'Dr. Hind Al-Mutairi',email:'h.almutairi@mu.edu.sa',role:'INSTRUCTOR',branches:[],active:true,firstLogin:'2026-09-01T08:00:00',lastLogin:'2026-09-26T06:40:00',pw:'Track2026'},
 ],
 sections:[
  {id:11,term:1,branch:4,course:2,no:'214',instructor:3},
  {id:12,term:1,branch:4,course:1,no:'215',instructor:3},
  {id:13,term:1,branch:1,course:1,no:'123',instructor:5},
  {id:14,term:1,branch:4,course:1,no:'123',instructor:7},
  {id:15,term:1,branch:3,course:3,no:'305',instructor:7},
  {id:16,term:1,branch:2,course:4,no:'142',instructor:5},
  {id:17,term:1,branch:5,course:5,no:'157',instructor:null},
  {id:18,term:2,branch:4,course:2,no:'214',instructor:6},
  {id:19,term:1,branch:1,course:6,no:'2298',instructor:3,degree:'الدبلوم المتوسط',activity:'محاضرة'},
 ],
 programs:[
  {id:1,code:'HR',ar:'الموارد البشرية',en:'Human Resources',unit:'admin',active:true},
  {id:2,code:'ACC',ar:'المحاسبة والضرائب',en:'Accounting & Tax',unit:'admin',active:true},
  {id:3,code:'LOG',ar:'إدارة اللوجستيات',en:'Logistics Management',unit:'admin',active:true},
  {id:4,code:'MKT',ar:'التسويق',en:'Marketing',unit:'admin',active:true},
  {id:5,code:'CS',ar:'البرمجة وعلوم الحاسب',en:'Programming & Computer Science',unit:'tech',active:true},
  {id:6,code:'APP',ar:'تطوير التطبيقات',en:'Application Development',unit:'tech',active:true},
  {id:7,code:'GD',ar:'التصميم الجرافيكي',en:'Graphic Design',unit:'tech',active:true},
  {id:8,code:'RE',ar:'إدارة وتطوير العقار',en:'Real Estate Management',unit:'admin',active:true},
  {id:9,code:'EC',ar:'التجارة الإلكترونية',en:'E-Commerce',unit:'tech',active:true},
 ],
 grants:[{id:1,section:15,user:3,by:1,at:'2026-09-10T10:00:00'}],
 history:[
  {section:11,instructor:6,from:'2026-08-30',to:'2026-09-08',by:1},
  {section:11,instructor:3,from:'2026-09-08',to:null,by:1},
 ],
 students:[],enrollments:[],audit:[],imports:[],
};
const fn=['عبدالرحمن','لمى','يوسف','جود','فيصل','رهف','سعود','غلا','نايف','دانة','بندر','شهد','ريان','أروى','مشاري','هيا','تركي','وعد'];
const ln=['العتيبي','القحطاني','الحربي','الدوسري','الشمري','المطيري','الغامدي','الزهراني','العنزي','السبيعي','الرشيدي','الشهري'];
const fe=['Abdulrahman','Lama','Yousef','Jood','Faisal','Rahaf','Saud','Ghala','Nayef','Dana','Bandar','Shahad','Rayan','Arwa','Mishari','Haya','Turki','Waad'];
const le=['Al-Otaibi','Al-Qahtani','Al-Harbi','Al-Dosari','Al-Shammari','Al-Mutairi','Al-Ghamdi','Al-Zahrani','Al-Anazi','Al-Subaie','Al-Rashidi','Al-Shehri'];
for(let i=0;i<36;i++){S.students.push({id:100+i,uid:'44'+String(1002315+i*37).padStart(7,'0'),ar:fn[i%18]+' '+ln[(i*5)%12],en:fe[i%18]+' '+le[(i*5)%12]});}
const secIds=[11,12,13,14,15,16,17];let eid=1;
S.students.forEach((st,i)=>{const a=secIds[i%7];S.enrollments.push({id:eid++,student:st.id,section:a,status:'active',enrolled:'2026-09-01',withdrawn:null,reason:null});
 if(i%3===0){const b=secIds[(i+2)%7];S.enrollments.push({id:eid++,student:st.id,section:b,status:'active',enrolled:'2026-09-02',withdrawn:null,reason:null});}});
S.enrollments.forEach((e,i)=>{e.seq=0;e.acad=i%11===5?'منسحب':i%13===7?'معتذر':'منتظم';});
const REAL_FILE={name:'filedownloadservlet(21).xlsx',size:'9 KB',meta:{campus:'المجمعة (طلاب)',degree:'الدبلوم المتوسط',course:'برمجة 2',activity:'محاضرة',section:'2298'},headers:['تسلسل','رقم الطالب','اسم الطالب','الحالة'],rows:[["1","442104959","سعود بن محمد بن ابراهيم الشدوخي","منتظم"],["2","462103556","علي بن ابراهيم بن علي حدادي","منتظم"],["3","462109105","عبدالعزيز بن عبدالرحمن بن عبدالعزيز الجويعي","منتظم"],["4","462109230","يونس بن علي بن محمد حدادي","منتظم"],["5","462109232","فهد بن سلطان بن ماضي العتيبي","منتظم"],["6","471103890","عبدالمجيد بن محمد بن عبدالله الراشد","منتظم"],["7","471104932","عبدالعزيز بن محمد بن سالم البديري المطيري","منتظم"],["8","471105041","ثامر بن ابراهيم بن حمد اللهيب","منتظم"],["9","471105043","عبدالسلام بن فهد بن حمود السبيعي","منتظم"],["10","471105048","عبدالرحمن بن عبدالله بن ضميان السبيعي العنزي","منتظم"],["11","471105050","مازن بن بشير بن نافع العنزي","منتظم"],["12","471105056","ريان بن جلوي بن مسفر المطيري","منتظم"],["13","471105058","تركي بن سلمان بن فيصل العتيبي","منتظم"],["14","471105060","بدر بن فهد بن سليمان السبيعي العنزي","منتظم"],["15","471105061","سلمان بن بدر بن محمد الشثيلي المطيري","منتظم"],["16","471105064","عبدالعزيز بن مطلق بن حمد العيباني المطيري","منتظم"],["17","471105067","فهد بن منيور بن قريزح الضبيطي المطيري","منتظم"],["18","471105068","فارس بن مرزوق بن مقبل البديرى المطيرى","منتظم"],["19","471105069","عبدالعزيز بن حمد بن عبدالعزيز الجوف","منتظم"],["20","471105074","نواف بن عبدالله بن محمد التويجري","منتظم"],["21","471105147","سليمان بن عبدالله بن سليمان العطيوي","منتظم"],["22","472113211","زياد بن عبدالله بن احمد الزعيبي","منتظم"]]};
REAL_FILE.rows.slice(0,19).forEach((r,i)=>{const st={id:500+i,uid:r[1],ar:r[2],en:''};S.students.push(st);S.enrollments.push({id:eid++,student:st.id,section:19,status:'active',enrolled:'2026-09-01',acad:r[3],seq:+r[0]});});
S.sections.forEach(x=>{x.degree=x.degree||'البكالوريوس';x.activity=x.activity||'محاضرة';});
const SEC_PROG={11:1,12:2,13:3,14:2,15:5,16:4,17:8,18:1,19:5};
const COURSE_PROG={1:[2,3,5],2:[1,4],3:[5,6,9],4:[2,4],5:[1,2,3,4,5,6,7,8,9],6:[5,6]};S.courses.forEach(c=>{c.programs=c.programs||COURSE_PROG[c.id]||[];});S.sections.forEach(x=>{x.program=x.program||SEC_PROG[x.id]||1;});
const TPL={1:['تسجيل الطالب','التوثيق','الانتهاء'],2:['التسجيل في المنصة','اجتياز الاختبار','رفع الشهادة'],3:['حضور الورشة','الانتهاء']};
S.assignments=[];let asid=1;S.sections.filter(x=>x.term===1).forEach(x=>{S.assignments.push({id:asid++,section:x.id,ini:1,stages:TPL[1].slice()});if(x.id%2===1&&x.id!==19)S.assignments.push({id:asid++,section:x.id,ini:2,stages:TPL[2].slice()});});
S.marks={};
S.enrollments.find(e=>e.section===11).status='withdrawn';Object.assign(S.enrollments.find(e=>e.section===11),{withdrawn:'2026-09-15',reason:'انسحاب من المقرر عبر عمادة القبول'});
const seedAudit=[
 ['2026-09-26T07:55:12',1,'auth.signed_in','user',1,null,null],
 ['2026-09-25T14:02:40',2,'access.denied','section',13,null,{reason:'out_of_scope'}],
 ['2026-09-25T11:20:03',1,'import.completed','import_job',1,null,{file:'roster_1448_first.xlsx',processed:4210,imported:4102,duplicates:64,errors:44}],
 ['2026-09-15T09:31:00',3,'enrollment.withdrawn','enrollment',1,{status:'active'},{status:'withdrawn',reason:'انسحاب من المقرر عبر عمادة القبول'}],
 ['2026-09-10T10:00:00',1,'section.access_granted','section',15,null,{user:'د. فيصل العتيبي'}],
 ['2026-09-08T08:12:45',1,'section.instructor_changed','section',11,{instructor:'أ. سلمان الرشيدي'},{instructor:'د. فيصل العتيبي'}],
 ['2026-09-05T13:40:00',1,'user.deactivated','user',6,{is_active:true},{is_active:false}],
 ['2026-09-01T08:00:00',1,'user.created','user',3,null,{name_ar:'د. فيصل العتيبي',email:'f.alotaibi@mu.edu.sa',role:'INSTRUCTOR'}],
 ['2026-08-28T10:15:00',1,'term.current_set','term',1,{is_current:false},{is_current:true}],
 ['2026-08-27T09:00:00',1,'user.role_changed','user',2,{branches:['الزلفي — طلاب']},{branches:['الزلفي — طالبات']}],
];
S.audit=seedAudit.map((a,i)=>({id:1000-i,at:a[0],actor:a[1],action:a[2],type:a[3],eid:a[4],old:a[5],neu:a[6],ip:['10.12.4.21','10.12.8.77','10.12.4.21'][i%3],ua:['Chrome 128 / Windows','Edge 128 / Windows','Safari 17 / macOS'][i%3]}));
S.imports=[
 {id:1,mode:'central',term:1,section:null,file:'roster_1448_first.xlsx',hash:'a1f3',by:1,at:'2026-09-25T11:12:00',status:'completed',total:4210,processed:4210,imported:4102,duplicates:64,errors:44},
 {id:2,mode:'section',term:1,section:11,file:'late_registrations_214.csv',hash:'b77c',by:3,at:'2026-09-20T15:02:00',status:'completed',total:3,processed:3,imported:2,duplicates:1,errors:0},
 {id:3,mode:'central',term:1,section:null,file:'roster_update_week3.xlsx',hash:'c09e',by:1,at:'2026-09-19T10:00:00',status:'failed',total:2600,processed:1500,imported:1480,duplicates:12,errors:8},
];
let subs=[];let aid=2000;
const store={
 initiatives:[{id:1,ar:'مهارات المستقبل',en:'Future Skills',term:1,start:'2026-09-01',end:'2026-12-01',status:'active',owner:'عمادة التطوير الأكاديمي',desc:'تنمية مهارات التفكير النقدي والعمل الجماعي عبر أنشطة صفية وتوثيقها.'},{id:2,ar:'الشهادات الاحترافية',en:'Professional Certificates',term:1,start:'2026-09-10',end:'2026-11-15',status:'active',owner:'وكالة الكلية للشؤون التعليمية',desc:'تهيئة الطلبة لاجتياز شهادات مهنية معتمدة.'},{id:3,ar:'الوعي السيبراني',en:'Cyber Awareness',term:1,start:'2026-10-01',end:'2026-10-30',status:'draft',owner:'وحدة الأمن السيبراني',desc:'ورش توعوية قصيرة حول حماية البيانات.'}],
 TPL,REAL_FILE,
 iniName(a,lang){const i=store.initiatives.find(x=>x.id===(a.ini||a.id));return i?(lang==='en'?i.en:i.ar):'—';},
 marks(a,studentId){const k=a.id+'|'+studentId;if(!S.marks[k]){const h=(studentId*7+a.id*3)%10;const n=a.stages.length;const done=h<4?n:h<6?Math.max(n-1,0):h<8?Math.min(1,n):0;S.marks[k]=a.stages.map((_,i)=>i<done);}if(S.marks[k].length!==a.stages.length)S.marks[k]=a.stages.map((_,i)=>!!S.marks[k][i]);return S.marks[k];},
 setMark(a,studentId,i,on){const m=store.marks(a,studentId).slice();if(on){for(let j=0;j<=i;j++)m[j]=true;}else{for(let j=i;j<m.length;j++)m[j]=false;}S.marks[a.id+'|'+studentId]=m;return m;},
 sectionInitiatives(secId){const sec=S.sections.find(x=>x.id===secId);if(!sec||sec.term!==1)return[];return secId%2===1?[1,2]:[1];},
 progress(studentId,iniId){const i=(studentId*7+iniId*3)%10;const reg=i<8;const doc=reg&&i<6;const done=doc&&i<4;return {registered:reg,documented:doc,completed:done};},
 S,now,
 subscribe(f){subs.push(f);return()=>{subs=subs.filter(x=>x!==f)}},
 emit(){subs.forEach(f=>f())},
 audit(actor,action,type,eid,old,neu){S.audit.unshift({id:aid++,at:now().slice(0,19),actor,action,type,eid,old,neu,ip:'10.12.4.21',ua:'Chrome 128 / Windows'});},
 branchLabel(id,lang){const b=S.branches.find(x=>x.id===id);if(!b)return '—';return lang==='en'?b.en+' — '+(b.gender==='male'?'Male':'Female'):b.ar+' — '+(b.gender==='male'?'طلاب':'طالبات');},
 user(id){return S.users.find(u=>u.id===id);},
 uname(id,lang){const u=S.users.find(x=>x.id===id);return u?(lang==='en'?u.en:u.ar):'—';},
 course(id){return S.courses.find(c=>c.id===id);},
 program(id){return S.programs.find(p=>p.id===id);},
 programName(id,lang){const p=S.programs.find(x=>x.id===id);return p?(lang==='en'?p.en:p.ar):'—';},
 term(id){return S.terms.find(t=>t.id===id);},
 currentTerm(){return S.terms.find(t=>t.current);},
 student(id){return S.students.find(s=>s.id===id);},
 sectionScope(u){
  if(!u)return[];
  if(u.role==='SUPER_ADMIN')return S.sections;
  if(u.role==='BRANCH_COORDINATOR')return S.sections.filter(s=>u.branches.includes(s.branch));
  const shared=S.grants.filter(g=>g.user===u.id).map(g=>g.section);
  return S.sections.filter(s=>s.instructor===u.id||shared.includes(s.id));
 },
 isShared(u,sec){return u&&u.role==='INSTRUCTOR'&&sec.instructor!==u.id&&S.grants.some(g=>g.user===u.id&&g.section===sec.id);},
 canSee(u,sec){return store.sectionScope(u).some(s=>s.id===sec.id);},
 studentScope(u){const ids=new Set(store.sectionScope(u).map(s=>s.id));const st=new Set(S.enrollments.filter(e=>ids.has(e.section)).map(e=>e.student));return u.role==='SUPER_ADMIN'?S.students:S.students.filter(s=>st.has(s.id));},
 PERMS:{
  SUPER_ADMIN:['*'],
  BRANCH_COORDINATOR:['term.view','branch.view','course.view','section.view','student.view','import.view','audit.view'],
  INSTRUCTOR:['section.view','student.view','student.create','enrollment.create','enrollment.withdraw','import.section.run','import.view'],
 },
 can(u,p){const l=store.PERMS[u.role]||[];return l.includes('*')||l.includes(p);},
};
window.TRACK_STORE=store;
})();
