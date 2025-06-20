# Managing Your Workers

This guide explains how to use the Worker Invitation feature to add team members to your MoBooking dashboard and manage their access.

## 1. Feature Overview

The Worker Invitation feature allows you as a Business Owner to invite team members (workers) to access your MoBooking dashboard. This helps you delegate tasks like managing bookings, services, and more, without sharing your primary account details. Each worker gets their own login and specific permissions based on the role you assign them.

**Benefits:**

*   **Improved Security:** No need to share your personal login credentials.
*   **Delegation:** Assign tasks to team members with appropriate access levels.
*   **Auditability:** (Future enhancement) Track actions performed by different workers.
*   **Efficiency:** Streamline your business operations by involving your team directly in the booking management process.

## 2. Understanding Worker Roles

When you invite a worker, you'll assign them a specific role. Each role has different permissions within the MoBooking dashboard:

*   **Manager (`mobooking_worker_manager`)**
    *   **Dashboard Access:** Full access to most operational areas of the dashboard.
    *   **Key Permissions:**
        *   Manage bookings (view, create, edit, delete).
        *   Manage services (view, create, edit, delete).
        *   Manage discounts (view, create, edit, delete).
        *   Manage service areas (view, create, edit, delete).
        *   Manage the booking form's appearance and settings.
    *   *Managers generally cannot manage other workers or change core business settings.*

*   **Staff (`mobooking_worker_staff`)**
    *   **Dashboard Access:** Access focused on day-to-day booking and service viewing.
    *   **Key Permissions:**
        *   Manage bookings (view, create, edit, delete - though you might guide them on specific actions).
        *   View services.
        *   View discounts.
        *   View service areas.
    *   *Staff members cannot create or edit services, discounts, areas, manage the booking form, manage other workers, or change business settings.*

*   **Viewer (`mobooking_worker_viewer`)**
    *   **Dashboard Access:** View-only access to key business data.
    *   **Key Permissions:**
        *   View bookings.
        *   View services.
        *   View discounts.
        *   View service areas.
    *   *Viewers cannot make any changes, manage bookings, or manage other aspects of the business.*

All worker roles also have basic 'read' access to WordPress, which is standard.

## 3. Inviting a New Worker

Follow these steps to invite a new worker:

1.  **Navigate to Manage Workers:**
    *   Log in to your MoBooking dashboard.
    *   In the sidebar navigation, click on "Workers".

2.  **Use the Invitation Form:**
    *   On the "Manage Workers" page, you'll find a section titled "Invite New Worker".
    *   **Worker Email:** Enter the email address of the person you want to invite. Make sure this is a valid email address they can access.
    *   **Assign Role:** Select the desired role (Manager, Staff, or Viewer) for this worker from the dropdown menu.
    *   **Send Invitation:** Click the "Send Invitation" button.

3.  **Email Invitation:**
    *   The system will send an email to the worker's email address. This email contains a unique registration link.

## 4. Worker Registration Process

Once you've sent an invitation, here's what the invited worker needs to do:

1.  **Check Email:** They will receive an email from your business (via MoBooking) with the subject "You have been invited to [Your Business Name]".
2.  **Click Registration Link:** The email contains a unique link to complete their registration. This link is valid for 7 days.
3.  **Complete Registration:**
    *   The registration page will have their email address pre-filled.
    *   They will need to choose a password and complete any other required fields on the simple registration form.
    *   Upon successful registration, they will be able to log in to the MoBooking dashboard with the permissions you assigned.

## 5. Managing Your Workers

On the "Manage Workers" page, below the invitation form, you'll see a list of "Current Workers" associated with your business.

*   **Viewing Workers:** The table displays each worker's email address and their currently assigned MoBooking role.

*   **Changing a Worker's Role:**
    1.  Find the worker in the list.
    2.  In the "Actions" column for that worker, you'll see a dropdown menu with the available roles.
    3.  Select the new role you want to assign from this dropdown.
    4.  Click the "Save Role" button next to the dropdown.
    5.  The system will update the worker's role, and you'll see a confirmation message. Their dashboard access will adjust according to the new role's permissions immediately.

*   **Revoking a Worker's Access:**
    1.  Find the worker in the list.
    2.  In the "Actions" column, click the "Revoke Access" button.
    3.  A confirmation prompt will appear to ensure you want to proceed.
    4.  If you confirm:
        *   The worker's specific MoBooking roles (Manager, Staff, Viewer) will be removed.
        *   Their association with your business account will be deleted.
        *   If the worker has no other roles on your WordPress site (e.g., if they were only a worker for your MoBooking system), their account will be set to a standard 'Subscriber' role. This means they can still log into your WordPress site but will not have access to the MoBooking dashboard or its functionalities.
        *   The worker will be removed from your "Current Workers" list.
        *   You'll see a confirmation message. This action cannot be undone directly; you would need to re-invite them if you wish to grant access again.

## 6. Troubleshooting & FAQ

*   **Q: What if an invited worker doesn't receive the invitation email?**
    *   **A:** Ask them to check their spam or junk mail folder. Verify that you entered their email address correctly. If necessary, you can try sending the invitation again (note: if the previous token hasn't expired and they find it, they could still use it. Revoking access for a non-registered user isn't currently an option, so ensure email accuracy).

*   **Q: What if a worker forgets their password?**
    *   **A:** They can use the standard WordPress password reset feature available on the login page. The reset link will be sent to their registered email address.

*   **Q: Can a worker change their own role?**
    *   **A:** No, only Business Owners (or users with administrative privileges over the website) can change a worker's role through the "Manage Workers" page.

*   **Q: What happens if an invitation link expires?**
    *   **A:** If a worker tries to use an invitation link that is older than 7 days, it will be invalid. You will need to send them a new invitation from the "Manage Workers" page.

---

We hope this guide helps you effectively manage your team with MoBooking!
If you have further questions, please refer to our main support documentation or contact our support team.
