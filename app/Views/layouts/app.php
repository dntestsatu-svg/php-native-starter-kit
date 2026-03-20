<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) ($title ?? config('app.name', 'Starter Kit'))); ?></title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: #f4f5f7;
            color: #111827;
        }

        header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 20px;
        }

        .header-inner {
            max-width: 980px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-weight: 700;
            text-decoration: none;
            color: #111827;
        }

        nav {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: #2563eb;
            font-weight: 600;
        }

        .logout-button {
            border: 0;
            border-radius: 8px;
            padding: 8px 12px;
            background: #111827;
            color: #fff;
            cursor: pointer;
        }

        main {
            max-width: 980px;
            margin: 40px auto;
            padding: 0 20px;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-inner">
            <a class="brand" href="/"><?= e((string) config('app.name', 'Starter Kit')); ?></a>
            <nav>
                <?php if (auth_check()): ?>
                    <a href="/dashboard">Dashboard</a>
                    <form method="POST" action="/logout" style="margin:0;">
                        <?= csrf_field(); ?>
                        <button class="logout-button" type="submit">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Login</a>
                    <a href="/register">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>
        <?= $content ?? ''; ?>
    </main>
</body>

</html>
