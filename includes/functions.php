<?php
declare(strict_types=1);

function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}

function read_json(string $f,array $d=[]):array{
    if(!is_file($f))return $d;
    $c=file_get_contents($f);
    $x=$c===false?null:json_decode($c,true);
    return is_array($x)?$x:$d;
}

function write_json(string $f,array $d):void{
    $tmp=$f.'.tmp';
    $j=json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    if($j===false||file_put_contents($tmp,$j,LOCK_EX)===false||!rename($tmp,$f)){
        throw new RuntimeException('Daten konnten nicht gespeichert werden.');
    }
}

function is_installed():bool{
    return !empty(users())||file_exists(DATA_DIR.'/install.lock');
}

function require_installed():void{
    if(!is_installed()){
        if(basename($_SERVER['SCRIPT_NAME']??'')!=='install.php'){
            header('Location: install.php');
            exit;
        }
    }
}

function users():array{
    return read_json(DATA_DIR.'/users.json');
}

function save_users(array $d):void{
    write_json(DATA_DIR.'/users.json',$d);
}

function current_user():?array{
    $id=$_SESSION['user_id']??null;
    if(!$id)return null;

    foreach(users() as $u){
        if(($u['id']??'')===$id)return $u;
    }

    return null;
}

function require_login(): array
{
    $u = current_user();

    if (!$u) {
        header('Location: login.php');
        exit;
    }

    $now = time();
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);

    // Automatischer Logout nach 30 Minuten Inaktivität
    if ($lastActivity > 0 && ($now - $lastActivity) >= SESSION_TIMEOUT) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        session_destroy();

        header('Location: login.php?timeout=1');
        exit;
    }

    // Aktivität aktualisieren
    $_SESSION['last_activity'] = $now;

    return $u;
}

function is_admin(?array $u=null):bool{
    return (($u??current_user())['role']??'user')==='admin';
}

function require_admin():array{
    $u=require_login();

    if(!is_admin($u)){
        http_response_code(403);
        exit('Zugriff verweigert.');
    }

    return $u;
}

function files():array{
    return read_json(DATA_DIR.'/files.json');
}

function save_files(array $d):void{
    write_json(DATA_DIR.'/files.json',$d);
}

function getLatestUploads(array $files,int $limit=5):array{
    usort($files,fn($a,$b)=>strcmp($b['uploaded_at']??'',$a['uploaded_at']??''));
    return array_slice($files,0,$limit);
}

function getMostDownloaded(array $files,int $limit=5):array{
    usort($files,function($a,$b){
        $aD=(int)($a['downloads']??0);
        $bD=(int)($b['downloads']??0);
        return $aD!==$bD?$bD<=>$aD:strcmp($b['uploaded_at']??'',$a['uploaded_at']??'');
    });

    return array_slice($files,0,$limit);
}

function getRecentlyUpdated(array $files,int $limit=5):array{
    $files=array_filter($files,fn($f)=>!empty($f['updated_at']));
    usort($files,fn($a,$b)=>strcmp($b['updated_at']??'',$a['updated_at']??''));
    return array_slice($files,0,$limit);
}
function categories():array{
    $file=DATA_DIR.'/categories.json';

    if(!is_file($file))write_json($file,[]);

    $result=[];

    foreach(read_json($file) as $c){
        if(!is_array($c))continue;

        $name=trim((string)($c['name']??''));
        if($name==='')continue;

        $subs=[];

        foreach((array)($c['subcategories']??[]) as $s){
            $s=trim((string)$s);

            if($s!==''&&!in_array($s,$subs,true))
                $subs[]=$s;
        }

        $result[]=[
            'name'=>$name,
            'subcategories'=>$subs
        ];
    }

    return $result;
}

function save_categories(array $categories):void{
    $result=[];

    foreach($categories as $c){
        if(!is_array($c))continue;

        $name=trim((string)($c['name']??''));
        if($name==='')continue;

        $subs=[];

        foreach((array)($c['subcategories']??[]) as $s){
            $s=trim((string)$s);

            if($s!==''&&mb_strlen($s)<=50&&!in_array($s,$subs,true))
                $subs[]=$s;
        }

        $result[]=[
            'name'=>$name,
            'subcategories'=>$subs
        ];
    }

    write_json(DATA_DIR.'/categories.json',$result);
}

function getSubcategories(string $category):array{
    foreach(categories() as $c){
        if(($c['name']??'')===$category)
            return (array)($c['subcategories']??[]);
    }

    return [];
}

function categoryExists(string $name):bool{
    foreach(categories() as $c){
        if(($c['name']??'')===$name)return true;
    }

    return false;
}

function subcategoryExists(string $category,string $subcategory):bool{
    return in_array($subcategory,getSubcategories($category),true);
}

function csrf_token():string{
    if(empty($_SESSION['csrf']))
        $_SESSION['csrf']=bin2hex(random_bytes(32));

    return $_SESSION['csrf'];
}

function verify_csrf():void{
    $t=$_POST['csrf']??'';

    if(!is_string($t)||!hash_equals($_SESSION['csrf']??'',$t)){
        http_response_code(403);
        exit('Ungültige Anfrage.');
    }
}

function flash(?string $m=null):?string{
    if($m!==null){
        $_SESSION['flash']=$m;
        return null;
    }

    $m=$_SESSION['flash']??null;
    unset($_SESSION['flash']);

    return $m;
}

