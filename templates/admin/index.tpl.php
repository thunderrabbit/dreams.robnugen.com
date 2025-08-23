<div class="PagePanel">
    What's up <?= $username ?>? <br />
</div>
<h1>Welcome to the Dreams Admin Dashboard</h1>

<?php if (isset($error)): ?>
    <div style="background: #600; border: 1px solid #c00; padding: 10px; margin: 10px 0; color: #fff;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<p><strong>Total Dreams:</strong> <?= number_format($total_dreams) ?></p>

<h2>Dreams by Year</h2>
<?php $max_year_count = $by_year ? max(array_column($by_year, 'count')) : 1; ?>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; margin-bottom: 20px;">
    <?php foreach ($by_year as $row): ?>
        <?php
        $percentage = $total_dreams > 0 ? ($row['count'] / $total_dreams) * 100 : 0;
        $intensity = $max_year_count > 0 ? ($row['count'] / $max_year_count) * 100 : 0;
        ?>
        <div style="border: 1px solid #666; padding: 8px; text-align: center; background: rgba(74, 144, 226, <?= $intensity / 100 * 0.7 + 0.1 ?>);">
            <strong><?= $row['year'] ?></strong><br>
            <span style="font-size: 16px;"><?= $row['count'] ?></span><br>
            <small><?= number_format($percentage, 1) ?>%</small>
        </div>
    <?php endforeach; ?>
</div>

<h2>Dreams by Month (All Years Combined)</h2>
<?php
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$month_data = [];
foreach ($by_month as $row) {
    $month_data[$row['month']] = $row['count'];
}
$max_month_count = $by_month ? max(array_column($by_month, 'count')) : 1;
?>
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
    <?php for ($i = 1; $i <= 12; $i++): ?>
        <?php
        $count = $month_data[$i] ?? 0;
        $percentage = $total_dreams > 0 ? ($count / $total_dreams) * 100 : 0;
        $intensity = $max_month_count > 0 ? ($count / $max_month_count) * 100 : 0;
        ?>
        <div style="border: 1px solid #666; padding: 10px; text-align: center; background: rgba(39, 174, 96, <?= $intensity / 100 * 0.7 + 0.1 ?>);">
            <strong><?= $months[$i-1] ?></strong><br>
            <span style="font-size: 18px;"><?= $count ?></span><br>
            <small><?= number_format($percentage, 1) ?>%</small>
        </div>
    <?php endfor; ?>
</div>

<h2>Dreams by Day of Month</h2>
<?php
$day_data = [];
foreach ($by_day as $row) {
    $day_data[$row['day']] = $row['count'];
}
$max_day_count = $by_day ? max(array_column($by_day, 'count')) : 1;
?>
<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; margin-bottom: 20px;">
    <?php for ($i = 1; $i <= 31; $i++): ?>
        <?php
        $count = $day_data[$i] ?? 0;
        $percentage = $total_dreams > 0 ? ($count / $total_dreams) * 100 : 0;
        $intensity = $max_day_count > 0 ? ($count / $max_day_count) * 100 : 0;
        ?>
        <div style="border: 1px solid #666; padding: 6px; text-align: center; background: rgba(231, 76, 60, <?= $intensity / 100 * 0.7 + 0.1 ?>); min-height: 45px; display: flex; flex-direction: column; justify-content: center;">
            <strong><?= $i ?></strong><br>
            <span style="font-size: 12px;"><?= $count ?></span><br>
            <small style="font-size: 10px;"><?= number_format($percentage, 1) ?>%</small>
        </div>
    <?php endfor; ?>
</div>
<?php
if ($has_pending_migrations) {
        echo "<h3>Pending DB Migrations</h3>";
        echo "<a href='/admin/migrate_tables.php'>Click here to migrate tables</a>";
    }
?>

<div class="fix">
    <p>Sentimental version: <?= $site_version ?></p>
</div>
