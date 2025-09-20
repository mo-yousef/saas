/*
 * Header Component JavaScript
 * Handles mobile menu, scroll behavior, and accessibility
 */

class HeaderComponent {
  constructor() {
    this.header = document.querySelector('.site-header');
    this.mobileToggle = document.querySelector('.mobile-menu-toggle');
    this.mobileMenu = document.querySelector('.mobile-menu');
    this.mobileOverlay = document.querySelector('.mobile-menu-overlay');
    this.mobileClose = document.querySelector('.mobile-menu-close');
    this.navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
    
    this.lastScrollY = window.scrollY;
    this.isMenuOpen = false;
    this.scrollThreshold = 100;
    
    this.init();
  }
  
  init() {
    this.bindEvents();
    this.handleInitialScroll();
    this.setActiveNavLink();
  }
  
  bindEvents() {
    // Mobile menu toggle
    if (this.mobileToggle) {
      this.mobileToggle.addEventListener('click', () => this.toggleMobileMenu());
    }
    
    // Mobile menu close
    if (this.mobileClose) {
      this.mobileClose.addEventListener('click', () => this.closeMobileMenu());
    }
    
    // Mobile overlay close
    if (this.mobileOverlay) {
      this.mobileOverlay.addEventListener('click', () => this.closeMobileMenu());
    }
    
    // Scroll behavior
    window.addEventListener('scroll', () => this.handleScroll(), { passive: true });
    
    // Resize handler
    window.addEventListener('resize', () => this.handleResize());
    
    // Navigation link clicks
    this.navLinks.forEach(link => {
      link.addEventListener('click', (e) => this.handleNavClick(e));
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => this.handleKeydown(e));
    
    // Focus management
    document.addEventListener('focusin', (e) => this.handleFocusIn(e));
  }
  
  toggleMobileMenu() {
    this.isMenuOpen = !this.isMenuOpen;
    
    if (this.isMenuOpen) {
      this.openMobileMenu();
    } else {
      this.closeMobileMenu();
    }
  }
  
  openMobileMenu() {
    this.isMenuOpen = true;
    
    // Add classes
    this.mobileToggle?.classList.add('open');
    this.mobileMenu?.classList.add('open');
    this.mobileOverlay?.classList.add('open');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Focus management
    setTimeout(() => {
      const firstLink = this.mobileMenu?.querySelector('.mobile-nav-link');
      firstLink?.focus();
    }, 300);
    
    // Announce to screen readers
    this.announceToScreenReader('Navigation menu opened');
  }
  
  closeMobileMenu() {
    this.isMenuOpen = false;
    
    // Remove classes
    this.mobileToggle?.classList.remove('open');
    this.mobileMenu?.classList.remove('open');
    this.mobileOverlay?.classList.remove('open');
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Return focus to toggle button
    this.mobileToggle?.focus();
    
    // Announce to screen readers
    this.announceToScreenReader('Navigation menu closed');
  }
  
  handleScroll() {
    const currentScrollY = window.scrollY;
    
    // Add scrolled class for styling
    if (currentScrollY > 10) {
      this.header?.classList.add('scrolled');
    } else {
      this.header?.classList.remove('scrolled');
    }
    
    // Hide/show header on scroll (optional behavior)
    if (currentScrollY > this.scrollThreshold) {
      if (currentScrollY > this.lastScrollY && !this.isMenuOpen) {
        // Scrolling down - hide header
        this.header?.classList.add('hide-on-scroll');
        this.header?.classList.remove('show-on-scroll');
      } else {
        // Scrolling up - show header
        this.header?.classList.remove('hide-on-scroll');
        this.header?.classList.add('show-on-scroll');
      }
    }
    
    this.lastScrollY = currentScrollY;
  }
  
  handleInitialScroll() {
    // Set initial scroll state
    if (window.scrollY > 10) {
      this.header?.classList.add('scrolled');
    }
  }
  
  handleResize() {
    // Close mobile menu on resize to desktop
    if (window.innerWidth >= 1024 && this.isMenuOpen) {
      this.closeMobileMenu();
    }
  }
  
  handleNavClick(e) {
    const link = e.currentTarget;
    const href = link.getAttribute('href');
    
    // Handle anchor links
    if (href && href.startsWith('#')) {
      e.preventDefault();
      
      const targetId = href.substring(1);
      const targetElement = document.getElementById(targetId);
      
      if (targetElement) {
        // Close mobile menu if open
        if (this.isMenuOpen) {
          this.closeMobileMenu();
        }
        
        // Smooth scroll to target
        this.scrollToElement(targetElement);
        
        // Update active link
        this.setActiveNavLink(href);
        
        // Update URL without triggering navigation
        history.pushState(null, null, href);
      }
    } else if (this.isMenuOpen) {
      // Close mobile menu for external links
      this.closeMobileMenu();
    }
  }
  
  scrollToElement(element) {
    const headerHeight = this.header?.offsetHeight || 0;
    const targetPosition = element.offsetTop - headerHeight - 20;
    
    window.scrollTo({
      top: targetPosition,
      behavior: 'smooth'
    });
  }
  
  setActiveNavLink(activeHref = null) {
    // Remove active class from all links
    this.navLinks.forEach(link => {
      link.classList.remove('active');
    });
    
    // Set active link based on current section or provided href
    if (activeHref) {
      const activeLinks = document.querySelectorAll(`[href="${activeHref}"]`);
      activeLinks.forEach(link => link.classList.add('active'));
    } else {
      // Auto-detect active section based on scroll position
      this.updateActiveNavOnScroll();
    }
  }
  
  updateActiveNavOnScroll() {
    const sections = document.querySelectorAll('section[id]');
    const headerHeight = this.header?.offsetHeight || 0;
    const scrollPosition = window.scrollY + headerHeight + 100;
    
    let activeSection = null;
    
    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionBottom = sectionTop + section.offsetHeight;
      
      if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
        activeSection = section.id;
      }
    });
    
    if (activeSection) {
      const activeLinks = document.querySelectorAll(`[href="#${activeSection}"]`);
      activeLinks.forEach(link => link.classList.add('active'));
    }
  }
  
  handleKeydown(e) {
    // Escape key closes mobile menu
    if (e.key === 'Escape' && this.isMenuOpen) {
      this.closeMobileMenu();
    }
    
    // Tab navigation within mobile menu
    if (e.key === 'Tab' && this.isMenuOpen) {
      this.handleTabNavigation(e);
    }
  }
  
  handleTabNavigation(e) {
    const focusableElements = this.mobileMenu?.querySelectorAll(
      'a, button, [tabindex]:not([tabindex="-1"])'
    );
    
    if (!focusableElements || focusableElements.length === 0) return;
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    if (e.shiftKey) {
      // Shift + Tab
      if (document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
      }
    } else {
      // Tab
      if (document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
      }
    }
  }
  
  handleFocusIn(e) {
    // Close mobile menu if focus moves outside
    if (this.isMenuOpen && this.mobileMenu && !this.mobileMenu.contains(e.target)) {
      // Allow focus on toggle button
      if (e.target !== this.mobileToggle) {
        this.closeMobileMenu();
      }
    }
  }
  
  announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    
    setTimeout(() => {
      document.body.removeChild(announcement);
    }, 1000);
  }
  
  // Public methods for external control
  showHeader() {
    this.header?.classList.remove('hide-on-scroll');
    this.header?.classList.add('show-on-scroll');
  }
  
  hideHeader() {
    this.header?.classList.add('hide-on-scroll');
    this.header?.classList.remove('show-on-scroll');
  }
  
  updateTrustBadge(text) {
    const trustBadge = document.querySelector('.trust-badge');
    if (trustBadge) {
      trustBadge.textContent = text;
    }
  }
  
  destroy() {
    // Clean up event listeners
    window.removeEventListener('scroll', this.handleScroll);
    window.removeEventListener('resize', this.handleResize);
    document.removeEventListener('keydown', this.handleKeydown);
    document.removeEventListener('focusin', this.handleFocusIn);
    
    // Reset body overflow
    document.body.style.overflow = '';
  }
}

// Initialize header component when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    window.headerComponent = new HeaderComponent();
  }, 50);
});

// Handle scroll-based active nav updates
window.addEventListener('scroll', () => {
  if (window.headerComponent) {
    window.headerComponent.updateActiveNavOnScroll();
  }
}, { passive: true });

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = HeaderComponent;
}