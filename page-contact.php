<?php
/**
 * Template Name: Contact Page
 * 
 * Contact page for enterprise inquiries and general support
 * 
 * @package NORDBOOKING
 */

get_header();

// Get plan parameter if coming from pricing page
$selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
?>

<style>
/* Contact Page Styles */
.contact-hero {
    background: linear-gradient(135deg, hsl(var(--nbk-primary)) 0%, hsl(var(--nbk-primary) / 0.8) 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.contact-hero h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    margin-bottom: 1rem;
    color: white;
}

.contact-hero p {
    font-size: 1.25rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
    color: white;
}


.contact-section {
    padding: 4rem 0;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: start;
}

.contact-info {
    background: white;
    border: 1px solid hsl(var(--nbk-border));
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.contact-form {
    background: white;
    border: 1px solid hsl(var(--nbk-border));
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.contact-info h2,
.contact-form h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: hsl(var(--nbk-foreground));
}

.contact-item {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: hsl(var(--nbk-muted) / 0.3);
    border-radius: 0.5rem;
}

.contact-icon {
    width: 24px;
    height: 24px;
    margin-right: 1rem;
    color: hsl(var(--nbk-primary));
    flex-shrink: 0;
}

.contact-details h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: hsl(var(--nbk-foreground));
}

.contact-details p {
    font-size: 0.875rem;
    color: hsl(var(--nbk-muted-foreground));
    margin: 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: hsl(var(--nbk-foreground));
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid hsl(var(--nbk-border));
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: hsl(var(--nbk-primary));
    box-shadow: 0 0 0 2px hsl(var(--nbk-primary) / 0.2);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.submit-btn {
    width: 100%;
    padding: 0.75rem 1.5rem;
    background: hsl(var(--nbk-primary));
    color: white;
    border: none;
    border-radius: 0.375rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.submit-btn:hover {
    background: hsl(var(--nbk-primary) / 0.9);
    transform: translateY(-1px);
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.enterprise-notice {
    background: hsl(var(--nbk-primary) / 0.1);
    border: 1px solid hsl(var(--nbk-primary) / 0.3);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 2rem;
    color: hsl(var(--nbk-primary));
}

.enterprise-notice h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.enterprise-notice p {
    margin: 0;
    font-size: 0.875rem;
}

.success-message,
.error-message {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    display: none;
}

.success-message {
    background: hsl(142 76% 36% / 0.1);
    border: 1px solid hsl(142 76% 36% / 0.3);
    color: hsl(142 76% 36%);
}

.error-message {
    background: hsl(0 84.2% 60.2% / 0.1);
    border: 1px solid hsl(0 84.2% 60.2% / 0.3);
    color: hsl(0 84.2% 60.2%);
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .contact-hero {
        padding: 3rem 0;
    }
    
    .contact-section {
        padding: 3rem 0;
    }
    
    .contact-info,
    .contact-form {
        padding: 1.5rem;
    }
}
</style>

<!-- Contact Hero Section -->
<section class="contact-hero">
    <div class="nbk-container">
        <h1>Get in Touch</h1>
        <p>We're here to help you grow your cleaning business. Reach out to our team for support, questions, or enterprise solutions.</p>
    </div>
</section>

<!-- Contact Content Section -->
<section class="contact-section">
    <div class="nbk-container">
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <h2>Contact Information</h2>
                
                <div class="contact-item">
                    <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <div class="contact-details">
                        <h3>Email Support</h3>
                        <p>support@nordbooking.com</p>
                    </div>
                </div>

                <div class="contact-item">
                    <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <div class="contact-details">
                        <h3>Phone Support</h3>
                        <p>+1 (555) 123-4567</p>
                    </div>
                </div>

                <div class="contact-item">
                    <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    <div class="contact-details">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM EST</p>
                    </div>
                </div>

                <div class="contact-item">
                    <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <div class="contact-details">
                        <h3>Office Location</h3>
                        <p>Stockholm, Sweden</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form">
                <h2>Send us a Message</h2>
                


                <div class="success-message" id="success-message">
                    <p>Thank you for your message! We'll get back to you within 24 hours.</p>
                </div>

                <div class="error-message" id="error-message">
                    <p>There was an error sending your message. Please try again or contact us directly.</p>
                </div>

                <form id="contact-form">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="company">Company Name</label>
                        <input type="text" id="company" name="company">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="sales">Sales Inquiry</option>
                            <option value="support">Technical Support</option>
                            <option value="billing">Billing Question</option>
                            <option value="feature">Feature Request</option>
                            <option value="partnership">Partnership Opportunity</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" placeholder="Tell us how we can help you..." required></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span class="btn-text">Send Message</span>
                        <span class="btn-loading" style="display: none;">Sending...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const submitBtn = form.querySelector('.submit-btn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        
        // Hide previous messages
        successMessage.style.display = 'none';
        errorMessage.style.display = 'none';

        // Get form data
        const formData = new FormData(form);
        


        // Simulate form submission (replace with actual endpoint)
        setTimeout(() => {
            // For demo purposes, always show success
            // In production, replace this with actual AJAX call to your contact form handler
            
            successMessage.style.display = 'block';
            form.reset();
            
            // Reset button state
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
            
            // Scroll to success message
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
        }, 2000);
        
        // Example of actual AJAX implementation:
        /*
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successMessage.style.display = 'block';
                form.reset();
                successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                errorMessage.style.display = 'block';
                errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })
        .catch(error => {
            errorMessage.style.display = 'block';
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })
        .finally(() => {
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        });
        */
    });
});
</script>

<?php get_footer(); ?>