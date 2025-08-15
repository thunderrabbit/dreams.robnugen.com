<h1>Dreams Table Migration Dashboard</h1>
<script>
    function applyMigration(migration) {
        if (confirm("Are you sure you want to apply this migration?\n\n" + migration)) {
            // AJAX request to apply the migration
            fetch('/admin/apply_migration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ migration: migration })
            })
            .then(response => {
                if (response.ok) {
                    alert("Migration applied successfully!");
                    location.reload(); // Reload the page to see the changes
                } else {
                    alert("Failed to apply migration. Please try again.");
                }
            })
            .catch(error => {
                console.error("Error applying migration:", error);
                alert("An error occurred while applying the migration.");
            });
        }
    }
</script>
<?php
if ($has_pending_migrations) {
        echo "<h3>Pending DB Migrations</h3><ul>";
        foreach ($pending_migrations as $migration) {
            echo "<li>$migration <button onclick=\"applyMigration('$migration')\">Apply</button></li>";
        }
        echo "</ul>";
    }
?>

<div class="PagePanel">
    <a href="/admin/">admin</a> <br />
</div>
