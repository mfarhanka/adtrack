<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">User Management</h1>
        <p class="text-secondary mb-0">Admins can create user accounts for ad operators.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h4 mb-3">Create User</h2>
                <form method="post" action="index.php?action=users" class="vstack gap-3">
                    <div>
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-danger">Create User</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h4 mb-3">Existing Users</h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= e($user['name']) ?></td>
                                <td><?= e($user['username']) ?></td>
                                <td><span class="badge rounded-pill <?= $user['role'] === 'admin' ? 'text-bg-dark' : 'text-bg-light' ?>"><?= e($user['role']) ?></span></td>
                                <td><?= e(substr($user['created_at'], 0, 10)) ?></td>
                                <td class="text-end">
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <form method="post" action="index.php?action=delete-user" onsubmit="return confirm('Delete this user and all ads?');">
                                            <input type="hidden" name="user_id" value="<?= e($user['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>