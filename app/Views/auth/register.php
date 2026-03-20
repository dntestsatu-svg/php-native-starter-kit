<section style="max-width:500px;margin:0 auto;background:#fff;padding:24px;border:1px solid #e5e7eb;border-radius:12px;">
    <h1 style="margin-top:0;">Register</h1>
    <p style="color:#4b5563;">Create your account to access dashboard features.</p>

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

    <form method="POST" action="/register">
        <?= csrf_field(); ?>
        <label for="username" style="display:block;margin:12px 0 6px;">Username</label>
        <input id="username" name="username" type="text" autocomplete="username" required
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="name" style="display:block;margin:12px 0 6px;">Name</label>
        <input id="name" name="name" type="text" autocomplete="name" required
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="email" style="display:block;margin:12px 0 6px;">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="password" style="display:block;margin:12px 0 6px;">Password</label>
        <input id="password" name="password" type="password" autocomplete="off" required
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="password_confirmation" style="display:block;margin:12px 0 6px;">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="off" required
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <button type="submit"
            style="margin-top:16px;border:0;background:#111827;color:#fff;padding:10px 14px;border-radius:8px;cursor:pointer;">
            Register
        </button>
    </form>

    <p style="margin-top:16px;">Already have an account? <a href="/login">Login here</a>.</p>
</section>
