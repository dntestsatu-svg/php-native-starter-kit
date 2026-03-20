<section style="max-width:420px;margin:0 auto;background:#fff;padding:24px;border:1px solid #e5e7eb;border-radius:12px;">
    <h1 style="margin-top:0;">Login</h1>
    <p style="color:#4b5563;">Sign in to access your dashboard.</p>

    <?php if (!empty($error)): ?>
        <div style="margin:12px 0;padding:10px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:8px;">
            <?= e((string) $error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="margin:12px 0;padding:10px;border:1px solid #a7f3d0;background:#ecfdf5;color:#065f46;border-radius:8px;">
            <?= e((string) $success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <?= csrf_field(); ?>
        <label for="email" style="display:block;margin:12px 0 6px;">Email</label>
        <input id="email" name="email" type="email" required
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">

        <label for="password" style="display:block;margin:12px 0 6px;">Password</label>
        <input id="password" name="password" type="password" required
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">

        <button type="submit"
            style="margin-top:16px;border:0;background:#111827;color:#fff;padding:10px 14px;border-radius:8px;cursor:pointer;">
            Login
        </button>
    </form>

    <p style="margin-top:16px;">No account yet? <a href="/register">Register here</a>.</p>
</section>
