<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content=""/>
    <title><?= $page_title ?? 'Dreams Admin' ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/menu.css">
</head>
<body>
    <div class="NavBar">
        <a href="/">View Site</a> |
        <a href="/admin/">Admin Site</a> |
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
        <?= $page_content ?>
    </div>
</body>
</html>
