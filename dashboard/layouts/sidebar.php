<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Wrapper -->
<div class="sidebar-wrapper" id="sidebar">
  <!-- Logo -->
  <div class="logo-wrapper">
    <a href="index.php">Dashboard</a>
    <button class="toggle-sidebar" id="toggleSidebar">
      <i class="fas fa-bars"></i>
    </button>
  </div>

  <!-- Sidebar Main -->
  <div class="sidebar-main">
    <!-- Menu Title -->
    <div class="sidebar-main-title">
      <h6>MENU</h6>
    </div>

    <!-- Navigation Links -->
    <ul class="sidebar-links">
      <li class="sidebar-list <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
        <a href="index.php">
          <i class="fas fa-chart-line"></i>
          <span>Sales Overview</span>
        </a>
      </li>

      <li class="sidebar-list <?php echo ($current_page == 'product.php') ? 'active' : ''; ?>">
        <a href="product.php">
          <i class="fas fa-box"></i>
          <span>Product Analysis</span>
        </a>
      </li>

      <li class="sidebar-list <?php echo ($current_page == 'geo.php') ? 'active' : ''; ?>">
        <a href="geo.php">
          <i class="fas fa-map-marked-alt"></i>
          <span>Customer Geo</span>
        </a>
      </li>

      <li class="sidebar-list <?php echo ($current_page == 'olap.php') ? 'active' : ''; ?>">
        <a href="olap.php">
          <i class="fas fa-chart-pie"></i>
          <span>OLAP</span>
        </a>
      </li>
    </ul>
  </div>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" style="display: none;"></div>

<script>
  // Sidebar toggle functionality
  document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn) {
      toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');

        // Show/hide overlay on mobile
        if (window.innerWidth <= 1024) {
          if (sidebar.classList.contains('active')) {
            overlay.style.display = 'block';
          } else {
            overlay.style.display = 'none';
          }
        }
      });
    }

    // Close sidebar when clicking overlay
    if (overlay) {
      overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
      });
    }

    // Handle window resize
    window.addEventListener('resize', function() {
      if (window.innerWidth > 1024) {
        overlay.style.display = 'none';
        sidebar.classList.remove('active');
      }
    });
  });
</script>