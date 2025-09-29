// Pricing toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const billingToggle = document.getElementById('billing-toggle');
    const priceAmounts = document.querySelectorAll('.amount');
    
    billingToggle.addEventListener('change', function() {
        const isAnnual = this.checked;
        
        priceAmounts.forEach(amount => {
            const monthlyPrice = amount.getAttribute('data-monthly');
            const annualPrice = amount.getAttribute('data-annual');
            
            if (isAnnual) {
                amount.textContent = annualPrice;
            } else {
                amount.textContent = monthlyPrice;
            }
        });
    });
    
    // FAQ accordion functionality
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Close all FAQ items
            faqItems.forEach(faqItem => {
                faqItem.classList.remove('active');
            });
            
            // Open clicked item if it wasn't active
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
    
    // CTA button functionality with routing logic
    const ctaButtons = document.querySelectorAll('.cta-button');
    
    ctaButtons.forEach(button => {
        button.addEventListener('click', function() {
            const plan = this.getAttribute('data-plan');
            handlePlanSelection(plan);
        });
    });
});

// Check if user is registered/logged in
function isUserLoggedIn() {
    // This would typically check localStorage, sessionStorage, or make an API call
    // For demo purposes, we'll simulate this
    return localStorage.getItem('userToken') !== null;
}

// Handle plan selection with proper routing
function handlePlanSelection(plan) {
    const isLoggedIn = isUserLoggedIn();
    
    if (isLoggedIn) {
        // User is logged in, redirect to subscription page
        redirectToSubscription(plan);
    } else {
        // User is not logged in, redirect to registration
        redirectToRegistration(plan);
    }
}

function redirectToRegistration(plan) {
    // Store selected plan for after registration
    localStorage.setItem('selectedPlan', plan);
    
    // In a real application, you would redirect to your registration page
    // For demo purposes, we'll show an alert and simulate the flow
    alert(`Redirecting to registration page. Selected plan: ${plan}`);
    
    // Simulate successful registration
    setTimeout(() => {
        // Simulate user registration success
        localStorage.setItem('userToken', 'demo-token-123');
        alert('Registration successful! Redirecting to subscription page...');
        
        // Now redirect to subscription
        const selectedPlan = localStorage.getItem('selectedPlan');
        redirectToSubscription(selectedPlan);
    }, 2000);
}

function redirectToSubscription(plan) {
    // In a real application, you would redirect to your subscription/checkout page
    alert(`Redirecting to subscription page for ${plan} plan`);
    
    // Example of what the redirect would look like:
    // window.location.href = `/subscription?plan=${plan}`;
    
    // For demo purposes, we'll simulate the subscription process
    console.log(`Processing subscription for ${plan} plan`);
}