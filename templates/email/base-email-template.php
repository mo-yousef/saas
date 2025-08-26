<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .email-wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
        }
        .email-header {
            background-color: #f8f8f8;
            padding: 20px;
            text-align: center;
        }
        .email-header img {
            max-width: 200px;
            height: auto;
        }
        .email-body {
            padding: 20px;
        }
        .email-footer {
            background-color: #f8f8f8;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td class="email-header" style="background-color: {{THEME_COLOR_LIGHT}};">
                <a href="{{SITE_URL}}" style="display:inline-block;">
                    <img src="{{LOGO_URL}}" alt="{{SITE_NAME}}">
                </a>
            </td>
        </tr>
        <tr>
            <td class="email-body">
                {{BODY_CONTENT}}
            </td>
        </tr>
        <tr>
            <td class="email-footer">
                <p>{{BIZ_NAME}}</p>
                <p>{{BIZ_ADDRESS}}</p>
                <p>{{BIZ_PHONE}} | <a href="mailto:{{BIZ_EMAIL}}">{{BIZ_EMAIL}}</a></p>
                <p><a href="{{SITE_URL}}">Visit our website</a></p>
                <p style="margin-top: 20px; font-size: 10px; color: #999;">Powered by <a href="https://nordbooking.se" style="color: #999; text-decoration: none;">Nord Booking</a></p>
            </td>
        </tr>
    </table>
</body>
</html>
