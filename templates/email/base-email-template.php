<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .button {
            background-color: {{THEME_COLOR}};
            color: #fff !important;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table th {
            background-color: #f2f2f2;
            text-align: left;
        }
    </style>
</head>
<body style="background-color: #f4f4f4; padding: 20px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <tr>
                        <td align="center" style="padding: 20px; background-color: {{THEME_COLOR_LIGHT}};">
                            <a href="{{SITE_URL}}">
                        {{LOGO_HTML}}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            {{BODY_CONTENT}}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px; background-color: #f8f9fa; font-size: 12px; color: #6c757d;">
                            <p style="margin: 0 0 5px 0;"><strong>{{BIZ_NAME}}</strong></p>
                            <p style="margin: 0 0 5px 0;">{{BIZ_ADDRESS}}</p>
                            <p style="margin: 0 0 5px 0;">{{BIZ_PHONE}} | <a href="mailto:{{BIZ_EMAIL}}" style="color: #6c757d;">{{BIZ_EMAIL}}</a></p>
                            <p style="margin-top: 20px; font-size: 10px; color: #999;">Powered by <a href="https://nordbooking.se" style="color: #999; text-decoration: none;">Nord Booking</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
