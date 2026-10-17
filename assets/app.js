var SECTIONS = window.__SECTIONS || [];
var N = SECTIONS.length;
var deskScroll = document.querySelector('.scroll');
var mobScroll = document.querySelector('.mscroll'); var heroEl = document.querySelector('.mob .mhero'); var MOB_HERO = heroEl ? heroEl.outerHTML : ''; if(heroEl) heroEl.parentNode.removeChild(heroEl);
var cur = 0, cooling = false;
var COOLDOWN = 500; render(0);

var lb = document.getElementById('lb');
var lbList = [], lbIdx = 0, lbGuard = 0;
function lbRender(){
  var c = lbList[lbIdx];
  if(!c) return;
  lb.querySelector('.lbimg').innerHTML = c.querySelector('.gimg').outerHTML;
  var cap = c.querySelector('p'), dt = c.querySelector('.gdate');
  lb.querySelector('.lbcap').innerHTML = (cap ? cap.textContent : '') + (dt ? '<i>' + dt.textContent + '</i>' : '');
  lb.querySelector('.lbno').textContent = (lbIdx + 1) + ' / ' + lbList.length;
  var multi = lbList.length > 1;
  lb.querySelector('.lbprev').style.display = multi ? '' : 'none';
  lb.querySelector('.lbnext').style.display = multi ? '' : 'none';
}
function lbOpen(cell){
  var root = cell.closest('.scroll') || cell.closest('.mscroll');
  lbList = Array.prototype.slice.call(root.querySelectorAll('.gcell')).filter(function(x){ return x.style.display !== 'none'; });
  lbIdx = lbList.indexOf(cell);
  if(lbIdx < 0) lbIdx = 0;
  lbRender();
  lb.classList.add('on');
}
function lbGo(d){
  if(lbList.length < 2) return;
  lbIdx = (lbIdx + d + lbList.length) % lbList.length;
  lbRender();
}
function lbClose(){ lb.classList.remove('on'); }
document.addEventListener('click', function(e){
  if(Date.now() - lbGuard < 350) return;
  var t = e.target;
  if(t.closest && t.closest('.gcell')){ lbOpen(t.closest('.gcell')); return; }
  if(t.classList && t.classList.contains('lb')){ lbClose(); return; }
  if(t.classList && t.classList.contains('lbclose')){ lbClose(); return; }
  if(t.classList && t.classList.contains('lbprev')){ lbGo(-1); return; }
  if(t.classList && t.classList.contains('lbnext')){ lbGo(1); return; }
});
document.addEventListener('keydown', function(e){
  if(!lb.classList.contains('on')) return;
  if(e.key === 'Escape') lbClose();
  else if(e.key === 'ArrowLeft') lbGo(-1);
  else if(e.key === 'ArrowRight') lbGo(1);
});
var lbTx = 0, lbTy = 0;
lb.addEventListener('touchstart', function(e){ lbTx = e.touches[0].clientX; lbTy = e.touches[0].clientY; }, {passive:true});
lb.addEventListener('touchend', function(e){
  var dx = e.changedTouches[0].clientX - lbTx;
  var dy = e.changedTouches[0].clientY - lbTy;
  if(Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)){ lbGuard = Date.now(); lbGo(dx < 0 ? 1 : -1); }
}, {passive:true});

