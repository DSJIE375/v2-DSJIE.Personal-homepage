  <div class="mob">
    <header class="mtopbar">
      <span class="mlogo"><?= logo_markup($s) ?></span>
      <span class='mtop-actions'><button class='mmenu' id='mmenuBtn' aria-label='菜单'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8' stroke-linecap='round'><path d='M4 7h16M4 12h16M4 17h16'/></svg></button><button class="theme-btn font-btn" id="fontBtnMob" aria-label="切换字体">Aa</button><button class="theme-btn" id="themeBtnMob" aria-label="切换主题"><?= $themetog ?></button></span>
    </header>
    <div class='mmenu-panel'><button class='mitem' data-i='0'><span>01</span>关于</button><button class='mitem' data-i='1'><span>02</span>作品</button><button class='mitem' data-i='2'><span>03</span>随笔</button><button class='mitem' data-i='3'><span>04</span>图库</button><button class='mitem' data-i='4'><span>05</span>动态</button><button class='mitem' data-i='5'><span>06</span>友链</button><button class='mitem' data-i='6'><span>07</span>下载</button><button class='mitem' data-i='7'><span>08</span>留言</button><button class='mitem' data-i='8'><span>09</span>版权</button><button class='mitem' data-i='9'><span>10</span>App</button></div>
    <section class="mhero">
      <div class="avatar"><?= avatar_markup($s) ?></div>
      <div class="dname"><?= e($s['name']) ?></div>
      <p class="dslogan"><span class="typer" data-typed><?= e($sl1) ?></span><i class="caret"></i></p>
      <?php if ($welcome !== ''): ?><p class="dwelcome"><?= e($welcome) ?></p><?php endif; ?>
      <div class="btnrow">
        <button class="mcontact" data-curl="<?= e($s['contact_url']) ?>"><?= e($s['contact_text']) ?></button>
        <?php if ($blog_text !== ''): ?><button class="mcontact blogbtn" data-curl="<?= e($blog_url) ?>"><?= e($blog_text) ?></button><?php endif; ?>
      </div>
      <div class="socials"><?= $socials_html ?></div>
      <div class="online-count">当前还有 <strong data-online-count>0</strong> 人在浏览 · 累计浏览 <strong><?= (int)visit_counter_total() ?></strong> 次</div>
      <?= $icp_html ?>
    </section>
    <main class="mcontent">
      <div class='prog'><span class='pnum'>1/5</span><i class='pd on'></i><i class='pd'></i><i class='pd'></i><i class='pd'></i><i class='pd'></i></div>
      <div class="mscroll"><?= $about_html ?></div>
    </main>
  </div>
</div>
<div class='lb' id='lb'><span class='lbno'>1 / 1</span><button class='lbclose' aria-label='关闭'>×</button><button class='lbprev' aria-label='上一张'>‹</button><div class='lbimg'></div><div class='lbcap'></div><button class='lbnext' aria-label='下一张'>›</button></div>
<div class='guide'></div>
<div class="toast" id="toast"></div>
<script>
window.__SECTIONS=<?= json_encode($sections, $enc) ?>;
window.RADAR=<?= json_encode($radar, $enc) ?>;
window.GBOOK=<?= json_encode($gbook, $enc) ?>;
window.__CONTENT_VERSION=<?= json_encode($content_version, $enc) ?>;
window.__SITE_VERSION=<?= json_encode($site_version, $enc) ?>;
window.SLOGANS=<?= $slogans_js ?>;
window.__SITE_NAME=<?= json_encode((string)($s['name'] ?? ''), $enc) ?>;
</script>
<script src="assets/intro.js"></script>
<script src="assets/app.js?v=32"></script>
<script src="assets/tour.js?v=6"></script>
<script>
(function(){
window.fsmx = function(b){
  var now = +new Date();
  if (b.__ft && now - b.__ft < 400) return;
  b.__ft = now;
  var sect = b.closest('.sect');
  if (!sect) return;
  var items = sect.querySelectorAll('.folded');
  var isOpen = b.getAttribute('data-open') === '1';
  var total = Number(b.getAttribute('data-total') || items.length || 0);
  if (!isOpen) {
    items.forEach(function(x){ x.classList.remove('folded'); x.style.display = ''; });
    b.setAttribute('data-open', '1');
    b.textContent = '收起';
  } else {
    items.forEach(function(x){ x.classList.add('folded'); x.style.display = 'none'; });
    b.setAttribute('data-open', '0');
    b.textContent = '查看更多（还有 ' + total + ' 条）';
  }
};
document.addEventListener('click', function(e){
  var f = e.target && e.target.closest && e.target.closest('[data-fold]');
  if (f) { if (typeof window.fsmx === 'function') window.fsmx(f); return; }
  var g = e.target && e.target.closest && e.target.closest('[data-gfold]');
  if (g) { window.__gopen = !window.__gopen; if (typeof gPaint === 'function') gPaint(); }
});
})();
</script>
<script>
/* 口号打字机：第一句从左到右打出 → 停留 → 整句退回 → 显示第二句 → 循环 */
(function(){
  var lines = window.SLOGANS || [];
  var els = document.querySelectorAll('[data-typed]');
  if (!lines.length || !els.length) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduce) return;
  Array.prototype.forEach.call(els, function (el) {
    el.textContent = '';
    if (lines.length === 1) {
      (function type1(){
        var n = el.textContent.length;
        if (n < lines[0].length) { el.textContent = lines[0].slice(0, n + 1); setTimeout(type1, 95); }
      })();
      return;
    }
    var li = 0, ci = 0, del = false;
    (function tick(){
      var cur = lines[li];
      if (!del) {
        ci++;
        el.textContent = cur.slice(0, ci);
        if (ci >= cur.length) { del = true; setTimeout(tick, 1800); }
        else setTimeout(tick, 95);
      } else {
        ci--;
        el.textContent = cur.slice(0, ci);
        if (ci <= 0) { del = false; li = (li + 1) % lines.length; setTimeout(tick, 450); }
        else setTimeout(tick, 42);
      }
    })();
  });
})();
</script>
</body>
</html>