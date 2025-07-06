/**
 * Admin Dashboard JavaScript
 * Simplified version with minimal effects
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard components
    initDashboard();

    // Setup modal functionality with configuration
    const modalConfig = {
        addModalId: 'addUserModal',
        editModalId: 'editUserModal',
        deleteModalId: 'deleteUserModal',
        addBtnId: 'addUserBtn',
        editBtnClass: 'edit-btn',
        deleteBtnClass: 'delete-btn',
        editDataMap: {
            'id': 'edit_user_id',
            'username': 'edit_username'
        },
        deleteDataMap: {
            'id': 'delete_user_id',
            'username': 'delete_username'
        }
    };
    setupModals(modalConfig);

    // Configure data tables with sorting and filtering
    setupDataTables();

    // Setup notification system
    setupNotifications();

    // Setup auto-save for forms
    setupFormAutoSave('deleteUserModal');

    // Setup keyboard shortcuts
    const shortcuts = {
        'alt+n': '#addUserBtn',
        'alt+h': 'dashboard.php',
        'alt+u': 'users.php'
    };
    const tooltips = {
        'addUserBtn': 'Add New User (Alt+N)'
    };
    setupKeyboardShortcuts(shortcuts, tooltips);

    // Add dashboard welcome message
    console.log('Welcome to StudyNotes Admin!');
});

/**
 * Sort table function for data tables
 */
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    // Get sort direction
    const th = table.querySelectorAll('th')[columnIndex];
    const sortDirection = th.getAttribute('data-sort') === 'asc' ? 'desc' : 'asc';

    // Sort the rows
    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();

        // Check if values are numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
            return sortDirection === 'asc' ?
                aValue.localeCompare(bValue) :
                bValue.localeCompare(aValue);
        }
    });

    // Remove all rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }

    // Append sorted rows
    rows.forEach(row => {
        tbody.appendChild(row);
    });
}

/**
 * Initialize dashboard components
 */
function initDashboard() {
    // Initialize sidebar menu with active state
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');

    // First, remove all active classes
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
    });

    // Then set the active class based on the current page
    const currentFile = currentPath.split('/').pop();

    // Add click event listeners to sidebar links
    sidebarLinks.forEach(link => {
        const href = link.getAttribute('href');
        const linkFile = href.split('/').pop();

        // Check if this link should be active
        if (currentFile === linkFile) {
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
    const tables = document.querySelectorAll('.users-table');

    tables.forEach(table => {
        // Add sorting functionality to table headers
        const headers = table.querySelectorAll('th');

        headers.forEach(header => {
            if (header.getAttribute('data-sortable') !== 'false') {
                header.style.cursor = 'pointer';
                header.title = 'Click to sort';

                // Add sort indicator
                const sortIndicator = document.createElement('span');
                sortIndicator.classList.add('sort-indicator');
                sortIndicator.innerHTML = ' <i class="fas fa-sort"></i>';
                header.appendChild(sortIndicator);

                // Add click event
                header.addEventListener('click', function() {
                    const columnIndex = Array.from(headers).indexOf(this);
                    sortTable(table, columnIndex);

                    // Update sort indicators
                    headers.forEach(h => {
                        const indicator = h.querySelector('.sort-indicator');
                        if (indicator) {
                            indicator.innerHTML = ' <i class="fas fa-sort"></i>';
                        }
                    });

                    // Set active sort indicator
                    const sortOrder = this.getAttribute('data-sort') === 'asc' ? 'desc' : 'asc';
                    this.setAttribute('data-sort', sortOrder);

                    const thisIndicator = this.querySelector('.sort-indicator');
                    if (thisIndicator) {
                        thisIndicator.innerHTML = sortOrder === 'asc' ?
                            ' <i class="fas fa-sort-up"></i>' :
                            ' <i class="fas fa-sort-down"></i>';
                    }
                });
            }
        });
    });
}