var GKEY = 'dsjie_guestbook_v1';
var gData = null;
function gesc(s){ return String(s).replace(/[&<>"]/g, function(c){ return c==='&' ? '&amp;' : (c==='<' ? '&lt;' : (c==='>' ? '&gt;' : '&quot;')); }); }
function gmask(m){ var i=m.indexOf('@'); if(i<1) return m; return m.slice(0,1)+'***'+m.slice(i); }
function gnow(){ var d=new Date(); function p(x){ return (x<10?'0':'')+x; } return d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate())+' '+p(d.getHours())+':'+p(d.getMinutes()); }
function gload(){ try{ var a=JSON.parse(localStorage.getItem(GKEY)); if(a && a.length) return a; }catch(e){} return [{id:'s1', qq:'10001', email:'xiaoming@example.com', text:'主页做得真简洁，黑白配色太好看了，收藏了！', time:'2026-10-15 20:12', replies:[{id:'s1r1', qq:'站长 DSJIE', email:'dsjie@example.com', text:'感谢支持！这版改了挺久，欢迎常来。', time:'2026-10-15 21:03'}]}, {id:'s2', qq:'20002', email:'lisi@example.com', text:'请问作品里的博客是用 PHP 写的吗？求技术栈分享。', time:'2026-10-16 09:40', replies:[]}, {id:'s3', qq:'30003', email:'wangwu@example.com', text:'从随笔过来的，写得挺实在，继续加油。', time:'2026-10-16 10:05', replies:[]}]; }
function gAll(){ if(!gData) gData = (window.GBOOK || []); return gData; }
function gpost(payload, okCb){
  fetch('api.php', {method:'POST', headers:{'Content-Type':'application/json;charset=utf-8'}, body:JSON.stringify(payload)})
    .then(function(r){ return r.json(); })
    .then(function(j){ if(j && j.ok){ if(j.messages) gData = j.messages; okCb && okCb(j); } else { showToast((j && j.msg) || '操作失败'); } })
    .catch(function(){ showToast('网络异常，请稍后重试'); });
}
function gReplyHTML(r){ return '<div class="greply"><div class="gmhead"><b class="gmqq">'+gesc(r.qq)+'</b><span class="gmtime">'+gesc(r.time)+'</span></div><p class="gmtext">'+gesc(r.text)+'</p><span class="gmmail">'+gesc(gmask(r.email))+'</span></div>'; }
function gMsgHTML(m){ var reps = (m.replies && m.replies.length) ? '<div class="greplies">'+m.replies.map(gReplyHTML).join('')+'</div>' : ''; return '<div class="gmsg" data-id="'+gesc(m.id)+'"><div class="gmhead"><b class="gmqq">'+gesc(m.qq)+'</b><span class="gmtime">'+gesc(m.time)+'</span></div><p class="gmtext">'+gesc(m.text)+'</p><div class="gmact"><button class="gbtn gre-btn" type="button">回复</button><span class="gmmail">'+gesc(gmask(m.email))+'</span></div><form class="gres" autocomplete="off"><div class="grow"><input class="gif rqq" type="text" inputmode="numeric" maxlength="12" placeholder="你的 QQ 号"><input class="gif remail" type="text" placeholder="你的邮箱"></div><textarea class="gif rtext" rows="2" maxlength="200" placeholder="回复内容（200 字内）"></textarea><button class="gsubmit2" type="submit">提交回复</button></form>'+reps+'</div>'; }
function gPaint(){ var a=gAll(); var html = a.length ? a.map(gMsgHTML).join('') : '<div class="gempty">还没有留言，来抢沙发</div>'; document.querySelectorAll('.gmsgs').forEach(function(el){ el.innerHTML = html; }); }
function gOKqq(v){ return /^[0-9]{5,12}$/.test(v); }
function gOKmail(v){ return /^[^ @]+@[^ @]+[.][^ @]+$/.test(v); }
document.addEventListener('submit', function(e){
  var f = e.target;
  if(!f.classList) return;
  if(f.classList.contains('gform')){
    e.preventDefault();
    var qq=f.querySelector('.gqq').value.trim(), mail=f.querySelector('.gemail').value.trim(), txt=f.querySelector('.gtext').value.trim();
    if(!gOKqq(qq)){ showToast('QQ 号需为 5-12 位数字'); return; }
    if(!gOKmail(mail)){ showToast('邮箱格式不正确'); return; }
    if(!txt){ showToast('留言内容不能为空'); return; }
    gpost({action:'add_msg', qq:qq, email:mail, text:txt}, function(){ gPaint(); f.reset(); showToast('留言成功，感谢！'); });
    return;
  }
  if(f.classList.contains('gres')){
    e.preventDefault();
    var box=f.closest('.gmsg'); if(!box) return;
    var id=box.getAttribute('data-id');
    var rq=f.querySelector('.rqq').value.trim(), rm=f.querySelector('.remail').value.trim(), rt=f.querySelector('.rtext').value.trim();
    if(!gOKqq(rq)){ showToast('QQ 号需为 5-12 位数字'); return; }
    if(!gOKmail(rm)){ showToast('邮箱格式不正确'); return; }
    if(!rt){ showToast('回复内容不能为空'); return; }
    gpost({action:'add_reply', id:id, qq:rq, email:rm, text:rt}, function(){ gPaint(); showToast('回复成功！'); });
  }
});
document.addEventListener('click', function(e){
  var t=e.target;
  if(t.classList && t.classList.contains('gre-btn')){
    var box=t.closest('.gmsg'); if(!box) return;
    var fb=box.querySelector('.gres');
    if(fb){ fb.classList.toggle('open'); if(fb.classList.contains('open')){ var ta=fb.querySelector('.rtext'); if(ta) ta.focus(); } }
  }
});

function drawRadar(){
  var skills = (window.RADAR && window.RADAR.length) ? window.RADAR : [['PHP',0.85],['Laravel',0.75],['JS',0.7],['HTML/CSS',0.8],['MySQL',0.7],['Linux',0.65],['Git',0.7]];
  var n = skills.length, cx = 170, cy = 150, R = 95;
  function pt(i, r){ var a = -Math.PI/2 + i*2*Math.PI/n; return [cx + r*Math.cos(a), cy + r*Math.sin(a)]; }
  document.querySelectorAll('.radar').forEach(function(host){
    var grid = '', axes = '', labels = '';
    [0.33,0.66,1].forEach(function(k){
      var pts = [];
      for(var i=0;i<n;i++){ var p = pt(i, R*k); pts.push(p[0].toFixed(1)+','+p[1].toFixed(1)); }
      grid += '<polygon class="rdgrid" points="'+pts.join(' ')+'"/>';
    });
    for(var i=0;i<n;i++){ var p = pt(i, R); axes += '<line class="rdaxis" x1="'+cx+'" y1="'+cy+'" x2="'+p[0].toFixed(1)+'" y2="'+p[1].toFixed(1)+'"/>'; }
    var dp = [];
    for(var i=0;i<n;i++){ var p = pt(i, R*skills[i][1]); dp.push(p[0].toFixed(1)+','+p[1].toFixed(1)); }
    for(var i=0;i<n;i++){
      var lp = pt(i, R+22);
      var anc = lp[0] > cx+10 ? 'start' : (lp[0] < cx-10 ? 'end' : 'middle');
      labels += '<text class="rdlabel" x="'+lp[0].toFixed(1)+'" y="'+(lp[1]+4).toFixed(1)+'" text-anchor="'+anc+'">'+skills[i][0]+'</text>';
    }
    host.innerHTML = '<svg viewBox="0 0 340 310">'+grid+axes+'<polygon class="rdshape" points="'+dp.join(' ')+'"/>'+labels+'</svg>';
  });
}
(function(){
  var g = document.querySelector('.guide');
  if(!g) return;
  var seen = null;
  try{ seen = localStorage.getItem('dsjie_guide'); }catch(e){}
  if(seen) return;
  setTimeout(function(){
    var mob = window.matchMedia && window.matchMedia('(max-width:767px)').matches;
    g.textContent = mob ? '内容滑到底后，继续上滑即可切换板块' : '滚动到底后，继续滚轮下滚即可切换板块';
    g.classList.add('show');
    setTimeout(function(){ g.classList.remove('show'); }, 4200);
    try{ localStorage.setItem('dsjie_guide','1'); }catch(e){}
  }, 1600);
})();

var mMenuBtn = document.getElementById('mmenuBtn');
var mMenuPanel = document.querySelector('.mmenu-panel');
if(mMenuBtn && mMenuPanel){
  mMenuBtn.addEventListener('click', function(e){
    e.stopPropagation();
    var open = mMenuPanel.classList.toggle('open');
    mMenuBtn.classList.toggle('on', open);
  });
  mMenuPanel.addEventListener('click', function(e){
    var it = e.target && e.target.closest ? e.target.closest('.mitem') : null;
    if(!it) return;
    var idx = parseInt(it.getAttribute('data-i'), 10);
    mMenuPanel.classList.remove('open');
    mMenuBtn.classList.remove('on');
    if(!isNaN(idx)) render(idx);
  });
  document.addEventListener('click', function(e){
    if(mMenuPanel.classList.contains('open') && !mMenuPanel.contains(e.target) && !(mMenuBtn.contains && mMenuBtn.contains(e.target))){
      mMenuPanel.classList.remove('open');
      mMenuBtn.classList.remove('on');
    }
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && mMenuPanel.classList.contains('open')){
      mMenuPanel.classList.remove('open');
      mMenuBtn.classList.remove('on');
    }
  });
}

function applyShowMore(){
  var cfg = [['.wcard',3],['.ecard',3],['.gcell',4],['.drow',3]];
  [deskScroll,mobScroll].forEach(function(root){
    var sect = root.querySelector('.sect');
    if(!sect || sect.getAttribute('data-sm')) return;
    sect.setAttribute('data-sm','1');
    cfg.forEach(function(c){
      var items = sect.querySelectorAll(c[0]);
      var lim = c[1];
      if(items.length <= lim) return;
      for(var k=lim;k<items.length;k++){ items[k].style.display='none'; }
      var expanded = false;
      var btn = document.createElement('button');
      btn.className = 'showmore';
      btn.textContent = '显示更多';
      sect.appendChild(btn);
      btn.onclick = function(){
        expanded = !expanded;
        for(var j=lim;j<items.length;j++){ items[j].style.display = expanded ? '' : 'none'; }
        btn.textContent = expanded ? '收起' : '显示更多';
      };
    });
  });
}

function render(i){
  cur = ((i % N) + N) % N;
  var s = SECTIONS[cur].html;
  deskScroll.innerHTML = s;
  mobScroll.innerHTML = (cur === 0 ? MOB_HERO : '') + s;
  deskScroll.scrollTop = 0;
  mobScroll.scrollTop = 0;
  document.querySelectorAll('.navbtn,.mtabbtn,.mitem').forEach(function(b,k){
    b.classList.toggle('on', k === cur);
  });
  var dn = document.querySelector('.navbtn.on');
  var dm = document.querySelector('.mtabbtn.on');
  if(dn) dn.scrollIntoView({block:'nearest'});
  if(dm) dm.scrollIntoView({inline:'nearest',block:'nearest'});
  document.querySelectorAll('.prog').forEach(function(p){
    var dots = p.querySelectorAll('.pd');
    if(dots.length !== N){
      var h = '<span class="pnum">' + (cur+1) + '/' + N + '</span>';
      for(var q=0;q<N;q++){ h += '<i class="pd' + (q===cur ? ' on' : '') + '"></i>'; }
      p.innerHTML = h;
    } else {
      p.querySelector('.pnum').textContent = (cur+1)+'/'+N;
      dots.forEach(function(d,k){ d.classList.toggle('on', k===cur); });
    }
  });
  applyShowMore();
  gPaint();
  drawRadar();
}

function go(dir){
  if(cooling) return;
  var t=cur+dir; if(t<0||t>=N) return; render(t);
  cooling = true;
  setTimeout(function(){ cooling = false; }, COOLDOWN);
}

var lastWheel = 0;
deskScroll.addEventListener('wheel', function(e){
  var now = Date.now();
  if(now - lastWheel < 800) return;
  var atB = deskScroll.scrollTop >= deskScroll.scrollHeight - deskScroll.clientHeight - 4;
  var atT = deskScroll.scrollTop <= 0;
  if(e.deltaY > 0 && atB && cur < N-1){ e.preventDefault(); lastWheel = now; go(1); }
  else if(e.deltaY < 0 && atT && cur > 0){ e.preventDefault(); lastWheel = now; go(-1); }
}, {passive:false});

document.querySelectorAll('.navbtn,.mtabbtn').forEach(function(b){
  b.addEventListener('click', function(){
    render(parseInt(b.getAttribute('data-i'),10));
  });
});

var SW = 64, ENTER = 6;
var st = {sx:0, sy:0, sTop:0, mode:'none', dir:1, os:0};

mobScroll.addEventListener('touchstart', function(e){
  if(cooling) return;
  st.sx = e.touches[0].clientX;
  st.sy = e.touches[0].clientY;
  st.sTop = mobScroll.scrollTop;
  st.mode = 'none';
  st.os = 0; st.dir = 1;
}, {passive:true});

mobScroll.addEventListener('touchmove', function(e){
  if(cooling) return;
  if(st.mode === 'native') return;
  var dx = e.touches[0].clientX - st.sx;
  var dy = e.touches[0].clientY - st.sy;
  if(st.mode === 'none'){
    if(Math.abs(dx) > 1.4 * Math.abs(dy)){ st.mode = 'native'; return; }
    var atTop = st.sTop <= 0;
    var atBot = st.sTop >= mobScroll.scrollHeight - mobScroll.clientHeight - 4;
    var short = mobScroll.scrollHeight <= mobScroll.clientHeight + 4;
    var pullUp = (short || atBot) && dy < 0 && cur < N - 1;
    var pullDown = (short || atTop) && dy > 0 && cur > 0;
    if((pullUp || pullDown) && Math.abs(dy) > ENTER){
      st.mode = 'pull'; st.dir = dy < 0 ? 1 : -1;
    } else {
      st.mode = 'native'; return;
    }
  }
  if(st.mode !== 'pull') return;
  e.preventDefault();
  st.os = st.dir === 1 ? -dy : dy;
  if(st.os < 0) st.os = 0;
  var ty = st.dir === 1 ? -Math.min(st.os, 80) : Math.min(st.os, 80);
  mobScroll.style.transform = 'translateY(' + ty + 'px)';
  mobScroll.style.opacity = Math.max(0, 1 - st.os / 160);
}, {passive:false});

function resetPos(){
  mobScroll.style.transition = '';
  mobScroll.style.transform = '';
  mobScroll.style.opacity = '';
}

mobScroll.addEventListener('touchend', function(){
  if(st.mode !== 'pull'){ st.mode = 'none'; return; }
  var dir = st.dir;
  if(st.os >= SW){
    mobScroll.style.transition = 'transform .35s cubic-bezier(.28,.11,.32,1), opacity .35s';
    mobScroll.style.transform = 'translateY(' + (st.dir === 1 ? -80 : 80) + 'px)';
    mobScroll.style.opacity = '0';
    setTimeout(function(){
      go(dir);
      resetPos();
    }, 300);
  } else {
    mobScroll.style.transition = 'transform .3s cubic-bezier(.28,.11,.32,1), opacity .3s';
    mobScroll.style.transform = 'translateY(0)';
    mobScroll.style.opacity = '1';
    setTimeout(function(){ resetPos(); }, 320);
  }
  st.mode = 'none';
}, {passive:true});

document.addEventListener('click', function(e){
  var b = e.target.closest('.dbtn');
  if(!b || b.classList.contains('loading')) return;
  var name = b.getAttribute('data-name') || '文件';
  var url = b.getAttribute('data-url') || '';
  if(!url){ showToast('该文件待站长上传，请在后台「下载」中添加'); return; }
  var a = document.createElement('a');
  a.href = url; a.setAttribute('download','');
  document.body.appendChild(a); a.click(); a.remove();
  b.classList.add('loading');
  b.innerHTML = '<svg style="width:16px;height:16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 3v12M7 10l5 5 5-5"/></svg>';
  var spin = b.querySelector('svg');
  spin.style.animation = 'spin .8s linear infinite';
  setTimeout(function(){
    b.classList.remove('loading');
    b.classList.add('done');
    b.innerHTML = '<svg style="width:16px;height:16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5l5 5 10-11"/></svg>';
    showToast(name + ' 开始下载');
    setTimeout(function(){
      b.classList.remove('done');
      b.textContent = '下载';
    }, 1000);
  }, 600);
});

var themes = ['light','dark','system'];
var mode = window.__themeMode || 'system';
function applyTheme(m){
  var dark = m === 'dark' || (m === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
}
function turn(btn){
  btn.classList.add('turn');
  setTimeout(function(){ btn.classList.remove('turn'); }, 420);
}
var btnD = document.getElementById('themeBtnDesk');
var btnM = document.getElementById('themeBtnMob');
function cycle(){
  var idx = themes.indexOf(mode);
  mode = themes[(idx + 1) % 3];
  applyTheme(mode);
  try{ localStorage.setItem('dsjie_theme', mode); }catch(e){}
  turn(btnD); turn(btnM);
}
btnD.addEventListener('click', cycle);
btnM.addEventListener('click', cycle);
if(window.matchMedia){
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(){
    if(mode === 'system') applyTheme('system');
  });
}

function showToast(msg){
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._h);
  t._h = setTimeout(function(){ t.classList.remove('show'); }, 2000);
}

setTimeout(function(){
  document.documentElement.setAttribute('data-loaded','');
}, 900);

document.addEventListener('click', function(e){
  var b = e.target && e.target.closest && (e.target.closest('.contact') || e.target.closest('.mcontact'));
  if(!b) return;
  var u = b.getAttribute('data-curl') || '';
  if(u){ location.href = u; } else { showToast('联系方式暂未配置，请在后台「站点设置」填写'); }
});