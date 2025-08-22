<?php
// Determine current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.mobile-menu-btn {
    display: none;
    flex-direction: column;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}

.mobile-menu-btn span {
    width: 25px;
    height: 3px;
    background: var(--primary-text);
    margin: 2px 0;
    transition: 0.3s;
}

.mobile-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--surface);
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    padding: 1rem;
    min-width: 200px;
    z-index: 1001;
}

.mobile-menu.active {
    display: block;
}

.mobile-nav-link {
    display: block;
    color: var(--primary-text);
    text-decoration: none;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
    transition: background 0.2s ease;
}

.mobile-nav-link:hover {
    background: var(--action-red);
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 0 !important;
        padding: 0 var(--space-sm) !important;
    }
    
    .nav-menu {
        display: none !important;
    }
    
    .mobile-menu-btn {
        display: flex !important;
    }
}
</style>

<!-- Header -->
<header class="header">
    <div class="header-content">
        <a href="dashboard_guest.php" class="logo">KITSDB</a>
        <nav class="nav-menu">
            <a href="kits_browse.php" class="nav-link"<?php echo $current_page === 'kits_browse.php' ? ' style="color: var(--highlight-yellow);"' : ''; ?>>Jersey List</a>
        </nav>
        
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="kits_browse.php" class="mobile-nav-link">Jersey List</a>
        </div>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('active');
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    
    if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
        mobileMenu.classList.remove('active');
    }
});
</script>