<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Dream - Dreams</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/menu.css">
    <link rel="stylesheet" href="/css/dreams.css">
</head>
<body>
    <div class="NavBar">
        <a href="/">Write Dream</a> |
        <a href="/admin/">Admin</a> |
        <div class="dropdown">
            <a href="/admin/dreams/">Dreams Import ▾</a>
            <div class="dropdown-menu">
                <a href="/admin/keywords/">Keyword Analysis</a>
            </div>
        </div> |
        <div class="dropdown">
            <a href="/profile/">Profile ▾</a>
            <div class="dropdown-menu">
                <a href="/logout/">Logout</a>
            </div>
        </div>
    </div>

    <div class="PageWrapper">
        <?php if ($message || $error): ?>
        <div class="PagePanel">
            <?php if ($message): ?>
                <div style="background: #2d5a27; border: 1px solid #4a8c3a; padding: 10px; margin: 10px 0; color: #90ee90; border-radius: 4px;">
                    ✅ <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #600; border: 1px solid #c00; padding: 10px; margin: 10px 0; color: #fff; border-radius: 4px;">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; // ($message || $error): ?>

        <form method="post" class="dreamForm">
            <div class="timeFields">
                <div class="timeField smallField">
                    <label for="time">Time:</label>
                    <input type="text" id="time" name="time" value="<?= $current_time ?>" placeholder="HH:MM">
                </div>

                <div class="timeField">
                    <label for="date">Date:</label>
                    <input type="text" id="date" name="date" value="<?= $current_date ?>" placeholder="Day DD Month YYYY">
                </div>
            </div>

            <label for="title">Dream Title:</label>
            <input type="text" id="title" name="title" value="Dream" placeholder="Enter your dream title..." required>

            <label for="tags">Tags:</label>
            <input type="text" id="tags" name="tags" value="dream, sleep" placeholder="dream, lucid, nightmare, etc.">

            <div class="dreamButtons">
                <span style="color: #999; font-size: 14px;">Quick tags:</span>
                <button type="button" class="dreamBtn" onclick="addTag('lucid')">💡 Lucid</button>
                <button type="button" class="dreamBtn" onclick="addTag('nightmare')">😱 Nightmare</button>
                <button type="button" class="dreamBtn" onclick="addTag('recurring')">🔄 Recurring</button>
                <button type="button" class="dreamBtn" onclick="addTag('flying')">🕊️ Flying</button>
                <button type="button" class="dreamBtn" onclick="addTag('water')">🌊 Water</button>
                <button type="button" class="dreamBtn" onclick="addTag('family')">👨‍👩‍👧‍👦 Family</button>
            </div>

            <label for="post_content">Dream Content:</label>
            <div class="contentWrapper">
                <div class="quickTagSidebar">
                    <button
                        type="button"
                        class="quickTagBtn"
                        onclick="wrapSelectedParagraphs('dream')"
                        title="Tag as dream">💭</button>
                    <button
                        type="button"
                        class="quickTagBtn"
                        onclick="wrapSelectedParagraphs('lucid')"
                        title="Tag as lucid dream">👁️</button>
                    <button
                        type="button"
                        class="quickTagBtn"
                        onclick="wrapSelectedParagraphs('nightmare')"
                        title="Tag as nightmare">😱</button>
                </div>
                <textarea id="post_content" name="post_content" placeholder="Describe your dream in detail..." required></textarea>
            </div>

            <input type="submit" value="💾 Save Dream">
        </form>

        <div class="PagePanel">
            <p><strong>Tips:</strong></p>
            <ul style="color: #ccc;">
                <li>Write in present tense: "I am walking..." instead of "I was walking..."</li>
                <li>Include emotions and sensations you felt in the dream</li>
                <li>Note any unusual or symbolic elements</li>
                <li>The file will be saved to your journal and can be imported later</li>
            </ul>
        </div>
    </div>

    <script>
    function wrapSelectedParagraphs(className) {
        const textarea = document.getElementById('post_content');
        const text = textarea.value;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;

        const before = text.substring(0, start);
        const selected = text.substring(start, end);
        const after = text.substring(end);

        const paragraphs = selected
            .split(/\n{2,}/)
            .map(p => `<p class="${className}">${p.trim()}</p>`);

        const newText = before + paragraphs.join("\n\n") + after;
        textarea.value = newText;

        // Don't reselect the new text because the buttons don't toggle the addition of <p> tags.
        // textarea.setSelectionRange(before.length, before.length + paragraphs.join("\n\n").length);
        // textarea.focus();
    }

    function addTag(tag) {
        const tagsField = document.getElementById('tags');
        const currentTags = tagsField.value.trim();

        // Split tags into array for easier manipulation
        let tagsArray = currentTags ? currentTags.split(',').map(t => t.trim()) : [];

        // Check if tag already exists - toggle it
        const tagIndex = tagsArray.indexOf(tag);
        if (tagIndex !== -1) {
            // Tag exists, remove it
            tagsArray.splice(tagIndex, 1);
        } else {
            // Tag doesn't exist, add it
            tagsArray.push(tag);
        }

        // Rebuild tags string
        tagsField.value = tagsArray.join(', ');
    }

    // Auto-focus on title field
    document.getElementById('title').focus();
    </script>
</body>
</html>
