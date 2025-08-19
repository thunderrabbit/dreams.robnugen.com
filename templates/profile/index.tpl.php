<div class="PagePanel">
    <div class="head"><h5 class="iUser">Change Password</h5></div>

    <?php if (!empty($success_message)): ?>
        <div class="success-message" style="color: #90ee90; padding: 10px; margin: 10px 0; border: 1px solid #4a8a4a; background-color: #1a3d1a;">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="error-message" style="color: #ff9999; padding: 10px; margin: 10px 0; border: 1px solid #8a4a4a; background-color: #3d1a1a;">
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <form action="/profile/" method="POST" class="mainForm">
        <fieldset>
            <div class="PageRow noborder">
                <label for="current_password">Current Password:</label>
                <div class="PageInput">
                    <input type="password" name="current_password" id="current_password" class="validate[required]" required />
                </div>
                <div class="fix"></div>
            </div>

            <div class="PageRow noborder">
                <label for="new_password">New Password:</label>
                <div class="PageInput">
                    <input type="password" name="new_password" id="new_password" class="validate[required]" required />
                </div>
                <div class="fix"></div>
            </div>

            <div class="PageRow noborder">
                <label for="confirm_password">Confirm New Password:</label>
                <div class="PageInput">
                    <input type="password" name="confirm_password" id="confirm_password" class="validate[required]" required />
                </div>
                <div class="fix"></div>
            </div>

            <div class="PageRow noborder">
                <input type="submit" value="Change Password" class="greyishBtn submitForm" />
                <div class="fix"></div>
            </div>
        </fieldset>
    </form>
</div>

<div class="PagePanel">
    <p>Logged in as: <strong><?= htmlspecialchars($username) ?></strong></p>
    <a href="/admin/">Back to Dashboard</a> |
    <a href="/logout/">Logout</a>
</div>
