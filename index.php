<?php
// ==========================================================
// 魔域怀旧网页版 · F3官方稳定版 · 非洲专属优化
// 框架：Fat-Free Framework 3.7.4 | 核心90KB | 兼容PHP5.3–8.2
// 特点：单文件框架｜内置压缩/缓存｜弱网秒开｜50倍倍率｜三地图
// ==========================================================

// ===== 🔥 非洲弱网专属优化（必须放最顶部！）=====
@ini_set('default_socket_timeout', 60);     // 超时拉长到60秒，信号波动不断开
@ini_set('max_execution_time', 120);        // 执行时间放宽，慢网也能跑完
@ini_set('memory_limit', '128M');          // 低内存服务器友好
if (!ob_start("ob_gzhandler")) ob_start(); // Gzip压缩 → 传输体积减70%！生命线
header('Cache-Control: public, max-age=604800'); // 7天缓存 → 第二次访问几乎不耗流量
header('Connection: keep-alive');           // 长连接 → 不用反复握手
header('Access-Control-Allow-Origin: *');   // 跨域开放 → 国际线路直连无阻碍

// ===== 配置区 · 一键改 =====
define('EXP_RATE', 50);       // 经验倍率 1–100随便改
define('DROP_RATE', 50);      // 爆率倍率 1–100随便改
define('DB_FILE', __DIR__ . '/data/game.db'); // 数据库位置，自动生成

// ===== 引入F3核心 =====
require 'lib/base.php';
$f3 = Base::instance();

// ===== 数据库初始化（SQLite，不用装数据库服务！）=====
$db = new SQLite3(DB_FILE);
$db->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE, password TEXT, level INTEGER DEFAULT 1,
    exp INTEGER DEFAULT 0, gold INTEGER DEFAULT 10000, stone INTEGER DEFAULT 0,
    map TEXT DEFAULT "leiming", x INTEGER DEFAULT 150, y INTEGER DEFAULT 200, reg_time INTEGER
)');
$db->exec('CREATE TABLE IF NOT EXISTS items (
    id INTEGER PRIMARY KEY AUTOINCREMENT, uid INTEGER, name TEXT, type TEXT, attr TEXT, count INTEGER DEFAULT 1
)');
$db->exec('CREATE TABLE IF NOT EXISTS pets (
    id INTEGER PRIMARY KEY AUTOINCREMENT, uid INTEGER, name TEXT DEFAULT "吉祥噜噜", star INTEGER DEFAULT 1, level INTEGER DEFAULT 1
)');

// ===== 地图数据 · 只保留经典三图，加载快 =====
$maps = [
    'leiming'   => ['name'=>'雷鸣大陆','w'=>800,'h'=>600,'monsters'=>[
        ['name'=>'鹿角兽','lv'=>1,'hp'=>80,'exp'=>10,'drop'=>'金币,木剑'],
        ['name'=>'巨杰士','lv'=>5,'hp'=>150,'exp'=>25,'drop'=>'金币,布衣'],
        ['name'=>'地精','lv'=>10,'hp'=>300,'exp'=>50,'drop'=>'金币,铁剑']
    ]],
    'kanuosa'   => ['name'=>'卡诺萨城','w'=>1000,'h'=>800,'monsters'=>[]],
    'shuxin'    => ['name'=>'树心城','w'=>900,'h'=>700,'monsters'=>[
        ['name'=>'冰眼魔狼','lv'=>15,'hp'=>500,'exp'=>80,'drop'=>'金币,幻兽蛋'],
        ['name'=>'绿魔精','lv'=>20,'hp'=>700,'exp'=>120,'drop'=>'精品装备']
    ]]
];

// ===== 商城 · 全免费，0魔石领取 =====
$mall_items = [
    ['id'=>1,'name'=>'速效生命药','price'=>0,'desc'=>'恢复500HP'],
    ['id'=>2,'name'=>'速效魔力药','price'=>0,'desc'=>'恢复500MP'],
    ['id'=>3,'name'=>'吉祥噜噜蛋','price'=>0,'desc'=>'初代幻兽'],
    ['id'=>4,'name'=>'精品+12武器','price'=>0,'desc'=>'毕业武器']
];

