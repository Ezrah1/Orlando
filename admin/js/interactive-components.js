/**
 * Orlando International Resorts - Interactive UI Components
 * Advanced interactive components with animation and accessibility support
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

/**
 * Advanced Modal Component
 */
class AdvancedModal {
    constructor(options = {}) {
        this.options = {
            id: options.id || this.generateId(),
            title: options.title || '',
            content: options.content || '',
            size: options.size || 'medium', // small, medium, large, full
            closable: options.closable !== false,
            backdrop: options.backdrop !== false,
            keyboard: options.keyboard !== false,
            animation: options.animation || 'fadeScale',
            onShow: options.onShow || null,
            onHide: options.onHide || null,
            buttons: options.buttons || []
        };
        
        this.isVisible = false;
        this.element = null;
        this.create();
    }

    create() {
        const sizeClass = `modal-${this.options.size}`;
        const buttonsHtml = this.options.buttons.map(btn => 
            `<button class="btn-advanced ${btn.class || 'btn-secondary'}" 
                     onclick="${btn.onclick || ''}">${btn.text}</button>`
        ).join('');

        const modalHtml = `
            <div class="modal-overlay" id="${this.options.id}" data-animation="${this.options.animation}">
                <div class="modal-container ${sizeClass}" role="dialog" aria-modal="true" aria-labelledby="${this.options.id}-title">
                    ${this.options.closable ? '<button class="modal-close" onclick="this.closest(\'.modal-overlay\').modalInstance.hide()"><i class="fas fa-times"></i></button>' : ''}
                    
                    ${this.options.title ? `
                        <div class="modal-header">
                            <h2 class="modal-title" id="${this.options.id}-title">${this.options.title}</h2>
                        </div>
                    ` : ''}
                    
                    <div class="modal-body">
                        ${this.options.content}
                    </div>
                    
                    ${this.options.buttons.length ? `
                        <div class="modal-footer">
                            ${buttonsHtml}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        this.element = document.getElementById(this.options.id);
        this.element.modalInstance = this;
        
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Backdrop click
        if (this.options.backdrop) {
            this.element.addEventListener('click', (e) => {
                if (e.target === this.element) {
                    this.hide();
                }
            });
        }

        // Keyboard events
        if (this.options.keyboard) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isVisible) {
                    this.hide();
                }
            });
        }
    }

    show() {
        if (this.isVisible) return;

        this.isVisible = true;
        this.element.classList.add('active');
        document.body.classList.add('modal-open');
        
        // Focus management
        this.previousFocus = document.activeElement;
        const focusTarget = this.element.querySelector('.modal-close, .btn-advanced, input, textarea, select');
        if (focusTarget) {
            setTimeout(() => focusTarget.focus(), 100);
        }

        if (this.options.onShow) {
            this.options.onShow(this);
        }
    }

    hide() {
        if (!this.isVisible) return;

        this.isVisible = false;
        this.element.classList.remove('active');
        document.body.classList.remove('modal-open');
        
        // Restore focus
        if (this.previousFocus) {
            this.previousFocus.focus();
        }

        if (this.options.onHide) {
            this.options.onHide(this);
        }
    }

    setContent(content) {
        const body = this.element.querySelector('.modal-body');
        if (body) {
            body.innerHTML = content;
        }
    }

    setTitle(title) {
        const titleElement = this.element.querySelector('.modal-title');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }

    destroy() {
        if (this.element) {
            this.element.remove();
        }
    }

    generateId() {
        return 'modal-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }
}

/**
 * Advanced Tooltip Component
 */
