<div class="PagePanel">
    What's up <?= $username ?>? <br />
</div>
<h1>Welcome to the Dreams Admin Dashboard</h1>
<p>This page can show numbers of workers, parts, snippets, etc</p>
<?php
if ($has_pending_migrations) {
        echo "<h3>Pending DB Migrations</h3>";
        echo "<a href='/admin/migrate_tables.php'>Click here to migrate tables</a>";
    }
?>

<div class="PagePanel">
    <a href="/profile/">Change Password</a> |
    <a href="/logout/">Logout</a> <br />
</div>
<div class="fix">
    <p>Sentimental version: <?= $site_version ?></p>
</div>
