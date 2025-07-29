<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Upload</title>
</head>
<body>
    <h1>Test Upload</h1>
    <form action="/owner/api/auto-egg-tray/upload-image.php" method="post" enctype="multipart/form-data">
        <input type="file" name="image">
        <button type="submit">Upload</button>
    </form>
</body>
</html>