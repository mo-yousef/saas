<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>%%SUBJECT%%</title>
    <style>
        /* Reset styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Email-safe styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .header {
            background-color: #ffffff;
            color: #0f172a;
            padding: 24px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .header-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .header-logo img {
            max-height: 40px;
            width: auto;
            margin-right: 12px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.025em;
            color: #0f172a;
        }

        .header p {
            margin: 8px 0 0 0;
            color: #64748b;
            font-size: 14px;
        }

        /* Hide logo if not available */
        .header-logo:empty {
            display: none;
        }

        /* Content */
        .content {
            padding: 32px 24px;
        }

        .greeting {
            margin-bottom: 24px;
        }

        .greeting h2 {
            color: #1e293b;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .greeting p {
            color: #64748b;
            font-size: 16px;
        }

        /* Alert/Banner */
        .alert {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
        }

        .alert-icon {
            color: #2563eb;
            margin-right: 12px;
            font-weight: bold;
            font-size: 16px;
        }

        .alert-content h3 {
            color: #1e40af;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .alert-content p {
            color: #1e40af;
            font-size: 14px;
            margin: 0;
        }

        /* Buttons */
        .button-group {
            margin: 24px 0;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            margin: 0 8px 8px 0;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0f172a;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1e293b;
        }

        .btn-secondary {
            background-color: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background-color: #f9fafb;
        }

        .btn-destructive {
            background-color: #dc2626;
            color: white;
        }

        .btn-destructive:hover {
            background-color: #b91c1c;
        }

        /* Card */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            background-color: #f8fafc;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h3 {
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .card-content {
            padding: 20px;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            margin: 16px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th {
            background-color: #f8fafc;
            color: #374151;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-confirmed {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Progress bar */
        .progress-container {
            margin: 16px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: #374151;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #0f172a;
            transition: width 0.3s ease;
        }

        /* List */
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 16px 0;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            padding: 8px 0;
            color: #374151;
        }

        .feature-list li::before {
            content: "✓";
            color: #16a34a;
            font-weight: bold;
            margin-right: 12px;
            background-color: #dcfce7;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        /* Separator */
        .separator {
            height: 1px;
            background-color: #e2e8f0;
            margin: 24px 0;
        }

        /* Footer */
        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .footer a {
            color: #0f172a;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .social-links {
            margin-top: 16px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 8px;
            padding: 8px 12px;
            background-color: #e2e8f0;
            border-radius: 4px;
            color: #374151;
            text-decoration: none;
            font-size: 12px;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .email-container {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }

            .content {
                padding: 24px 16px;
            }

            .header {
                padding: 20px 16px;
            }

            .btn {
                display: block;
                margin: 8px 0;
                text-align: center;
            }

            .table-container {
                overflow-x: scroll;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Nord Booking</h1>
            <p>Professional Booking Management System</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                <h2>%%GREETING%%</h2>
            </div>

            %%BODY_CONTENT%%

            <!-- Action Buttons -->
            <div class="button-group">
                %%BUTTON_GROUP%%
            </div>

            <div class="separator"></div>

            <!-- Call to Action -->
            <div style="text-align: center;">
                <p style="color: #64748b; margin-bottom: 16px;">Need help with your booking?</p>
                <a href="mailto:support@nordbk.com" class="btn btn-secondary">Contact Support</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for choosing Nord Booking for your reservation needs.</p>
            <p>If you have any questions, feel free to <a href="mailto:support@nordbk.com">contact our support team</a>.</p>

            <div class="social-links">
                <a href="#">Help Center</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>

            <div class="separator" style="margin: 16px 0;"></div>

            <p style="margin-bottom: 0;">
                <a href="https://nordbk.com" style="font-weight: 600;">Powered by Nord Booking</a>
            </p>
        </div>
    </div>
</body>
</html>
