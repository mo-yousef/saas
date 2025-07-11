<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>%%BLOG_NAME%%</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style type="text/css">
    /* Base Styles */
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333333;
      background-color: #f4f4f4;
      width: 100% !important;
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
    }
    table {
      border-collapse: collapse;
      mso-table-lspace: 0pt;
      mso-table-rspace: 0pt;
    }
    table td {
      border-collapse: collapse;
    }
    img {
      outline: none;
      text-decoration: none;
      -ms-interpolation-mode: bicubic;
      border: 0;
    }
    p {
      margin: 0 0 1em 0;
    }
    a {
      color: #0073aa; /* WordPress blue */
    }

    /* Wrapper */
    .email-wrapper {
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
    }

    /* Header */
    .email-header {
      padding: 20px;
      background-color: #0073aa; /* WordPress blue */
      text-align: center;
    }
    .email-header h1 {
      color: #ffffff;
      font-size: 24px;
      margin: 0;
      font-weight: normal;
    }
    .email-header h1 a {
      color: #ffffff;
      text-decoration: none;
    }
    .email-header .site-logo {
        max-height: 50px; /* Adjust as needed */
        margin-bottom: 10px;
    }


    /* Content */
    .email-content {
      padding: 30px 20px;
    }
    .email-content h2 {
      font-size: 20px;
      color: #333333;
      margin-top: 0;
    }

    /* Footer */
    .email-footer {
      padding: 20px;
      background-color: #f9f9f9;
      text-align: center;
      font-size: 12px;
      color: #777777;
    }
    .email-footer a {
      color: #0073aa;
    }

    /* Responsive */
    @media only screen and (max-width: 600px) {
      .email-wrapper {
        width: 100% !important;
        max-width: none !important;
      }
      .email-content {
        padding: 20px 15px !important;
      }
      .email-header h1 {
        font-size: 20px !important;
      }
    }
  </style>
</head>
<body>
  <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;">
    <tr>
      <td align="center" valign="top">
        <table class="email-wrapper" width="600" border="0" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border: 1px solid #dddddd;">
          <!-- Header -->
          <tr>
            <td class="email-header" style="padding: 20px; background-color: #0073aa; text-align: center;">
              %%EMAIL_HEADER_CONTENT%%
            </td>
          </tr>
          <!-- Content -->
          <tr>
            <td class="email-content" style="padding: 30px 20px;">
              %%EMAIL_CONTENT%%
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td class="email-footer" style="padding: 20px; background-color: #f9f9f9; text-align: center; font-size: 12px; color: #777777;">
              <p>%%EMAIL_FOOTER_CONTENT%%</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
