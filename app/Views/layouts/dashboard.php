<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) ($title ?? 'Dashboard')); ?> - <?= e((string) config('app.name', 'Starter Kit')); ?></title>
    <style>body{margin:0;font-family:"Segoe UI",sans-serif;background:#f8fafc;color:#0f172a}.wrapper{min-height:100vh;display:grid;grid-template-columns:240px 1fr}aside{background:#0f172a;color:#e2e8f0;padding:24px 16px}aside h2{margin-top:0;font-size:18px}aside a{display:block;padding:8px 10px;margin-bottom:6px;text-decoration:none;color:#cbd5e1;border-radius:8px}aside a:hover{background:#1e293b;color:#fff}.logout-button{margin-top:16px;width:100%;border:1px solid #334155;border-radius:8px;background:#111827;color:#fff;padding:8px 10px;cursor:pointer}.content{padding:24px}.topline{margin-bottom:24px}</style>
</head>

<body>
    <div class="wrapper">
        <aside>
            <h2><?= e((string) config('app.name', 'Starter Kit')); ?></h2>
            <a href="/dashboard">Dashboard</a>
            <a href="/dashboard/users/create">Create User</a>
            <form method="POST" action="/logout">
                <?= csrf_field(); ?>
                <button class="logout-button" type="submit">Logout</button>
            </form>
        </aside>
        <div class="content">
            <div class="topline">
                <strong>Signed in as:</strong>
                <?= e((string) ((auth_user()['email'] ?? 'unknown'))); ?>
            </div>
            <?= $content ?? ''; ?>
        </div>
    </div>
</body>

</html>
