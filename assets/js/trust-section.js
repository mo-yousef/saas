/*
 * Trust Section JavaScript
 * Handles animations, interactions, and dynamic trust indicators
 */

class TrustSection {
  constructor() {
    this.trustSection = document.querySelector('.trust-section');
    this.trustCards = document.querySelectorAll('.trust-card');
    this.logoItems = document.querySelectorAll('.logo-item');
    this.metricNumbers = document.querySelectorAll('.metric-number');
    this.certBadges = document.querySelectorAll('.cert-badge');
    
    this.isVisible = false;
    this.animationDelay = 150;
    
    this.init();
  }
  
  init() {
    this.setupIntersectionObserver();
    this.bindEvents();
    this.initializeAnimations();
    this.loadTrustData();
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
          this.animateTrustContent();
        }
      });
    }, options);
    
    if (this.trustSection) {
      this.observer.observe(this.trustSection);
    }
  }
  
  bindEvents() {
    // Trust card interactions
    this.trustCards.forEach(card => {
      card.addEventListener('mouseenter', () => this.handleCardHover(card));
      card.addEventListener('click', () => this.handleCardClick(card));
    });
    
    // Certification badge interactions
    this.certBadges.forEach(badge => {
      badge.addEventListener('click', () => this.handleCertClick(badge));
    });
    
    // Logo item interactions
    this.logoItems.forEach(logo => {
      logo.addEventListener('click', () => this.handleLogoClick(logo));
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => this.handleKeydown(e));
  }
  
  initializeAnimations() {
    // Set initial states for animations
    const animatedElements = [
      ...this.trustCards,
      ...this.logoItems,
      ...this.certBadges
    ];
    
    animatedElements.forEach((element, index) => {
      element.style.opacity = '0';
      element.style.transform = 'translateY(30px)';
      element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
  }
  
  animateTrustContent() {
    // Animate trust cards
    this.trustCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
        card.classList.add('animate-in');
      }, index * this.animationDelay);
    });
    
    // Animate logo items
    setTimeout(() => {
      this.logoItems.forEach((logo, index) => {
        setTimeout(() => {
          logo.style.opacity = '1';
          logo.style.transform = 'translateY(0)';
          logo.classList.add('animate-in');
        }, index * 100);
      });
    }, 600);
    
    // Animate certification badges
    setTimeout(() => {
      this.certBadges.forEach((badge, index) => {
        setTimeout(() => {
          badge.style.opacity = '1';
          badge.style.transform = 'translateY(0)';
        }, index * 50);
      });
    }, 800);
    
    // Animate metrics
    this.animateMetrics();
  }
  
  animateMetrics() {
    const metrics = [
      { element: document.querySelector('.metric-item:nth-child(1) .metric-number'), target: 500, suffix: '+', duration: 2000 },
      { element: document.querySelector('.metric-item:nth-child(2) .metric-number'), target: 99.9, suffix: '%', duration: 2500 },
      { element: document.querySelector('.metric-item:nth-child(3) .metric-number'), target: 24, suffix: '/7', duration: 1500 },
      { element: document.querySelector('.metric-item:nth-child(4) .metric-number'), target: 2020, suffix: '', duration: 2000 }
    ];
    
    metrics.forEach(({ element, target, suffix, duration }) => {
      if (!element) return;
      
      this.animateCounter(element, 0, target, duration, suffix);
    });
  }
  
  animateCounter(element, start, end, duration, suffix = '') {
    const startTime = performance.now();
    const isDecimal = end % 1 !== 0;
    
    const updateCounter = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Easing function for smooth animation
      const easeOutQuart = 1 - Math.pow(1 - progress, 4);
      const current = start + (end - start) * easeOutQuart;
      
      const displayValue = isDecimal ? current.toFixed(1) : Math.floor(current);
      element.textContent = displayValue + suffix;
      
      if (progress < 1) {
        requestAnimationFrame(updateCounter);
      }
    };
    
    requestAnimationFrame(updateCounter);
  }
  
  handleCardHover(card) {
    // Add subtle interaction feedback
    const icon = card.querySelector('.trust-icon');
    if (icon) {
      icon.style.transform = 'scale(1.05) rotate(2deg)';
      setTimeout(() => {
        icon.style.transform = '';
      }, 300);
    }
  }
  
  handleCardClick(card) {
    // Show detailed trust information
    const cardType = this.getCardType(card);
    this.showTrustModal(cardType);
    
    // Track interaction
    this.trackTrustInteraction('card_click', cardType);
  }
  
  handleCertClick(badge) {
    const certName = badge.querySelector('.cert-name')?.textContent || 'certification';
    this.showCertificationModal(certName);
    
    // Track interaction
    this.trackTrustInteraction('cert_click', certName);
  }
  
  handleLogoClick(logo) {
    const companyName = logo.textContent.trim();
    this.showCustomerStory(companyName);
    
    // Track interaction
    this.trackTrustInteraction('logo_click', companyName);
  }
  
  getCardType(card) {
    const title = card.querySelector('h3')?.textContent || '';
    if (title.includes('Business')) return 'business';
    if (title.includes('Security')) return 'security';
    if (title.includes('Results')) return 'results';
    return 'general';
  }
  
  showTrustModal(type) {
    const modalContent = this.getTrustModalContent(type);
    this.createModal('Trust Information', modalContent);
  }
  
  showCertificationModal(certName) {
    const modalContent = this.getCertificationContent(certName);
    this.createModal(`${certName} Certification`, modalContent);
  }
  
  showCustomerStory(companyName) {
    const modalContent = this.getCustomerStoryContent(companyName);
    this.createModal(`${companyName} Success Story`, modalContent);
  }
  
  getTrustModalContent(type) {
    const content = {
      business: `
        <div class="trust-modal-content">
          <h4>Established Business</h4>
          <p>NordBK has been serving the cleaning industry since 2020, building trust through:</p>
          <ul>
            <li>Transparent business practices</li>
            <li>Registered business entity</li>
            <li>Physical headquarters location</li>
            <li>Dedicated customer support team</li>
            <li>Regular financial audits</li>
          </ul>
          <div class="business-details">
            <p><strong>Registration:</strong> [Business Registration Number]</p>
            <p><strong>Address:</strong> [Physical Business Address]</p>
            <p><strong>Founded:</strong> 2020</p>
          </div>
        </div>
      `,
      security: `
        <div class="trust-modal-content">
          <h4>Enterprise Security</h4>
          <p>Your data security is our top priority. We maintain:</p>
          <ul>
            <li>SOC 2 Type II compliance</li>
            <li>256-bit SSL encryption</li>
            <li>GDPR compliance</li>
            <li>Regular security audits</li>
            <li>Secure data centers</li>
            <li>24/7 security monitoring</li>
          </ul>
          <div class="security-certifications">
            <img src="/assets/images/soc2-cert.png" alt="SOC 2 Certificate" />
            <img src="/assets/images/gdpr-cert.png" alt="GDPR Compliance" />
          </div>
        </div>
      `,
      results: `
        <div class="trust-modal-content">
          <h4>Proven Results</h4>
          <p>Our customers consistently see significant improvements:</p>
          <ul>
            <li>40% average increase in bookings</li>
            <li>10+ hours saved per week</li>
            <li>60% reduction in no-shows</li>
            <li>25% increase in customer satisfaction</li>
            <li>99.9% platform uptime</li>
          </ul>
          <div class="results-chart">
            <p><em>Based on data from 500+ active customers over 12 months</em></p>
          </div>
        </div>
      `
    };
    
    return content[type] || content.business;
  }
  
  getCertificationContent(certName) {
    return `
      <div class="cert-modal-content">
        <h4>${certName}</h4>
        <p>This certification demonstrates our commitment to:</p>
        <ul>
          <li>Industry best practices</li>
          <li>Security standards compliance</li>
          <li>Regular third-party audits</li>
          <li>Continuous improvement</li>
        </ul>
        <div class="cert-details">
          <p><strong>Issued:</strong> [Certification Date]</p>
          <p><strong>Valid Until:</strong> [Expiration Date]</p>
          <p><strong>Auditor:</strong> [Third-party Auditor]</p>
        </div>
        <a href="#" class="btn btn-primary">View Certificate</a>
      </div>
    `;
  }
  
  getCustomerStoryContent(companyName) {
    const stories = {
      'CleanPro Services': `
        <div class="story-content">
          <h4>CleanPro Services Success Story</h4>
          <p>CleanPro Services transformed their business with NordBK:</p>
          <ul>
            <li>150% increase in online bookings</li>
            <li>Reduced administrative time by 12 hours/week</li>
            <li>Improved customer satisfaction scores</li>
            <li>Expanded to 3 new service areas</li>
          </ul>
          <blockquote>
            "NordBK gave us the tools to scale our business professionally. 
            The booking system is intuitive and our customers love it."
            <cite>- Sarah Johnson, Owner</cite>
          </blockquote>
        </div>
      `,
      'Elite Cleaning Co': `
        <div class="story-content">
          <h4>Elite Cleaning Co Success Story</h4>
          <p>Elite Cleaning Co streamlined operations with NordBK:</p>
          <ul>
            <li>Automated 80% of booking processes</li>
            <li>Increased revenue by 35%</li>
            <li>Better team coordination</li>
            <li>Enhanced customer communication</li>
          </ul>
          <blockquote>
            "The analytics dashboard helps us make data-driven decisions. 
            We've never been more organized."
            <cite>- Michael Rodriguez, Manager</cite>
          </blockquote>
        </div>
      `
    };
    
    return stories[companyName] || `
      <div class="story-content">
        <h4>${companyName} Success Story</h4>
        <p>Another satisfied customer using NordBK to grow their cleaning business.</p>
        <p>Contact us to learn more about their success story.</p>
        <a href="#contact" class="btn btn-primary">Get in Touch</a>
      </div>
    `;
  }
  
  createModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'trust-modal';
    modal.innerHTML = `
      <div class="trust-modal-overlay">
        <div class="trust-modal-dialog">
          <div class="trust-modal-header">
            <h3>${title}</h3>
            <button class="trust-modal-close" aria-label="Close modal">×</button>
          </div>
          <div class="trust-modal-body">
            ${content}
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Bind close events
    const closeBtn = modal.querySelector('.trust-modal-close');
    const overlay = modal.querySelector('.trust-modal-overlay');
    
    closeBtn.addEventListener('click', () => this.closeModal(modal));
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        this.closeModal(modal);
      }
    });
    
    // Keyboard close
    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        this.closeModal(modal);
        document.removeEventListener('keydown', handleKeydown);
      }
    };
    document.addEventListener('keydown', handleKeydown);
    
    // Animate in
    setTimeout(() => {
      modal.classList.add('show');
    }, 10);
  }
  
  closeModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => {
      document.body.removeChild(modal);
      document.body.style.overflow = '';
    }, 300);
  }
  
  handleKeydown(e) {
    // Handle keyboard navigation
    if (e.key === 'Enter' || e.key === ' ') {
      const focusedElement = document.activeElement;
      if (focusedElement.classList.contains('trust-card') ||
          focusedElement.classList.contains('cert-badge') ||
          focusedElement.classList.contains('logo-item')) {
        e.preventDefault();
        focusedElement.click();
      }
    }
  }
  
  loadTrustData() {
    // Simulate loading real trust data (in production, this would be an API call)
    setTimeout(() => {
      this.updateTrustMetrics({
        customers: 500,
        uptime: 99.9,
        support: '24/7',
        founded: 2020
      });
    }, 1000);
  }
  
  updateTrustMetrics(data) {
    const metrics = document.querySelectorAll('.metric-number');
    if (metrics.length >= 4) {
      metrics[0].textContent = data.customers + '+';
      metrics[1].textContent = data.uptime + '%';
      metrics[2].textContent = data.support;
      metrics[3].textContent = data.founded;
    }
  }
  
  trackTrustInteraction(action, label) {
    // Analytics tracking
    if (typeof gtag !== 'undefined') {
      gtag('event', 'trust_interaction', {
        event_category: 'trust',
        event_label: label,
        action: action
      });
    }
    
    // Custom analytics
    if (window.analytics) {
      window.analytics.track('Trust Interaction', {
        action,
        label,
        section: 'trust',
        timestamp: new Date().toISOString()
      });
    }
  }
  
  // Public methods
  refreshTrustData() {
    this.loadTrustData();
  }
  
  triggerAnimation() {
    if (!this.isVisible) {
      this.isVisible = true;
      this.animateTrustContent();
    }
  }
  
  updateCustomerCount(count) {
    const customerMetric = document.querySelector('.metric-item:nth-child(1) .metric-number');
    if (customerMetric) {
      this.animateCounter(customerMetric, parseInt(customerMetric.textContent), count, 1000, '+');
    }
  }
  
  destroy() {
    // Clean up
    if (this.observer) {
      this.observer.disconnect();
    }
    
    // Remove event listeners
    this.trustCards.forEach(card => {
      card.removeEventListener('mouseenter', this.handleCardHover);
      card.removeEventListener('click', this.handleCardClick);
    });
    
    this.certBadges.forEach(badge => {
      badge.removeEventListener('click', this.handleCertClick);
    });
    
    this.logoItems.forEach(logo => {
      logo.removeEventListener('click', this.handleLogoClick);
    });
    
    document.removeEventListener('keydown', this.handleKeydown);
  }
}

// Trust modal styles
const trustModalStyles = `
  .trust-modal {
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
  
  .trust-modal.show {
    opacity: 1;
    visibility: visible;
  }
  
  .trust-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  
  .trust-modal-dialog {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    transform: scale(0.9);
    transition: transform 0.3s ease;
  }
  
  .trust-modal.show .trust-modal-dialog {
    transform: scale(1);
  }
  
  .trust-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .trust-modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .trust-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    color: #64748b;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .trust-modal-close:hover {
    background-color: #f1f5f9;
    color: #334155;
  }
  
  .trust-modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(90vh - 120px);
  }
  
  .trust-modal-content h4 {
    margin: 0 0 16px 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .trust-modal-content ul {
    margin: 16px 0;
    padding-left: 20px;
  }
  
  .trust-modal-content li {
    margin-bottom: 8px;
    color: #475569;
  }
  
  .business-details,
  .cert-details {
    margin-top: 20px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #6366f1;
  }
  
  .story-content blockquote {
    margin: 20px 0;
    padding: 16px;
    background: #f8fafc;
    border-left: 4px solid #10b981;
    border-radius: 8px;
    font-style: italic;
  }
  
  .story-content cite {
    display: block;
    margin-top: 8px;
    font-style: normal;
    font-weight: 600;
    color: #374151;
  }
`;

// Inject trust modal styles
const trustStyleSheet = document.createElement('style');
trustStyleSheet.textContent = trustModalStyles;
document.head.appendChild(trustStyleSheet);

// Initialize trust section when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    window.trustSection = new TrustSection();
  }, 250);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = TrustSection;
}