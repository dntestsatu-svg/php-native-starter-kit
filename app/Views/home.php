<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) config('app.name', 'Native Starter')); ?></title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: #f7f7f7;
            color: #1f2937;
        }

        .container {
            max-width: 720px;
            margin: 48px auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
        }

        h1 {
            margin-top: 0;
        }

        .notice {
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .notice.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .notice.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"] {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
        }

        button {
            border: 0;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            padding: 10px 16px;
            cursor: pointer;
        }

        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <main class="container">
        <h1><?= e((string) config('app.name', 'Native Starter')); ?></h1>
        <p>Minimalist MVC starter kit is active with Redis sessions and Redis-backed CSRF tokens.</p>

        <?php if (!empty($success)): ?>
            <div class="notice success"><?= e((string) $success); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="notice error"><?= e((string) $error); ?></div>
        <?php endif; ?>

        <form method="POST" action="/demo/submit">
            <?= csrf_field(); ?>
            <label for="name">Your Name</label>
            <input id="name" name="name" type="text" value="<?= e((string) ($name ?? '')); ?>" autocomplete="off">
            <button type="submit">Submit</button>
        </form>

        <p>Session ID: <code><?= e(session_id()); ?></code></p>
        <p>Health endpoint: <a href="/health">/health</a></p>
    </main>
</body>

</html>