class AdvancedTooltip {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            content: options.content || element.getAttribute('title') || element.getAttribute('data-tooltip'),
            position: options.position || 'top',
            delay: options.delay || 300,
            animation: options.animation || 'fade',
            interactive: options.interactive || false,
            maxWidth: options.maxWidth || 300,
            theme: options.theme || 'dark'
        };
        
        this.tooltip = null;
        this.isVisible = false;
        this.showTimeout = null;
        this.hideTimeout = null;
        
        this.init();
    }

    init() {
        // Remove original title to prevent default tooltip
        if (this.element.hasAttribute('title')) {
            this.element.removeAttribute('title');
        }

        this.setupEventListeners();
    }

    setupEventListeners() {
        this.element.addEventListener('mouseenter', () => this.show());
        this.element.addEventListener('mouseleave', () => this.hide());
        this.element.addEventListener('focus', () => this.show());
        this.element.addEventListener('blur', () => this.hide());
    }

    show() {
        if (this.isVisible || !this.options.content) return;

        clearTimeout(this.hideTimeout);
        
        this.showTimeout = setTimeout(() => {
            this.createTooltip();
            this.positionTooltip();
            this.isVisible = true;
            
            requestAnimationFrame(() => {
                this.tooltip.classList.add('active');
            });
        }, this.options.delay);
    }

    hide() {
        if (!this.isVisible) return;

        clearTimeout(this.showTimeout);
        
        this.hideTimeout = setTimeout(() => {
            if (this.tooltip) {
                this.tooltip.classList.remove('active');
                setTimeout(() => this.destroyTooltip(), 200);
            }
            this.isVisible = false;
        }, this.options.interactive ? 100 : 0);
    }

    createTooltip() {
        if (this.tooltip) return;

        this.tooltip = document.createElement('div');
        this.tooltip.className = `tooltip-advanced tooltip-${this.options.theme} tooltip-${this.options.animation}`;
        this.tooltip.innerHTML = `
            <div class="tooltip-content" style="max-width: ${this.options.maxWidth}px;">
                ${this.options.content}
            </div>
            <div class="tooltip-arrow"></div>
        `;
        
        if (this.options.interactive) {
            this.tooltip.addEventListener('mouseenter', () => clearTimeout(this.hideTimeout));
            this.tooltip.addEventListener('mouseleave', () => this.hide());
        }

        document.body.appendChild(this.tooltip);
    }

    positionTooltip() {
        if (!this.tooltip) return;

        const elementRect = this.element.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const spacing = 8;

        let top, left;

        switch (this.options.position) {
            case 'top':
                top = elementRect.top - tooltipRect.height - spacing;
                left = elementRect.left + (elementRect.width - tooltipRect.width) / 2;
                break;
            case 'bottom':
                top = elementRect.bottom + spacing;
                left = elementRect.left + (elementRect.width - tooltipRect.width) / 2;
                break;
            case 'left':
                top = elementRect.top + (elementRect.height - tooltipRect.height) / 2;
                left = elementRect.left - tooltipRect.width - spacing;
                break;
            case 'right':
                top = elementRect.top + (elementRect.height - tooltipRect.height) / 2;
                left = elementRect.right + spacing;
                break;
        }

        // Viewport bounds checking
        const viewport = {
            width: window.innerWidth,
            height: window.innerHeight
        };

        if (left < 0) left = spacing;
        if (left + tooltipRect.width > viewport.width) left = viewport.width - tooltipRect.width - spacing;
        if (top < 0) top = spacing;
        if (top + tooltipRect.height > viewport.height) top = viewport.height - tooltipRect.height - spacing;

        this.tooltip.style.top = `${top + window.scrollY}px`;
        this.tooltip.style.left = `${left + window.scrollX}px`;
        this.tooltip.setAttribute('data-position', this.options.position);
    }

    destroyTooltip() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    }

    updateContent(content) {
        this.options.content = content;
        if (this.tooltip) {
            const contentEl = this.tooltip.querySelector('.tooltip-content');
            if (contentEl) {
                contentEl.innerHTML = content;
            }
        }
    }
}

/**
 * Advanced Dropdown Component
 */
