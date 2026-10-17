  <div class="mob">
    <header class="mtopbar">
      <span class="mlogo"><?= e($s['name']) ?>.</span>
      <span class='mtop-actions'><button class='mmenu' id='mmenuBtn' aria-label='菜单'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8' stroke-linecap='round'><path d='M4 7h16M4 12h16M4 17h16'/></svg></button><button class="theme-btn" id="themeBtnMob" aria-label="切换主题"><?= $themetog ?></button></span>
    </header>
    <div class='mmenu-panel'><button class='mitem' data-i='0'><span>01</span>关于</button><button class='mitem' data-i='1'><span>02</span>作品</button><button class='mitem' data-i='2'><span>03</span>随笔</button><button class='mitem' data-i='3'><span>04</span>图库</button><button class='mitem' data-i='4'><span>05</span>下载</button><button class='mitem' data-i='5'><span>06</span>留言</button><button class='mitem' data-i='6'><span>07</span>动态</button><button class='mitem' data-i='7'><span>08</span>友链</button></div>
    <section class="mhero">
      <div class="avatar"><?= e($s['avatar']) ?></div>
      <div class="dname"><?= e($s['name']) ?></div>
      <p class="dslogan"><?= e($s['slogan']) ?></p>
      <button class="mcontact" data-curl="<?= e($s['contact_url']) ?>"><?= e($s['contact_text']) ?></button>
      <div class="socials"><?= $socials_html ?></div>
    </section>
    <main class="mcontent">
      <div class='prog'><span class='pnum'>1/5</span><i class='pd on'></i><i class='pd'></i><i class='pd'></i><i class='pd'></i><i class='pd'></i></div>
      <div class="mscroll"><?= $about_html ?></div>
    </main>
    <footer class="mfooter"><?= e($s['footer']) ?></footer>
  </div>
</div>
<div class='lb' id='lb'><span class='lbno'>1 / 1</span><button class='lbclose' aria-label='关闭'>×</button><button class='lbprev' aria-label='上一张'>‹</button><div class='lbimg'></div><div class='lbcap'></div><button class='lbnext' aria-label='下一张'>›</button></div>
<div class='guide'></div>
<div class="toast" id="toast"></div>
<script>
window.__SECTIONS=<?= json_encode($sections, $enc) ?>;
window.RADAR=<?= json_encode($radar, $enc) ?>;
window.GBOOK=<?= json_encode($gbook, $enc) ?>;
</script>
<script src="assets/app.js"></script>
</body>
</html>
