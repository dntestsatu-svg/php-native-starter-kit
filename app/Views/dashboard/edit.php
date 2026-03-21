<section style="max-width:640px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;">
    <h1 style="margin-top:0;">Edit User</h1>
    <p style="color:#334155;">Update account details. Leave password empty to keep current password.</p>
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
    <form method="POST" action="/dashboard/users/update">
        <?= csrf_field(); ?>
        <input type="hidden" name="user_id" value="<?= e((string) ($targetUser['id'] ?? '')); ?>">
        <label for="username" style="display:block;margin:12px 0 6px;">Username</label>
        <input id="username" name="username" type="text" readonly required
            value="<?= e((string) ($targetUser['username'] ?? '')); ?>"
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="name" style="display:block;margin:12px 0 6px;">Name</label>
        <input id="name" name="name" type="text" required
            value="<?= e((string) ($targetUser['name'] ?? '')); ?>"
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="email" style="display:block;margin:12px 0 6px;">Email</label>
        <input id="email" name="email" type="email" required
            value="<?= e((string) ($targetUser['email'] ?? '')); ?>"
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="password" style="display:block;margin:12px 0 6px;">New Password (optional)</label>
        <input id="password" name="password" type="password"
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <label for="password_confirmation" style="display:block;margin:12px 0 6px;">Confirm New Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password"
            style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
        <div style="margin-top:16px;display:flex;gap:8px;">
            <button type="submit"
                style="border:0;background:#0f172a;color:#fff;border-radius:8px;padding:10px 14px;cursor:pointer;">
                Update User
            </button>
            <a href="/dashboard"
                style="text-decoration:none;border:1px solid #cbd5e1;border-radius:8px;padding:10px 14px;color:#0f172a;">
                Cancel
            </a>
        </div>
    </form>
</section>
