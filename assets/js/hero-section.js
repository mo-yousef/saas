/*
 * Hero Section JavaScript
 * Handles animations, interactions, and dynamic content
 */

class HeroSection {
  constructor() {
    this.heroSection = document.querySelector('.hero-section');
    this.heroTitle = document.querySelector('.hero-title');
    this.heroDescription = document.querySelector('.hero-description');
    this.heroBadge = document.querySelector('.hero-badge');
    this.ctaButtons = document.querySelectorAll('.hero-cta-group .btn');
    this.socialProofItems = document.querySelectorAll('.proof-item');
    this.floatingCards = document.querySelectorAll('.booking-card, .revenue-card');
    
    this.isVisible = false;
    this.animationDelay = 100;
    
    this.init();
  }
  
  init() {
    this.setupIntersectionObserver();
    this.bindEvents();
    this.initializeAnimations();
    this.updateDynamicContent();
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
          this.animateHeroContent();
        }
      });
    }, options);
    
    if (this.heroSection) {
      this.observer.observe(this.heroSection);
    }
  }
  
  bindEvents() {
    // CTA button interactions
    this.ctaButtons.forEach(button => {
      button.addEventListener('click', (e) => this.handleCTAClick(e));
      button.addEventListener('mouseenter', () => this.handleCTAHover(button));
    });
    
    // Badge interaction
    if (this.heroBadge) {
      this.heroBadge.addEventListener('click', () => this.handleBadgeClick());
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => this.handleKeydown(e));
    
    // Window resize
    window.addEventListener('resize', () => this.handleResize());
  }
  
  initializeAnimations() {
    // Set initial states for animations
    const animatedElements = [
      this.heroBadge,
      this.heroTitle,
      this.heroDescription,
      ...Array.from(this.ctaButtons),
      ...Array.from(this.socialProofItems)
    ].filter(Boolean);
    
    animatedElements.forEach((element, index) => {
      if (element) {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      }
    });
    
    // Set initial state for floating cards
    this.floatingCards.forEach(card => {
      if (card) {
        card.style.opacity = '0';
      }
    });
  }
  
  animateHeroContent() {
    const animatedElements = [
      { element: this.heroBadge, delay: 0 },
      { element: this.heroTitle, delay: 100 },
      { element: this.heroDescription, delay: 200 },
      { element: this.ctaButtons[0], delay: 300 },
      { element: this.ctaButtons[1], delay: 350 },
      ...Array.from(this.socialProofItems).map((item, index) => ({
        element: item,
        delay: 400 + (index * 50)
      }))
    ].filter(item => item.element);
    
    animatedElements.forEach(({ element, delay }) => {
      setTimeout(() => {
        if (element) {
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
        }
      }, delay);
    });
    
    // Animate hero visual
    const heroVisual = document.querySelector('.hero-visual');
    if (heroVisual) {
      setTimeout(() => {
        heroVisual.classList.add('animate-in');
      }, 500);
    }
    
    // Animate floating cards
    this.animateFloatingCards();
  }
  
  animateFloatingCards() {
    this.floatingCards.forEach((card, index) => {
      if (card) {
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.animation = `float 3s ease-in-out infinite ${index * 1.5}s`;
        }, 600 + (index * 200));
      }
    });
  }
  
  handleCTAClick(e) {
    const button = e.currentTarget;
    const href = button.getAttribute('href');
    
    // Add click animation
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
      button.style.transform = '';
    }, 150);
    
    // Track analytics
    this.trackCTAClick(button);
    
    // Handle demo button
    if (href === '#demo') {
      e.preventDefault();
      this.showDemoModal();
    }
  }
  
  handleCTAHover(button) {
    // Add subtle hover effect
    const rect = button.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    button.style.setProperty('--mouse-x', `${x}px`);
    button.style.setProperty('--mouse-y', `${y}px`);
  }
  
  handleBadgeClick() {
    // Show trust information modal or scroll to testimonials
    const testimonialsSection = document.querySelector('#testimonials');
    if (testimonialsSection) {
      testimonialsSection.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Add click feedback
    this.heroBadge.style.transform = 'scale(0.95)';
    setTimeout(() => {
      this.heroBadge.style.transform = '';
    }, 150);
  }
  
  handleKeydown(e) {
    // Handle keyboard navigation for hero CTAs
    if (e.key === 'Enter' || e.key === ' ') {
      const focusedElement = document.activeElement;
      if (focusedElement.classList.contains('btn-hero')) {
        e.preventDefault();
        focusedElement.click();
      }
    }
  }
  
  handleResize() {
    // Adjust animations for mobile
    if (window.innerWidth <= 768) {
      this.floatingCards.forEach(card => {
        card.style.display = 'none';
      });
    } else {
      this.floatingCards.forEach(card => {
        card.style.display = 'block';
      });
    }
  }
  
  updateDynamicContent() {
    // Update social proof numbers with animation
    this.animateCounters();
    
    // Update trust badge with real-time data
    this.updateTrustBadge();
  }
  
  animateCounters() {
    const counters = [
      { element: document.querySelector('.proof-item:nth-child(1) .proof-number'), target: 500, suffix: '+' },
      { element: document.querySelector('.proof-item:nth-child(2) .proof-number'), target: 10, suffix: 'k+' },
      { element: document.querySelector('.proof-item:nth-child(3) .proof-number'), target: 99.9, suffix: '%' }
    ];
    
    counters.forEach(({ element, target, suffix }) => {
      if (!element) return;
      
      let current = 0;
      const increment = target / 50;
      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        
        const displayValue = suffix === '%' ? current.toFixed(1) : Math.floor(current);
        element.textContent = displayValue + suffix;
      }, 30);
    });
  }
  
  updateTrustBadge() {
    // Simulate real-time updates (in production, this would fetch from API)
    const messages = [
      'Trusted by 500+ cleaning businesses',
      'Processing 10k+ bookings monthly',
      '99.9% uptime guarantee',
      'Enterprise-grade security'
    ];
    
    let currentIndex = 0;
    const badgeText = this.heroBadge?.querySelector('.badge-text');
    
    if (badgeText) {
      setInterval(() => {
        badgeText.style.opacity = '0';
        setTimeout(() => {
          currentIndex = (currentIndex + 1) % messages.length;
          badgeText.textContent = messages[currentIndex];
          badgeText.style.opacity = '1';
        }, 300);
      }, 5000);
    }
  }
  
  showDemoModal() {
    // Create and show demo modal
    const modal = document.createElement('div');
    modal.className = 'demo-modal';
    modal.innerHTML = `
      <div class="demo-modal-overlay">
        <div class="demo-modal-content">
          <div class="demo-modal-header">
            <h3>Watch NordBK Demo</h3>
            <button class="demo-modal-close" aria-label="Close demo modal">×</button>
          </div>
          <div class="demo-modal-body">
            <div class="demo-video-container">
              <iframe 
                src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                frameborder="0" 
                allowfullscreen
                title="NordBK Platform Demo">
              </iframe>
            </div>
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Bind close events
    const closeBtn = modal.querySelector('.demo-modal-close');
    const overlay = modal.querySelector('.demo-modal-overlay');
    
    closeBtn.addEventListener('click', () => this.closeDemoModal(modal));
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        this.closeDemoModal(modal);
      }
    });
    
    // Keyboard close
    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        this.closeDemoModal(modal);
        document.removeEventListener('keydown', handleKeydown);
      }
    };
    document.addEventListener('keydown', handleKeydown);
    
    // Animate in
    setTimeout(() => {
      modal.classList.add('show');
    }, 10);
  }
  
  closeDemoModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => {
      document.body.removeChild(modal);
      document.body.style.overflow = '';
    }, 300);
  }
  
  trackCTAClick(button) {
    // Analytics tracking
    const buttonText = button.textContent.trim();
    const buttonType = button.classList.contains('btn-primary') ? 'primary' : 'secondary';
    
    // Google Analytics 4
    if (typeof gtag !== 'undefined') {
      gtag('event', 'cta_click', {
        event_category: 'hero',
        event_label: buttonText,
        button_type: buttonType,
        section: 'hero'
      });
    }
    
    // Custom analytics
    if (window.analytics) {
      window.analytics.track('Hero CTA Clicked', {
        buttonText,
        buttonType,
        section: 'hero',
        timestamp: new Date().toISOString()
      });
    }
  }
  
  // Public methods
  refreshContent() {
    this.updateDynamicContent();
  }
  
  triggerAnimation() {
    if (!this.isVisible) {
      this.isVisible = true;
      this.animateHeroContent();
    }
  }
  
  updateStats(stats) {
    // Update social proof stats
    const proofNumbers = document.querySelectorAll('.proof-number');
    if (stats && proofNumbers.length >= 3) {
      proofNumbers[0].textContent = stats.businesses + '+';
      proofNumbers[1].textContent = stats.bookings + 'k+';
      proofNumbers[2].textContent = stats.uptime + '%';
    }
  }
  
  destroy() {
    // Clean up
    if (this.observer) {
      this.observer.disconnect();
    }
    
    // Remove event listeners
    this.ctaButtons.forEach(button => {
      button.removeEventListener('click', this.handleCTAClick);
      button.removeEventListener('mouseenter', this.handleCTAHover);
    });
    
    if (this.heroBadge) {
      this.heroBadge.removeEventListener('click', this.handleBadgeClick);
    }
    
    document.removeEventListener('keydown', this.handleKeydown);
    window.removeEventListener('resize', this.handleResize);
  }
}

// Demo modal styles
const demoModalStyles = `
  .demo-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
  }
  
  .demo-modal.show {
    opacity: 1;
    visibility: visible;
  }
  
  .demo-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  
  .demo-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    transform: scale(0.9);
    transition: transform 0.3s ease;
  }
  
  .demo-modal.show .demo-modal-content {
    transform: scale(1);
  }
  
  .demo-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .demo-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
  }
  
  .demo-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.2s;
  }
  
  .demo-modal-close:hover {
    background-color: #f1f5f9;
  }
  
  .demo-modal-body {
    padding: 0;
  }
  
  .demo-video-container {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
  }
  
  .demo-video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
`;

// Inject demo modal styles
const demoStyleSheet = document.createElement('style');
demoStyleSheet.textContent = demoModalStyles;
document.head.appendChild(demoStyleSheet);

// Initialize hero section when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  // Add a small delay to ensure all elements are rendered
  setTimeout(() => {
    window.heroSection = new HeroSection();
  }, 100);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = HeroSection;
}