class AdvancedDropdown {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            trigger: options.trigger || 'click',
            position: options.position || 'bottom-start',
            closeOnSelect: options.closeOnSelect !== false,
            searchable: options.searchable || false,
            multiple: options.multiple || false,
            placeholder: options.placeholder || 'Select an option...',
            maxHeight: options.maxHeight || 200,
            data: options.data || [],
            onSelect: options.onSelect || null,
            onOpen: options.onOpen || null,
            onClose: options.onClose || null
        };
        
        this.isOpen = false;
        this.selectedItems = [];
        this.filteredData = [...this.options.data];
        
        this.init();
    }

    init() {
        this.createDropdown();
        this.setupEventListeners();
        this.populateOptions();
    }

    createDropdown() {
        this.element.classList.add('dropdown-advanced');
        
        const dropdownHtml = `
            <div class="dropdown-trigger" tabindex="0">
                <span class="dropdown-value">${this.options.placeholder}</span>
                <i class="dropdown-arrow fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu">
                ${this.options.searchable ? `
                    <div class="dropdown-search">
                        <input type="text" class="dropdown-search-input" placeholder="Search...">
                        <i class="fas fa-search"></i>
                    </div>
                ` : ''}
                <div class="dropdown-options" style="max-height: ${this.options.maxHeight}px;">
                    <!-- Options will be populated here -->
                </div>
            </div>
        `;
        
        this.element.innerHTML = dropdownHtml;
        
        this.trigger = this.element.querySelector('.dropdown-trigger');
        this.menu = this.element.querySelector('.dropdown-menu');
        this.optionsContainer = this.element.querySelector('.dropdown-options');
        this.searchInput = this.element.querySelector('.dropdown-search-input');
    }

    setupEventListeners() {
        // Trigger events
        if (this.options.trigger === 'click') {
            this.trigger.addEventListener('click', () => this.toggle());
        } else if (this.options.trigger === 'hover') {
            this.element.addEventListener('mouseenter', () => this.open());
            this.element.addEventListener('mouseleave', () => this.close());
        }

        // Keyboard navigation
        this.trigger.addEventListener('keydown', (e) => this.handleKeyDown(e));
        
        // Search functionality
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target)) {
                this.close();
            }
        });
    }

    populateOptions() {
        this.optionsContainer.innerHTML = this.filteredData.map((item, index) => `
            <div class="dropdown-item" data-value="${item.value}" data-index="${index}">
                ${this.options.multiple ? '<i class="dropdown-checkbox far fa-square"></i>' : ''}
                <span class="dropdown-item-text">${item.text}</span>
                ${item.description ? `<small class="dropdown-item-description">${item.description}</small>` : ''}
            </div>
        `).join('');

        // Add click listeners to options
        this.optionsContainer.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', () => this.selectItem(item));
        });
    }

    open() {
        if (this.isOpen) return;

        this.isOpen = true;
        this.element.classList.add('active');
        
        if (this.searchInput) {
            setTimeout(() => this.searchInput.focus(), 100);
        }

        if (this.options.onOpen) {
            this.options.onOpen(this);
        }
    }

    close() {
        if (!this.isOpen) return;

        this.isOpen = false;
        this.element.classList.remove('active');
        
        if (this.options.onClose) {
            this.options.onClose(this);
        }
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    selectItem(itemElement) {
        const value = itemElement.getAttribute('data-value');
        const index = parseInt(itemElement.getAttribute('data-index'));
        const item = this.filteredData[index];

        if (this.options.multiple) {
            this.toggleMultipleSelection(item, itemElement);
        } else {
            this.selectedItems = [item];
            this.updateTriggerText();
            
            if (this.options.closeOnSelect) {
                this.close();
            }
        }

        if (this.options.onSelect) {
            this.options.onSelect(item, this.selectedItems);
        }
    }

    toggleMultipleSelection(item, itemElement) {
        const existingIndex = this.selectedItems.findIndex(selected => selected.value === item.value);
        
        if (existingIndex > -1) {
            this.selectedItems.splice(existingIndex, 1);
            itemElement.classList.remove('selected');
            itemElement.querySelector('.dropdown-checkbox').className = 'dropdown-checkbox far fa-square';
        } else {
            this.selectedItems.push(item);
            itemElement.classList.add('selected');
            itemElement.querySelector('.dropdown-checkbox').className = 'dropdown-checkbox fas fa-check-square';
        }
        
        this.updateTriggerText();
    }

    updateTriggerText() {
        const valueElement = this.trigger.querySelector('.dropdown-value');
        
        if (this.selectedItems.length === 0) {
            valueElement.textContent = this.options.placeholder;
        } else if (this.options.multiple) {
            if (this.selectedItems.length === 1) {
                valueElement.textContent = this.selectedItems[0].text;
            } else {
                valueElement.textContent = `${this.selectedItems.length} items selected`;
            }
        } else {
            valueElement.textContent = this.selectedItems[0].text;
        }
    }

    handleSearch(query) {
        this.filteredData = this.options.data.filter(item => 
            item.text.toLowerCase().includes(query.toLowerCase()) ||
            (item.description && item.description.toLowerCase().includes(query.toLowerCase()))
        );
        this.populateOptions();
    }

    handleKeyDown(e) {
        switch (e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();
                this.toggle();
                break;
            case 'Escape':
                this.close();
                break;
            case 'ArrowDown':
                e.preventDefault();
                if (!this.isOpen) {
                    this.open();
                } else {
                    this.focusNextOption();
                }
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (this.isOpen) {
                    this.focusPreviousOption();
                }
                break;
        }
    }

    focusNextOption() {
        const options = this.optionsContainer.querySelectorAll('.dropdown-item');
        const focused = this.optionsContainer.querySelector('.dropdown-item:focus');
        const currentIndex = focused ? Array.from(options).indexOf(focused) : -1;
        const nextIndex = (currentIndex + 1) % options.length;
        options[nextIndex].focus();
    }

    focusPreviousOption() {
        const options = this.optionsContainer.querySelectorAll('.dropdown-item');
        const focused = this.optionsContainer.querySelector('.dropdown-item:focus');
        const currentIndex = focused ? Array.from(options).indexOf(focused) : options.length;
        const prevIndex = (currentIndex - 1 + options.length) % options.length;
        options[prevIndex].focus();
    }

    setValue(value) {
        if (this.options.multiple) {
            this.selectedItems = Array.isArray(value) ? 
                this.options.data.filter(item => value.includes(item.value)) : [];
        } else {
            const item = this.options.data.find(item => item.value === value);
            this.selectedItems = item ? [item] : [];
        }
        
        this.updateTriggerText();
        this.updateSelectedStates();
    }

    getValue() {
        if (this.options.multiple) {
            return this.selectedItems.map(item => item.value);
        } else {
            return this.selectedItems.length > 0 ? this.selectedItems[0].value : null;
        }
    }

    updateSelectedStates() {
        this.optionsContainer.querySelectorAll('.dropdown-item').forEach(item => {
            const value = item.getAttribute('data-value');
            const isSelected = this.selectedItems.some(selected => selected.value === value);
            
            item.classList.toggle('selected', isSelected);
            
            if (this.options.multiple) {
                const checkbox = item.querySelector('.dropdown-checkbox');
                if (checkbox) {
                    checkbox.className = isSelected ? 
                        'dropdown-checkbox fas fa-check-square' : 
                        'dropdown-checkbox far fa-square';
                }
            }
        });
    }
}

