<?php
$platforms = platform_options();
$hydratedAds = hydrate_ads($ads);
?>
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Ad Library</h1>
        <p class="text-secondary mb-0">Store ad copy, track platforms, and control re-advertising schedules.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h4 mb-3"><?= $editingAd !== null ? 'Edit Ad' : 'Create Ad' ?></h2>
                <form method="post" action="index.php?action=ads" class="vstack gap-3" enctype="multipart/form-data">
                    <?php if ($editingAd !== null): ?>
                        <input type="hidden" name="ad_id" value="<?= e($editingAd['id']) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="form-label">Ad Title</label>
                        <input type="text" name="title" class="form-control" required value="<?= e($editingAd['title'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">Platform</label>
                        <select name="platform" class="form-select" required>
                            <option value="">Choose platform</option>
                            <?php foreach ($platforms as $platform): ?>
                                <option value="<?= e($platform) ?>" <?= (($editingAd['platform'] ?? '') === $platform) ? 'selected' : '' ?>>
                                    <?= e(ucwords($platform)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Ad Details</label>
                        <textarea name="details" rows="8" class="form-control" required><?= e($editingAd['details'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Last Advertised</label>
                            <input type="date" name="last_advertised_at" class="form-control" required value="<?= e($editingAd['last_advertised_at'] ?? now_date()) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Repeat Every (days)</label>
                            <input type="number" min="1" name="repeat_every_days" class="form-control" required value="<?= e((string) ($editingAd['repeat_every_days'] ?? 7)) ?>">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control"><?= e($editingAd['notes'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">Photos</label>
                        <input type="file" name="photos[]" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                        <div class="form-text">Upload one or more ad photos. Max 5MB each.</div>
                    </div>
                    <?php if ($editingAd !== null && ($editingAd['photos'] ?? []) !== []): ?>
                        <div>
                            <div class="small text-secondary mb-2">Current Photos</div>
                            <div class="photo-grid photo-grid-editor">
                                <?php foreach ($editingAd['photos'] as $photoIndex => $photo): ?>
                                    <div class="photo-card">
                                        <img src="<?= e($photo['path']) ?>" alt="<?= e($photo['name'] ?? 'Ad photo') ?>" class="photo-thumb">
                                        <div class="photo-meta"><?= e($photo['name'] ?? 'Photo') ?></div>
                                        <form method="post" action="index.php?action=delete-photo">
                                            <input type="hidden" name="ad_id" value="<?= e($editingAd['id']) ?>">
                                            <input type="hidden" name="photo_index" value="<?= e((string) $photoIndex) ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Remove</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-danger"><?= $editingAd !== null ? 'Update Ad' : 'Save Ad' ?></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h4 mb-3">Saved Ads</h2>
                <?php if ($hydratedAds === []): ?>
                    <div class="empty-state">No ads added yet.</div>
                <?php else: ?>
                    <div class="vstack gap-3">
                        <?php foreach ($hydratedAds as $ad): ?>
                            <div class="ad-card compact <?= e($ad['status']) ?>">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="small text-uppercase text-secondary fw-semibold"><?= e($ad['platform']) ?></div>
                                        <h3 class="h5 mb-1"><?= e($ad['title']) ?></h3>
                                        <div class="small text-secondary mb-2">
                                            Last: <?= e($ad['last_advertised_at']) ?>
                                            <span class="mx-2">|</span>
                                            Next: <?= e($ad['next_advertise_at']) ?>
                                        </div>
                                        <?php if ($currentUser['role'] === 'admin'): ?>
                                            <?php $owner = find_user_by_id($ad['user_id']); ?>
                                            <div class="small text-secondary mb-2">Owner: <?= e($owner['name'] ?? 'Unknown') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge rounded-pill soft-badge"><?= e((string) $ad['repeat_every_days']) ?> day cycle</span>
                                </div>
                                <pre id="ad-list-copy-<?= e($ad['id']) ?>" class="copy-box mt-3 mb-3"><?= e($ad['details']) ?></pre>
                                <?php if (($ad['photos'] ?? []) !== []): ?>
                                    <div class="photo-grid mb-3">
                                        <?php foreach ($ad['photos'] as $photo): ?>
                                            <a href="<?= e($photo['path']) ?>" target="_blank" rel="noreferrer" class="photo-card photo-link">
                                                <img src="<?= e($photo['path']) ?>" alt="<?= e($photo['name'] ?? 'Ad photo') ?>" class="photo-thumb">
                                                <div class="photo-meta"><?= e($photo['name'] ?? 'Photo') ?></div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($ad['notes'] !== ''): ?>
                                    <div class="small text-secondary mb-3">Notes: <?= e($ad['notes']) ?></div>
                                <?php endif; ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-copy-target="#ad-list-copy-<?= e($ad['id']) ?>">Copy details</button>
                                    <a class="btn btn-outline-dark btn-sm" href="index.php?action=ads&edit=<?= urlencode($ad['id']) ?>">Edit</a>
                                    <form method="post" action="index.php?action=mark-advertised">
                                        <input type="hidden" name="ad_id" value="<?= e($ad['id']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Mark Advertised</button>
                                    </form>
                                    <form method="post" action="index.php?action=delete-ad" onsubmit="return confirm('Delete this ad?');">
                                        <input type="hidden" name="ad_id" value="<?= e($ad['id']) ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>