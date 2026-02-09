// Main JavaScript file for CarthageGG Platform

// Tab switching functionality
function switchTab(tabName) {
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => {
        btn.classList.remove('border-[#D4AF37]', 'text-[#D4AF37]');
        btn.classList.add('border-transparent', 'text-gray-400');
    });
    
    if (event && event.target) {
        event.target.classList.add('border-[#D4AF37]', 'text-[#D4AF37]');
        event.target.classList.remove('border-transparent', 'text-gray-400');
    }
}

// Mobile menu toggle (for future mobile menu implementation)
function toggleMobileMenu() {
    const menu = document.querySelector('[data-mobile-menu]');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Form validation helper
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Show notification/toast
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-[#DC2626]' :
        'bg-[#D4AF37]'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Add active class to current page in navigation
function setActiveNavLink() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('nav a').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.html')) {
            link.classList.add('bg-[#D4AF37]/10', 'text-[#D4AF37]');
            link.classList.remove('text-gray-300', 'hover:bg-[#1e1e2e]');
        }
    });
}

// Smooth scroll to element
function smoothScroll(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(amount);
}

// Format date
function formatDate(date) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(date).toLocaleDateString('en-US', options);
}

// Debounce function for search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Search functionality
const handleSearch = debounce((query) => {
    console.log('Searching for:', query);
    // Implement search logic here
}, 300);

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    setActiveNavLink();
    
    // Add search functionality
    const searchInputs = document.querySelectorAll('input[placeholder*="Search"]');
    searchInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            handleSearch(e.target.value);
        });
    });
    
    // Add click handlers to buttons
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.classList.contains('tab-button')) {
                switchTab(this.dataset.tab);
            }
        });
    });
});

// Utility: Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard!', 'success');
    }).catch(() => {
        showNotification('Failed to copy', 'error');
    });
}

// Utility: Generate UUID
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Analytics helper - track page views
function trackPageView(pageName) {
    console.log('Page view tracked:', pageName);
    // Integrate with analytics service (Google Analytics, etc.)
}

// Initialize analytics on page load
document.addEventListener('DOMContentLoaded', () => {
    const pageName = document.title;
    trackPageView(pageName);
});

// Export functions for use in inline scripts
window.switchTab = switchTab;
window.toggleMobileMenu = toggleMobileMenu;
window.showNotification = showNotification;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.copyToClipboard = copyToClipboard;

// Store selection functionality
let selectedItems = [];

function addStoreItem(button, itemName, price) {
    // Add item to selection
    selectedItems.push({ name: itemName, price: price, id: generateUUID() });
    
    // Update UI
    button.classList.add('bg-green-500');
    button.textContent = '✓ Added';
    setTimeout(() => {
        button.classList.remove('bg-green-500');
        button.textContent = 'Add to Selection';
    }, 1500);
    
    // Update display
    updateSelectedItemsDisplay();
    
    // Show notification
    showNotification(`${itemName} added to selection!`, 'success');
}

function updateSelectedItemsDisplay() {
    const container = document.getElementById('selectedItems');
    const totalEl = document.getElementById('selectedTotal');
    
    if (!container) return;
    
    if (selectedItems.length === 0) {
        container.innerHTML = '<p class="text-gray-400 text-sm italic">No items selected yet</p>';
        if (totalEl) totalEl.textContent = 'Total: $0.00';
        return;
    }
    
    // Clear container
    container.innerHTML = '';
    
    // Add items
    let total = 0;
    selectedItems.forEach((item, index) => {
        total += item.price;
        const itemEl = document.createElement('div');
        itemEl.className = 'flex justify-between items-center p-2 bg-[#0a0a0f] rounded text-sm';
        itemEl.innerHTML = `
            <div>
                <p class="font-bold">${item.name}</p>
                <p class="text-[#D4AF37] font-bold">$${item.price.toFixed(2)}</p>
            </div>
            <button onclick="removeStoreItem(${index})" class="text-red-400 hover:text-red-500 text-xs">✕</button>
        `;
        container.appendChild(itemEl);
    });
    
    // Update total
    if (totalEl) {
        totalEl.textContent = `Total: $${total.toFixed(2)}`;
    }
}

function removeStoreItem(index) {
    selectedItems.splice(index, 1);
    updateSelectedItemsDisplay();
    showNotification('Item removed', 'info');
}

function clearSelection() {
    if (selectedItems.length === 0) {
        showNotification('No items to clear', 'info');
        return;
    }
    
    if (confirm('Are you sure you want to clear all selected items?')) {
        selectedItems = [];
        updateSelectedItemsDisplay();
        showNotification('Selection cleared', 'info');
    }
}

// Initialize selected items display on page load
document.addEventListener('DOMContentLoaded', () => {
    updateSelectedItemsDisplay();
});

// Export store functions
window.addStoreItem = addStoreItem;
window.removeStoreItem = removeStoreItem;
window.clearSelection = clearSelection;
