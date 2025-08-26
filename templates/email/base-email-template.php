<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style type="text/css">
        <?php
        // It's better to file_get_contents than include, to avoid executing PHP in CSS file.
        // Also, ensure the path is correct relative to the theme root.
        $css_path = get_template_directory() . '/assets/css/email.css';
        if (file_exists($css_path)) {
            echo file_get_contents($css_path);
        }
        ?>
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>{{HEADER_TITLE}}</h1>
            </div>
            <div class="email-body">
                {{BODY_CONTENT}}
            </div>
            <div class="email-footer">
                <p>&copy; <?php echo date('Y'); ?> {{SITE_NAME}}. All rights reserved.</p>
                <p><a href="{{SITE_URL}}">{{SITE_URL}}</a></p>
            </div>
        </div>
    </div>
</body>
</html>
