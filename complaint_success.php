<?php
// ===== complaint_success.php =====
include 'db.php'; // fail sambungan database kau (ubah nama kalau lain)

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) { header("Location: submit_complaint.php"); exit(); }

// Prepared statement (selamat dari SQL injection)
$stmt = mysqli_prepare($conn, "SELECT * FROM complaints WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$row) { header("Location: submit_complaint.php"); exit(); }

// !! Ubah nama column ikut table kau !!
$name     = $row['student_name'] ?? 'Student';
$location = $row['location']     ?? '-';
$problem  = $row['problem']      ?? '-';
$desc     = $row['description']  ?? '';
$created  = !empty($row['created_at']) ? date('d M Y, h:i A', strtotime($row['created_at'])) : date('d M Y, h:i A');
$ticket   = 'TVT-' . str_pad($id, 4, '0', STR_PAD_LEFT);

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Complaint Received | TVETMARA Lumut</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#030712; --panel:#0a1120; --ink:#e6f1ff; --muted:#8aa0bd;
    --cyan:#00e5ff; --cyan-dim:rgba(0,229,255,.25); --green:#2bff9a; --line:rgba(0,229,255,.18);
    --sans:'Space Grotesk','Segoe UI',system-ui,sans-serif;
    --mono:'JetBrains Mono',Consolas,'Courier New',monospace;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html,body{min-height:100%}
  body{
    background:radial-gradient(ellipse at 50% 0%,#06314a 0%,var(--bg) 55%);
    color:var(--ink); font-family:var(--sans); overflow-x:hidden;
  }

  /* ---------- perspective grid floor ---------- */
  .grid{position:fixed;inset:auto 0 0 0;height:60vh;perspective:500px;pointer-events:none;z-index:0}
  .grid::before{
    content:"";position:absolute;inset:-50% -50% 0 -50%;
    background:
      linear-gradient(var(--cyan-dim) 1px,transparent 1px) 0 0/60px 60px,
      linear-gradient(90deg,var(--cyan-dim) 1px,transparent 1px) 0 0/60px 60px;
    transform:rotateX(62deg);transform-origin:50% 100%;
    -webkit-mask-image:linear-gradient(to top,#000 10%,transparent 85%);
            mask-image:linear-gradient(to top,#000 10%,transparent 85%);
    animation:drift 6s linear infinite;
  }
  @keyframes drift{to{background-position:0 60px,60px 0}}

  /* ---------- floating code symbols ---------- */
  .sym{position:fixed;font-family:var(--mono);font-weight:700;color:var(--cyan);opacity:.35;
       text-shadow:0 0 12px var(--cyan);z-index:0;pointer-events:none;animation:float 7s ease-in-out infinite}
  .s1{left:9%;top:34%;font-size:26px}
  .s2{left:22%;top:62%;font-size:28px;animation-delay:-2s}
  .s3{right:10%;top:40%;font-size:30px;animation-delay:-4s}
  .s4{right:6%;top:68%;font-size:26px;animation-delay:-1s}
  .s5{right:26%;top:14%;font-size:22px;animation-delay:-3s}
  @keyframes float{50%{transform:translateY(-16px) rotate(4deg)}}

  /* ---------- layout ---------- */
  main{position:relative;z-index:1;max-width:760px;margin:0 auto;padding:28px 20px 70px}
  .pill{display:block;width:max-content;margin:0 auto 26px;padding:9px 26px;border:1px solid var(--cyan);
        border-radius:99px;font:700 13px var(--mono);letter-spacing:.22em;color:var(--cyan);background:rgba(0,229,255,.05)}
  h1{text-align:center;font-size:clamp(32px,6vw,52px);line-height:1.1;font-weight:700}
  h1 span{color:var(--cyan);text-shadow:0 0 28px rgba(0,229,255,.5)}
  .sub{text-align:center;color:var(--muted);margin:12px 0 30px}

  /* ---------- terminal (the memorable bit) ---------- */
  .term{background:rgba(3,7,18,.92);border:1px solid var(--line);border-radius:14px;overflow:hidden;
        box-shadow:0 0 40px rgba(0,229,255,.08)}
  .term-bar{display:flex;align-items:center;gap:7px;padding:10px 14px;border-bottom:1px solid var(--line);
            font:12px var(--mono);color:var(--muted)}
  .dot{width:10px;height:10px;border-radius:50%;background:#16324a}
  .term-bar b{margin-left:auto;font-weight:400}
  .log{padding:18px 20px;font:14px/1.9 var(--mono);color:#9ec3e6;min-height:196px}
  .log .ln{display:block;white-space:pre-wrap;word-break:break-word}
  .log .p{color:var(--cyan)} .log .ok{color:var(--green)} .log .v{color:var(--ink)}
  .cursor{display:inline-block;width:8px;height:15px;background:var(--cyan);vertical-align:-2px;animation:blink 1s steps(1) infinite}
  @keyframes blink{50%{opacity:0}}

  /* ---------- ticket ---------- */
  .ticket{margin-top:22px;background:var(--panel);border:1px solid var(--cyan);border-radius:18px;
          box-shadow:0 0 0 1px rgba(0,229,255,.1),0 0 50px rgba(0,229,255,.15);position:relative}
  .ticket-top{padding:24px 26px 20px;display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
  .label{font:12px var(--mono);color:var(--muted);letter-spacing:.1em}
  .tid{font:700 clamp(30px,6vw,42px) var(--mono);color:var(--cyan);text-shadow:0 0 20px rgba(0,229,255,.45);margin-top:4px}
  .copy{background:transparent;color:var(--cyan);border:1px solid var(--line);border-radius:8px;padding:8px 14px;
        font:13px var(--mono);cursor:pointer;transition:.2s}
  .copy:hover,.copy:focus-visible{background:rgba(0,229,255,.1);border-color:var(--cyan);outline:none}

  /* tear-off notch */
  .tear{position:relative;height:0;border-top:2px dashed var(--line);margin:0 18px}
  .tear::before,.tear::after{content:"";position:absolute;top:-13px;width:24px;height:24px;border-radius:50%;
        background:var(--bg);border:1px solid var(--cyan)}
  .tear::before{left:-31px;clip-path:inset(0 0 0 50%)}
  .tear::after{right:-31px;clip-path:inset(0 50% 0 0)}

  .details{padding:22px 26px;display:grid;grid-template-columns:1fr 1fr;gap:18px 28px}
  .details .full{grid-column:1/-1}
  .details dd{margin-top:3px;font-size:17px;font-weight:500;word-break:break-word}
  .details dd.small{font-size:15px;color:#c3d4ea;font-weight:400;line-height:1.6}

  /* stamp */
  .stamp{position:absolute;right:22px;bottom:150px;transform:rotate(-10deg);border:3px solid var(--green);color:var(--green);
         padding:6px 16px;border-radius:8px;font:700 20px var(--mono);letter-spacing:.2em;opacity:0;
         text-shadow:0 0 10px rgba(43,255,154,.6);box-shadow:0 0 14px rgba(43,255,154,.35);background:rgba(3,7,18,.6)}
  .stamp.hit{animation:stamp .55s cubic-bezier(.2,1.6,.4,1) forwards}
  @keyframes stamp{0%{opacity:0;transform:rotate(-10deg) scale(2.6)}100%{opacity:1;transform:rotate(-10deg) scale(1)}}

  /* status tracker (ini memang urutan sebenar) */
  .track{padding:6px 26px 28px}
  .steps{list-style:none;display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:12px}
  .steps li{position:relative;padding-top:22px;font:12px var(--mono);color:#4f6580;text-align:left}
  .steps li::before{content:"";position:absolute;top:0;left:0;right:6px;height:4px;border-radius:4px;background:#12263b}
  .steps li.on{color:var(--green)}
  .steps li.on::before{background:var(--green);box-shadow:0 0 12px var(--green)}
  .steps li.next{color:var(--cyan)}
  .steps li.next::before{background:linear-gradient(90deg,var(--cyan),#12263b);animation:pulse 1.6s ease-in-out infinite}
  @keyframes pulse{50%{opacity:.4}}

  /* actions */
  .actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px;justify-content:center}
  .btn{padding:13px 22px;border-radius:10px;font:700 14px var(--sans);text-decoration:none;border:1px solid var(--cyan);
       color:var(--cyan);background:rgba(0,229,255,.04);transition:.2s}
  .btn:hover,.btn:focus-visible{background:rgba(0,229,255,.14);box-shadow:0 0 22px rgba(0,229,255,.3);transform:translateY(-2px);outline:none}
  .btn.main{background:var(--cyan);color:#001018}
  .btn.main:hover,.btn.main:focus-visible{background:#5df0ff}

  /* reveal states (JS hanya aktif bila ada) */
  .js .ticket,.js .actions{opacity:0;transform:translateY(14px);transition:opacity .6s,transform .6s}
  .js.done .ticket,.js.done .actions{opacity:1;transform:none}
  .js.done .actions{transition-delay:.35s}

  @media (max-width:560px){
    .details{grid-template-columns:1fr}
    .steps{grid-template-columns:1fr 1fr;row-gap:16px}
    .stamp{position:static;display:inline-block;margin:0 26px 18px}
    .sym{display:none}
  }
  @media (prefers-reduced-motion:reduce){
    *,*::before,*::after{animation:none!important;transition:none!important}
    .stamp{opacity:1}
  }
</style>
</head>
<body>
<div class="grid"></div>
<span class="sym s1">&lt;/&gt;</span><span class="sym s2">01</span><span class="sym s3">[ ]</span>
<span class="sym s4">&lt;code&gt;</span><span class="sym s5">{ }</span>

<main>
  <span class="pill">TVETMARA LUMUT // IT SYSTEM</span>
  <h1>Complaint <span>Received</span></h1>
  <p class="sub">Terima kasih, <?= e($name) ?>. Laporan kau sudah selamat sampai ke pasukan IT.</p>

  <!-- Terminal -->
  <section class="term" aria-label="Transmission log">
    <div class="term-bar"><i class="dot"></i><i class="dot"></i><i class="dot"></i><b>tvetmara@lumut:~</b></div>
    <div class="log" id="log" aria-live="polite"></div>
  </section>

  <!-- Ticket -->
  <article class="ticket" id="ticket">
    <div class="ticket-top">
      <div>
        <div class="label">TICKET ID</div>
        <div class="tid" id="tid"><?= e($ticket) ?></div>
      </div>
      <button class="copy" id="copy" type="button">Copy ID</button>
    </div>
    <div class="tear"></div>
    <dl class="details">
      <div><dt class="label">LOCATION</dt><dd><?= e($location) ?></dd></div>
      <div><dt class="label">SUBMITTED</dt><dd><?= e($created) ?></dd></div>
      <div class="full"><dt class="label">PROBLEM</dt><dd><?= e($problem) ?></dd></div>
      <?php if ($desc !== ''): ?>
      <div class="full"><dt class="label">DESCRIPTION</dt><dd class="small"><?= nl2br(e($desc)) ?></dd></div>
      <?php endif; ?>
    </dl>
    <div class="stamp" id="stamp">RECEIVED</div>
    <div class="track">
      <div class="label">PROGRESS</div>
      <ol class="steps">
        <li class="on">1. Received</li>
        <li class="next">2. Reviewing</li>
        <li>3. In progress</li>
        <li>4. Resolved</li>
      </ol>
    </div>
  </article>

  <nav class="actions" aria-label="Next steps">
    <a class="btn main" href="submit_complaint.php">Submit another complaint</a>
    <a class="btn" href="view_complaints.php">Track my complaints</a>
    <a class="btn" href="campus-gallery.html">Campus gallery</a>
    <a class="btn" href="index.html">Back to home</a>
  </nav>
</main>

<script>
(function(){
  var root = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var stamp = document.getElementById('stamp');
  var log = document.getElementById('log');

  // Copy ticket ID
  document.getElementById('copy').addEventListener('click', function(){
    var b = this, t = document.getElementById('tid').textContent.trim();
    (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function(){
      b.textContent = 'Copied ✓';
    }).catch(function(){ b.textContent = t; });
    setTimeout(function(){ b.textContent = 'Copy ID'; }, 1800);
  });

  if (reduce) { stamp.style.opacity = 1; return; }

  // Data dari PHP, dihantar selamat sebagai JSON
  var d = <?= json_encode([
    'name' => $name, 'loc' => $location, 'prob' => $problem, 'id' => $ticket
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  var lines = [
    ['connecting to tvetmara-lumut/it-system', 'OK'],
    ['reading report from ' + d.name, 'OK'],
    ['location locked: ' + d.loc, 'OK'],
    ['issue logged: ' + d.prob, 'OK'],
    ['generating ticket ' + d.id, 'DONE']
  ];

  root.classList.add('js');
  var i = 0;
  function nextLine(){
    if (i >= lines.length) { return finish(); }
    var row = document.createElement('span'); row.className = 'ln';
    var p = document.createElement('span'); p.className = 'p'; p.textContent = '> ';
    var txt = document.createElement('span'); txt.className = 'v';
    var cur = document.createElement('i'); cur.className = 'cursor';
    row.append(p, txt, cur); log.appendChild(row);
    var s = lines[i][0], k = 0;
    (function type(){
      if (k < s.length) { txt.textContent += s.charAt(k++); setTimeout(type, 16); }
      else {
        cur.remove();
        var ok = document.createElement('span'); ok.className = 'ok'; ok.textContent = '  [' + lines[i][1] + ']';
        row.appendChild(ok); i++; setTimeout(nextLine, 220);
      }
    })();
  }
  function finish(){
    var row = document.createElement('span'); row.className = 'ln';
    row.innerHTML = '<span class="p">&gt; </span><span class="ok">transmission complete</span> <i class="cursor"></i>';
    log.appendChild(row);
    setTimeout(function(){ root.classList.add('done'); }, 350);
    setTimeout(function(){ stamp.classList.add('hit'); }, 1000);
  }
  setTimeout(nextLine, 400);
})();
</script>
</body>
</html>