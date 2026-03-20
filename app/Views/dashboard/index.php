<section>
    <h1 style="margin:0 0 10px;">Dashboard</h1>
    <p style="margin-top:0;color:#334155;">This area is protected by `AuthMiddleware`.</p>

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

    <div style="margin:18px 0;padding:14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">
        <strong>Current User</strong>
        <div style="margin-top:8px;">
            <?= e((string) ($user['name'] ?? '-')); ?> (<?= e((string) ($user['email'] ?? '-')); ?>)
        </div>
    </div>

    <h2 style="margin-top:24px;">Users</h2>
    <div style="overflow-x:auto;background:#fff;border:1px solid #e2e8f0;border-radius:10px;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;text-align:left;">
                    <th style="padding:10px;border-bottom:1px solid #e2e8f0;">ID</th>
                    <th style="padding:10px;border-bottom:1px solid #e2e8f0;">Username</th>
                    <th style="padding:10px;border-bottom:1px solid #e2e8f0;">Name</th>
                    <th style="padding:10px;border-bottom:1px solid #e2e8f0;">Email</th>
                    <th style="padding:10px;border-bottom:1px solid #e2e8f0;">Bio</th>
                    <th style="padding:10px;border-bottom:1px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="padding:12px;">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $listedUser): ?>
                        <tr>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?= e((string) ($listedUser['id'] ?? '')); ?></td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?= e((string) ($listedUser['username'] ?? '')); ?></td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?= e((string) ($listedUser['name'] ?? '')); ?></td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?= e((string) ($listedUser['email'] ?? '')); ?></td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?= e((string) ($listedUser['profile']['bio'] ?? '-')); ?></td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                                <a href="/dashboard/users/edit?user_id=<?= e((string) ($listedUser['id'] ?? '')); ?>">Edit</a>
                                <form method="POST" action="/dashboard/users/delete" style="display:inline-block;margin-left:8px;">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="user_id" value="<?= e((string) ($listedUser['id'] ?? '')); ?>">
                                    <button type="submit"
                                        style="border:0;background:#ef4444;color:#fff;border-radius:6px;padding:6px 10px;cursor:pointer;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