function format_bytes(int $b):string{
    $u=['B','KB','MB','GB'];
    $i=0;
    $s=(float)$b;

    while($s>=1024&&$i<3){
        $s/=1024;
        $i++;
    }

    return number_format($s,$i?1:0,',','.').' '.$u[$i];
}

function tags_from_input(string $s):array{
    $r=[];

    foreach(preg_split('/[,\n]+/',$s)?:[] as $t){
        $t=trim($t);

        if($t!==''&&mb_strlen($t)<=30)
            $r[]=$t;
    }

    return array_slice(array_values(array_unique($r)),0,10);
}

function login_attempts_file():string{
    return DATA_DIR.'/login_attempts.json';
}

function login_attempts():array{
    $f=login_attempts_file();
    return is_file($f)?read_json($f):[];
}

function save_login_attempts(array $data):void{
    if(!is_dir(DATA_DIR))
        mkdir(DATA_DIR,0750,true);

    write_json(login_attempts_file(),$data);
}

function login_is_locked(string $username):bool{
    $attempts=login_attempts();
    $key=strtolower(trim($username));

    if(!isset($attempts[$key]))return false;

    $until=(int)($attempts[$key]['locked_until']??0);

    if(!$until)return false;

    if($until>time())return true;

    unset($attempts[$key]);
    save_login_attempts($attempts);

    return false;
}

function record_login_failure(string $username):void{
    $attempts=login_attempts();
    $key=strtolower(trim($username));

    if(!isset($attempts[$key])||!is_array($attempts[$key])){
        $attempts[$key]=[
            'attempts'=>0,
            'locked_until'=>0
        ];
    }

    $attempts[$key]['attempts']=(int)($attempts[$key]['attempts']??0)+1;

    if($attempts[$key]['attempts']>=5)
        $attempts[$key]['locked_until']=time()+300;

    save_login_attempts($attempts);
}
function client_ip():string{
    return $_SERVER['REMOTE_ADDR']??'unknown';
}

function login_ip_key():string{
    return 'ip:'.client_ip();
}

function login_is_ip_locked():bool{
    return login_is_locked(login_ip_key());
}

function record_login_ip_failure():void{
    record_login_failure(login_ip_key());
}

function clear_login_ip_failures():void{
    clear_login_failures(login_ip_key());
}

function clear_login_failures(string $username):void{
    $attempts=login_attempts();
    $key=strtolower(trim($username));

    if(isset($attempts[$key])){
        unset($attempts[$key]);
        save_login_attempts($attempts);
    }
}

function max_upload_size_text(): string
{
    $size = format_bytes(MAX_UPLOAD_BYTES);

    return str_replace(
        ',0 ',
        ' ',
        $size
    );
}

function layout_start(
    string $title,
    bool $showNav=true,
    bool $preview=false,
    array $meta=[]
):void{

    $meta=array_merge([
        'title'=>$title,
        'description'=>'',
        'keywords'=>'',
        'author'=>'',
        'robots'=>'index,follow'
    ],$meta);

    $u=current_user();
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($meta['title'])?></title>
<?php if($meta['description']!==''): ?>
<meta name="description" content="<?=e($meta['description'])?>">
<?php endif; ?>
<?php if($meta['keywords']!==''): ?>
<meta name="keywords" content="<?=e($meta['keywords'])?>">
<?php endif; ?>
<link rel="stylesheet" href="assets/style.css">
<link rel="alternate" type="application/rss+xml" title="moziloCMS Downloads" href="rss.php">
<script src="assets/scripts.js" defer></script>
</head>

<body>
<header class="topbar">
<a class="brand" href="index.php"><?=e(lang('platform_name'))?></a>

<?php if($showNav): ?>
<nav aria-label="Hauptnavigation">

<?php if($u && !$preview): ?>
<a href="index.php?preview=1"><?=e(lang('preview'))?></a>
<a href="dashboard.php"><?=e(lang('dashboard'))?></a>
<a href="upload.php"><?=e(lang('upload'))?></a>

<?php if(is_admin($u)): ?>
<a href="admin.php"><?=e(lang('admin'))?></a>
<?php endif; ?>

<a href="logout.php"><?=e(lang('logout'))?></a>

<?php elseif($u && $preview): ?>
<a href="index.php?preview=1"><?=e(lang('files'))?></a>
<a href="plugins.php?preview=1"><?=e(lang('plugins'))?></a>
<a href="layouts.php?preview=1"><?=e(lang('layouts'))?></a>
<a href="dashboard.php">← <?=e(lang('dashboard'))?></a>


<?php else: ?>
<a href="index.php"><?=e(lang('files'))?></a>
<a href="plugins.php"><?=e(lang('plugins'))?></a>
<a href="layouts.php"><?=e(lang('layouts'))?></a>
<a href="login.php"><?=e(lang('login'))?></a>
<a href="register.php"><?=e(lang('register'))?></a>

<?php endif; ?>
</nav>
<?php endif; ?>

</header>

<main class="container">

<?php if($m=flash()): ?>
<div class="notice" role="status"><?=e($m)?></div>
<?php endif; ?>
<dialog id="confirmDialog">
    <p id="confirmMessage"></p>
    <div class="dialog-actions">
        <button type="button" class="button secondary" id="cancelConfirm"><?= e(lang('no')) ?></button>
        <button type="button" class="button danger" id="confirmAction"><?= e(lang('yes')) ?> </button>
    </div>
</dialog>
<?php
}
function layout_end():void{
?>
</main>

<footer>
<?=e(lang('platform_name'))?> – <?=e(APP_VERSION)?>
</footer>

</body>
</html>
<?php
}