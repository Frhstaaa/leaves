<?php
/**
 * Standalone Database & Login Diagnoser (Zero-Dependency)
 * Akses: https://www.sgin.co.id/leaves-application/fix-database.php
 */

@ini_set('display_errors', 1);
error_reporting(E_ALL);
if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

$basePath = __DIR__;
if (!file_exists($basePath . '/.env') && file_exists(dirname(__DIR__) . '/.env')) {
    $basePath = dirname(__DIR__);
}

$envFile = $basePath . '/.env';
$envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

preg_match('/DB_HOST=(.*)/', $envContent, $mHost);
preg_match('/DB_PORT=(.*)/', $envContent, $mPort);
preg_match('/DB_DATABASE=(.*)/', $envContent, $mDb);
preg_match('/DB_USERNAME=(.*)/', $envContent, $mUser);
preg_match('/DB_PASSWORD=(.*)/', $envContent, $mPass);

$host = trim($mHost[1] ?? '127.0.0.1');
$port = (int)trim($mPort[1] ?? '3306');
$db   = trim($mDb[1] ?? 'sginco_dbleav_fix');
$user = trim($mUser[1] ?? 'sginco_dbleav_fix');
$pass = trim(trim($mPass[1] ?? '', '"'), "'");

$msg = '';
$connected = false;
$tables = [];
$usersList = [];

// Handle form update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? $host);
    $db   = trim($_POST['db'] ?? $db);
    $user = trim($_POST['user'] ?? $user);
    $pass = trim($_POST['pass'] ?? $pass);

    // Save to .env
    $newEnv = preg_replace('/DB_HOST=.*$/m', "DB_HOST={$host}", $envContent);
    $newEnv = preg_replace('/DB_DATABASE=.*$/m', "DB_DATABASE={$db}", $newEnv);
    $newEnv = preg_replace('/DB_USERNAME=.*$/m', "DB_USERNAME={$user}", $newEnv);
    $newEnv = preg_replace('/DB_PASSWORD=.*$/m', "DB_PASSWORD=\"{$pass}\"", $newEnv);
    @file_put_contents($envFile, $newEnv);
    $envContent = $newEnv;

    // Reset cache
    foreach (glob($basePath . '/bootstrap/cache/*.php') as $f) { @unlink($f); }
    if (function_exists('opcache_reset')) { @opcache_reset(); }
}

// Test connection
try {
    $mysqli = @new mysqli($host, $user, $pass, $db, $port);
    if ($mysqli && !$mysqli->connect_errno) {
        $connected = true;
        $res = $mysqli->query("SHOW TABLES");
        if ($res) {
            while ($row = $res->fetch_array()) {
                $tables[] = $row[0];
            }
        }

        // Check users table
        $uRes = $mysqli->query("SELECT id, name, email, nik, role FROM users LIMIT 10");
        if ($uRes) {
            while ($uRow = $uRes->fetch_assoc()) {
                $usersList[] = $uRow;
            }
        }
        $msg = "✓ KONEKSI BERHASIL! Database '$db' terhubung secara normal.";
    } else {
        $err = $mysqli ? $mysqli->connect_error : 'Koneksi gagal';
        $msg = "❌ KONEKSI GAGAL: $err";
    }
} catch (\Throwable $e) {
    $msg = "❌ ERROR: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN - Database & Login Doctor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8 flex flex-col justify-between font-sans">
    <div class="max-w-3xl mx-auto w-full space-y-6">
        
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <span>🩺</span> SGIN Database Doctor & Login Fixer
            </h1>
            <p class="text-xs text-slate-400 mt-1">Alat diagnosa langsung tanpa ketergantungan Laravel</p>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900 border <?= $connected ? 'border-emerald-500/40' : 'border-rose-500/40' ?> space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Koneksi MySQL:</span>
                <span class="text-xs px-3 py-1 rounded-full font-bold <?= $connected ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                    <?= $connected ? 'Terhubung (OK)' : 'Gagal Konek' ?>
                </span>
            </div>
            
            <div class="p-4 rounded-xl font-mono text-xs <?= $connected ? 'bg-emerald-950/40 text-emerald-300 border border-emerald-800' : 'bg-rose-950/40 text-rose-300 border border-rose-800' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>

            <?php if (!$connected): ?>
            <div class="p-4 rounded-xl bg-amber-950/30 border border-amber-800/50 text-amber-200 text-xs space-y-2">
                <p class="font-bold">⚠️ CARA MEMPERBAIKI DI cPANEL (1 Menit):</p>
                <ol class="list-decimal pl-5 space-y-1">
                    <li>Buka cPanel &gt; menu <b>MySQL Databases</b>.</li>
                    <li>Pada bagian <b>Add User to Database</b>: pilih User <b><?= htmlspecialchars($user) ?></b> dan Database <b><?= htmlspecialchars($db) ?></b> &gt; klik <b>Add</b>.</li>
                    <li>Centang <b>ALL PRIVILEGES</b> &gt; klik <b>Make Changes</b>.</li>
                    <li>Pada <b>Current Users</b>: klik <b>Change Password</b> untuk user <b><?= htmlspecialchars($user) ?></b> dan pastikan passwordnya sama.</li>
                </ol>
            </div>
            <?php endif; ?>

            <?php if ($connected && !empty($usersList)): ?>
            <div class="space-y-2 pt-2 border-t border-slate-800">
                <h3 class="text-xs font-bold text-slate-300 uppercase">Daftar Akun User yang Siap Login:</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-950 text-slate-400">
                            <tr>
                                <th class="p-2">Nama</th>
                                <th class="p-2">Email</th>
                                <th class="p-2">NIK</th>
                                <th class="p-2">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <?php foreach ($usersList as $u): ?>
                            <tr>
                                <td class="p-2 font-bold text-white"><?= htmlspecialchars($u['name']) ?></td>
                                <td class="p-2 text-emerald-400"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="p-2 text-slate-300 font-mono"><?= htmlspecialchars($u['nik']) ?></td>
                                <td class="p-2 text-teal-300"><?= htmlspecialchars($u['role']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-3 pt-4 border-t border-slate-800">
                <h3 class="text-xs font-bold text-slate-300 uppercase">Ubah Kredensial Database (.env):</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] text-slate-400 uppercase font-bold mb-1">Host (127.0.0.1 / localhost)</label>
                        <input type="text" name="host" value="<?= htmlspecialchars($host) ?>" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-400 uppercase font-bold mb-1">Database Name</label>
                        <input type="text" name="db" value="<?= htmlspecialchars($db) ?>" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-400 uppercase font-bold mb-1">Username</label>
                        <input type="text" name="user" value="<?= htmlspecialchars($user) ?>" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-400 uppercase font-bold mb-1">Password</label>
                        <input type="password" name="pass" value="<?= htmlspecialchars($pass) ?>" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white">
                    </div>
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all shadow-lg shadow-emerald-900/40">
                    💾 Simpan &amp; Tes Ulang Koneksi
                </button>
            </form>
        </div>

        <div class="text-center">
            <a href="./login" class="text-xs text-emerald-400 hover:underline font-bold">➔ Kembali ke Halaman Login</a>
        </div>

    </div>
</body>
</html>
