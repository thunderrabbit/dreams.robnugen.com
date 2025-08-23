<div class="PagePanel">
    <h1>Dream Keyword Analysis</h1>

    <?php if ($message): ?>
        <div style="background: #060; border: 1px solid #0c0; padding: 10px; margin: 10px 0; color: #fff;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #600; border: 1px solid #c00; padding: 10px; margin: 10px 0; color: #fff;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div style="margin: 20px 0;">
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="analyze">
            <button type="submit" style="background: #4a90e2; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
                Analyze All Dreams
            </button>
        </form>
        <span style="margin-left: 20px; color: #666;">
            This will process all dreams and extract keyword frequencies
        </span>
    </div>
</div>

<?php if ($stats && $stats['total_keywords'] > 0): ?>
<div class="PagePanel">
    <h2>Keyword Statistics</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="border: 1px solid #666; padding: 15px; text-align: center; background: rgba(74, 144, 226, 0.1);">
            <strong>Total Keywords</strong><br>
            <span style="font-size: 24px;"><?= number_format($stats['total_keywords']) ?></span>
        </div>
        <div style="border: 1px solid #666; padding: 15px; text-align: center; background: rgba(39, 174, 96, 0.1);">
            <strong>Total Instances</strong><br>
            <span style="font-size: 24px;"><?= number_format($stats['total_instances']) ?></span>
        </div>
        <div style="border: 1px solid #666; padding: 15px; text-align: center; background: rgba(231, 76, 60, 0.1);">
            <strong>Avg per Keyword</strong><br>
            <span style="font-size: 24px;"><?= number_format($stats['avg_frequency'], 1) ?></span>
        </div>
        <div style="border: 1px solid #666; padding: 15px; text-align: center; background: rgba(155, 89, 182, 0.1);">
            <strong>Most Frequent</strong><br>
            <span style="font-size: 24px;"><?= number_format($stats['max_frequency']) ?></span>
        </div>
    </div>

    <p><strong>Date Range:</strong> <?= $stats['earliest_date'] ?> to <?= $stats['latest_date'] ?></p>
</div>

<div class="PagePanel">
    <h2>Search Keywords</h2>
    <form method="get" style="margin: 15px 0;">
        <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>"
               placeholder="Search for keywords..." style="padding: 8px; width: 300px;">
        <button type="submit" style="background: #27ae60; color: white; padding: 8px 15px; border: none; border-radius: 4px;">
            Search
        </button>
        <?php if ($search_term): ?>
            <a href="?" style="margin-left: 10px; color: #e74c3c;">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($search_term && $search_results): ?>
        <h3>Search Results for "<?= htmlspecialchars($search_term) ?>"</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;">
            <?php foreach ($search_results as $result): ?>
                <div style="border: 1px solid #666; padding: 10px; background: rgba(52, 152, 219, 0.1);">
                    <strong><?= htmlspecialchars($result['keyword']) ?></strong><br>
                    <span>Count: <?= $result['frequency'] ?></span><br>
                    <small><?= $result['first_seen'] ?> - <?= $result['last_seen'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($search_term): ?>
        <p style="color: #e74c3c;">No keywords found matching "<?= htmlspecialchars($search_term) ?>"</p>
    <?php endif; ?>
</div>

<div class="PagePanel">
    <h2>Top Keywords</h2>
    <?php if ($top_keywords): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px;">
            <?php foreach ($top_keywords as $keyword): ?>
                <?php
                $intensity = min(1.0, $keyword['frequency'] / $stats['max_frequency']);
                $span_years = max(1, round(($keyword['span_days'] ?? 1) / 365, 1));
                ?>
                <div style="border: 1px solid #666; padding: 8px; text-align: center; background: rgba(74, 144, 226, <?= $intensity * 0.6 + 0.1 ?>);">
                    <strong style="font-size: 14px;"><?= htmlspecialchars($keyword['keyword']) ?></strong><br>
                    <span style="font-size: 16px; color: #fff;"><?= $keyword['frequency'] ?></span><br>
                    <small style="font-size: 11px;">
                        <?= $span_years ?>y span<br>
                        <?= substr($keyword['first_seen'], 0, 4) ?>-<?= substr($keyword['last_seen'], 0, 4) ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No keyword data available. Click "Analyze All Dreams" to generate keyword frequencies.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="PagePanel">
    <a href="/admin/">← Back to Admin Dashboard</a>
</div>
