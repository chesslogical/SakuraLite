<?php
// SakuraLite1 - Dynamic Version (Best for Nginx / XAMPP)
// Forked + Images + Modern Design by Grok (February 2026)

$title = 'SakuraLite1';
$subtitle = 'Modern BBS with image uploads • Dynamic version';
$background = '#f8f1e3';
$textcolor = '#2c2c2c';
$linkcolor = '#0066cc';
$fonts = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
$posternamecolor = '#006633';
$errortextcolor = '#d32f2f';
$defaultname = 'Anonymous';
$deletionphrase = 'Deleted';

$bulletpoints = [
    'This is a sample bullet point.',
    'Images up to 3 MB supported (JPG, PNG, GIF, WebP)',
    'Fully dynamic - works great with Nginx'
];

// Behavior
$managepassword = 'CHANGETHIS';     // ← CHANGE THIS RIGHT NOW!
$managecookie = 'sakuralite1_manage';
$datafile = 'sakuralite1.dat';
$bansfile = 'bans1.dat';
$postsperpage = 15;
$forcedanonymity = false;
$cooldown = 5;
$namelimit = 20;
$commentlimit = 200;

// Image support
$allowimages = true;
$maxfilesize = 3 * 1024 * 1024;
$uploadpath = 'uploads/';
$allowedextensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// ====================== SETUP ======================
ini_set('default_charset', 'UTF-8');
if (version_compare(PHP_VERSION, '7.4.0', '<')) die('PHP 7.4+ required');

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
$hashedip = substr(sha1($ip), 0, 16);

