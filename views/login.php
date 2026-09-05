<div class="row justify-content-center">
    <div class="col-lg-5 col-xl-4">
        <div class="card auth-card border-0 shadow-lg">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4 text-center">
                    <span class="badge rounded-pill text-bg-danger px-3 py-2 mb-3">Advertise Smarter</span>
                    <h1 class="h3 mb-2">Sign in to AdTrack</h1>
                    <p class="text-secondary mb-0">Track every post and know exactly when to re-advertise it.</p>
                </div>
                <form method="post" action="index.php?action=login" class="vstack gap-3">
                    <div>
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control form-control-lg" required>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>
                    <button type="submit" class="btn btn-danger btn-lg w-100">Login</button>
                </form>
                <div class="small text-secondary mt-4 pt-3 border-top">
                    Default admin login: <strong>admin</strong> / <strong>admin123</strong>
                </div>
                <?php if ($speedLoginUsers !== []): ?>
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <span class="fw-semibold">Speed login</span>
                            <span class="badge text-bg-warning">Testing only</span>
                        </div>
                        <div class="row g-2">
                            <?php foreach ($speedLoginUsers as $role => $user): ?>
                                <div class="col-sm-6">
                                    <form method="post" action="index.php?action=login">
                                        <input type="hidden" name="speed_role" value="<?= e($role) ?>">
                                        <button type="submit" class="btn btn-outline-danger w-100">
                                            <?= e(ucfirst($role)) ?>
                                            <span class="d-block small opacity-75"><?= e($user['name']) ?></span>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
