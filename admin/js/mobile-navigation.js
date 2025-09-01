/**
 * Orlando International Resorts - Mobile Navigation Manager
 * Touch-friendly navigation with gesture support and responsive behavior
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class MobileNavigation {
    constructor() {
        this.sidebar = null;
        this.overlay = null;
        this.toggleButton = null;
        this.isOpen = false;
        this.touchStartX = 0;
        this.touchCurrentX = 0;
        this.touchStartY = 0;
        this.touchCurrentY = 0;
        this.isDragging = false;
        this.isVerticalScroll = false;
        
        this.init();
    }

    /**
     * Initialize mobile navigation
     */
    init() {
        console.log('[MobileNavigation] Initializing...');
        
        this.createMobileNavigation();
        this.setupEventListeners();
        this.setupGestureHandling();
        this.setupKeyboardNavigation();
        
        console.log('[MobileNavigation] Initialization complete');
    }

    /**
     * Create mobile navigation structure
     */
    createMobileNavigation() {
        // Check if mobile nav already exists
        if (document.querySelector('.mobile-nav')) {
            this.attachToExisting();
            return;
        }

        // Create mobile navigation header
        const mobileNav = document.createElement('div');
        mobileNav.className = 'mobile-nav d-desktop-none';
        mobileNav.innerHTML = `
            <a href="/admin/" class="logo">
                <img src="/Hotel/images/logo-full.png" alt="Orlando International Resorts" height="32">
                Orlando Resorts
            </a>
            <button class="mobile-menu-toggle" type="button" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        `;

        // Create mobile sidebar
        const mobileSidebar = document.createElement('div');
        mobileSidebar.className = 'mobile-sidebar';
        mobileSidebar.innerHTML = this.createSidebarContent();

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'mobile-sidebar-overlay';

        // Insert into DOM
        document.body.insertBefore(mobileNav, document.body.firstChild);
        document.body.appendChild(mobileSidebar);
        document.body.appendChild(overlay);

        // Add body padding for fixed header
        document.body.style.paddingTop = '60px';

        // Store references
        this.sidebar = mobileSidebar;
        this.overlay = overlay;
        this.toggleButton = mobileNav.querySelector('.mobile-menu-toggle');
    }

    /**
     * Attach to existing navigation elements
     */
    attachToExisting() {
        this.sidebar = document.querySelector('.mobile-sidebar');
        this.overlay = document.querySelector('.mobile-sidebar-overlay');
        this.toggleButton = document.querySelector('.mobile-menu-toggle');
    }

    /**
     * Create sidebar content from existing navigation
     */
    createSidebarContent() {
        // Try to get navigation from existing admin sidebar
        const existingSidebar = document.querySelector('.admin-sidebar');
        let menuItems = [];

        if (existingSidebar) {
            const navLinks = existingSidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                const icon = link.querySelector('i');
                const text = link.querySelector('span') || link;
                
                menuItems.push({
                    href: link.href,
                    icon: icon ? icon.className : 'fas fa-circle',
                    text: text.textContent.trim(),
                    active: link.classList.contains('active')
                });
            });
        } else {
            // Default menu items
            menuItems = [
                { href: '/admin/', icon: 'fas fa-tachometer-alt', text: 'Dashboard', active: true },
                { href: '/admin/reservation.php', icon: 'fas fa-calendar-plus', text: 'Reservations' },
                { href: '/admin/room.php', icon: 'fas fa-bed', text: 'Rooms' },
                { href: '/admin/orders.php', icon: 'fas fa-utensils', text: 'Orders' },
                { href: '/admin/financial_reports.php', icon: 'fas fa-chart-line', text: 'Reports' },
                { href: '/admin/messages.php', icon: 'fas fa-envelope', text: 'Messages' },
                { href: '/admin/settings.php', icon: 'fas fa-cogs', text: 'Settings' }
            ];
        }

        // Generate menu HTML
        const menuHTML = menuItems.map(item => `
            <li class="mobile-menu-item">
                <a href="${item.href}" class="mobile-menu-link ${item.active ? 'active' : ''}">
                    <i class="${item.icon}"></i>
                    <span>${item.text}</span>
                </a>
            </li>
        `).join('');

        return `
            <div class="mobile-sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name">${this.getCurrentUser()}</div>
                        <div class="user-role">${this.getCurrentUserRole()}</div>
                    </div>
                </div>
            </div>
            <nav class="mobile-sidebar-nav">
                <ul class="mobile-menu">
                    ${menuHTML}
                </ul>
            </nav>
            <div class="mobile-sidebar-footer">
                <a href="/admin/logout.php" class="mobile-menu-link logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        `;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Toggle button click
        if (this.toggleButton) {
            this.toggleButton.addEventListener('click', () => {
                this.toggle();
            });
        }

        // Overlay click
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                this.close();
            });
        }

        // Menu item clicks
        if (this.sidebar) {
            const menuLinks = this.sidebar.querySelectorAll('.mobile-menu-link');
            menuLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    // Close sidebar on navigation
                    if (!link.classList.contains('logout-link')) {
                        this.close();
                    }
                    
                    // Add loading state
                    this.addLoadingState(link);
                });
            });
        }

        // Window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Prevent scrolling when sidebar is open
        document.addEventListener('touchmove', (e) => {
            if (this.isOpen && !this.sidebar.contains(e.target)) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    /**
     * Setup gesture handling for swipe navigation
     */
    setupGestureHandling() {
        // Touch events for swipe gestures
        document.addEventListener('touchstart', (e) => {
            this.handleTouchStart(e);
        }, { passive: true });

        document.addEventListener('touchmove', (e) => {
            this.handleTouchMove(e);
        }, { passive: false });

        document.addEventListener('touchend', (e) => {
            this.handleTouchEnd(e);
        }, { passive: true });

        // Mouse events for desktop testing
        document.addEventListener('mousedown', (e) => {
            if (e.button === 0) { // Left click only
                this.handleTouchStart(e);
            }
        });

        document.addEventListener('mousemove', (e) => {
            this.handleTouchMove(e);
        });

        document.addEventListener('mouseup', (e) => {
            this.handleTouchEnd(e);
        });
    }

    /**
     * Setup keyboard navigation
     */
    setupKeyboardNavigation() {
        if (!this.sidebar) return;

        const menuLinks = this.sidebar.querySelectorAll('.mobile-menu-link');
        
        menuLinks.forEach((link, index) => {
            link.addEventListener('keydown', (e) => {
                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        const nextIndex = (index + 1) % menuLinks.length;
                        menuLinks[nextIndex].focus();
                        break;
                        
                    case 'ArrowUp':
                        e.preventDefault();
                        const prevIndex = (index - 1 + menuLinks.length) % menuLinks.length;
                        menuLinks[prevIndex].focus();
                        break;
                        
                    case 'Home':
                        e.preventDefault();
                        menuLinks[0].focus();
                        break;
                        
                    case 'End':
                        e.preventDefault();
                        menuLinks[menuLinks.length - 1].focus();
                        break;
                }
            });
        });
    }

    /**
     * Handle touch start
     */
    handleTouchStart(e) {
        const touch = e.touches ? e.touches[0] : e;
        this.touchStartX = touch.clientX;
        this.touchStartY = touch.clientY;
        this.touchCurrentX = touch.clientX;
        this.touchCurrentY = touch.clientY;
        this.isDragging = false;
        this.isVerticalScroll = false;
    }

    /**
     * Handle touch move
     */
    handleTouchMove(e) {
        if (!e.touches && e.type === 'mousemove' && e.buttons !== 1) {
            return; // Not dragging with mouse
        }

        const touch = e.touches ? e.touches[0] : e;
        this.touchCurrentX = touch.clientX;
        this.touchCurrentY = touch.clientY;

        const deltaX = this.touchCurrentX - this.touchStartX;
        const deltaY = this.touchCurrentY - this.touchStartY;
        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);

        // Determine if this is vertical scrolling
        if (!this.isDragging && !this.isVerticalScroll) {
            if (absDeltaY > absDeltaX && absDeltaY > 10) {
                this.isVerticalScroll = true;
                return;
            } else if (absDeltaX > 10) {
                this.isDragging = true;
            }
        }

        if (this.isVerticalScroll) {
            return; // Let normal scrolling happen
        }

        if (this.isDragging) {
            e.preventDefault();
            
            // Handle swipe to open (from left edge)
            if (!this.isOpen && this.touchStartX < 20 && deltaX > 0) {
                const progress = Math.min(deltaX / 250, 1);
                this.updateSidebarPosition(progress);
            }
            
            // Handle swipe to close (when open)
            if (this.isOpen && deltaX < 0) {
                const progress = Math.max(1 + (deltaX / 250), 0);
                this.updateSidebarPosition(progress);
            }
        }
    }

    /**
     * Handle touch end
     */
    handleTouchEnd(e) {
        if (!this.isDragging) {
            this.isDragging = false;
            this.isVerticalScroll = false;
            return;
        }

        const deltaX = this.touchCurrentX - this.touchStartX;
        const threshold = 100; // Minimum swipe distance

        // Swipe to open
        if (!this.isOpen && this.touchStartX < 20 && deltaX > threshold) {
            this.open();
        }
        // Swipe to close
        else if (this.isOpen && deltaX < -threshold) {
            this.close();
        }
        // Reset position if threshold not met
        else {
            this.updateSidebarPosition(this.isOpen ? 1 : 0);
        }

        this.isDragging = false;
        this.isVerticalScroll = false;
    }

    /**
     * Update sidebar position during swipe
     */
    updateSidebarPosition(progress) {
        if (!this.sidebar || !this.overlay) return;

        const translateX = -300 + (progress * 300); // 300px is sidebar width
        const opacity = progress * 0.5;

        this.sidebar.style.transform = `translateX(${translateX}px)`;
        this.overlay.style.opacity = opacity;
        this.overlay.style.visibility = progress > 0 ? 'visible' : 'hidden';
    }

    /**
     * Open sidebar
     */
    open() {
        if (this.isOpen) return;

        this.isOpen = true;
        
        if (this.sidebar) {
            this.sidebar.classList.add('open');
            this.sidebar.style.transform = '';
        }
        
        if (this.overlay) {
            this.overlay.classList.add('active');
            this.overlay.style.opacity = '';
            this.overlay.style.visibility = '';
        }

        // Update toggle button
        if (this.toggleButton) {
            this.toggleButton.classList.add('active');
            this.toggleButton.innerHTML = '<i class="fas fa-times"></i>';
            this.toggleButton.setAttribute('aria-expanded', 'true');
        }

        // Prevent body scroll
        document.body.classList.add('mobile-nav-open');
        
        // Focus first menu item for accessibility
        setTimeout(() => {
            const firstLink = this.sidebar?.querySelector('.mobile-menu-link');
            if (firstLink) {
                firstLink.focus();
            }
        }, 300);

        // Analytics
        this.trackEvent('mobile_nav_opened');
    }

    /**
     * Close sidebar
     */
    close() {
        if (!this.isOpen) return;

        this.isOpen = false;
        
        if (this.sidebar) {
            this.sidebar.classList.remove('open');
        }
        
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }

        // Update toggle button
        if (this.toggleButton) {
            this.toggleButton.classList.remove('active');
            this.toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
            this.toggleButton.setAttribute('aria-expanded', 'false');
        }

        // Restore body scroll
        document.body.classList.remove('mobile-nav-open');

        // Analytics
        this.trackEvent('mobile_nav_closed');
    }

    /**
     * Toggle sidebar
     */
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        // Close sidebar on desktop
        if (window.innerWidth >= 768 && this.isOpen) {
            this.close();
        }

        // Update mobile nav visibility
        const mobileNav = document.querySelector('.mobile-nav');
        if (mobileNav) {
            if (window.innerWidth >= 768) {
                mobileNav.style.display = 'none';
                document.body.style.paddingTop = '0';
            } else {
                mobileNav.style.display = 'flex';
                document.body.style.paddingTop = '60px';
            }
        }
    }

    /**
     * Add loading state to menu link
     */
    addLoadingState(link) {
        const icon = link.querySelector('i');
        if (icon) {
            const originalClass = icon.className;
            icon.className = 'fas fa-spinner fa-spin';
            
            // Restore original icon after navigation
            setTimeout(() => {
                icon.className = originalClass;
            }, 1000);
        }
    }

    /**
     * Get current user name
     */
    getCurrentUser() {
        // Try to get from session or DOM
        const userElement = document.querySelector('.user-name, .username');
        if (userElement) {
            return userElement.textContent.trim();
        }
        
        // Fallback
        return 'Admin User';
    }

    /**
     * Get current user role
     */
    getCurrentUserRole() {
        // Try to get from session or DOM
        const roleElement = document.querySelector('.user-role, .user-type');
        if (roleElement) {
            return roleElement.textContent.trim();
        }
        
        // Fallback
        return 'Administrator';
    }

    /**
     * Track analytics events
     */
    trackEvent(eventName, properties = {}) {
        // Send to analytics if available
        if (window.gtag) {
            window.gtag('event', eventName, {
                event_category: 'mobile_navigation',
                ...properties
            });
        }
        
        // Send to internal analytics
        if (window.analyticsManager) {
            window.analyticsManager.track(eventName, {
                category: 'mobile_navigation',
                timestamp: Date.now(),
                ...properties
            });
        }
    }

    /**
     * Update active menu item
     */
    updateActiveMenuItem(href) {
        if (!this.sidebar) return;

        const menuLinks = this.sidebar.querySelectorAll('.mobile-menu-link');
        menuLinks.forEach(link => {
            link.classList.remove('active');
            if (link.href === href) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Add notification badge to menu item
     */
    addNotificationBadge(menuSelector, count) {
        if (!this.sidebar) return;

        const menuItem = this.sidebar.querySelector(menuSelector);
        if (!menuItem) return;

        // Remove existing badge
        const existingBadge = menuItem.querySelector('.notification-badge');
        if (existingBadge) {
            existingBadge.remove();
        }

        // Add new badge if count > 0
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'notification-badge';
            badge.textContent = count > 99 ? '99+' : count.toString();
            menuItem.appendChild(badge);
        }
    }

    /**
     * Destroy mobile navigation
     */
    destroy() {
        // Remove event listeners
        this.removeEventListeners();
        
        // Remove DOM elements
        const mobileNav = document.querySelector('.mobile-nav');
        if (mobileNav) mobileNav.remove();
        
        if (this.sidebar) this.sidebar.remove();
        if (this.overlay) this.overlay.remove();
        
        // Reset body styles
        document.body.style.paddingTop = '';
        document.body.classList.remove('mobile-nav-open');
        
        console.log('[MobileNavigation] Destroyed');
    }

    /**
     * Remove event listeners
     */
    removeEventListeners() {
        // Implementation would remove all added event listeners
        // This is a simplified version
        document.removeEventListener('keydown', this.handleKeydown);
        document.removeEventListener('touchmove', this.handleTouchMove);
    }
}

// Global mobile navigation instance
window.mobileNavigation = null;

// Initialize mobile navigation when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on mobile/tablet or when specifically needed
    if (window.innerWidth < 768 || document.querySelector('.mobile-nav')) {
        window.mobileNavigation = new MobileNavigation();
    }
});

// Initialize on window resize if needed
window.addEventListener('resize', () => {
    if (window.innerWidth < 768 && !window.mobileNavigation) {
        window.mobileNavigation = new MobileNavigation();
    } else if (window.innerWidth >= 768 && window.mobileNavigation) {
        window.mobileNavigation.close();
    }
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileNavigation;
}
