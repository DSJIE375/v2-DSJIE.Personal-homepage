<?php
/** DSJIE 个人主页 - 前台入口（头部 + 桌面侧栏；公共装配见 setup.php） */
require __DIR__ . '/setup.php';
require_once __DIR__ . '/lib_visits.php';
visit_log(); // 访问统计：记录 IP/时间/设备/来路（后台「访问日志」查看）
// 站点 Logo：微信分享卡 og:image + 浏览器标签页图标共用；未填 → 回退字母图标
$lgraw = trim((string)($s['logo'] ?? ''));
$lgicon = is_img_url($lgraw) ? $lgraw : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<!--解除QQ空间限制图片外链-->
<meta name="referrer" content="never">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= e($s['seo_title']) ?></title>
<meta name='description' content='<?= e($s['seo_desc']) ?>'>
<meta name='keywords' content='<?= e($s['seo_keywords']) ?>'>
<meta property='og:type' content='website'>
<meta property='og:locale' content='zh_CN'>
<meta property='og:site_name' content='<?= e($s['name']) ?>'>
<meta property='og:title' content='<?= e($s['seo_title']) ?>'>
<meta property='og:description' content='<?= e($s['seo_desc']) ?>'>
<?php if ($lgicon !== ''): ?><meta property='og:image' content='<?= e($lgicon) ?>'>
<?php endif; ?><meta name='theme-color' content='#ffffff' media='(prefers-color-scheme: light)'>
<meta name='theme-color' content='#0a0a0a' media='(prefers-color-scheme: dark)'>
<link rel='icon' href='<?= $lgicon !== '' ? e($lgicon) : "data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%23111827%22/><text x=%2232%22 y=%2244%22 font-size=%2236%22 font-family=%22Arial%22 font-weight=%22bold%22 fill=%22white%22 text-anchor=%22middle%22>D</text></svg>" ?>'>
<link rel='stylesheet' href='assets/style.css?v=3'>
<link rel='stylesheet' href='assets/extra.css?v=33'>
<link rel='stylesheet' href='assets/misans/MiSans.min.css'>
<?= $theme_css ?>
<script>
(function(){
  var KEY='dsjie_theme';
  var saved=null; try{saved=localStorage.getItem(KEY);}catch(e){}
  var mode=saved||'system';
  var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.setAttribute('data-theme', dark?'dark':'light');
  window.__themeMode=mode;
})();
</script>
<style>
/*DSJIE-STYLE*/
body{font-weight:var(--fw,700);-webkit-text-stroke:var(--fws,0px) currentColor;}
.font-pop{min-width:216px;}
.font-pop .fextra{display:flex;flex-direction:column;gap:8px;margin-top:6px;padding-top:8px;border-top:1px solid var(--border);}
.font-pop .fsl{display:flex;align-items:center;gap:8px;}
.font-pop .fsl label{font-size:12px;color:var(--t2);width:30px;flex:none;}
.font-pop .fsl input[type=range]{flex:1;min-width:0;accent-color:var(--t1);}
.font-pop .fsl b{font-size:12px;color:var(--t1);width:46px;text-align:right;flex:none;font-variant-numeric:tabular-nums;}
.font-pop .freset{padding:7px 10px;border-radius:8px;font-size:12px;color:var(--t2);background:var(--tint);cursor:pointer;transition:.15s;}
.font-pop .freset:hover{color:var(--t1);}
</style>
<script>/*DSJIE-FONT*/(function(){
try{var fv=localStorage.getItem('dsjie_font');if(fv)document.documentElement.setAttribute('data-font',fv);}catch(e){}
try{var fwv=localStorage.getItem('dsjie_fw');if(fwv&&fwv!=='700'){document.documentElement.style.setProperty('--fw',fwv);var wvn=parseInt(fwv,10);document.documentElement.style.setProperty('--fws',(wvn>700?((wvn-700)*0.85/300).toFixed(2):0)+'px');}}catch(e){}
try{var fsz=localStorage.getItem('dsjie_fs');if(fsz&&fsz!=='100')document.documentElement.style.setProperty('--fz',String(parseInt(fsz,10)/100));}catch(e){}
document.addEventListener('DOMContentLoaded',function(){
var pop=document.createElement('div');pop.className='font-pop';
var fx=document.createElement('div');fx.className='fextra';
fx.innerHTML='<div class="fsl"><label>字号</label><input type="range" min="50" max="200" step="5" aria-label="字号"><b></b></div><div class="fsl"><label>粗细</label><input type="range" min="200" max="1000" step="100" aria-label="粗细"><b></b></div><button class="freset" type="button">恢复默认</button>';
pop.appendChild(fx);
document.body.appendChild(pop);
var sl=fx.querySelectorAll('.fsl'),fsr=sl[0].querySelector('input'),fsb=sl[0].querySelector('b'),fwr=sl[1].querySelector('input'),fwb=sl[1].querySelector('b'),frt=fx.querySelector('.freset');
function fget(k,d){try{var v=localStorage.getItem(k);return v||d;}catch(e){return d;}}
function fset(k,v){try{localStorage.setItem(k,v);}catch(e){}}
var curFs=fget('dsjie_fs','100'),curFw=fget('dsjie_fw','700');
fsr.value=curFs;fsb.textContent=curFs+'%';fwr.value=curFw;fwb.textContent=curFw;
fsr.addEventListener('input',function(){curFs=fsr.value;fsb.textContent=curFs+'%';document.documentElement.style.setProperty('--fz',String(parseInt(curFs,10)/100));});
fsr.addEventListener('change',function(){fset('dsjie_fs',curFs);});
fwr.addEventListener('input',function(){curFw=fwr.value;fwb.textContent=curFw;document.documentElement.style.setProperty('--fw',curFw);document.documentElement.style.setProperty('--fws',(curFw>700?((curFw-700)*0.85/300).toFixed(2):0)+'px');fset('dsjie_fw',curFw);});
frt.addEventListener('click',function(e){e.stopPropagation();curFs='100';curFw='700';fsr.value='100';fsb.textContent='100%';fwr.value='700';fwb.textContent='700';document.documentElement.style.setProperty('--fz','1');document.documentElement.style.removeProperty('--fw');document.documentElement.style.setProperty('--fws','0px');fset('dsjie_fs','100');fset('dsjie_fw','700');});
['click','touchstart'].forEach(function(ev){fx.addEventListener(ev,function(e){e.stopPropagation();});});
function initFs(){var list=[document.body],all=document.body.querySelectorAll('*'),i,n;
for(i=0;i<all.length;i++)list.push(all[i]);
for(i=0;i<list.length;i++){if(list[i].dataset.fs0)continue;n=parseFloat(getComputedStyle(list[i]).fontSize);if(n)list[i].dataset.fs0=n;}
for(i=0;i<list.length;i++){if(list[i].dataset.fs0&&!list[i].style.fontSize)list[i].style.fontSize='calc('+list[i].dataset.fs0+'px * var(--fz,1))';}}
initFs();
var pending=[];
new MutationObserver(function(ms){var i,j,k;
for(i=0;i<ms.length;i++)for(j=0;j<ms[i].addedNodes.length;j++){var nd=ms[i].addedNodes[j];if(nd.nodeType===1)pending.push(nd);}
if(!pending.length)return;
var keep=document.documentElement.style.getPropertyValue('--fz');document.documentElement.style.setProperty('--fz','1');
for(k=0;k<pending.length;k++){if(pending[k].dataset.fs0)continue;
var lst=[pending[k]],al=pending[k].querySelectorAll('*'),m;
for(m=0;m<al.length;m++)lst.push(al[m]);
for(m=0;m<lst.length;m++){if(lst[m].dataset.fs0)continue;var n2=parseFloat(getComputedStyle(lst[m]).fontSize);
if(n2){lst[m].dataset.fs0=n2;lst[m].style.fontSize='calc('+n2+'px * var(--fz,1))';}}}
document.documentElement.style.setProperty('--fz',keep||'1');pending=[];}).observe(document.body,{childList:true,subtree:true});
function sync(){var cur=document.documentElement.getAttribute('data-font')||'';Array.prototype.forEach.call(pop.querySelectorAll('button[data-v]'),function(x){x.classList.toggle('on',x.getAttribute('data-v')===cur);});}
sync();
function bind(id){var el=document.getElementById(id);if(!el)return;el.addEventListener('click',function(ev){ev.stopPropagation();
var show=pop.classList.toggle('show');if(show){var r=el.getBoundingClientRect();pop.style.top=(r.bottom+8)+'px';
if(r.left>innerWidth/2){pop.style.right=(innerWidth-r.right)+'px';pop.style.left='';}else{pop.style.left=r.left+'px';pop.style.right='';}}});}
bind('fontBtnDesk');bind('fontBtnMob');
document.addEventListener('click',function(){pop.classList.remove('show');});
});})();
</script>
</head>
<body>
<div class="loader"><div class="ring"></div></div>
<div class="stage">
  <div class="desk">
    <div class="window">
      <div class="winbar">
        <div class="dots"><i></i><i></i><i></i></div>
        <div class="wintitle"><?= e($s['seo_title']) ?></div>
        <button class="theme-btn font-btn" id="fontBtnDesk" aria-label="切换字体">Aa</button><button class="theme-btn" id="themeBtnDesk" aria-label="切换主题"><?= $themetog ?></button>
      </div>
      <div class="winbody">
        <aside class="side">
          <div class="avatar"><?= avatar_markup($s) ?></div>
          <div class="dname"><?= e($s['name']) ?></div>
          <p class="dslogan"><span class="typer" data-typed><?= e($sl1) ?></span><i class="caret"></i></p>
          <?php if ($welcome !== ''): ?><p class="dwelcome"><?= e($welcome) ?></p><?php endif; ?>
          <div class="btnrow">
            <button class="contact" data-curl="<?= e($s['contact_url']) ?>"><?= e($s['contact_text']) ?></button>
            <?php if ($blog_text !== ''): ?><button class="contact blogbtn" data-curl="<?= e($blog_url) ?>"><?= e($blog_text) ?></button><?php endif; ?>
          </div>
          <div class="socials"><?= $socials_html ?></div>
          <div class="online-count">当前还有 <strong data-online-count>0</strong> 人在浏览 · 累计浏览 <strong><?= (int)visit_counter_total() ?></strong> 次</div>
          <?= $icp_html ?>
        </aside>