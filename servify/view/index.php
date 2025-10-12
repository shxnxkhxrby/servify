<?php
session_start();
include '../controls/connection.php';

$sql = "SELECT job_id, job_name, job_description FROM jobs";
$result = $conn->query($sql);

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Servify - Home</title>
  <link rel="stylesheet" type="text/css" href="../styles/landing_page.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* --- Modal --- */ 
.modal{display:flex;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:20px;}
.modal-content{background:#fff;padding:30px;width:50%;text-align:center;border-radius:8px;max-height:80vh;overflow-y:auto;box-shadow:0 4px 10px rgba(0,0,0,.2);}
.modal-content h3{margin:20px 0 10px;}
.modal-content ul{margin:10px 0;padding-left:20px;text-align:left;}
.hidden{display:none;}
.btn{padding:10px 15px;margin:15px 10px 0;border:none;cursor:pointer;border-radius:5px;}
.accept-btn{background:green;color:#fff;}
.decline-btn{background:red;color:#fff;}
.button.active{background:#0d6efd;color:#fff;}
/* --- Profile & Filters --- */ 
.profile-img{height:150px;width:auto;border-radius:50%;object-fit:cover;}
.filters-section{display:flex;justify-content:flex-end;gap:10px;margin-right:210px;align-items:center;}
/* --- Categories --- */ 
.categories-wrapper{margin:20px auto;max-width:1650px;padding:0 10px;overflow-x:hidden;}
.buttons-container{width:100%;overflow-x:visible;}
.categories-wrapper .buttons{display:grid;grid-template-columns:repeat(6,1fr);gap:16px;justify-items:center;align-items:start;width:100%;box-sizing:border-box;}
.categories-wrapper .buttons .button{width:100%;height:120px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;border-radius:10px;border:1px solid #e3e3e3;background:#fff;padding:10px;gap:6px;cursor:pointer;transition:.15s;color:#333;}
.categories-wrapper .buttons .button i{font-size:28px;}
.categories-wrapper .buttons .button span{display:block;font-size:13px;margin-top:4px;word-break:break-word;}
.categories-wrapper .buttons .button:hover,.categories-wrapper .buttons .button.active{background:#0d6efd;color:#fff;border-color:#0d6efd;transform:scale(1.03);box-shadow:0 4px 10px rgba(0,0,0,.12);}
.categories-wrapper .buttons {display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; justify-content: center; align-items: start; width: 100%; box-sizing: border-box;}
/* --- Pagination --- */ 
#workers-pagination{display:flex;justify-content:center;gap:6px;margin-top:18px;list-style:none;padding-left:0;}
#workers-pagination .page-item{margin:0 4px;}
#workers-pagination .page-link{cursor:pointer;}
/* --- How It Works --- */ 
.step-card{border-radius:15px;transition:.3s;}
.step-card:hover{transform:translateY(-8px);box-shadow:0 8px 18px rgba(0,0,0,.15);}
#howItWorks .icon i{transition:.4s;}
#howItWorks .step-card:hover .icon i{transform:scale(1.2) rotate(10deg);}
/* --- Announcement Image --- */ 
.announcement-image{display:block;width:100%;max-width:940px;height:320px;object-fit:cover;object-position:center;margin:0 auto 20px;border:none;border-radius:8px;transition:.4s;}
.announcement-image:hover{transform:scale(1.02);}
.card{border-radius:12px;}
@media(max-width:1024px){.announcement-image{height:260px;}}
@media(max-width:768px){.announcement-image{height:200px;}}
@media(max-width:480px){.announcement-image{height:150px;}}
/* --- Category Arrows --- */ 
.nav-arrow{background:#fff;border:2px solid #0d6efd;color:#0d6efd;font-size:1.6rem;width:55px;height:55px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.3s;box-shadow:0 3px 8px rgba(0,0,0,.15);}
.nav-arrow:hover{background:#0d6efd;color:#fff;transform:scale(1.1);box-shadow:0 5px 12px rgba(13,110,253,.4);}
.nav-arrow:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none;}
@media(max-width:768px){.nav-arrow{font-size:1.3rem;width:45px;height:45px;}}
/* --- Responsive Grid --- */ 
@media(max-width:1200px){.categories-wrapper .buttons{grid-template-columns:repeat(4,1fr);}}
@media(max-width:900px){.categories-wrapper .buttons{grid-template-columns:repeat(3,1fr);}}
@media(max-width:600px){.categories-wrapper .buttons{grid-template-columns:repeat(2,1fr);}.categories-wrapper .buttons .button{height:110px;}}
@media(max-width:400px){.categories-wrapper .buttons{grid-template-columns:1fr;}.categories-wrapper .buttons .button{height:96px;}}
</style>

</head>
<body>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container">
      <!-- Logo -->
      <a class="navbar-brand fw-bold text-white" href="index.php">Servify</a>

          <!-- Search Bar
    <div class="search-container">
      <form class="d-flex align-items-center" role="search">
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input class="form-control" type="search" aria-label="Search" id="search-input" placeholder="Search users by name, job, location..." onkeyup="filterUsers()">
        </div>
      </form>
    </div> -->

      <!-- Toggler -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Navbar Links -->
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <?php if ($is_logged_in): ?>
            <!-- Messages Icon -->
            <li class="nav-item position-relative">
              <a class="nav-link" href="../view/messages.php">
                <i class="bi bi-chat-dots"></i> Messages
                <span id="unread-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                  
                </span>
              </a>
            </li>
            <!-- Profile -->
            <li class="nav-item">
              <a class="nav-link" href="../view/profile.php">
                <i class="bi bi-person-circle"></i> Profile
              </a>
            </li>
            <!-- Logout -->
            <li class="nav-item">
              <a class="nav-link" href="../controls/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="../view/signup.php">Sign Up</a></li>
            <li class="nav-item"><a class="nav-link">|</a></li>
            <li class="nav-item"><a class="nav-link" href="../view/login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- CAROUSEL -->
  <div class="container mt-5 mb-4">
    <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="../image/bg2.png" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
          <div class="carousel-caption d-none d-md-block">
            <h1 class="display-5" style="color: #fff;">Welcome to Servify</h1>
            <p class="lead" style="color: #fff">Connecting you with the right laborer, creating opportunities and maximizing potential earnings.</p>
          </div>
        </div>
        <div class="carousel-item">
          <img src="../image/electrician.jpg" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
          <div class="carousel-caption d-none d-md-block">
            <h5>Find Electricians</h5>
            <p>Get electrical services for your needs.</p>
          </div>
        </div>
        <div class="carousel-item">
          <img src="../image/plumber.png" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
          <div class="carousel-caption d-none d-md-block">
            <h5>Hire Plumbers</h5>
            <p>Reliable plumbing solutions for your home and business.</p>
          </div>
        </div>
        <div class="carousel-item">
          <img src="../image/catering.jpeg" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
          <div class="carousel-caption d-none d-md-block">
            <h5>Book Caterers</h5>
            <p>Delicious catering services for all your events.</p>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
  </div>

<!-- CATEGORIES -->
<div class="container text-center mt-5">
  <h4 class="fw-bold">Browse Categories</h4>
  <p class="text-muted">Find the right laborer for your needs</p>
</div>
<div class="categories-wrapper d-flex align-items-center justify-content-center">
  <!-- Left arrow -->
  <button id="prev-btn" class="nav-arrow"><i class="bi bi-chevron-left"></i></button>
  <!-- Categories grid -->
  <div class="buttons-container flex-grow-1">
    <div class="buttons" id="category-buttons">
      <!-- Categories loaded dynamically -->
    </div>
  </div>
  <!-- Right arrow -->
  <button id="next-btn" class="nav-arrow"><i class="bi bi-chevron-right"></i></button>
</div>

<!-- Laborer's Section -->
<div class="container text-center mt-5">
  <h4 class="fw-bold">Search for Laborers</h4>
  <p class="text-muted">Connect with skilled laborers in your area</p>
</div>

<div class="text-center mt-3">
  <a href="browse.php" class="btn btn-primary">Browse More</a>
</div>

<!-- Existing Laborers Container -->
<div id="workers-container" class="container p-4">
    <!-- Laborers will be displayed here -->
</div>

<!-- Workers pagination (hidden if backend doesn't return pages) -->
<nav class="d-flex justify-content-center">
  <ul id="workers-pagination" class="pagination" style="display:none;"></ul>
</nav>

<!-- Barangay Announcements Display -->
<div class="bg-white p-4 rounded-2xl mb-10">
  <h3 class="text-center text-success fw-bold mb-4">📢 Barangay Announcements 📢</h3>
  <?php
  $ann_query = "SELECT * FROM barangay_announcements ORDER BY date_posted DESC";
  $ann_result = $conn->query($ann_query);

  if ($ann_result && $ann_result->num_rows > 0):
      while ($ann = $ann_result->fetch_assoc()):
  ?>
    <!-- single announcement wrapper - centered and limited width -->
    <div class="mx-auto mb-5" style="max-width: 980px;">
      <div class="card border-0 shadow-sm overflow-hidden">
        <!-- image (centered) -->
        <?php if (!empty($ann['image_path'])): ?>
          <div class="d-flex justify-content-center bg-light">
            <img
              src="<?php echo htmlspecialchars($ann['image_path']); ?>"
              alt="Announcement Image"
              class="announcement-image"
            >
          </div>
        <?php endif; ?>

        <!-- text -->
        <div class="card-body text-center">
          <h4 class="card-title fw-semibold mb-2"><?php echo htmlspecialchars($ann['title']); ?></h4>
          <p class="card-text text-muted mb-3"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
          <p class="small text-secondary mb-0">📅 Posted on <?php echo date('F j, Y, g:i A', strtotime($ann['date_posted'])); ?></p>
        </div>
      </div>
    </div>
  <?php
      endwhile;
  else:
      echo '<p class="text-center text-muted">No announcements available at the moment.</p>';
  endif;
  ?>
</div>

<!-- HOW IT WORKS SECTION -->
<section id="howItWorks">
  <div class="container text-center">
    <h4 class="fw-bold">How It Works</h4>
    <p class="text-muted mb-5">Connecting you with reliable laborers in just a few simple steps.</p>

    <div class="row g-4 justify-content-center">
      <!-- Step 1 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="100">
          <div class="icon mb-3">
            <i class="bi bi-person-plus-fill fs-1 text-primary"></i>
          </div>
          <h5 class="fw-semibold">1. Create an Account</h5>
          <p class="text-muted small">Sign up easily as a user or laborer and start connecting today.</p>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="200">
          <div class="icon mb-3">
            <i class="bi bi-search fs-1 text-success"></i>
          </div>
          <h5 class="fw-semibold">2. Find a Laborer</h5>
          <p class="text-muted small">Browse skilled workers by category or location in just a few clicks.</p>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="300">
          <div class="icon mb-3">
            <i class="bi bi-hand-thumbs-up-fill fs-1 text-warning"></i>
          </div>
          <h5 class="fw-semibold">3. Hire & Transact</h5>
          <p class="text-muted small">Connect directly, agree on terms, and complete your task smoothly.</p>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="400">
          <div class="icon mb-3">
            <i class="bi bi-star-fill fs-1 text-danger"></i>
          </div>
          <h5 class="fw-semibold">4. Rate & Review</h5>
          <p class="text-muted small">Leave feedback to help others choose trusted and skilled laborers.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DISCLAIMER MODAL -->
<div id="disclaimerModal" class="modal">
  <div class="modal-content">
    <h2>Welcome to Servify!</h2>
    <p>
      Servify is a community-based platform that connects residents with local workers 
      for various services. We provide the digital space, while Barangay Staff and 
      Administrators help oversee the smooth operation of the platform.
    </p>
    <h3>Terms of Use</h3>
    <p>By using Servify, you acknowledge and agree to the following:</p>
    <ul>
      <li>Servify only serves as a platform to connect residents and workers; it does not employ or control them.</li>
      <li>Barangay Staff may help monitor user and worker activities, but final agreements are strictly between users and workers.</li>
      <li>We do not guarantee the quality of services, and we are not responsible for disputes or misconduct.</li>
      <li>Users are responsible for verifying the credibility of workers before engaging in any service.</li>
      <li>Servify is not liable for damages, losses, or issues arising from transactions made outside the platform.</li>
    </ul>
    <h3>Privacy Policy</h3>
    <p>
      Servify collects only the necessary information to operate the platform, such as 
      account details and contact information. Your data will not be shared without consent, 
      except when required by law or for community safety.
    </p>
    <!-- Checkbox Agreement -->
    <div style="margin-top: 20px; text-align: left;">
      <input type="checkbox" id="acceptCheckbox">
      <label for="acceptCheckbox"> I accept and agree to the Terms of Use and Privacy Policy of Servify.</label>
    </div>

    <!-- Accept Button -->
    <button id="agreeBtn" class="btn accept-btn" disabled>Accept & Continue</button>
  </div>
</div>

<script>
// ================== DISCLAIMER MODAL ==================
document.addEventListener("DOMContentLoaded", function () {
  const checkbox = document.getElementById("acceptCheckbox");
  const agreeBtn = document.getElementById("agreeBtn");
  const modal = document.getElementById("disclaimerModal");

  // change if the version was updated to reappear the modal
  const TERMS_VERSION = "v1";
  const acceptedVersion = localStorage.getItem("acceptedTermsVersion");

  if (checkbox && agreeBtn && modal) {
    if (acceptedVersion !== TERMS_VERSION) {
      modal.style.display = "flex";
    } else {
      modal.style.display = "none";
    }

    checkbox.addEventListener("change", function () {
      agreeBtn.disabled = !this.checked;
    });

    agreeBtn.addEventListener("click", function () {
      localStorage.setItem("acceptedTermsVersion", TERMS_VERSION);
      modal.style.display = "none";
    });
  }
});

// ================== CATEGORIES (Dynamic + Arrows) ==================
document.addEventListener("DOMContentLoaded", function () {
  const categoriesContainer = document.getElementById("category-buttons");
  const prevBtn = document.getElementById("prev-btn");
  const nextBtn = document.getElementById("next-btn");

  let currentCategoryPage = 1;
  const categoriesPerPage = 6;
  let totalCategoryPages = 1;

function loadCategories(page = 1) {
  fetch("fetch_categories.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `page=${page}&limit=${categoriesPerPage}`,
  })
    .then((res) => res.json())
    .then((data) => {
      categoriesContainer.innerHTML = data.categories
        .map(
          (cat) => `
          <button class="button" data-job-id="${cat.id}">
            <span class="d-block fs-3">${cat.emoji}</span>
            <span>${cat.name}</span>
          </button>
        `
        )
        .join("");

      currentCategoryPage = data.page;
      totalCategoryPages = Math.ceil(data.total / data.limit);
      updateCategoryArrows();

      // ✅ attach worker fetching logic to new buttons
      attachCategoryClick();
    })
    .catch((err) => console.error("Error loading categories:", err));
}

  function updateCategoryArrows() {
    prevBtn.disabled = currentCategoryPage <= 1;
    nextBtn.disabled = currentCategoryPage >= totalCategoryPages;
  }

  prevBtn.addEventListener("click", () => {
    if (currentCategoryPage > 1) loadCategories(currentCategoryPage - 1);
  });

  nextBtn.addEventListener("click", () => {
    if (currentCategoryPage < totalCategoryPages) {
      loadCategories(currentCategoryPage + 1);
    }
  });

  loadCategories(); // ✅ Initial load
});

// ================== WORKERS FETCHING & PAGINATION ==================
document.addEventListener("DOMContentLoaded", function () {
  const filterBySelect = document.getElementById("filter_by_select");
  const sortOrderSelect = document.getElementById("sort_order_select");
  const workersContainer = document.getElementById("workers-container");
  const workersPagination = document.getElementById("workers-pagination");

  let currentJobId = null;
  let currentPage = 1;

  function shuffleElements(container) {
    let items = Array.from(container.children);
    for (let i = items.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      container.appendChild(items[j]);
      items.splice(j, 1);
    }
  }

  function fetchLaborers(job_id, page = 1) {
    const filterBy = filterBySelect ? filterBySelect.value : "labor";
    const sortOrder = sortOrderSelect ? sortOrderSelect.value : "ASC";

    const params = new URLSearchParams();
    params.append("job_id", job_id);
    params.append("filter_by", filterBy);
    params.append("sort_order", sortOrder);
    params.append("page", page);

    fetch("fetch_workers.php", {
      method: "POST",
      body: params,
    })
      .then((response) => response.text())
      .then((text) => {
        let parsed;
        try {
          parsed = JSON.parse(text);
        } catch (e) {
          parsed = null;
        }

        if (parsed && parsed.html !== undefined) {
          workersContainer.innerHTML = parsed.html;
          if (job_id === "all") shuffleElements(workersContainer);
          setupWorkersPagination(
            parsed.total_pages || 1,
            parsed.current_page || 1
          );
        } else {
          workersContainer.innerHTML = text;
          if (job_id === "all") shuffleElements(workersContainer);
          workersPagination.style.display = "none";
        }
      })
      .catch((error) => console.error("Error fetching workers:", error));
  }

  function setupWorkersPagination(totalPages, activePage) {
    workersPagination.innerHTML = "";
    workersPagination.style.display = totalPages > 1 ? "" : "none";

    const makePageItem = (label, page, isActive, isDisabled) => {
      const li = document.createElement("li");
      li.className =
        "page-item" +
        (isActive ? " active" : "") +
        (isDisabled ? " disabled" : "");
      li.innerHTML = `<a class="page-link" href="#" data-page="${page}">${label}</a>`;
      return li;
    };

    // Prev
    workersPagination.appendChild(
      makePageItem("Prev", Math.max(1, activePage - 1), false, activePage <= 1)
    );

    const maxVisible = 7;
    let start = Math.max(1, activePage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

    if (start > 1) {
      workersPagination.appendChild(makePageItem("1", 1, false, false));
      if (start > 2) {
        const dots = document.createElement("li");
        dots.className = "page-item disabled";
        dots.innerHTML = `<span class="page-link">…</span>`;
        workersPagination.appendChild(dots);
      }
    }

    for (let p = start; p <= end; p++) {
      workersPagination.appendChild(
        makePageItem(p, p, p === activePage, false)
      );
    }

    if (end < totalPages) {
      if (end < totalPages - 1) {
        const dots = document.createElement("li");
        dots.className = "page-item disabled";
        dots.innerHTML = `<span class="page-link">…</span>`;
        workersPagination.appendChild(dots);
      }
      workersPagination.appendChild(
        makePageItem(totalPages, totalPages, false, false)
      );
    }

    // Next
    workersPagination.appendChild(
      makePageItem(
        "Next",
        Math.min(totalPages, activePage + 1),
        false,
        activePage >= totalPages
      )
    );

    workersPagination.querySelectorAll(".page-link").forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        const page = parseInt(this.getAttribute("data-page")) || 1;
        if (page === currentPage) return;
        currentPage = page;
        fetchLaborers(currentJobId, currentPage);
      });
    });
  }

  // ✅ Attach worker fetching to category buttons
  window.attachCategoryClick = function () {
    const categoryButtons = document.querySelectorAll(".button");
    categoryButtons.forEach((btn) => {
      btn.addEventListener("click", function () {
        categoryButtons.forEach((b) => b.classList.remove("active"));
        this.classList.add("active");
        const job_id = this.getAttribute("data-job-id");
        currentJobId = job_id;
        currentPage = 1;
        fetchLaborers(job_id, currentPage);
      });
    });
  };

  // ✅ Initial load of workers
  fetchLaborers(null, 1);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<div style="margin-top:100px;">
  <?php include '../view/footer.php'; ?>
</div>
<!-- AOS Animation Library -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 1000,
    once: true
  });
</script>
<style>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.8s ease forwards;
}
</style>
</body>
</html>
