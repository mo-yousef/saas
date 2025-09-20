/*
 * Features Section JavaScript
 * Handles animations, interactions, and dynamic feature content
 */

class FeaturesSection {
  constructor() {
    this.featuresSection = document.querySelector('.features-section');
    this.featureCards = document.querySelectorAll('.feature-card, .feature-card-large');
    this.featureIcons = document.querySelectorAll('.feature-icon');
    this.featureCTAs = document.querySelectorAll('.feature-cta');
    this.sectionBadge = document.querySelector('.section-badge');
    
    this.isVisible = false;
    this.animationDelay = 200;
    this.currentFeatureIndex = 0;
    
    this.init();
  }
  
  init() {
    this.setupIntersectionObserver();
    this.bindEvents();
    this.initializeAnimations();
    this.setupKeyboardNavigation();
    this.loadFeatureData();
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
          this.animateFeatures();
        }
      });
    }, options);
    
    if (this.featuresSection) {
      this.observer.observe(this.featuresSection);
    }
  }
  
  bindEvents() {
    // Feature card interactions
    this.featureCards.forEach((card, index) => {
      card.addEventListener('mouseenter', () => this.handleCardHover(card, index));
      card.addEventListener('mouseleave', () => this.handleCardLeave(card, index));
      card.addEventListener('click', () => this.handleCardClick(card, index));
    });
    
    // Feature CTA interactions
    this.featureCTAs.forEach(cta => {
      cta.addEventListener('click', (e) => this.handleCTAClick(e));
    });
    
    // Section badge interaction
    if (this.sectionBadge) {
      this.sectionBadge.addEventListener('click', () => this.handleBadgeClick());
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => this.handleKeydown(e));
    
    // Window resize
    window.addEventListener('resize', () => this.handleResize());
  }
  
  initializeAnimations() {
    // Set initial states for animations
    this.featureCards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(40px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      
      // Make cards focusable for keyboard navigation
      card.setAttribute('tabindex', '0');
      card.setAttribute('role', 'button');
      card.setAttribute('aria-label', `Feature: ${this.getFeatureTitle(card)}`);
    });
    
    // Initialize icons
    this.featureIcons.forEach(icon => {
      icon.style.opacity = '0';
      icon.style.transform = 'scale(0.3)';
      icon.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
    });
  }
  
  animateFeatures() {
    // Animate section badge first
    if (this.sectionBadge) {
      setTimeout(() => {
        this.sectionBadge.style.opacity = '1';
        this.sectionBadge.style.transform = 'translateY(0)';
      }, 0);
    }
    
    // Animate feature cards
    this.featureCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
        card.classList.add('animate-in');
        
        // Animate icon after card
        const icon = card.querySelector('.feature-icon');
        if (icon) {
          setTimeout(() => {
            icon.style.opacity = '1';
            icon.style.transform = 'scale(1)';
            icon.classList.add('animate-in');
          }, 200);
        }
      }, index * this.animationDelay);
    });
  }
  
  handleCardHover(card, index) {
    // Add hover effects
    const icon = card.querySelector('.feature-icon');
    if (icon) {
      icon.style.transform = 'scale(1.05) rotate(3deg)';
    }
    
    // Track hover for analytics
    this.trackFeatureInteraction('hover', index);
  }
  
  handleCardLeave(card, index) {
    // Reset hover effects
    const icon = card.querySelector('.feature-icon');
    if (icon) {
      icon.style.transform = 'scale(1) rotate(0deg)';
    }
  }
  
  handleCardClick(card, index) {
    // Show feature details modal
    const featureData = this.getFeatureData(card, index);
    this.showFeatureModal(featureData);
    
    // Add click animation
    card.style.transform = 'scale(0.98)';
    setTimeout(() => {
      card.style.transform = '';
    }, 150);
    
    // Track click
    this.trackFeatureInteraction('click', index);
  }
  
  handleCTAClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const cta = e.currentTarget;
    const featureCard = cta.closest('.feature-card, .feature-card-large');
    const featureIndex = Array.from(this.featureCards).indexOf(featureCard);
    
    // Show detailed feature information
    this.showFeatureDetails(featureIndex);
    
    // Track CTA click
    this.trackFeatureInteraction('cta_click', featureIndex);
  }
  
  handleBadgeClick() {
    // Scroll to features or show features overview
    this.showFeaturesOverview();
  }
  
  handleKeydown(e) {
    // Handle keyboard navigation
    if (e.key === 'Enter' || e.key === ' ') {
      const focusedElement = document.activeElement;
      if (focusedElement.classList.contains('feature-card') || 
          focusedElement.classList.contains('feature-card-large')) {
        e.preventDefault();
        focusedElement.click();
      }
    }
    
    // Arrow key navigation
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      this.handleArrowNavigation(e);
    }
  }
  
  handleArrowNavigation(e) {
    e.preventDefault();
    
    const direction = e.key === 'ArrowRight' ? 1 : -1;
    this.currentFeatureIndex = (this.currentFeatureIndex + direction + this.featureCards.length) % this.featureCards.length;
    
    this.featureCards[this.currentFeatureIndex].focus();
  }
  
  handleResize() {
    // Adjust animations for mobile
    if (window.innerWidth <= 768) {
      this.animationDelay = 100;
    } else {
      this.animationDelay = 200;
    }
  }
  
  setupKeyboardNavigation() {
    // Add keyboard navigation hints
    this.featureCards.forEach((card, index) => {
      card.addEventListener('focus', () => {
        this.currentFeatureIndex = index;
        card.style.outline = '2px solid var(--color-primary)';
        card.style.outlineOffset = '2px';
      });
      
      card.addEventListener('blur', () => {
        card.style.outline = '';
        card.style.outlineOffset = '';
      });
    });
  }
  
  getFeatureTitle(card) {
    const titleElement = card.querySelector('h3');
    return titleElement ? titleElement.textContent.trim() : 'Feature';
  }
  
  getFeatureData(card, index) {
    const title = card.querySelector('h3')?.textContent || 'Feature';
    const description = card.querySelector('p')?.textContent || '';
    const benefits = Array.from(card.querySelectorAll('.feature-benefits li')).map(li => li.textContent);
    const stats = Array.from(card.querySelectorAll('.feature-stats .stat')).map(stat => stat.textContent);
    
    return {
      index,
      title,
      description,
      benefits,
      stats,
      isLarge: card.classList.contains('feature-card-large')
    };
  }
  
  showFeatureModal(featureData) {
    const modalContent = this.createFeatureModalContent(featureData);
    this.createModal(featureData.title, modalContent);
  }
  
  createFeatureModalContent(data) {
    return `
      <div class="feature-modal-content">
        <div class="feature-modal-header">
          <h4>${data.title}</h4>
          ${data.isLarge ? '<span class="featured-badge">Featured</span>' : ''}
        </div>
        
        <div class="feature-modal-description">
          <p>${data.description}</p>
        </div>
        
        ${data.benefits.length > 0 ? `
          <div class="feature-modal-benefits">
            <h5>Key Benefits</h5>
            <ul>
              ${data.benefits.map(benefit => `<li>${benefit}</li>`).join('')}
            </ul>
          </div>
        ` : ''}
        
        ${data.stats.length > 0 ? `
          <div class="feature-modal-stats">
            <h5>Performance Metrics</h5>
            <div class="stats-grid">
              ${data.stats.map(stat => `<div class="stat-item">${stat}</div>`).join('')}
            </div>
          </div>
        ` : ''}
        
        <div class="feature-modal-actions">
          <a href="/register" class="btn btn-primary">Get Started</a>
          <a href="#demo" class="btn btn-secondary">Watch Demo</a>
        </div>
      </div>
    `;
  }
  
  showFeatureDetails(index) {
    const featureData = this.getFeatureData(this.featureCards[index], index);
    this.showFeatureModal(featureData);
  }
  
  showFeaturesOverview() {
    const overviewContent = `
      <div class="features-overview">
        <h4>Complete Feature Overview</h4>
        <p>NordBK provides everything you need to manage and grow your cleaning business:</p>
        
        <div class="features-list">
          <div class="feature-category">
            <h5>🚀 Core Features</h5>
            <ul>
              <li>Smart Booking System</li>
              <li>Customer Management</li>
              <li>Payment Processing</li>
              <li>Service Area Management</li>
            </ul>
          </div>
          
          <div class="feature-category">
            <h5>📊 Analytics & Reporting</h5>
            <ul>
              <li>Business Analytics</li>
              <li>Revenue Tracking</li>
              <li>Performance Metrics</li>
              <li>Custom Reports</li>
            </ul>
          </div>
          
          <div class="feature-category">
            <h5>🔧 Advanced Tools</h5>
            <ul>
              <li>Team Management</li>
              <li>Mobile Optimization</li>
              <li>API Integration</li>
              <li>White-label Options</li>
            </ul>
          </div>
        </div>
        
        <div class="overview-cta">
          <a href="/features" class="btn btn-primary">View All Features</a>
        </div>
      </div>
    `;
    
    this.createModal('Features Overview', overviewContent);
  }
  
  createModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'feature-modal';
    modal.innerHTML = `
      <div class="feature-modal-overlay">
        <div class="feature-modal-dialog">
          <div class="feature-modal-header-bar">
            <h3>${title}</h3>
            <button class="feature-modal-close" aria-label="Close modal">×</button>
          </div>
          <div class="feature-modal-body">
            ${content}
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Bind close events
    const closeBtn = modal.querySelector('.feature-modal-close');
    const overlay = modal.querySelector('.feature-modal-overlay');
    
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
  
  loadFeatureData() {
    // Simulate loading dynamic feature data
    setTimeout(() => {
      this.updateFeatureStats();
    }, 1000);
  }
  
  updateFeatureStats() {
    // Update feature statistics with real data
    const statsElements = document.querySelectorAll('.feature-stats .stat');
    
    // Example: Update booking system stats
    const bookingStats = document.querySelector('.feature-card-large .feature-stats');
    if (bookingStats) {
      const stats = bookingStats.querySelectorAll('.stat');
      if (stats.length >= 2) {
        stats[0].textContent = '40% increase in bookings';
        stats[1].textContent = 'Save 10+ hours per week';
      }
    }
  }
  
  trackFeatureInteraction(action, index) {
    const featureTitle = this.getFeatureTitle(this.featureCards[index]);
    
    // Google Analytics 4
    if (typeof gtag !== 'undefined') {
      gtag('event', 'feature_interaction', {
        event_category: 'features',
        event_label: featureTitle,
        action: action,
        feature_index: index
      });
    }
    
    // Custom analytics
    if (window.analytics) {
      window.analytics.track('Feature Interaction', {
        action,
        featureTitle,
        featureIndex: index,
        section: 'features',
        timestamp: new Date().toISOString()
      });
    }
  }
  
  // Public methods
  refreshFeatures() {
    this.loadFeatureData();
  }
  
  triggerAnimation() {
    if (!this.isVisible) {
      this.isVisible = true;
      this.animateFeatures();
    }
  }
  
  highlightFeature(index) {
    if (index >= 0 && index < this.featureCards.length) {
      const card = this.featureCards[index];
      card.style.transform = 'scale(1.02)';
      card.style.boxShadow = '0 25px 50px -12px rgba(99, 102, 241, 0.25)';
      
      setTimeout(() => {
        card.style.transform = '';
        card.style.boxShadow = '';
      }, 2000);
    }
  }
  
  destroy() {
    // Clean up
    if (this.observer) {
      this.observer.disconnect();
    }
    
    // Remove event listeners
    this.featureCards.forEach(card => {
      card.removeEventListener('mouseenter', this.handleCardHover);
      card.removeEventListener('mouseleave', this.handleCardLeave);
      card.removeEventListener('click', this.handleCardClick);
    });
    
    this.featureCTAs.forEach(cta => {
      cta.removeEventListener('click', this.handleCTAClick);
    });
    
    if (this.sectionBadge) {
      this.sectionBadge.removeEventListener('click', this.handleBadgeClick);
    }
    
    document.removeEventListener('keydown', this.handleKeydown);
    window.removeEventListener('resize', this.handleResize);
  }
}

// Feature modal styles
const featureModalStyles = `
  .feature-modal {
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
  
  .feature-modal.show {
    opacity: 1;
    visibility: visible;
  }
  
  .feature-modal-overlay {
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
  
  .feature-modal-dialog {
    background: white;
    border-radius: 16px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    transform: scale(0.9);
    transition: transform 0.3s ease;
  }
  
  .feature-modal.show .feature-modal-dialog {
    transform: scale(1);
  }
  
  .feature-modal-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .feature-modal-header-bar h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .feature-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    color: #64748b;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .feature-modal-close:hover {
    background-color: #f1f5f9;
    color: #334155;
  }
  
  .feature-modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(90vh - 120px);
  }
  
  .feature-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
  }
  
  .feature-modal-header h4 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .featured-badge {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  
  .feature-modal-description p {
    color: #475569;
    line-height: 1.6;
    margin-bottom: 20px;
  }
  
  .feature-modal-benefits h5,
  .feature-modal-stats h5 {
    margin: 20px 0 12px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .feature-modal-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  
  .feature-modal-benefits li {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #475569;
  }
  
  .feature-modal-benefits li::before {
    content: '✓';
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-radius: 50%;
    font-weight: bold;
    font-size: 0.875rem;
    flex-shrink: 0;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
  }
  
  .stat-item {
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    text-align: center;
  }
  
  .feature-modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
  }
  
  .features-overview .features-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
  }
  
  .feature-category h5 {
    margin: 0 0 12px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .feature-category ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  
  .feature-category li {
    padding: 4px 0;
    color: #475569;
    font-size: 0.875rem;
  }
  
  .overview-cta {
    text-align: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
  }
`;

// Inject feature modal styles
const featureStyleSheet = document.createElement('style');
featureStyleSheet.textContent = featureModalStyles;
document.head.appendChild(featureStyleSheet);

// Initialize features section when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    window.featuresSection = new FeaturesSection();
  }, 150);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = FeaturesSection;
}