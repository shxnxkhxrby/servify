<?php

// Check login status
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

// Detect if we're in the root or view folder
$in_view_folder = basename(dirname($_SERVER['PHP_SELF'])) === 'view';
$base_path = $in_view_folder ? '../' : '';

// Get current page for active state detection
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch logged-in user's name and profile picture
$current_user_name = '';
$profile_picture = $base_path . 'uploads/profile_pics/default.jpg'; // Default fallback

if ($is_logged_in && $current_user_id) {
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname, profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user_row = $res->fetch_assoc();

        // Build full name
        $current_user_name = $user_row['firstname'];
        if (!empty($user_row['middlename'])) {
            $current_user_name .= ' ' . $user_row['middlename'];
        }
        $current_user_name .= ' ' . $user_row['lastname'];

        // Use profile picture if available
        if (!empty($user_row['profile_picture'])) {
            $profile_picture = $base_path . $user_row['profile_picture'];
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="<?php echo $base_path; ?>styles/navbar1.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title></title>
</head>
<body>

<!-- NAVIGATION BAR -->
<header>
  <div class="header-content">
    <div class="brand"><a href="<?php echo $base_path; ?>index.php">Servify</a></div>
    <div class="menu-container">
      <nav class="wrapper-2" id="menu">
        <p><a href="<?php echo $base_path; ?>view/browse.php">Services</a></p>
        <?php if ($is_logged_in): ?>
        <p><a href="<?php echo $base_path; ?>view/messages.php">Messages</a></p>
        <?php endif; ?>
        <p class="divider">|</p>

        <?php if ($is_logged_in): ?>
          <p class="profile-wrapper">
            <span class="profile-icon" onclick="toggleProfileMenu()">
              <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="icon">
            </span>
            <div id="profile-menu" class="profile-menu d-none">
              <a href="<?php echo $base_path; ?>view/profile.php" class="user-info-link">
                <div class="user-info">
                  <span><?php echo htmlspecialchars($current_user_name); ?></span>
                  <i class="bi bi-pencil-square"></i>
                </div>
              </a>
              <a href="<?php echo $base_path; ?>view/messages.php"><i class="bi bi-chat-dots"></i> Inbox</a>
              <a href="<?php echo $base_path; ?>controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
          </p>
        <?php else: ?>
          <p class="login"><a href="<?php echo $base_path; ?>view/login.php"><i class="bi bi-box-arrow-in-right"></i> Login / Signup</a></p>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</header>


<!-- Bottom Navigation (Mobile/Tablet Only) -->
<div class="bottom-nav mobile-only">
  <div class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" data-page="home">
    <i class="bi bi-house-fill"></i>
    <span>Home</span>
  </div>
  <div class="nav-item <?php echo ($current_page == 'browse.php') ? 'active' : ''; ?>" data-page="services">
    <i class="bi bi-search"></i>
    <span>Services</span>
  </div>
  <?php if ($is_logged_in): ?>
  <div class="nav-item <?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>" data-page="messages">
    <i class="bi bi-chat-dots"></i>
    <span>Messages</span>
  </div>
  <div class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" data-page="profile">
    <i class="bi bi-person-circle"></i>
    <span>Profile</span>
  </div>
  <?php else: ?>
  <div class="nav-item" data-page="more">
    <i class="bi bi-three-dots"></i>
    <span>More</span>
  </div>
  <?php endif; ?>
</div>


<!-- Fullscreen More Menu -->
<div id="more-menu" class="fullscreen-menu d-none">
  <div class="menu-panel">
    <div class="menu-header">
      <h1 class="menu-title">SERVIFY</h1>
      <span class="close-btn" onclick="toggleMoreMenu()">✕</span>
    </div>

    <?php if ($is_logged_in): ?>
    <!-- Logged-in User Menu -->
      <div class="user-section">
        <div class="profile-info">
          <a href="<?php echo $base_path; ?>view/profile.php" class="profile-link">
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="icon">
            <h3 class="user-name"><?php echo htmlspecialchars($current_user_name); ?></h3>
          </a>
        </div>
        <i class="bi bi-pencil-square edit-icon"></i>
      </div>

      <div class="section-divider"></div>

      <div class="menu-options">
        <a href="<?php echo $base_path; ?>view/messages.php"><i class="bi bi-chat-dots"></i> Inbox</a>
        <a href="<?php echo $base_path; ?>controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>

    <?php else: ?>
      <!-- Non-User Menu -->
      <div class="menu-options">
        <a href="<?php echo $base_path; ?>view/become-laborer.php"><i class="bi bi-person-workspace"></i> Become a laborer</a>
        <a href="<?php echo $base_path; ?>view/login.php"><i class="bi bi-person-circle"></i> Signin / Signup</a>
      </div>
    <?php endif; ?>
  </div>
</div>



<script>
  // Toggle profile dropdown
  function toggleProfileMenu() {
    const menu = document.getElementById('profile-menu');
    menu.classList.toggle('d-none');
  }

  // Toggle fullscreen "More" menu
  function toggleMoreMenu() {
    const menu = document.getElementById('more-menu');
    menu.classList.toggle('d-none');
  }

  // Navigation actions
  const basePath = '<?php echo $base_path; ?>';
  
  function goToHome() {
    window.location.href = basePath + 'index.php';
  }

  function goToServices() {
    window.location.href = basePath + 'view/browse.php';
  }

  function goToMessages() {
    window.location.href = basePath + 'view/messages.php';
  }

  function goToProfile() {
    window.location.href = basePath + 'view/profile.php';
  }

  // Bottom navigation click handlers with sliding indicator
  document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.bottom-nav .nav-item');
    const bottomNav = document.querySelector('.bottom-nav');
    
    // Function to update sliding indicator position
    function updateIndicator(activeItem) {
      const itemWidth = activeItem.offsetWidth;
      const itemLeft = activeItem.offsetLeft;
      const indicatorWidth = 40; // Width of the indicator bar
      const indicatorPosition = itemLeft + (itemWidth / 2) - (indicatorWidth / 2);
      
      bottomNav.style.setProperty('--indicator-position', indicatorPosition + 'px');
      bottomNav.style.setProperty('--indicator-width', indicatorWidth + 'px');
    }
    
    // Initialize indicator position on page load
    const activeItem = document.querySelector('.bottom-nav .nav-item.active');
    if (activeItem) {
      updateIndicator(activeItem);
    }
    
    // Handle window resize to recalculate indicator position
    let resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        const currentActive = document.querySelector('.bottom-nav .nav-item.active');
        if (currentActive) {
          updateIndicator(currentActive);
        }
      }, 250);
    });
    
    navItems.forEach(item => {
      item.addEventListener('click', function(e) {
        e.preventDefault();
        const page = this.getAttribute('data-page');
        
        // Don't navigate if clicking "more" button
        if (page !== 'more') {
          // Remove active from all items
          navItems.forEach(nav => nav.classList.remove('active'));
          // Add active to clicked item
          this.classList.add('active');
          
          // Update indicator position
          updateIndicator(this);
        }
        
        // Navigate to page or toggle menu
        switch(page) {
          case 'home':
            goToHome();
            break;
          case 'services':
            goToServices();
            break;
          case 'messages':
            goToMessages();
            break;
          case 'profile':
            goToProfile();
            break;
          case 'more':
            toggleMoreMenu();
            break;
        }
      });
      
      // Prevent text decoration on all events
      item.addEventListener('touchstart', function(e) {
        this.style.textDecoration = 'none';
      });
      
      item.addEventListener('mousedown', function(e) {
        this.style.textDecoration = 'none';
      });
    });
    
    // Close profile menu when clicking outside
    document.addEventListener('click', function(event) {
      const profileMenu = document.getElementById('profile-menu');
      const profileIcon = document.querySelector('.profile-icon');
      
      if (profileMenu && !profileMenu.contains(event.target) && 
          profileIcon && !profileIcon.contains(event.target)) {
        profileMenu.classList.add('d-none');
      }
    });
    
    // Close more menu when clicking outside
    document.addEventListener('click', function(event) {
      const moreMenu = document.getElementById('more-menu');
      const moreButtons = document.querySelectorAll('.nav-item[data-page="more"]');
      let clickedMoreButton = false;
      
      moreButtons.forEach(btn => {
        if (btn.contains(event.target)) {
          clickedMoreButton = true;
        }
      });
      
      if (moreMenu && !moreMenu.classList.contains('d-none') && 
          !moreMenu.contains(event.target) && !clickedMoreButton) {
        toggleMoreMenu();
      }
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>