/**
 * Advanced Tab Component
 */
class AdvancedTabs {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            defaultTab: options.defaultTab || 0,
            animation: options.animation || 'fade',
            keyboard: options.keyboard !== false,
            onTabChange: options.onTabChange || null
        };
        
        this.currentTab = this.options.defaultTab;
        this.tabs = [];
        this.panels = [];
        
        this.init();
    }

    init() {
        this.createTabStructure();
        this.setupEventListeners();
        this.showTab(this.currentTab);
    }

    createTabStructure() {
        const tabsData = Array.from(this.element.children).map((panel, index) => ({
            id: panel.id || `tab-${index}`,
            title: panel.getAttribute('data-tab-title') || `Tab ${index + 1}`,
            icon: panel.getAttribute('data-tab-icon') || null,
            disabled: panel.hasAttribute('data-disabled')
        }));

        const tabsNavHtml = `
            <div class="tabs-nav" role="tablist">
                ${tabsData.map((tab, index) => `
                    <button class="tab-button" 
                            role="tab" 
                            aria-controls="${tab.id}"
                            aria-selected="${index === this.currentTab}"
                            data-tab-index="${index}"
                            ${tab.disabled ? 'disabled' : ''}>
                        ${tab.icon ? `<i class="${tab.icon}"></i>` : ''}
                        <span>${tab.title}</span>
                    </button>
                `).join('')}
            </div>
        `;

        const existingPanels = Array.from(this.element.children);
        this.element.innerHTML = tabsNavHtml;
        
        const tabsContent = document.createElement('div');
        tabsContent.className = 'tabs-content';
        
        existingPanels.forEach((panel, index) => {
            panel.className = `tab-panel ${panel.className}`.trim();
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', `tab-${index}`);
            panel.style.display = index === this.currentTab ? 'block' : 'none';
            tabsContent.appendChild(panel);
        });
        
        this.element.appendChild(tabsContent);
        this.element.classList.add('tabs-container');

        this.tabs = this.element.querySelectorAll('.tab-button');
        this.panels = this.element.querySelectorAll('.tab-panel');
    }

    setupEventListeners() {
        this.tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => this.showTab(index));
            
            if (this.options.keyboard) {
                tab.addEventListener('keydown', (e) => this.handleKeyDown(e, index));
            }
        });
    }

    showTab(index) {
        if (index < 0 || index >= this.tabs.length || this.tabs[index].disabled) {
            return;
        }

        const previousTab = this.currentTab;
        this.currentTab = index;

        // Update tab buttons
        this.tabs.forEach((tab, i) => {
            tab.classList.toggle('active', i === index);
            tab.setAttribute('aria-selected', i === index);
        });

        // Update panels
        this.panels.forEach((panel, i) => {
            if (i === index) {
                panel.style.display = 'block';
                panel.classList.add('active', `animate-${this.options.animation}`);
            } else {
                panel.classList.remove('active', `animate-${this.options.animation}`);
                setTimeout(() => {
                    if (!panel.classList.contains('active')) {
                        panel.style.display = 'none';
                    }
                }, 200);
            }
        });

        if (this.options.onTabChange) {
            this.options.onTabChange(index, previousTab, this.panels[index]);
        }
    }

    handleKeyDown(e, currentIndex) {
        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                this.focusPreviousTab(currentIndex);
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.focusNextTab(currentIndex);
                break;
            case 'Home':
                e.preventDefault();
                this.focusFirstTab();
                break;
            case 'End':
                e.preventDefault();
                this.focusLastTab();
                break;
        }
    }

    focusNextTab(currentIndex) {
        let nextIndex = (currentIndex + 1) % this.tabs.length;
        while (this.tabs[nextIndex].disabled && nextIndex !== currentIndex) {
            nextIndex = (nextIndex + 1) % this.tabs.length;
        }
        this.tabs[nextIndex].focus();
        this.showTab(nextIndex);
    }

    focusPreviousTab(currentIndex) {
        let prevIndex = (currentIndex - 1 + this.tabs.length) % this.tabs.length;
        while (this.tabs[prevIndex].disabled && prevIndex !== currentIndex) {
            prevIndex = (prevIndex - 1 + this.tabs.length) % this.tabs.length;
        }
        this.tabs[prevIndex].focus();
        this.showTab(prevIndex);
    }

    focusFirstTab() {
        const firstEnabledTab = Array.from(this.tabs).findIndex(tab => !tab.disabled);
        if (firstEnabledTab !== -1) {
            this.tabs[firstEnabledTab].focus();
            this.showTab(firstEnabledTab);
        }
    }

    focusLastTab() {
        const lastEnabledTab = Array.from(this.tabs).reverse().findIndex(tab => !tab.disabled);
        if (lastEnabledTab !== -1) {
            const actualIndex = this.tabs.length - 1 - lastEnabledTab;
            this.tabs[actualIndex].focus();
            this.showTab(actualIndex);
        }
    }

    addTab(title, content, options = {}) {
        const index = this.panels.length;
        const tabId = options.id || `tab-${index}`;
        
        // Add tab button
        const tabButton = document.createElement('button');
        tabButton.className = 'tab-button';
        tabButton.setAttribute('role', 'tab');
        tabButton.setAttribute('aria-controls', tabId);
        tabButton.setAttribute('data-tab-index', index);
        tabButton.innerHTML = `
            ${options.icon ? `<i class="${options.icon}"></i>` : ''}
            <span>${title}</span>
        `;
        
        this.element.querySelector('.tabs-nav').appendChild(tabButton);
        
        // Add tab panel
        const tabPanel = document.createElement('div');
        tabPanel.id = tabId;
        tabPanel.className = 'tab-panel';
        tabPanel.setAttribute('role', 'tabpanel');
        tabPanel.innerHTML = content;
        tabPanel.style.display = 'none';
        
        this.element.querySelector('.tabs-content').appendChild(tabPanel);
        
        // Update references
        this.tabs = this.element.querySelectorAll('.tab-button');
        this.panels = this.element.querySelectorAll('.tab-panel');
        
        // Add event listener
        tabButton.addEventListener('click', () => this.showTab(index));
        
        return index;
    }

    removeTab(index) {
        if (index < 0 || index >= this.tabs.length) return;
        
        this.tabs[index].remove();
        this.panels[index].remove();
        
        // Update references
        this.tabs = this.element.querySelectorAll('.tab-button');
        this.panels = this.element.querySelectorAll('.tab-panel');
        
        // Update current tab if necessary
        if (this.currentTab >= index) {
            this.currentTab = Math.max(0, this.currentTab - 1);
        }
        
        if (this.tabs.length > 0) {
            this.showTab(this.currentTab);
        }
    }
}

// Global utility functions for easy component initialization
window.UIComponents = {
    Modal: AdvancedModal,
    Tooltip: AdvancedTooltip,
    Dropdown: AdvancedDropdown,
    Tabs: AdvancedTabs,
    
    // Quick initialization functions
    initTooltips: function(selector = '[data-tooltip]') {
        document.querySelectorAll(selector).forEach(element => {
            new AdvancedTooltip(element);
        });
    },
    
    initDropdowns: function(selector = '.dropdown-advanced') {
        document.querySelectorAll(selector).forEach(element => {
            const options = element.dataset;
            new AdvancedDropdown(element, options);
        });
    },
    
    initTabs: function(selector = '.tabs-container') {
        document.querySelectorAll(selector).forEach(element => {
            new AdvancedTabs(element);
        });
    },
    
    showModal: function(options) {
        const modal = new AdvancedModal(options);
        modal.show();
        return modal;
    }
};

// Auto-initialization on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Auto-initialize tooltips
    UIComponents.initTooltips();
    
    // Auto-initialize dropdowns
    UIComponents.initDropdowns();
    
    // Auto-initialize tabs
    UIComponents.initTabs();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AdvancedModal, AdvancedTooltip, AdvancedDropdown, AdvancedTabs };
}
