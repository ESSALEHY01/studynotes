/**
 * User Dashboard JavaScript
 * Simplified version with minimal effects
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard components
    initDashboard();

    // Setup modal functionality with configuration
    const modalConfig = {
        addModalId: 'addModuleModal',
        editModalId: 'editModuleModal',
        deleteModalId: 'deleteModuleModal',
        addBtnId: 'addModuleBtn',
        editBtnClass: 'edit-module-btn',
        deleteBtnClass: 'delete-module-btn',
        editDataMap: {
            'id': 'edit_module_id',
            'name': 'edit_module_name',
            'description': 'edit_module_description'
        },
        deleteDataMap: {
            'id': 'delete_module_id',
            'name': 'delete_module_name'
        }
    };
    setupModals(modalConfig);

    // Configure data tables with sorting and filtering
    setupDataTables();

    // Setup notification system
    setupNotifications();

    // Setup auto-save for forms
    setupFormAutoSave('deleteModuleModal');

    // Setup keyboard shortcuts
    const shortcuts = {
        'alt+n': '#addModuleBtn',
        'alt+h': 'dashboard.php',
        'alt+m': 'modules.php',
        'alt+t': 'notes.php'
    };
    const tooltips = {
        'addModuleBtn': 'Add New Module (Alt+N)'
    };
    setupKeyboardShortcuts(shortcuts, tooltips);

    // Add dashboard welcome message
    console.log('Welcome to StudyNotes!');
});

/**
 * Initialize dashboard components
 */
function initDashboard() {
    // Initialize sidebar menu with active state
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');

    sidebarLinks.forEach(link => {
        const href = link.getAttribute('href');

        // Set active class based on current path
        if (href === currentPath || currentPath.includes(href)) {
            link.classList.add('active');
        }
    });

    // Add collapsible sidebar functionality for mobile
    const sidebarToggle = document.createElement('button');
    sidebarToggle.classList.add('sidebar-toggle');
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    sidebarToggle.style.cssText = `
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        z-index: 100;
    `;

    document.body.appendChild(sidebarToggle);

    const sidebar = document.querySelector('.sidebar');

    // Show toggle button on mobile
    if (window.innerWidth <= 992) {
        sidebarToggle.style.display = 'flex';
        sidebarToggle.style.alignItems = 'center';
        sidebarToggle.style.justifyContent = 'center';

        // Initially hide sidebar on mobile
        if (sidebar) {
            sidebar.style.transform = 'translateX(-110%)';
            sidebar.style.position = 'fixed';
            sidebar.style.top = '0';
            sidebar.style.left = '0';
            sidebar.style.height = '100vh';
            sidebar.style.zIndex = '99';
        }
    }

    sidebarToggle.addEventListener('click', function() {
        if (sidebar) {
            if (sidebar.style.transform === 'translateX(-110%)') {
                sidebar.style.transform = 'translateX(0)';
                this.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                sidebar.style.transform = 'translateX(-110%)';
                this.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992 && sidebar &&
            !sidebar.contains(e.target) &&
            e.target !== sidebarToggle &&
            !sidebarToggle.contains(e.target)) {
            sidebar.style.transform = 'translateX(-110%)';
            sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 992) {
            sidebarToggle.style.display = 'flex';

            if (sidebar) {
                sidebar.style.position = 'fixed';
            }
        } else {
            sidebarToggle.style.display = 'none';

            if (sidebar) {
                sidebar.style.transform = 'translateX(0)';
                sidebar.style.position = 'static';
                sidebar.style.height = 'auto';
            }
        }
    });
}



/**
 * Setup data tables with sorting and filtering
 */
function setupDataTables() {
    // Add sorting functionality to tables
    document.querySelectorAll('th[data-sortable="true"]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const table = this.closest('table');
            const index = Array.from(this.parentNode.children).indexOf(this);
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAsc = this.classList.contains('asc');

            // Remove sort classes from all headers
            table.querySelectorAll('th').forEach(header => {
                header.classList.remove('asc', 'desc');
            });

            // Add sort class to current header
            this.classList.add(isAsc ? 'desc' : 'asc');

            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.children[index].textContent.trim();
                const bValue = b.children[index].textContent.trim();

                // Check if values are numbers
                if (!isNaN(aValue) && !isNaN(bValue)) {
                    return isAsc ? bValue - aValue : aValue - bValue;
                }

                // Sort as strings
                return isAsc ?
                    bValue.localeCompare(aValue) :
                    aValue.localeCompare(bValue);
            });

            // Reorder rows
            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        });
    });
}
