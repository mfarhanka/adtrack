<?php
$dueAds = array_values(array_filter($ads, static function ($ad) {
    return $ad['status'] !== 'upcoming';
}));
$upcomingAds = array_values(array_filter($ads, static function ($ad) {
    return $ad['status'] === 'upcoming';
}));
?>
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Advertising Dashboard</h1>
        <p class="text-secondary mb-0">See overdue posts, due-today items, and what is scheduled next.</p>
    </div>
    <a class="btn btn-danger" href="index.php?action=ads">Manage Ads</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small mb-2">Overdue</div>
                <div class="display-6 fw-bold"><?= count_ads_by_status($ads, 'overdue') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small mb-2">Due Today</div>
                <div class="display-6 fw-bold"><?= count_ads_by_status($ads, 'due') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small mb-2">Upcoming</div>
                <div class="display-6 fw-bold"><?= count_ads_by_status($ads, 'upcoming') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">Needs Re-Advertising</h2>
                    <span class="badge text-bg-danger rounded-pill"><?= count($dueAds) ?> items</span>
                </div>

                <?php if ($dueAds === []): ?>
                    <div class="empty-state">No ads are due right now.</div>
                <?php else: ?>
                    <div class="vstack gap-3">
                        <?php foreach ($dueAds as $ad): ?>
                            <div class="ad-card <?= e($ad['status']) ?>">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <div class="small text-uppercase text-secondary fw-semibold"><?= e($ad['platform']) ?></div>
                                        <h3 class="h5 mb-1"><?= e($ad['title']) ?></h3>
                                        <div class="small text-secondary">
                                            Last advertised: <?= e($ad['last_advertised_at']) ?>
                                            <span class="mx-2">|</span>
                                            Next date: <?= e($ad['next_advertise_at']) ?>
                                        </div>
                                    </div>
                                    <span class="badge rounded-pill text-bg-dark">
                                        <?= $ad['status'] === 'overdue' ? abs((int) $ad['days_until']) . ' day(s) late' : 'Due today' ?>
                                    </span>
                                </div>
                                <pre id="ad-copy-<?= e($ad['id']) ?>" class="copy-box mb-3"><?= e($ad['details']) ?></pre>
                                <?php if (isset($ad['photos']) && $ad['photos'] !== []): ?>
                                    <div class="photo-grid mb-3">
                                        <?php foreach ($ad['photos'] as $photo): ?>
                                            <a href="<?= e($photo['path']) ?>" target="_blank" rel="noreferrer" class="photo-card photo-link">
                                                <img src="<?= e($photo['path']) ?>" alt="<?= e(isset($photo['name']) ? $photo['name'] : 'Ad photo') ?>" class="photo-thumb">
                                                <div class="photo-meta"><?= e(isset($photo['name']) ? $photo['name'] : 'Photo') ?></div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($ad['readvertise_link'] !== ''): ?>
                                    <div class="small mb-3">Current link: <a href="<?= e($ad['readvertise_link']) ?>" target="_blank" rel="noreferrer"><?= e($ad['readvertise_link']) ?></a></div>
                                <?php endif; ?>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-copy-target="#ad-copy-<?= e($ad['id']) ?>">Copy details</button>
                                </div>
                                <form method="post" action="index.php?action=mark-advertised" class="row g-2 align-items-end">
                                    <input type="hidden" name="ad_id" value="<?= e($ad['id']) ?>">
                                    <input type="hidden" name="return_to" value="dashboard">
                                    <div class="col-md-8">
                                        <label class="form-label small text-secondary mb-1">Re-Advertise Link</label>
                                        <input type="url" name="readvertise_link" class="form-control form-control-sm" placeholder="https://example.com/listing" value="<?= e($ad['readvertise_link']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="hidden" name="ad_id" value="<?= e($ad['id']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm w-100">Mark Advertised Today</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">Upcoming Schedule</h2>
                    <span class="badge rounded-pill soft-badge"><?= count($upcomingAds) ?> planned</span>
                </div>

                <?php if ($upcomingAds === []): ?>
                    <div class="empty-state">Create ads to start building a schedule.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingAds as $ad): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold"><?= e($ad['title']) ?></div>
                                        <div class="small text-secondary"><?= e($ad['platform']) ?></div>
                                    </div>
                                    <div class="text-end small">
                                        <div class="fw-semibold text-danger"><?= e($ad['next_advertise_at']) ?></div>
                                        <div class="text-secondary">In <?= e((string) $ad['days_until']) ?> day(s)</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>