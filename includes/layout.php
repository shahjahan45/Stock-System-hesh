<?php
declare(strict_types=1);
function render_header(string $title, string $active='dashboard'): void { global $config,$pdo; $u=user(); $q=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE (user_id=? OR user_id IS NULL) AND read_at IS NULL');$q->execute([$u['id']??0]);$u['unread']=(int)$q->fetchColumn();$flashes=take_flashes(); ?>
<!doctype html><html lang="en" data-theme="light"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#6d5dfc"><title><?=e($title)?> · <?=e($config['app']['name'])?></title>
<link rel="manifest" href="<?=app_url('manifest.json')?>"><link rel="icon" href="<?=app_url('assets/icons/icon.svg')?>">
<link rel="stylesheet" href="<?=app_url('assets/css/app.css?v=1.8.0')?>">
<link rel="stylesheet" href="<?=app_url('assets/css/mobile.css?v=1.8.0')?>">
<link rel="stylesheet" href="<?=app_url('assets/css/v17.css?v=1.8.0')?>">
<script>window.APP_URL=<?=json_encode($config['app']['url'])?>;try{document.documentElement.dataset.theme=localStorage.getItem('theme')||'light'}catch(e){}</script></head>
<body><div class="app-shell">
<aside class="sidebar" id="sidebar"><a class="brand" href="<?=app_url('dashboard.php')?>"><span class="brand-mark">HF</span><span><b>HESH / FRN</b><small>Operations Suite</small></span></a>
<nav class="side-nav">
<?php $groups=[
'OVERVIEW'=>[['dashboard','dashboard.php','grid','Dashboard'],['activity','daily-activity.php','pulse','Daily Activity']],
'DAILY OPERATIONS'=>[['sales','sales.php','receipt','Daily Sales'],['receiving','receiving.php','inbox','Received Stock'],['wastage','wastage.php','trash','Wastage']],
'STOCK'=>[['inventory','inventory.php','boxes','Available Stock'],['products','products.php','package','Raw Items']],
'SETUP'=>[['branches','branches.php','pin','Branches'],['brands','brands.php','layers','Brands'],['users','users.php','shield','Users & Roles']],
'REPORTING'=>[['reports','reports.php','chart','Reports'],['audit','audit-logs.php','history','Audit Logs']],
'ACCOUNT'=>[['settings','settings.php','settings','Settings'],['profile','profile.php','user','Profile']]
]; foreach($groups as $label=>$items): ?><span class="nav-label"><?=e($label)?></span><?php foreach($items as [$key,$href,$icon,$text]): ?><a class="nav-item <?=$active===$key?'active':''?>" href="<?=app_url($href)?>"><i data-icon="<?=$icon?>"></i><span><?=e($text)?></span></a><?php endforeach; endforeach; ?></nav>
<a class="logout" href="<?=app_url('logout.php')?>"><i data-icon="logout"></i><span>Sign out</span></a></aside>
<main class="main"><header class="topbar"><button class="icon-btn desktop-only" data-sidebar aria-label="Toggle sidebar"><i data-icon="menu"></i></button><button class="search-trigger"><i data-icon="search"></i><span>Search anything…</span><kbd>Ctrl K</kbd></button><div class="top-actions"><button class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i data-icon="moon"></i></button><a class="icon-btn has-badge" href="<?=app_url('notifications.php')?>" aria-label="Notifications"><i data-icon="bell"></i><b><?=e((string)($u['unread']??0))?></b></a><a class="avatar" href="<?=app_url('profile.php')?>"><span><?=e(strtoupper(substr($u['name']??'A',0,1)))?></span><em><b><?=e($u['name']??'Admin')?></b><small><?=e($u['role_name']??'Administrator')?></small></em></a></div></header>
<section class="content"><?php foreach($flashes as $f): ?><div class="toast <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endforeach; ?>
<?php }
function render_footer(string $active='dashboard'): void { ?>
</section></main></div>
<nav class="mobile-nav"><a class="<?=$active==='dashboard'?'active':''?>" href="<?=app_url('dashboard.php')?>"><i data-icon="grid"></i><span>Home</span></a><a class="<?=$active==='receiving'?'active':''?>" href="<?=app_url('receiving.php?action=create')?>"><i data-icon="inbox"></i><span>Receive</span></a><a class="quick-fab <?=$active==='sales'?'active':''?>" href="<?=app_url('sales.php?action=create')?>"><i data-icon="receipt"></i><span>Sales</span></a><a class="<?=$active==='products'?'active':''?>" href="<?=app_url('products.php')?>"><i data-icon="package"></i><span>Items</span></a><a class="<?=$active==='profile'?'active':''?>" href="<?=app_url('profile.php')?>"><i data-icon="user"></i><span>Profile</span></a></nav>
<div class="sheet-backdrop" data-sheet-close></div><section class="quick-sheet"><div class="sheet-handle"></div><h2>Quick entry</h2><div class="quick-grid"><a href="<?=app_url('sales.php?action=create')?>"><i data-icon="receipt"></i>Daily Sales</a><a href="<?=app_url('receiving.php?action=create')?>"><i data-icon="inbox"></i>Receive Raw Item</a><a href="<?=app_url('products.php?action=create')?>"><i data-icon="package"></i>Add Raw Item</a><a href="<?=app_url('wastage.php')?>"><i data-icon="trash"></i>Wastage</a><a href="<?=app_url('inventory.php')?>"><i data-icon="boxes"></i>Available Stock</a><a href="<?=app_url('inventory.php?action=adjust')?>"><i data-icon="sliders"></i>Adjustment</a></div></section>
<div class="command" hidden><div class="command-box"><input placeholder="Search products, references, people…" autofocus><div class="command-results"><p>Start typing to search the system.</p></div></div></div>
<script src="<?=app_url('assets/js/app.js?v=1.8.0')?>"></script></body></html><?php }
