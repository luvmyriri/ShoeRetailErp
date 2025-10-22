/**
 * Shoe Retail ERP - Main Application JavaScript
 * Odoo-inspired interactive components
 */

(function() {
    'use strict';

    // =====================================================
    // Global Configuration
    // =====================================================

    const CONFIG = {
        transitionDuration: 250,
        sidebarCollapseWidth: 1024,
    };

    // =====================================================
    // DOM Elements Cache
    // =====================================================

    const DOM = {
        sidebar: null,
        sidebarToggle: null,
        main: null,
        navbarAvatar: null,
        navbar: null,
        modals: {},
        alerts: {},
    };

    // =====================================================
    // Application State
    // =====================================================

    const state = {
        sidebarCollapsed: window.innerWidth <= CONFIG.sidebarCollapseWidth,
        currentPage: null,
        user: null,
    };

    // =====================================================
    // Initialize Application
    // =====================================================

    function init() {
        cacheDOM();
        setupEventListeners();
        initializeComponents();
        setInitialState();
    }

    function cacheDOM() {
        DOM.sidebar = document.querySelector('.sidebar');
        DOM.sidebarToggle = document.querySelector('[data-toggle="sidebar"]');
        DOM.main = document.querySelector('.main-wrapper');
        DOM.navbarAvatar = document.querySelector('.navbar-avatar');
        DOM.navbar = document.querySelector('.navbar');
    }

    function setupEventListeners() {
        // Sidebar toggle
        if (DOM.sidebarToggle) {
            DOM.sidebarToggle.addEventListener('click', toggleSidebar);
        }

        // Window resize
        window.addEventListener('resize', handleWindowResize);

        // Modal handlers
        document.addEventListener('click', handleModalTriggers);

        // Dropdown handlers
        document.addEventListener('click', handleDropdowns);

        // Tab handlers
        document.addEventListener('click', handleTabs);

        // Form submission
        document.addEventListener('submit', handleFormSubmit);

        // Alert close buttons
        document.addEventListener('click', handleAlertClose);

        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboardShortcuts);
    }

    function initializeComponents() {
        initializeNavigation();
        initializeTooltips();
        initializeNotifications();
    }

    function setInitialState() {
        if (state.sidebarCollapsed) {
            collapseSidebar(false);
        }
    }

    // =====================================================
    // Sidebar Management
    // =====================================================

    function toggleSidebar(e) {
        e.preventDefault();
        state.sidebarCollapsed ? expandSidebar() : collapseSidebar();
    }

    function collapseSidebar(animate = true) {
        state.sidebarCollapsed = true;
        
        if (DOM.sidebar) {
            if (animate) DOM.sidebar.style.transition = `all ${CONFIG.transitionDuration}ms ease`;
            DOM.sidebar.classList.add('sidebar-collapsed');
        }

        if (DOM.main) {
            if (animate) DOM.main.style.transition = `all ${CONFIG.transitionDuration}ms ease`;
            DOM.main.classList.add('collapsed');
        }

        localStorage.setItem('sidebarCollapsed', 'true');
    }

    function expandSidebar() {
        state.sidebarCollapsed = false;
        
        if (DOM.sidebar) {
            DOM.sidebar.style.transition = `all ${CONFIG.transitionDuration}ms ease`;
            DOM.sidebar.classList.remove('sidebar-collapsed');
        }

        if (DOM.main) {
            DOM.main.style.transition = `all ${CONFIG.transitionDuration}ms ease`;
            DOM.main.classList.remove('collapsed');
        }

        localStorage.setItem('sidebarCollapsed', 'false');
    }

    function handleWindowResize() {
        if (window.innerWidth <= CONFIG.sidebarCollapseWidth) {
            if (!state.sidebarCollapsed) {
                collapseSidebar(false);
            }
        } else {
            if (state.sidebarCollapsed) {
                expandSidebar();
            }
        }
    }

    // =====================================================
    // Navigation
    // =====================================================

    function initializeNavigation() {
        const navLinks = document.querySelectorAll('.sidebar-link');
        const currentPath = window.location.pathname;

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPath || currentPath.includes(href)) {
                link.classList.add('active');
            }
        });
    }

    function setActiveNavLink(href) {
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
        });
        const activeLink = document.querySelector(`.sidebar-link[href="${href}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }

    // =====================================================
    // Modal Management
    // =====================================================

    function handleModalTriggers(e) {
        const trigger = e.target.closest('[data-toggle="modal"]');
        if (trigger) {
            const modalId = trigger.getAttribute('data-target');
            openModal(modalId);
        }

        const closeBtn = e.target.closest('[data-dismiss="modal"]');
        if (closeBtn) {
            closeModal();
        }
    }

    function openModal(modalId) {
        const backdrop = document.querySelector('.modal-backdrop');
        const modal = document.getElementById(modalId);

        if (!modal) return;

        if (backdrop) backdrop.classList.add('show');
        modal.classList.add('show');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const backdrop = document.querySelector('.modal-backdrop');
        const modal = document.querySelector('.modal.show');

        if (backdrop) backdrop.classList.remove('show');
        if (modal) modal.classList.remove('show');

        // Restore body scroll
        document.body.style.overflow = '';
    }

    // Close modal on backdrop click
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-backdrop')) {
            closeModal();
        }
    });

    // =====================================================
    // Dropdown Management
    // =====================================================

    function handleDropdowns(e) {
        // Don't prevent default for links (including logout)
        const dropdownToggle = e.target.closest('.dropdown');
        const isLink = e.target.tagName === 'A';
        
        if (dropdownToggle && !isLink) {
            e.preventDefault();
            toggleDropdown(dropdownToggle);
        } else if (!dropdownToggle) {
            // Close all dropdowns when clicking outside
            closeAllDropdowns();
        }
    }

    function toggleDropdown(dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!menu) return;

        const isVisible = menu.classList.contains('show');
        closeAllDropdowns();

        if (!isVisible) {
            menu.classList.add('show');
        }
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }

    // =====================================================
    // Tabs Management
    // =====================================================

    function handleTabs(e) {
        const tab = e.target.closest('.nav-link');
        if (!tab) return;

        e.preventDefault();

        const tabGroup = tab.closest('.nav-tabs');
        if (!tabGroup) return;

        // Get target pane
        const tabPane = tab.getAttribute('data-tab');
        if (!tabPane) return;

        // Deactivate all tabs and panes
        tabGroup.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

        // Activate clicked tab and corresponding pane
        tab.classList.add('active');
        const targetPane = document.getElementById(tabPane);
        if (targetPane) {
            targetPane.classList.add('active');
        }
    }

    // =====================================================
    // Form Management
    // =====================================================

    function handleFormSubmit(e) {
        const form = e.target;
        
        // Validate form
        if (!form.checkValidity()) {
            e.preventDefault();
            return false;
        }

        // Add loading state to submit button
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            // Restore after a reasonable time
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }, 3000);
        }
    }

    // =====================================================
    // Alert Management
    // =====================================================

    function showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.querySelector('.alert-container');
        if (!alertContainer) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <div class="alert-icon">
                ${getAlertIcon(type)}
            </div>
            <div class="flex-1">
                ${message}
            </div>
            <button class="alert-close" type="button">&times;</button>
        `;

        alertContainer.appendChild(alert);

        // Auto-close alert
        if (duration > 0) {
            setTimeout(() => {
                alert.remove();
            }, duration);
        }

        // Handle close button
        alert.querySelector('.alert-close').addEventListener('click', () => {
            alert.remove();
        });
    }

    function handleAlertClose(e) {
        const closeBtn = e.target.closest('.alert-close');
        if (closeBtn) {
            closeBtn.closest('.alert').remove();
        }
    }

    function getAlertIcon(type) {
        const icons = {
            success: '✓',
            danger: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || 'ℹ';
    }

    // =====================================================
    // Tooltips
    // =====================================================

    function initializeTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', showTooltip);
            element.addEventListener('mouseleave', hideTooltip);
        });
    }

    function showTooltip(e) {
        const element = e.target;
        const text = element.getAttribute('data-tooltip');
        const position = element.getAttribute('data-tooltip-position') || 'top';

        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.style.position = 'fixed';
        tooltip.style.zIndex = 9999;
        tooltip.style.backgroundColor = '#333';
        tooltip.style.color = 'white';
        tooltip.style.padding = '8px 12px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '12px';
        tooltip.style.whiteSpace = 'nowrap';
        tooltip.style.pointerEvents = 'none';

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        const positions = {
            top: {
                left: rect.left + (rect.width - tooltipRect.width) / 2,
                top: rect.top - tooltipRect.height - 8
            },
            bottom: {
                left: rect.left + (rect.width - tooltipRect.width) / 2,
                top: rect.bottom + 8
            },
            left: {
                left: rect.left - tooltipRect.width - 8,
                top: rect.top + (rect.height - tooltipRect.height) / 2
            },
            right: {
                left: rect.right + 8,
                top: rect.top + (rect.height - tooltipRect.height) / 2
            }
        };

        const pos = positions[position] || positions.top;
        tooltip.style.left = pos.left + 'px';
        tooltip.style.top = pos.top + 'px';

        element._tooltip = tooltip;
    }

    function hideTooltip(e) {
        const element = e.target;
        if (element._tooltip) {
            element._tooltip.remove();
            delete element._tooltip;
        }
    }

    // =====================================================
    // Notifications
    // =====================================================

    function initializeNotifications() {
        // Check for notification badges
        updateNotificationBadges();
    }

    function updateNotificationBadges() {
        const badges = document.querySelectorAll('[data-notification-count]');
        badges.forEach(badge => {
            const count = badge.getAttribute('data-notification-count');
            if (count && count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = count > 99 ? '99+' : count;
            }
        });
    }

    // =====================================================
    // Utility Functions
    // =====================================================

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }

    function formatDate(date, format = 'short') {
        const d = new Date(date);
        const formats = {
            short: d.toLocaleDateString('en-US'),
            long: d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
            time: d.toLocaleTimeString('en-US')
        };
        return formats[format] || formats.short;
    }

    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // =====================================================
    // Keyboard Shortcuts
    // =====================================================

    function handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + K: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('.navbar-search input');
            if (searchInput) searchInput.focus();
        }

        // Escape: Close modals and dropdowns
        if (e.key === 'Escape') {
            closeModal();
            closeAllDropdowns();
        }
    }

    // =====================================================
    // API Helper
    // =====================================================

    const API = {
        async fetch(endpoint, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            const response = await fetch(endpoint, {
                ...defaultOptions,
                ...options
            });

            if (!response.ok) {
                throw new Error(`API Error: ${response.statusText}`);
            }

            return await response.json();
        },

        async get(endpoint) {
            return this.fetch(endpoint, { method: 'GET' });
        },

        async post(endpoint, data) {
            return this.fetch(endpoint, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },

        async put(endpoint, data) {
            return this.fetch(endpoint, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },

        async delete(endpoint) {
            return this.fetch(endpoint, { method: 'DELETE' });
        }
    };

    // =====================================================
    // Public API
    // =====================================================

    window.ERP = {
        init,
        showAlert,
        closeModal,
        openModal,
        setActiveNavLink,
        collapseSidebar,
        expandSidebar,
        API,
        formatCurrency,
        formatDate,
        debounce,
        throttle,
        state,
    };

    // =====================================================
    // Start Application
    // =====================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
