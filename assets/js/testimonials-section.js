/*
 * Testimonials Section JavaScript
 * Handles animations and interactions for testimonials
 */

class TestimonialsSection {
  constructor() {
    this.testimonialsSection = document.querySelector('.testimonials-section');
    this.testimonialCards = document.querySelectorAll('.testimonial-card');
    
    this.isVisible = false;
    this.animationDelay = 200;
    
    this.init();
  }
  
  init() {
    this.setupIntersectionObserver();
    this.bindEvents();
    this.initializeAnimations();
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
          this.animateTestimonials();
        }
      });
    }, options);
    
    if (this.testimonialsSection) {
      this.observer.observe(this.testimonialsSection);
    }
  }
  
  bindEvents() {
    // Testimonial card interactions
    this.testimonialCards.forEach((card, index) => {
      card.addEventListener('mouseenter', () => this.handleCardHover(card, index));
      card.addEventListener('click', () => this.handleCardClick(card, index));
    });
  }
  
  initializeAnimations() {
    // Set initial states for animations
    this.testimonialCards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(40px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
  }
  
  animateTestimonials() {
    // Animate testimonial cards
    this.testimonialCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
        card.classList.add('animate-in');
        
        // Animate avatar after card
        const avatar = card.querySelector('.author-avatar');
        if (avatar) {
          setTimeout(() => {
            avatar.classList.add('animate-in');
          }, 200);
        }
      }, index * this.animationDelay);
    });
  }
  
  handleCardHover(card, index) {
    // Add subtle hover effect
    const avatar = card.querySelector('.author-avatar');
    if (avatar) {
      avatar.style.transform = 'scale(1.1) rotate(5deg)';
      setTimeout(() => {
        avatar.style.transform = '';
      }, 300);
    }
  }
  
  handleCardClick(card, index) {
    // Show expanded testimonial or customer story
    const customerName = card.querySelector('.author-name')?.textContent || 'Customer';
    this.showCustomerStory(customerName, card);
  }
  
  showCustomerStory(customerName, card) {
    const testimonialContent = card.querySelector('.testimonial-content p')?.textContent || '';
    const authorTitle = card.querySelector('.author-title')?.textContent || '';
    const stats = Array.from(card.querySelectorAll('.testimonial-stats .stat')).map(stat => ({
      value: stat.querySelector('.stat-value')?.textContent || '',
      label: stat.querySelector('.stat-label')?.textContent || ''
    }));
    
    const modalContent = `
      <div class="customer-story-content">
        <div class="story-header">
          <h4>${customerName}'s Success Story</h4>
          <p class="story-subtitle">${authorTitle}</p>
        </div>
        
        <div class="story-testimonial">
          <blockquote>
            "${testimonialContent}"
          </blockquote>
        </div>
        
        ${stats.length > 0 ? `
          <div class="story-results">
            <h5>Results Achieved</h5>
            <div class="results-grid">
              ${stats.map(stat => `
                <div class="result-item">
                  <span class="result-value">${stat.value}</span>
                  <span class="result-label">${stat.label}</span>
                </div>
              `).join('')}
            </div>
          </div>
        ` : ''}
        
        <div class="story-cta">
          <p>Ready to achieve similar results?</p>
          <a href="/register" class="btn btn-primary">Start Your Free Trial</a>
        </div>
      </div>
    `;
    
    this.createModal(`${customerName}'s Success Story`, modalContent);
  }
  
  createModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'testimonial-modal';
    modal.innerHTML = `
      <div class="testimonial-modal-overlay">
        <div class="testimonial-modal-dialog">
          <div class="testimonial-modal-header">
            <h3>${title}</h3>
            <button class="testimonial-modal-close" aria-label="Close modal">×</button>
          </div>
          <div class="testimonial-modal-body">
            ${content}
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Bind close events
    const closeBtn = modal.querySelector('.testimonial-modal-close');
    const overlay = modal.querySelector('.testimonial-modal-overlay');
    
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
  
  // Public methods
  triggerAnimation() {
    if (!this.isVisible) {
      this.isVisible = true;
      this.animateTestimonials();
    }
  }
  
  destroy() {
    // Clean up
    if (this.observer) {
      this.observer.disconnect();
    }
    
    // Remove event listeners
    this.testimonialCards.forEach(card => {
      card.removeEventListener('mouseenter', this.handleCardHover);
      card.removeEventListener('click', this.handleCardClick);
    });
  }
}

// Testimonial modal styles
const testimonialModalStyles = `
  .testimonial-modal {
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
  
  .testimonial-modal.show {
    opacity: 1;
    visibility: visible;
  }
  
  .testimonial-modal-overlay {
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
  
  .testimonial-modal-dialog {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    transform: scale(0.9);
    transition: transform 0.3s ease;
  }
  
  .testimonial-modal.show .testimonial-modal-dialog {
    transform: scale(1);
  }
  
  .testimonial-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .testimonial-modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .testimonial-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    color: #64748b;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .testimonial-modal-close:hover {
    background-color: #f1f5f9;
    color: #334155;
  }
  
  .testimonial-modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(90vh - 120px);
  }
  
  .story-header h4 {
    margin: 0 0 8px 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .story-subtitle {
    color: #64748b;
    margin: 0 0 20px 0;
  }
  
  .story-testimonial blockquote {
    margin: 0 0 24px 0;
    padding: 20px;
    background: #f8fafc;
    border-left: 4px solid #10b981;
    border-radius: 8px;
    font-style: italic;
    font-size: 1.1rem;
    line-height: 1.6;
  }
  
  .story-results h5 {
    margin: 0 0 16px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  
  .result-item {
    text-align: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .result-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #10b981;
    margin-bottom: 4px;
  }
  
  .result-label {
    font-size: 0.875rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  
  .story-cta {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
  }
  
  .story-cta p {
    margin: 0 0 16px 0;
    color: #475569;
  }
`;

// Inject testimonial modal styles
const testimonialStyleSheet = document.createElement('style');
testimonialStyleSheet.textContent = testimonialModalStyles;
document.head.appendChild(testimonialStyleSheet);

// Initialize testimonials section when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    window.testimonialsSection = new TestimonialsSection();
  }, 200);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = TestimonialsSection;
}