/*
 * Pricing Section JavaScript
 * Handles pricing toggle, animations, and interactions
 */

class PricingSection {
  constructor() {
    this.pricingSection = document.querySelector('.pricing-section');
    this.pricingCards = document.querySelectorAll('.pricing-card');
    this.pricingToggle = document.querySelector('.toggle-switch');
    this.toggleLabels = document.querySelectorAll('.toggle-label');
    this.faqItems = document.querySelectorAll('.faq-item');
    
    this.isVisible = false;
    this.isAnnual = false;
    this.animationDelay = 150;
    
    // Pricing data
    this.pricingData = {
      monthly: {
        starter: 29,
        professional: 79,
        enterprise: 199
      },
      annual: {
        starter: 23, // 20% discount
        professional: 63,
        enterprise: 159
      }
    };
    
    this.init();
  }
  
  init() {
    this.setupIntersectionObserver();
    this.bindEvents();
    this.initializeAnimations();
    this.setupFAQ();
  }
  
  setupIntersectionObserver() {
    const options = {
      threshold: 0.2,
      rootMargin: '0px 0px -50px 0px'
    };
    
    this.observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !this.isVisible) {
          this.isVisible = true;
          this.animatePricing();
        }
      });
    }, options);
    
    if (this.pricingSection) {
      this.observer.observe(this.pricingSection);
    }
  }
  
  bindEvents() {
    // Pricing toggle disabled - monthly only
    // if (this.pricingToggle) {
    //   this.pricingToggle.addEventListener('click', () => this.togglePricing());
    // }
    
    // Pricing card interactions
    this.pricingCards.forEach((card, index) => {
      card.addEventListener('mouseenter', () => this.handleCardHover(card, index));
      card.addEventListener('click', () => this.handleCardClick(card, index));
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => this.handleKeydown(e));
  }
  
  initializeAnimations() {
    // Set initial states for animations
    this.pricingCards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(40px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
  }
  
  setupFAQ() {
    this.faqItems.forEach(item => {
      const question = item.querySelector('.faq-question');
      const answer = item.querySelector('.faq-answer');
      
      if (question && answer) {
        question.addEventListener('click', () => {
          const isActive = question.classList.contains('active');
          
          // Close all other FAQ items
          this.faqItems.forEach(otherItem => {
            const otherQuestion = otherItem.querySelector('.faq-question');
            const otherAnswer = otherItem.querySelector('.faq-answer');
            
            if (otherItem !== item) {
              otherQuestion.classList.remove('active');
              otherAnswer.classList.remove('active');
              otherQuestion.setAttribute('aria-expanded', 'false');
            }
          });
          
          // Toggle current item
          if (isActive) {
            question.classList.remove('active');
            answer.classList.remove('active');
            question.setAttribute('aria-expanded', 'false');
          } else {
            question.classList.add('active');
            answer.classList.add('active');
            question.setAttribute('aria-expanded', 'true');
          }
        });
      }
    });
  }
  
  animatePricing() {
    // Animate pricing cards
    this.pricingCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
        card.classList.add('animate-in');
      }, index * this.animationDelay);
    });
  }
  
  togglePricing() {
    this.isAnnual = !this.isAnnual;
    
    // Update toggle UI
    this.pricingToggle.classList.toggle('active', this.isAnnual);
    
    // Update toggle labels
    this.toggleLabels.forEach((label, index) => {
      if (index === 0) {
        label.classList.toggle('active', !this.isAnnual);
      } else {
        label.classList.toggle('active', this.isAnnual);
      }
    });
    
    // Update pricing
    this.updatePricing();
    
    // Track toggle
    this.trackPricingToggle();
  }
  
  updatePricing() {
    const period = this.isAnnual ? 'annual' : 'monthly';
    const periodText = this.isAnnual ? '/year' : '/month';
    
    this.pricingCards.forEach(card => {
      const planName = this.getPlanName(card);
      const amountElement = card.querySelector('.amount');
      const periodElement = card.querySelector('.period');
      
      if (amountElement && this.pricingData[period][planName]) {
        // Animate price change
        amountElement.style.transform = 'scale(0.8)';
        amountElement.style.opacity = '0.5';
        
        setTimeout(() => {
          amountElement.textContent = this.pricingData[period][planName];
          if (periodElement) {
            periodElement.textContent = periodText;
          }
          
          amountElement.style.transform = 'scale(1)';
          amountElement.style.opacity = '1';
        }, 150);
      }
    });
  }
  
  getPlanName(card) {
    const planNameElement = card.querySelector('.plan-name');
    if (planNameElement) {
      return planNameElement.textContent.toLowerCase();
    }
    return '';
  }
  
  handleCardHover(card, index) {
    // Add subtle hover effect
    if (!card.classList.contains('featured')) {
      card.style.transform = 'translateY(-8px) scale(1.02)';
      setTimeout(() => {
        card.style.transform = '';
      }, 300);
    }
  }
  
  handleCardClick(card, index) {
    const planName = this.getPlanName(card);
    const ctaButton = card.querySelector('.btn');
    
    if (ctaButton) {
      ctaButton.click();
    }
    
    // Track plan selection
    this.trackPlanSelection(planName, index);
  }
  
  handleKeydown(e) {
    // Handle keyboard navigation for pricing cards
    if (e.key === 'Enter' || e.key === ' ') {
      const focusedElement = document.activeElement;
      if (focusedElement.classList.contains('pricing-card')) {
        e.preventDefault();
        focusedElement.click();
      }
    }
  }
  
  trackPricingToggle() {
    // Analytics tracking
    if (typeof gtag !== 'undefined') {
      gtag('event', 'pricing_toggle', {
        event_category: 'pricing',
        event_label: this.isAnnual ? 'annual' : 'monthly',
        billing_period: this.isAnnual ? 'annual' : 'monthly'
      });
    }
    
    // Custom analytics
    if (window.analytics) {
      window.analytics.track('Pricing Toggle', {
        billingPeriod: this.isAnnual ? 'annual' : 'monthly',
        section: 'pricing',
        timestamp: new Date().toISOString()
      });
    }
  }
  
  trackPlanSelection(planName, index) {
    // Analytics tracking
    if (typeof gtag !== 'undefined') {
      gtag('event', 'plan_selection', {
        event_category: 'pricing',
        event_label: planName,
        plan_name: planName,
        billing_period: this.isAnnual ? 'annual' : 'monthly'
      });
    }
    
    // Custom analytics
    if (window.analytics) {
      window.analytics.track('Plan Selected', {
        planName,
        billingPeriod: this.isAnnual ? 'annual' : 'monthly',
        planIndex: index,
        section: 'pricing',
        timestamp: new Date().toISOString()
      });
    }
  }
  
  // Public methods
  triggerAnimation() {
    if (!this.isVisible) {
      this.isVisible = true;
      this.animatePricing();
    }
  }
  
  switchToAnnual() {
    if (!this.isAnnual) {
      this.togglePricing();
    }
  }
  
  switchToMonthly() {
    if (this.isAnnual) {
      this.togglePricing();
    }
  }
  
  highlightPlan(planName) {
    this.pricingCards.forEach(card => {
      if (this.getPlanName(card) === planName.toLowerCase()) {
        card.style.transform = 'scale(1.05)';
        card.style.boxShadow = '0 25px 50px -12px rgba(99, 102, 241, 0.25)';
        
        setTimeout(() => {
          card.style.transform = '';
          card.style.boxShadow = '';
        }, 2000);
      }
    });
  }
  
  destroy() {
    // Clean up
    if (this.observer) {
      this.observer.disconnect();
    }
    
    // Remove event listeners
    if (this.pricingToggle) {
      this.pricingToggle.removeEventListener('click', this.togglePricing);
    }
    
    this.pricingCards.forEach(card => {
      card.removeEventListener('mouseenter', this.handleCardHover);
      card.removeEventListener('click', this.handleCardClick);
    });
    
    this.faqItems.forEach(item => {
      const question = item.querySelector('.faq-question');
      if (question) {
        question.removeEventListener('click', this.setupFAQ);
      }
    });
    
    document.removeEventListener('keydown', this.handleKeydown);
  }
}

// Initialize pricing section when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    window.pricingSection = new PricingSection();
  }, 300);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = PricingSection;
}