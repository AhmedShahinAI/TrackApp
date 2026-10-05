(function(){
async function unzip(buf){
  const dv=new DataView(buf.buffer,buf.byteOffset,buf.byteLength);let eocd=-1;
  for(let i=buf.length-22;i>=0;i--){if(dv.getUint32(i,true)===0x06054b50){eocd=i;break;}}
  if(eocd<0)throw new Error('not-zip');
  const cd=dv.getUint32(eocd+16,true),n=dv.getUint16(eocd+10,true);let p=cd;const E={};
  for(let i=0;i<n;i++){const comp=dv.getUint16(p+10,true),cs=dv.getUint32(p+20,true),nl=dv.getUint16(p+28,true),el=dv.getUint16(p+30,true),cl=dv.getUint16(p+32,true),lo=dv.getUint32(p+42,true);
    E[new TextDecoder().decode(buf.slice(p+46,p+46+nl))]={comp,cs,lo};p+=46+nl+el+cl;}
  return async name=>{const e=E[name];if(!e)return null;const a=dv.getUint16(e.lo+26,true),b=dv.getUint16(e.lo+28,true);const d=buf.slice(e.lo+30+a+b,e.lo+30+a+b+e.cs);
    if(e.comp===0)return new TextDecoder().decode(d);return await new Response(new Blob([d]).stream().pipeThrough(new DecompressionStream('deflate-raw'))).text();};
}
const unesc=s=>s.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&apos;/g,"'").replace(/&amp;/g,'&');
const colIdx=c=>{let n=0;for(const ch of c)n=n*26+(ch.charCodeAt(0)-64);return n-1;};
async function readXlsx(buf){
  const get=await unzip(buf);
  const ss=await get('xl/sharedStrings.xml');
  const strs=ss?[...ss.matchAll(/<si>([\s\S]*?)<\/si>/g)].map(m=>unesc([...m[1].matchAll(/<t[^>]*>([\s\S]*?)<\/t>/g)].map(x=>x[1]).join(''))):[];
  const x=await get('xl/worksheets/sheet1.xml');if(!x)throw new Error('no-sheet');
  return [...x.matchAll(/<row[^>]*>([\s\S]*?)<\/row>/g)].map(r=>{const out=[];
    for(const c of r[1].matchAll(/<c r="([A-Z]+)\d+"([^>]*?)(?:\/>|>([\s\S]*?)<\/c>)/g)){const t=/t="(\w+)"/.exec(c[2]);const v=/<v>([\s\S]*?)<\/v>/.exec(c[3]||'');const is=/<t[^>]*>([\s\S]*?)<\/t>/.exec(c[3]||'');
      let val=v?v[1]:is?is[1]:'';if(t&&t[1]==='s')val=strs[+val];out[colIdx(c[1])]=unesc(String(val)).replace(/\s+/g,' ').trim();}
    return Array.from(out,v=>v??'');});
}
function readCsv(text){return text.replace(/^\uFEFF/,'').split(/\r?\n/).filter(l=>l.trim()).map(l=>l.split(/,(?=(?:[^"]*"[^"]*")*[^"]*$)/).map(c=>c.replace(/^"|"$/g,'').trim()));}
const META={'المقر':'campus','الدرجة':'degree','اسم المقرر':'course','النشاط':'activity','الشعبة':'section'};
function shape(grid){
  const meta={};let h=-1;
  for(let i=0;i<grid.length;i++){const r=grid[i];if(r.some(c=>/رقم الطالب|University ID|الرقم الجامعي/i.test(c))){h=i;break;}
    if(r[0]&&META[r[0]]&&r.filter(Boolean).length<=2)meta[META[r[0]]]=r[1]||'';}
  if(h<0)h=0;
  const headers=grid[h].map(String);const rows=grid.slice(h+1).filter(r=>r.some(Boolean));
  return {meta,headers,rows};
}
window.parseRosterFile=async function(file){
  const ext=(file.name.split('.').pop()||'').toLowerCase();
  if(!['xlsx','csv'].includes(ext))throw new Error('type');
  if(file.size>20*1024*1024)throw new Error('size');
  const buf=new Uint8Array(await file.arrayBuffer());
  if(ext==='xlsx'&&!(buf[0]===0x50&&buf[1]===0x4b))throw new Error('type');
  const grid=ext==='xlsx'?await readXlsx(buf):readCsv(new TextDecoder().decode(buf));
  const s=shape(grid);if(s.rows.length>50000)throw new Error('rows');
  return {name:file.name,size:file.size<1024?file.size+' B':Math.round(file.size/1024)+' KB',...s};
};
})();
