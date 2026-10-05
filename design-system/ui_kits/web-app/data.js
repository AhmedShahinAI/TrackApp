window.DS = window.TRACKDesignSystem_c33017;
window.TRACK_DATA = (function(){
  const semesters = [
    {id:'2026-1', label:'2026/2027 - الأول', current:true},
    {id:'2025-2', label:'2025/2026 - الثاني'},
    {id:'2025-1', label:'2025/2026 - الأول'},
  ];
  const branches = [
    {id:'mj', name:'المجمعة', coordinator:'د. خالد العتيبي', sections:42, students:1024, completion:92},
    {id:'zl', name:'الزلفي', coordinator:'د. سارة القحطاني', sections:31, students:768, completion:78},
    {id:'hl', name:'حائل', coordinator:'د. فهد الشمري', sections:27, students:655, completion:64},
    {id:'gt', name:'الغاط', coordinator:'د. نورة الدوسري', sections:25, students:590, completion:88},
    {id:'hs', name:'حوطة سدير', coordinator:'د. عبدالله السبيعي', sections:20, students:445, completion:70},
  ];
  const initiatives = [
    {id:'i1', name:'مهارات المستقبل', term:'2026-1', start:'2026/09/01', end:'2026/12/01', status:'active', sections:48, branches:5, completion:81, owner:'عمادة التطوير الأكاديمي', desc:'تنمية مهارات التفكير النقدي والعمل الجماعي لدى طلبة السنة الأولى عبر أنشطة صفية وتقييم مرحلي.'},
    {id:'i2', name:'الشهادات الاحترافية', term:'2026-1', start:'2026/09/10', end:'2026/11/15', status:'progress', sections:32, branches:4, completion:54, owner:'وكالة الكلية للشؤون التعليمية', desc:'تهيئة الطلبة لاجتياز شهادات مهنية معتمدة مرتبطة بالمقررات التخصصية.'},
    {id:'i3', name:'الوعي السيبراني', term:'2026-1', start:'2026/09/05', end:'2026/10/20', status:'completed', sections:26, branches:5, completion:100, owner:'وحدة الأمن السيبراني', desc:'ورش توعوية قصيرة داخل الشعب حول حماية البيانات والسلوك الرقمي الآمن.'},
    {id:'i4', name:'القراءة المنهجية', term:'2026-1', start:'2026/10/01', end:'2027/01/10', status:'notStarted', sections:18, branches:3, completion:0, owner:'عمادة التطوير الأكاديمي', desc:'برنامج قراءة موجّهة لمراجع المقرر مع تقارير أسبوعية.'},
    {id:'i5', name:'التعلم بالمشاريع', term:'2025-2', start:'2026/02/01', end:'2026/05/20', status:'closed', sections:40, branches:5, completion:96, owner:'وكالة الكلية للشؤون التعليمية', desc:'مشاريع تطبيقية جماعية ضمن المقررات الأساسية.'},
    {id:'i6', name:'الإرشاد الأكاديمي', term:'2025-1', start:'2025/09/05', end:'2025/12/25', status:'closed', sections:35, branches:4, completion:88, owner:'عمادة شؤون الطلاب', desc:'متابعة فردية للطلبة المتعثرين داخل الشعب.'},
  ];
  const sections = [
    {id:'s123', no:'123', course:'ريض 101', courseName:'مبادئ الرياضيات', branch:'mj', instructor:'أ. محمد الحربي', students:38, completion:20, status:'late', initiative:'i1'},
    {id:'s214', no:'214', course:'نجل 102', courseName:'اللغة الإنجليزية 2', branch:'zl', instructor:'أ. ريم العنزي', students:34, completion:35, status:'late', initiative:'i1'},
    {id:'s305', no:'305', course:'نجل 201', courseName:'الكتابة الأكاديمية', branch:'hl', instructor:'أ. سلمان الرشيدي', students:29, completion:0, status:'notStarted', initiative:'i1'},
    {id:'s118', no:'118', course:'حاسب 101', courseName:'مقدمة في الحاسب', branch:'mj', instructor:'د. هند المطيري', students:41, completion:100, status:'completed', initiative:'i1'},
    {id:'s142', no:'142', course:'إحص 101', courseName:'مبادئ الإحصاء', branch:'gt', instructor:'أ. تركي الزهراني', students:36, completion:72, status:'active', initiative:'i1'},
    {id:'s157', no:'157', course:'سلم 101', courseName:'الثقافة الإسلامية', branch:'hs', instructor:'أ. منى الغامدي', students:40, completion:88, status:'active', initiative:'i1'},
    {id:'s231', no:'231', course:'حاسب 210', courseName:'قواعد البيانات', branch:'zl', instructor:'د. ماجد الشهري', students:27, completion:64, status:'progress', initiative:'i2'},
  ];
  const firstNames = ['عبدالرحمن','لمى','يوسف','جود','فيصل','رهف','سعود','غلا','نايف','دانة','بندر','شهد'];
  const lastNames = ['العتيبي','القحطاني','الحربي','الدوسري','الشمري','المطيري','الغامدي','الزهراني','العنزي','السبيعي','الرشيدي','الشهري'];
  const students = firstNames.map((f,i)=>({id:'4410'+(2315+i*7), name:f+' '+lastNames[(i*5)%12], gender:i%2?'أنثى':'ذكر', attendance:[100,92,85,100,78,96,64,100,88,90,72,100][i], score:[18,17,12,20,9,19,null,16,14,null,11,20][i], done:[1,1,0,1,0,1,0,1,1,0,0,1][i]}));
  const trend = [{label:'2024/2025 الأول',value:34},{label:'2024/2025 الثاني',value:42},{label:'2025/2026 الأول',value:51},{label:'2025/2026 الثاني',value:63},{label:'2026/2027 الأول',value:76}];
  return {semesters, branches, initiatives, sections, students, trend,
    branchName:id=>(branches.find(b=>b.id===id)||{}).name,
    initiativeName:id=>(initiatives.find(b=>b.id===id)||{}).name};
})();
