/**
 * Orlando International Resorts - Theme Manager
 * Advanced theme customization with real-time preview and user preferences
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.customThemes = this.getStoredCustomThemes();
        this.themePreferences = this.getStoredPreferences();
        this.observers = [];
        
        this.init();
    }

    /**
     * Initialize theme manager
     */
    init() {
        console.log('[ThemeManager] Initializing...');
        
        // Apply current theme
        this.applyTheme(this.currentTheme);
        
        // Setup theme detection
        this.setupSystemThemeDetection();
        
        // Setup accessibility preferences
        this.setupAccessibilityDetection();
        
        // Create theme controls
        this.createThemeControls();
        
        // Setup event listeners
        this.setupEventListeners();
        
        console.log('[ThemeManager] Initialization complete');
    }

    /**
     * Get available themes
     */
    getAvailableThemes() {
        return {
            light: {
                name: 'Light Theme',
                description: 'Clean and bright interface',
                preview: '/admin/assets/img/themes/light-preview.jpg',
                variables: {
                    '--primary-color': '#667eea',
                    '--bg-primary': '#ffffff',
                    '--text-primary': '#2d3748'
                }
            },
            dark: {
                name: 'Dark Theme',
                description: 'Easy on the eyes for low-light environments',
                preview: '/admin/assets/img/themes/dark-preview.jpg',
                variables: {
                    '--primary-color': '#667eea',
                    '--bg-primary': '#1a202c',
                    '--text-primary': '#f7fafc'
                }
            },
            'high-contrast': {
                name: 'High Contrast',
                description: 'Maximum contrast for accessibility',
                preview: '/admin/assets/img/themes/high-contrast-preview.jpg',
                variables: {
                    '--primary-color': '#0000ff',
                    '--bg-primary': '#ffffff',
                    '--text-primary': '#000000'
                }
            },
            'blue-ocean': {
                name: 'Blue Ocean',
                description: 'Calming blue tones',
                preview: '/admin/assets/img/themes/blue-ocean-preview.jpg',
                variables: {
                    '--primary-color': '#0ea5e9',
                    '--secondary-color': '#0284c7',
                    '--accent-color': '#38bdf8'
                }
            },
            'forest-green': {
                name: 'Forest Green',
                description: 'Natural green palette',
                preview: '/admin/assets/img/themes/forest-green-preview.jpg',
                variables: {
                    '--primary-color': '#059669',
                    '--secondary-color': '#047857',
                    '--accent-color': '#10b981'
                }
            },
            'sunset-orange': {
                name: 'Sunset Orange',
                description: 'Warm orange and red tones',
                preview: '/admin/assets/img/themes/sunset-orange-preview.jpg',
                variables: {
                    '--primary-color': '#ea580c',
                    '--secondary-color': '#dc2626',
                    '--accent-color': '#f97316'
                }
            }
        };
    }

    /**
     * Apply theme to the document
     */
    applyTheme(themeName, temporary = false) {
        const themes = this.getAvailableThemes();
        const customThemes = this.getStoredCustomThemes();
        
        let theme = themes[themeName] || customThemes[themeName];
        
        if (!theme) {
            console.warn(`Theme '${themeName}' not found, falling back to light theme`);
            theme = themes.light;
            themeName = 'light';
        }

        // Set theme attribute on document
        document.documentElement.setAttribute('data-theme', themeName);
        
        // Apply CSS custom properties
        if (theme.variables) {
            Object.entries(theme.variables).forEach(([property, value]) => {
                document.documentElement.style.setProperty(property, value);
            });
        }

        // Update current theme if not temporary
        if (!temporary) {
            this.currentTheme = themeName;
            this.storeTheme(themeName);
        }

        // Notify observers
        this.notifyObservers('themeChanged', { theme: themeName, temporary });

        console.log(`[ThemeManager] Applied theme: ${themeName}`);
    }

    /**
     * Create custom theme
     */
    createCustomTheme(name, baseTheme, customizations) {
        const themes = this.getAvailableThemes();
        const base = themes[baseTheme] || themes.light;
        
        const customTheme = {
            name: name,
            description: `Custom theme based on ${base.name}`,
            isCustom: true,
            baseTheme: baseTheme,
            variables: {
                ...base.variables,
                ...customizations
            },
            createdAt: new Date().toISOString()
        };

        // Store custom theme
        this.customThemes[this.generateThemeId(name)] = customTheme;
        this.storeCustomThemes();

        console.log(`[ThemeManager] Created custom theme: ${name}`);
        return customTheme;
    }

    /**
     * Delete custom theme
     */
    deleteCustomTheme(themeId) {
        if (this.customThemes[themeId] && this.customThemes[themeId].isCustom) {
            delete this.customThemes[themeId];
            this.storeCustomThemes();
            
            // Switch to default theme if current theme was deleted
            if (this.currentTheme === themeId) {
                this.applyTheme('light');
            }
            
            console.log(`[ThemeManager] Deleted custom theme: ${themeId}`);
            return true;
        }
        return false;
    }

    /**
     * Setup system theme detection
     */
    setupSystemThemeDetection() {
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Handle initial preference
            if (this.themePreferences.followSystem) {
                this.applyTheme(darkModeQuery.matches ? 'dark' : 'light');
            }
            
            // Listen for changes
            darkModeQuery.addEventListener('change', (e) => {
                if (this.themePreferences.followSystem) {
                    this.applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    /**
     * Setup accessibility detection
     */
    setupAccessibilityDetection() {
        // Detect reduced motion preference
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('reduce-motion');
            this.themePreferences.reducedMotion = true;
        }

        // Detect high contrast preference
        if (window.matchMedia && window.matchMedia('(prefers-contrast: high)').matches) {
            if (this.themePreferences.autoHighContrast) {
                this.applyTheme('high-contrast');
            }
        }

        // Listen for changes
        const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        reducedMotionQuery.addEventListener('change', (e) => {
            if (e.matches) {
                document.documentElement.classList.add('reduce-motion');
            } else {
                document.documentElement.classList.remove('reduce-motion');
            }
            this.themePreferences.reducedMotion = e.matches;
            this.storePreferences();
        });
    }

    /**
     * Create theme control panel
     */
    createThemeControls() {
        // Only create if not already exists
        if (document.getElementById('theme-controls')) return;

        const controlsHTML = `
            <div id="theme-controls" class="theme-controls">
                <button class="theme-toggle-btn" onclick="themeManager.toggleThemePanel()">
                    <i class="fas fa-palette"></i>
                    <span class="sr-only">Theme Settings</span>
                </button>
                
                <div class="theme-panel" id="theme-panel">
                    <div class="theme-panel-header">
                        <h3>Theme Settings</h3>
                        <button class="theme-panel-close" onclick="themeManager.closeThemePanel()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="theme-panel-content">
                        <!-- Theme Selection -->
                        <div class="theme-section">
                            <h4>Choose Theme</h4>
                            <div class="theme-grid" id="theme-grid">
                                <!-- Themes will be populated here -->
                            </div>
                        </div>
                        
                        <!-- Quick Settings -->
                        <div class="theme-section">
                            <h4>Quick Settings</h4>
                            <div class="theme-quick-settings">
                                <label class="theme-setting">
                                    <input type="checkbox" id="follow-system" ${this.themePreferences.followSystem ? 'checked' : ''}>
                                    <span>Follow system theme</span>
                                </label>
                                
                                <label class="theme-setting">
                                    <input type="checkbox" id="reduced-motion" ${this.themePreferences.reducedMotion ? 'checked' : ''}>
                                    <span>Reduce animations</span>
                                </label>
                                
                                <label class="theme-setting">
                                    <input type="checkbox" id="auto-high-contrast" ${this.themePreferences.autoHighContrast ? 'checked' : ''}>
                                    <span>Auto high contrast</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Custom Theme Creator -->
                        <div class="theme-section">
                            <h4>Custom Theme</h4>
                            <button class="btn-advanced btn-outline" onclick="themeManager.openThemeCreator()">
                                <i class="fas fa-plus"></i>
                                Create Custom Theme
                            </button>
                        </div>
                        
                        <!-- Theme Export/Import -->
                        <div class="theme-section">
                            <h4>Theme Management</h4>
                            <div class="theme-management">
                                <button class="btn-advanced btn-ghost btn-sm" onclick="themeManager.exportThemes()">
                                    <i class="fas fa-download"></i>
                                    Export Themes
                                </button>
                                <button class="btn-advanced btn-ghost btn-sm" onclick="themeManager.importThemes()">
                                    <i class="fas fa-upload"></i>
                                    Import Themes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert theme controls into document
        document.body.insertAdjacentHTML('beforeend', controlsHTML);
        
        // Populate theme grid
        this.populateThemeGrid();
        
        // Add CSS for theme controls
        this.addThemeControlsCSS();
    }

    /**
     * Populate theme selection grid
     */
    populateThemeGrid() {
        const themeGrid = document.getElementById('theme-grid');
        if (!themeGrid) return;

        const allThemes = { ...this.getAvailableThemes(), ...this.customThemes };
        
        themeGrid.innerHTML = Object.entries(allThemes).map(([id, theme]) => `
            <div class="theme-option ${this.currentTheme === id ? 'active' : ''}" 
                 data-theme="${id}" onclick="themeManager.selectTheme('${id}')">
                <div class="theme-preview" style="background: ${theme.variables?.['--primary-color'] || '#667eea'}">
                    <div class="theme-preview-content">
                        <div class="theme-preview-header" style="background: ${theme.variables?.['--bg-primary'] || '#ffffff'}"></div>
                        <div class="theme-preview-body" style="background: ${theme.variables?.['--bg-secondary'] || '#f8fafc'}"></div>
                    </div>
                </div>
                <div class="theme-info">
                    <div class="theme-name">${theme.name}</div>
                    <div class="theme-description">${theme.description}</div>
                    ${theme.isCustom ? '<div class="theme-custom-badge">Custom</div>' : ''}
                </div>
                ${theme.isCustom ? `<button class="theme-delete" onclick="themeManager.deleteTheme('${id}')" title="Delete theme"><i class="fas fa-trash"></i></button>` : ''}
            </div>
        `).join('');
    }

    /**
     * Add CSS for theme controls
     */
    addThemeControlsCSS() {
        if (document.getElementById('theme-controls-css')) return;

        const css = `
            <style id="theme-controls-css">
                .theme-controls {
                    position: fixed;
                    top: 50%;
                    right: 20px;
                    transform: translateY(-50%);
                    z-index: var(--z-toast);
                }
                
                .theme-toggle-btn {
                    width: 56px;
                    height: 56px;
                    border-radius: 50%;
                    background: var(--primary-color);
                    color: white;
                    border: none;
                    box-shadow: var(--shadow-lg);
                    cursor: pointer;
                    transition: all var(--transition-normal);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                }
                
                .theme-toggle-btn:hover {
                    transform: scale(1.1);
                    box-shadow: var(--shadow-xl);
                }
                
                .theme-panel {
                    position: absolute;
                    right: 70px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 360px;
                    max-height: 80vh;
                    background: var(--bg-primary);
                    border: 1px solid var(--border-light);
                    border-radius: var(--radius-xl);
                    box-shadow: var(--shadow-xl);
                    opacity: 0;
                    visibility: hidden;
                    transition: all var(--transition-normal);
                    overflow: hidden;
                }
                
                .theme-panel.active {
                    opacity: 1;
                    visibility: visible;
                    transform: translateY(-50%) translateX(0);
                }
                
                .theme-panel-header {
                    padding: var(--space-lg);
                    border-bottom: 1px solid var(--border-light);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    background: var(--bg-secondary);
                }
                
                .theme-panel-header h3 {
                    margin: 0;
                    color: var(--text-primary);
                    font-size: var(--text-lg);
                }
                
                .theme-panel-close {
                    background: none;
                    border: none;
                    color: var(--text-muted);
                    cursor: pointer;
                    padding: var(--space-sm);
                    border-radius: var(--radius-md);
                    transition: all var(--transition-fast);
                }
                
                .theme-panel-close:hover {
                    background: var(--bg-tertiary);
                    color: var(--text-primary);
                }
                
                .theme-panel-content {
                    padding: var(--space-lg);
                    max-height: calc(80vh - 80px);
                    overflow-y: auto;
                }
                
                .theme-section {
                    margin-bottom: var(--space-xl);
                }
                
                .theme-section h4 {
                    margin: 0 0 var(--space-md) 0;
                    color: var(--text-primary);
                    font-size: var(--text-base);
                    font-weight: 600;
                }
                
                .theme-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: var(--space-md);
                }
                
                .theme-option {
                    border: 2px solid var(--border-light);
                    border-radius: var(--radius-lg);
                    cursor: pointer;
                    transition: all var(--transition-fast);
                    position: relative;
                    overflow: hidden;
                }
                
                .theme-option:hover {
                    border-color: var(--primary-color);
                    transform: translateY(-2px);
                    box-shadow: var(--shadow-md);
                }
                
                .theme-option.active {
                    border-color: var(--primary-color);
                    box-shadow: 0 0 0 2px var(--primary-alpha);
                }
                
                .theme-preview {
                    height: 60px;
                    position: relative;
                    overflow: hidden;
                }
                
                .theme-preview-content {
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                }
                
                .theme-preview-header {
                    height: 20px;
                    opacity: 0.8;
                }
                
                .theme-preview-body {
                    flex: 1;
                    opacity: 0.6;
                }
                
                .theme-info {
                    padding: var(--space-md);
                }
                
                .theme-name {
                    font-weight: 600;
                    color: var(--text-primary);
                    font-size: var(--text-sm);
                    margin-bottom: var(--space-xs);
                }
                
                .theme-description {
                    color: var(--text-muted);
                    font-size: var(--text-xs);
                    line-height: var(--leading-tight);
                }
                
                .theme-custom-badge {
                    background: var(--accent-color);
                    color: white;
                    font-size: var(--text-xs);
                    padding: 2px 6px;
                    border-radius: var(--radius-sm);
                    margin-top: var(--space-xs);
                    display: inline-block;
                }
                
                .theme-delete {
                    position: absolute;
                    top: var(--space-xs);
                    right: var(--space-xs);
                    background: var(--error-color);
                    color: white;
                    border: none;
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: var(--text-xs);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity var(--transition-fast);
                }
                
                .theme-option:hover .theme-delete {
                    opacity: 1;
                }
                
                .theme-quick-settings {
                    display: flex;
                    flex-direction: column;
                    gap: var(--space-md);
                }
                
                .theme-setting {
                    display: flex;
                    align-items: center;
                    gap: var(--space-sm);
                    cursor: pointer;
                    color: var(--text-primary);
                    font-size: var(--text-sm);
                }
                
                .theme-setting input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    cursor: pointer;
                }
                
                .theme-management {
                    display: flex;
                    gap: var(--space-sm);
                    flex-wrap: wrap;
                }
                
                @media (max-width: 768px) {
                    .theme-controls {
                        right: 10px;
                    }
                    
                    .theme-panel {
                        right: 0;
                        left: 10px;
                        width: auto;
                    }
                    
                    .theme-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', css);
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Theme preference changes
        document.addEventListener('change', (e) => {
            if (e.target.id === 'follow-system') {
                this.themePreferences.followSystem = e.target.checked;
                this.storePreferences();
                
                if (e.target.checked) {
                    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    this.applyTheme(isDark ? 'dark' : 'light');
                }
            }
            
            if (e.target.id === 'reduced-motion') {
                this.themePreferences.reducedMotion = e.target.checked;
                this.storePreferences();
                
                if (e.target.checked) {
                    document.documentElement.classList.add('reduce-motion');
                } else {
                    document.documentElement.classList.remove('reduce-motion');
                }
            }
            
            if (e.target.id === 'auto-high-contrast') {
                this.themePreferences.autoHighContrast = e.target.checked;
                this.storePreferences();
            }
        });

        // Close theme panel when clicking outside
        document.addEventListener('click', (e) => {
            const themePanel = document.getElementById('theme-panel');
            const themeControls = document.getElementById('theme-controls');
            
            if (themePanel && themePanel.classList.contains('active')) {
                if (!themeControls.contains(e.target)) {
                    this.closeThemePanel();
                }
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + T to toggle theme panel
            if ((e.ctrlKey || e.metaKey) && e.key === 't') {
                e.preventDefault();
                this.toggleThemePanel();
            }
            
            // Escape to close theme panel
            if (e.key === 'Escape') {
                this.closeThemePanel();
            }
        });
    }

    /**
     * Toggle theme panel visibility
     */
    toggleThemePanel() {
        const themePanel = document.getElementById('theme-panel');
        if (themePanel) {
            themePanel.classList.toggle('active');
        }
    }

    /**
     * Close theme panel
     */
    closeThemePanel() {
        const themePanel = document.getElementById('theme-panel');
        if (themePanel) {
            themePanel.classList.remove('active');
        }
    }

    /**
     * Select and apply theme
     */
    selectTheme(themeName) {
        this.applyTheme(themeName);
        this.populateThemeGrid(); // Refresh to show active state
    }

    /**
     * Delete custom theme
     */
    deleteTheme(themeId) {
        if (confirm('Are you sure you want to delete this custom theme?')) {
            this.deleteCustomTheme(themeId);
            this.populateThemeGrid();
        }
    }

    /**
     * Preview theme temporarily
     */
    previewTheme(themeName) {
        this.applyTheme(themeName, true);
        
        // Reset to original theme after 3 seconds if not confirmed
        setTimeout(() => {
            if (this.currentTheme !== themeName) {
                this.applyTheme(this.currentTheme);
            }
        }, 3000);
    }

    /**
     * Open theme creator modal
     */
    openThemeCreator() {
        // This would open a modal for creating custom themes
        // Implementation would include color pickers, preview, etc.
        console.log('[ThemeManager] Opening theme creator...');
        alert('Theme creator functionality would be implemented here with color pickers and live preview.');
    }

    /**
     * Export themes to JSON
     */
    exportThemes() {
        const exportData = {
            customThemes: this.customThemes,
            preferences: this.themePreferences,
            currentTheme: this.currentTheme,
            exportedAt: new Date().toISOString()
        };

        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `orlando-resorts-themes-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        console.log('[ThemeManager] Themes exported');
    }

    /**
     * Import themes from JSON
     */
    importThemes() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const importData = JSON.parse(e.target.result);
                    
                    if (importData.customThemes) {
                        this.customThemes = { ...this.customThemes, ...importData.customThemes };
                        this.storeCustomThemes();
                    }
                    
                    if (importData.preferences) {
                        this.themePreferences = { ...this.themePreferences, ...importData.preferences };
                        this.storePreferences();
                    }
                    
                    this.populateThemeGrid();
                    alert('Themes imported successfully!');
                    
                } catch (error) {
                    console.error('[ThemeManager] Import error:', error);
                    alert('Failed to import themes. Please check the file format.');
                }
            };
            reader.readAsText(file);
        };
        
        input.click();
    }

    /**
     * Generate theme ID from name
     */
    generateThemeId(name) {
        return name.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').trim('-');
    }

    /**
     * Add theme change observer
     */
    addObserver(callback) {
        this.observers.push(callback);
    }

    /**
     * Remove theme change observer
     */
    removeObserver(callback) {
        this.observers = this.observers.filter(obs => obs !== callback);
    }

    /**
     * Notify observers of theme changes
     */
    notifyObservers(event, data) {
        this.observers.forEach(callback => {
            try {
                callback(event, data);
            } catch (error) {
                console.error('[ThemeManager] Observer error:', error);
            }
        });
    }

    /**
     * Storage methods
     */
    getStoredTheme() {
        return localStorage.getItem('orlando-resorts-theme');
    }

    storeTheme(theme) {
        localStorage.setItem('orlando-resorts-theme', theme);
    }

    getStoredCustomThemes() {
        try {
            return JSON.parse(localStorage.getItem('orlando-resorts-custom-themes') || '{}');
        } catch {
            return {};
        }
    }

    storeCustomThemes() {
        localStorage.setItem('orlando-resorts-custom-themes', JSON.stringify(this.customThemes));
    }

    getStoredPreferences() {
        try {
            return {
                followSystem: false,
                reducedMotion: false,
                autoHighContrast: false,
                ...JSON.parse(localStorage.getItem('orlando-resorts-theme-preferences') || '{}')
            };
        } catch {
            return {
                followSystem: false,
                reducedMotion: false,
                autoHighContrast: false
            };
        }
    }

    storePreferences() {
        localStorage.setItem('orlando-resorts-theme-preferences', JSON.stringify(this.themePreferences));
    }

    /**
     * Get current theme information
     */
    getCurrentTheme() {
        return {
            name: this.currentTheme,
            theme: this.getAvailableThemes()[this.currentTheme] || this.customThemes[this.currentTheme],
            preferences: this.themePreferences
        };
    }

    /**
     * Reset to default theme
     */
    resetToDefault() {
        this.applyTheme('light');
        this.themePreferences = {
            followSystem: false,
            reducedMotion: false,
            autoHighContrast: false
        };
        this.storePreferences();
        this.populateThemeGrid();
    }

    /**
     * Cleanup and destroy theme manager
     */
    destroy() {
        // Remove theme controls
        const themeControls = document.getElementById('theme-controls');
        if (themeControls) {
            themeControls.remove();
        }

        // Remove CSS
        const themeCSS = document.getElementById('theme-controls-css');
        if (themeCSS) {
            themeCSS.remove();
        }

        // Clear observers
        this.observers = [];

        console.log('[ThemeManager] Destroyed');
    }
}

// Global theme manager instance
let themeManager;

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    themeManager = new ThemeManager();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
