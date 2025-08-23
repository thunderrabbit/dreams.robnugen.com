<div class="PagePanel">
    <h1>Dreams Import</h1>

    <form method="post">
        <input type="hidden" name="action" value="import">
        <button type="submit" <?= $stats['remaining_count'] == 0 ? 'disabled' : '' ?>>
            Import Next 50 Dreams
        </button>
    </form>

    <?php if (!empty($message)): ?>
        <div style="background: #2a3f5f; border: 1px solid #4a90e2; padding: 10px; margin: 10px 0; color: #e0e0e0;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <h2>Import Status</h2>
    <p><strong>Total dream files found:</strong> <?= $stats['total_files'] ?></p>
    <p><strong>Files processed:</strong> <?= $stats['processed_count'] ?></p>
    <p><strong>Files remaining:</strong> <?= $stats['remaining_count'] ?></p>
    <p><strong>Dreams in database:</strong> <?= $stats['db_count'] ?></p>

    <?php if (!empty($stats['last_processed_file'])): ?>
        <p><strong>Last processed:</strong><br>
        <code><?= htmlspecialchars($stats['last_processed_file']) ?></code></p>
    <?php endif; ?>

    <h2>Import Actions</h2>
    <form method="post">
        <input type="hidden" name="action" value="import">
        <button type="submit" <?= $stats['remaining_count'] == 0 ? 'disabled' : '' ?>>
            Import Next 50 Dreams
        </button>
    </form>

    <form method="post" style="margin-top: 10px;">
        <input type="hidden" name="action" value="reset">
        <button type="submit" onclick="return confirm('Reset import pointer to beginning?')">
            Reset Import Pointer
        </button>
    </form>

    <?php if (!empty($import_results)): ?>
        <h2>Files in This Batch</h2>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #666; padding: 10px; background: #2d2d2d;">
            <?php foreach ($import_results as $file_path): ?>
                <div style="margin: 2px 0; font-family: monospace; font-size: 12px;">
                    <?= htmlspecialchars(str_replace('/home/barefoot_rob/robnugen.com/journal/journal/', '', $file_path)) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($failed_files) && count($failed_files) > 0): ?>
        <h2>Failed Files (Encoding Issues)</h2>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #666; padding: 10px; background: #2d2d2d;">
            <?php foreach ($failed_files as $file_path): ?>
                <div style="margin: 2px 0; font-family: monospace; font-size: 12px;">
                    <?= htmlspecialchars(str_replace('/home/barefoot_rob/robnugen.com/journal/journal/', '', $file_path)) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <form method="post" style="margin-top: 10px;">
            <input type="hidden" name="action" value="clear_failed">
            <button type="submit">Clear Failed Files List</button>
        </form>
    <?php endif; ?>
</div>

<div class="PagePanel">
    <a href="/admin/">← Back to Admin</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the remaining count from the page
    const remainingCount = <?= $stats['remaining_count'] ?>;

    // Only auto-click if remaining count is >= 50
    if (remainingCount >= 90) {
        console.log('Auto-import will start in 10 seconds. Remaining files: ' + remainingCount);

        setTimeout(function() {
            const importButton = document.querySelector('button[type="submit"]');
            if (importButton && !importButton.disabled) {
                console.log('Auto-clicking import button...');
                importButton.click();
            } else {
                console.log('Import button not found or disabled');
            }
        }, 8000); // 8 seconds
    } else {
        console.log('Auto-import stopped. Remaining files (' + remainingCount + ') is below 50 threshold.');
    }
});
</script>