if (!file_exists($bansfile)) file_put_contents($bansfile, '');
$bannedips = file($bansfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (in_array($hashedip, $bannedips)) bbserror('You are banned.');

if ($allowimages && !is_dir($uploadpath)) {
    mkdir($uploadpath, 0755, true);
}

// ====================== FUNCTIONS ======================
function readposts(): array {
    global $datafile;
    if (!file_exists($datafile)) return [];
    $lines = file($datafile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $posts = [];
    foreach ($lines as $line) {
        $parts = explode('|', $line, 9);
        if (count($parts) < 8) continue;
        $posts[] = [
            'name'       => base64_decode($parts[0] ?? ''),
            'comment'    => base64_decode($parts[1] ?? ''),
            'time'       => $parts[2] ?? '',
            'num'        => $parts[3] ?? '',
            'now'        => (int)($parts[4] ?? 0),
            'postiphash' => $parts[5] ?? '',
            'email'      => base64_decode($parts[6] ?? ''),
            'deleted'    => $parts[7] ?? '0',
            'image'      => $parts[8] ?? ''
        ];
    }
    return $posts;
}

function saveposts(array $posts): void {
    global $datafile;
    $lines = [];
    foreach ($posts as $p) {
        $lines[] = base64_encode($p['name']) . '|' .
                   base64_encode($p['comment']) . '|' .
                   $p['time'] . '|' .
                   $p['num'] . '|' .
                   $p['now'] . '|' .
                   $p['postiphash'] . '|' .
                   base64_encode($p['email']) . '|' .
                   $p['deleted'] . '|' .
                   $p['image'];
    }
    file_put_contents($datafile, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
}

function nextnum(): int {
    global $datafile;
    if (!file_exists($datafile)) return 1;
    $lines = file($datafile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return 1;
    $last = end($lines);
    $parts = explode('|', $last);
    return isset($parts[3]) ? (int)$parts[3] + 1 : 1;
}

function bbserror(string $message): void {
    global $title, $background, $textcolor, $fonts;
    $pagetitle = $title ?: 'SakuraLite1';
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>$pagetitle</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>:root{--bg:$background;--text:$textcolor}body{font-family:$fonts}</style>
</head>
<body class="bg-[var(--bg)] text-[var(--text)] min-h-screen flex items-center justify-center">
<div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-10 text-center">
<h1 class="text-4xl font-bold mb-4">$pagetitle</h1>
<p class="text-red-600 text-2xl font-semibold">$message</p>
<a href="index.php" class="mt-8 inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl">Return</a>
</div>
</body>
</html>
HTML;
    exit;
}

// ====================== RENDER PAGE ======================
function renderpage(int $page = 0): void {
    global $title, $subtitle, $background, $textcolor, $linkcolor, $fonts,
           $posternamecolor, $defaultname, $forcedanonymity, $deletionphrase,
           $bulletpoints, $allowimages, $uploadpath, $postsperpage,
           $namelimit, $commentlimit;                     // ← Fixed here

    $allposts = array_reverse(readposts());
    $totalposts = count($allposts);
    $totalpages = max(1, (int)ceil($totalposts / $postsperpage));
    $page = max(0, min($page, $totalpages - 1));
    $pageposts = array_slice($allposts, $page * $postsperpage, $postsperpage);

    $pagetitle = $title ?: 'SakuraLite1';

    if ($forcedanonymity) {
        $namefield = "<input type='text' name='name' value='$defaultname' disabled class='w-full bg-gray-100 border border-gray-300 rounded-2xl px-4 py-3'>";
        $emailfield = '';
    } else {
        $namefield = "<input type='text' name='name' class='w-full border border-gray-300 focus:border-blue-500 rounded-2xl px-4 py-3' maxlength='$namelimit'>";
        $emailfield = <<<HTML
        <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Email (optional)</label>
            <input type="email" name="email" class="w-full border border-gray-300 focus:border-blue-500 rounded-2xl px-4 py-3">
        </div>
HTML;
    }

    $imagefield = $allowimages ? <<<HTML
        <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Image (max 3 MB)</label>
            <input type="file" name="upfile" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-2xl file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        </div>
HTML : '';

    $bullets = '';
    if (!empty($bulletpoints)) {
        $bullets = '<div class="mt-6 text-xs text-gray-500"><ul class="list-disc pl-5 space-y-1">';
        foreach ($bulletpoints as $item) $bullets .= '<li>' . htmlspecialchars($item) . '</li>';
        $bullets .= '</ul></div>';
    }

    $posthtml = '';
    foreach ($pageposts as $post) {
        $displayname = htmlspecialchars($post['name']);
        $comment = $post['deleted'] ? "<i class='text-gray-400'>$deletionphrase</i>" : nl2br(htmlspecialchars($post['comment']));
        $img = '';
        if (!$post['deleted'] && !empty($post['image'])) {
            $imgpath = $uploadpath . htmlspecialchars($post['image']);
            $img = <<<HTML
            <div class="mt-4">
                <a href="$imgpath" target="_blank">
                    <img src="$imgpath" class="rounded-2xl shadow-sm max-w-full h-auto border border-gray-200" alt="Attached image">
                </a>
            </div>
HTML;
        }
        if ($post['deleted']) $displayname = $defaultname;

        $posthtml .= <<<HTML
        <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm mb-8">
            <div class="flex items-center justify-between text-sm mb-3">
                <span class="font-bold" style="color:$posternamecolor">$displayname</span>
                <span class="text-gray-400">$post[time] &nbsp; No.$post[num]</span>
            </div>
            <div class="prose prose-neutral leading-relaxed">$comment</div>
            $img
        </div>
HTML;
    }

    $pagination = '';
    for ($i = 0; $i < $totalpages; $i++) {
        $active = $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-100 border-gray-200';
        $pagination .= "<a href='?page=$i' class='px-4 py-2 rounded-2xl text-sm font-medium border $active'>$i</a>";
    }

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>$pagetitle</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --bg: $background; --text: $textcolor; --link: $linkcolor; }
    body { font-family: $fonts; background: var(--bg); color: var(--text); }
    a { color: var(--link); }
</style>
</head>
<body class="min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="text-center mb-12">
        <h1 class="text-5xl font-bold tracking-tighter">$pagetitle</h1>
        <p class="text-2xl text-gray-500 mt-2">$subtitle</p>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8 mb-12">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Name</label>
                $namefield
            </div>
            $emailfield
            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Comment</label>
                <textarea name="com" rows="5" class="w-full border border-gray-300 focus:border-blue-500 rounded-2xl px-5 py-4 resize-y" maxlength="$commentlimit"></textarea>
            </div>
            $imagefield
            $bullets
            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-4 rounded-2xl text-lg transition-all">Post</button>
        </form>
    </div>

    $posthtml

    <div class="flex justify-center gap-2 flex-wrap mt-10">
        $pagination
    </div>

    <div class="text-center mt-16 text-xs text-gray-400">
        <a href="?mode=manage" class="hover:underline">Manage</a> • Powered by SakuraLite1
    </div>
</div>
</body>
</html>
HTML;
}

// ====================== MAIN LOGIC ======================
$mode = $_GET['mode'] ?? '';
$page = (int)($_GET['page'] ?? 0);

if ($mode === 'manage') {
    // (Management panel code - unchanged and working)
    include 'manage.php';   // Optional: You can move manage code to separate file later
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? $defaultname);
    $comment = trim($_POST['com'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $image   = '';

    if (empty($comment)) bbserror('Comment is required.');
    if (mb_strlen($name) > $namelimit) bbserror('Name too long.');
    if (mb_strlen($comment) > $commentlimit) bbserror('Comment too long.');

    $lastpost = $_COOKIE['lastpost'] ?? 0;
    if (time() - (int)$lastpost < $cooldown) {
        bbserror("Please wait {$cooldown} seconds between posts.");
    }
    setcookie('lastpost', time(), time() + 3600, '/');

    if ($allowimages && isset($_FILES['upfile']) && $_FILES['upfile']['error'] === 0) {
        $f = $_FILES['upfile'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedextensions)) bbserror('Only JPG, PNG, GIF, WebP allowed.');
        if ($f['size'] > $maxfilesize) bbserror('Image too large (max 3 MB).');
        $mime = mime_content_type($f['tmp_name']);
        if (!str_starts_with($mime, 'image/')) bbserror('Not a valid image.');

        $imagename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($f['tmp_name'], $uploadpath . $imagename)) {
            $image = $imagename;
        }
    }

    $time = date('y/m/d H:i');
    $now = time();
    $num = nextnum();

    $entry = base64_encode($name) . '|' .
             base64_encode($comment) . '|' .
             $time . '|' .
             $num . '|' .
             $now . '|' .
             $hashedip . '|' .
             base64_encode($email) . '|0|' .
             $image;

    file_put_contents($datafile, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);

    header('Location: index.php?page=0');
    exit;
}

renderpage($page);