session_start();

// ===== F3路由系统（比Slim更简洁，自带优化）=====
$f3->route('GET /', function() {
    echo <<<HTML
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>魔域怀旧版 - 2007经典复刻</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Microsoft YaHei",sans-serif}
body{background:#000 url('https://picsum.photos/id/1015/1200/800') center/cover}
.wrap{width:420px;margin:120px auto;background:rgba(0,0,0,0.85);padding:40px;border-radius:12px;border:2px solid #d4af37}
h1{text-align:center;color:#ffd700;margin-bottom:30px}
input{width:100%;height:45px;margin:10px 0;padding:0 15px;background:#1a1a1a;border:1px solid #666;color:#fff;border-radius:6px}
button{width:100%;height:50px;background:linear-gradient(180deg,#d4af37,#b8860b);border-radius:6px;color:#000;font-weight:bold;font-size:18px;margin-top:15px;cursor:pointer}
.tip{color:#aaa;text-align:center;margin-top:20px;font-size:14px} a{color:#ffd700;text-decoration:none}
</style></head><body>
    <div class="wrap"><h1>⚔️ 魔域怀旧版 ⚔️</h1>
        <form method="post" action="/login">
            <input type="text" name="user" placeholder="账号" required>
            <input type="password" name="pass" placeholder="密码" required>
            <button type="submit">进入游戏</button>
            <div class="tip">还没账号？<a href="/reg">立即注册</a> · {$GLOBALS['EXP_RATE']}倍经验 · 免费商城</div>
        </form>
    </div>
</body></html>
HTML;
});

$f3->route('GET /reg', function() {
    echo <<<HTML
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">
<title>注册账号</title><style>body{background:#000;color:#fff;padding:50px;text-align:center}
form{max-width:400px;margin:0 auto} input{width:100%;padding:10px;margin:8px 0;background:#222;border:1px solid #666;color:#fff}
button{padding:10px 30px;background:#d4af37;border:none;border-radius:4px;color:#000;font-weight:bold}</style></head><body>
<h1>📝 注册新账号</h1><form method="post" action="/doReg">
<input type="text" name="user" placeholder="账号（≥3位）" required>
<input type="password" name="pass" placeholder="密码（≥6位）" required>
<button type="submit">注册</button>
</form><p style="margin-top:20px"><a href="/" style="color:#ffd700">返回登录</a></p>
</body></html>
HTML;
});

$f3->route('POST /doReg', function() use ($db) {
    $u = trim($_POST['user']); $p = trim($_POST['pass']);
    if(strlen($u)<3 || strlen($p)<6){exit('账号≥3位/密码≥6位 <a href="/reg">返回</a>');}
    if($db->querySingle("SELECT id FROM users WHERE username='$u'")){exit('账号已存在 <a href="/reg">返回</a>');}
    $db->exec("INSERT INTO users (username,password,reg_time) VALUES ('$u','".md5($p)."',".time().")");
    exit('注册成功！<a href="/">去登录</a>');
});

$f3->route('POST /login', function() use ($db) {
    $u = trim($_POST['user']); $p = md5(trim($_POST['pass']));
    $res = $db->querySingle("SELECT id FROM users WHERE username='$u' AND password='$p'");
    if(!$res){exit('账号或密码错误 <a href="/">返回</a>');}
    $_SESSION['uid'] = $res;
    Base::instance()->redirect('/game');
});

$f3->route('GET /game', function() use ($db,$maps) {
    if(!isset($_SESSION['uid'])){Base::instance()->redirect('/');}
    $uid = $_SESSION['uid'];
    $user = $db->querySingle("SELECT * FROM users WHERE id=$uid",true);
    $map = $maps[$user['map']];
    echo <<<HTML
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">
<title>魔域 - {$map['name']}</title><style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#111;color:#fff;font-family:"Microsoft YaHei"}
#game{width:100%;height:100vh;position:relative;overflow:hidden;background:url('https://picsum.photos/id/1036/1600/900') center/cover}
.ui{position:absolute;z-index:10;background:rgba(0,0,0,0.7);border:1px solid #d4af37;border-radius:8px;padding:10px}
.top{top:10px;left:10px;right:10px;display:flex;justify-content:space-between}
.left{top:60px;left:10px;width:200px;height:calc(100vh - 80px)}
.right{top:60px;right:10px;width:220px;height:calc(100vh - 80px)}
.btn{background:#b8860b;border:none;padding:8px 12px;border-radius:4px;color:#000;margin:5px;cursor:pointer;font-weight:bold;text-decoration:none;display:inline-block}
.btn:hover{background:#ffd700}
.monster{position:absolute;width:40px;height:40px;background:#8b0000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;transition:.2s}
.monster:hover{transform:scale(1.2);box-shadow:0 0 10px red}
hr{border-color:#444;margin:10px 0}
</style></head><body>
    <div id="game">
        <div class="ui top">
            <div>👤 {$user['username']} | Lv.{$user['level']} | 💰 {$user['gold']} | 💎 {$user['stone']}</div>
            <div>🗺️ {$map['name']} | 经验倍率:{$GLOBALS['EXP_RATE']}x</div>
            <div><a href="/mall" class="btn">🛒 免费商城</a> <a href="/logout" class="btn">退出</a></div>
        </div>
        <div class="ui left">
            <h3>📦 功能菜单</h3>
            <a href="/map?to=leiming" class="btn">雷鸣大陆</a>
            <a href="/map?to=kanuosa" class="btn">卡诺萨城</a>
            <a href="/map?to=shuxin" class="btn">树心城</a>
            <hr>
            <h3>🐾 幻兽</h3>
            <div style="padding:5px">吉祥噜噜 ★{$db->querySingle("SELECT star FROM pets WHERE uid=$uid") ?: 1} 星</div>
        </div>
        <div class="ui right">
            <h3>📢 公告</h3>
            <p style="font-size:13px;line-height:1.5">欢迎来到2007怀旧版！<br>✅ 经验{$GLOBALS['EXP_RATE']}倍 · 爆率{$GLOBALS['DROP_RATE']}倍<br>✅ 商城全免费 · 无氪金点<br>✅ 仅保留经典三图 · 原汁原味</p>
        </div>
        <script>
            const monsters = {$map['monsters']};
            monsters.forEach(m=>{
                let x = Math.random()*700+50; let y = Math.random()*500+80;
                document.write(`<div class="monster" style="left:${x}px;top:${y}px" onclick="alert('击杀成功！经验+${m.exp*{$GLOBALS['EXP_RATE']}}')">${m.name.substr(0,2)}</div>`);
            });
        </script>
    </div>
</body></html>
HTML;
});

$f3->route('GET /map', function() use ($db,$maps) {
    if(!isset($_SESSION['uid'])){Base::instance()->redirect('/');}
    $to = $_GET['to'] ?? 'leiming';
    if(!isset($maps[$to])){exit('地图不存在');}
    $db->exec("UPDATE users SET map='$to' WHERE id=".$_SESSION['uid']);
    Base::instance()->redirect('/game');
});

$f3->route('GET /mall', function() use ($mall_items) {
    if(!isset($_SESSION['uid'])){Base::instance()->redirect('/');}
    echo <<<HTML
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">
<title>免费商城</title><style>body{background:#111;color:#fff;padding:20px}
.item{background:#222;padding:15px;margin:10px 0;border-radius:8px;border:1px solid #ffd700}
.btn{background:#b8860b;border:none;padding:8px 15px;border-radius:4px;color:#000;font-weight:bold;text-decoration:none;display:inline-block}</style></head>
<body>
    <h1>🛒 免费商城 · 全商品0魔石</h1>
HTML;
    foreach($mall_items as $i){
        echo "<div class='item'><b>{$i['name']}</b> — {$i['desc']} <button class='btn' style='float:right'>领取</button></div>";
    }
    echo '<br><a href="/game" class="btn">返回游戏</a></body></html>';
});

$f3->route('GET /logout', function() {
    session_destroy();
    Base::instance()->redirect('/');
});

// ===== 启动框架 =====
$f3->run();
?>
