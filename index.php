<?php
session_start();
include 'controls/connection.php';

// Fetch jobs (if needed)
$sql = "SELECT job_id, job_name, job_description FROM jobs";
$result = $conn->query($sql);

// Check login status
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

// Fetch logged-in user's name and profile picture
$current_user_name = '';
$profile_picture = 'uploads/profile_pics/default.jpg'; // Default fallback

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
            $profile_picture = $user_row['profile_picture'];
        }
    }
    $stmt->close();
}
?>
<?php include 'nav.php' ?>
<?php include 'view/chatbot.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Servify - Home</title>
  <link rel="stylesheet" type="text/css" href="styles/landing_page.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="favicon.ico" type="image/x-icon">

<style>
.modal{display:flex;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:20px;}
.modal-content{background:#fff;padding:30px;width:50%;text-align:center;border-radius:8px;max-height:80vh;overflow-y:auto;box-shadow:0 4px 10px rgba(0,0,0,.2);}
.modal-content h3{margin:20px 0 10px;}
.modal-content ul{margin:10px 0;padding-left:20px;text-align:left;}
.hidden{display:none;}
.btn{padding:10px 15px;margin:15px 10px 0;border:none;cursor:pointer;border-radius:5px;}
.accept-btn{background:green;color:#fff;}
.decline-btn{background:red;color:#fff;}
.button.active{background:linear-gradient(135deg,#027d8d,#0293a1);color:#fff;}
.profile-img{height:150px;width:auto;border-radius:50%;object-fit:cover;}
.filters-section{display:flex;justify-content:flex-end;gap:10px;margin-right:210px;align-items:center;}
.section-header-wrapper{text-align:center;margin:40px 0 30px;position:relative;}
.section-icon{font-size:2.5rem;background:linear-gradient(135deg,#027d8d,#0293a1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:8px;display:inline-block;animation:iconFloat 3s ease-in-out infinite;}
@keyframes iconFloat{0%,100%{transform:translateY(0px);}50%{transform:translateY(-5px);}}
.section-title{font-size:2.2rem;font-weight:700;background:linear-gradient(135deg,#027d8d,#0293a1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin:0;letter-spacing:-0.5px;line-height:1.2;}
.section-subtitle{font-size:1.05rem;color:#5a6c7d;font-weight:400;margin:8px 0 0;line-height:1.5;}
#worker-header h4,#worker-containers h4,.container.text-center h4{font-size:2.2rem;font-weight:700;background:linear-gradient(135deg,#027d8d,#0293a1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin:100 0 8px;letter-spacing:-0.5px;line-height:1.2;}
#worker-header p.text-muted,#worker-containers p.text-muted,.container.text-center p.text-muted{font-size:1.05rem;color:#5a6c7d;font-weight:400;margin-top:8px;}
#howItWorks h4{font-size:2.2rem;font-weight:700;background:linear-gradient(135deg,#027d8d,#0293a1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin:0 0 8px;letter-spacing:-0.5px;line-height:1.2;}
#howItWorks p.text-muted{font-size:1.05rem;color:#5a6c7d;font-weight:400;margin-top:8px;}
.announcement-header{text-align:center;margin:50px 0 35px;position:relative;padding:0 15px;}
.announcement-icon{display:none;}
.announcement-title{font-size:2.2rem;font-weight:700;background:linear-gradient(135deg,#027d8d,#0293a1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin:0;letter-spacing:-0.5px;line-height:1.2;}
.bg-white h3{font-size:2.2rem;font-weight:700;background:linear-gradient(135deg,#027d8d,#0293a1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin:0 0 30px;letter-spacing:-0.5px;line-height:1.2;}
#howItWorks .step-card h5{color:#2c3e50;font-weight:600;margin-top:10px;}
.categories-wrapper{margin:20px auto;max-width:1650px;padding:0 10px;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:nowrap;}
.buttons-container{width:100%;overflow:hidden;position:relative;background:transparent!important;}
.categories-wrapper .buttons{display:grid;grid-template-columns:repeat(6,1fr);gap:20px;justify-items:center;align-items:stretch;width:100%;box-sizing:border-box;transition:transform .3s ease;background:transparent!important;}
#category-buttons{background:transparent!important;}
.categories-wrapper .buttons .button{width:100%;height:130px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;border-radius:12px;border:1px solid #e3e3e3;background:rgba(255,255,255,0.9)!important;backdrop-filter:blur(10px);padding:10px;gap:6px;cursor:pointer;transition:.15s ease;color:#333;box-shadow:0 2px 5px rgba(0,0,0,.05);}
.categories-wrapper .buttons .button i{font-size:28px;}
.categories-wrapper .buttons .button span{display:block;font-size:14px;margin-top:4px;word-break:break-word;}
.categories-wrapper .buttons .button:hover,.categories-wrapper .buttons .button.active{background:linear-gradient(135deg,#027d8d,#0293a1);color:#fff;border-color:#027d8d;transform:translateY(-4px);box-shadow:0 6px 12px rgba(2,125,141,.3);}
.nav-arrow{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);border:2px solid #027d8d;color:#027d8d;font-size:1.6rem;width:55px;height:55px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.3s;box-shadow:0 3px 8px rgba(0,0,0,.15);flex-shrink:0;cursor:pointer;}
.nav-arrow:hover{background:linear-gradient(135deg,#027d8d,#0293a1);color:#fff;transform:scale(1.1);box-shadow:0 5px 12px rgba(2,125,141,.4);}
.nav-arrow:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none;}
@media(max-width:1200px){.categories-wrapper .buttons{grid-template-columns:repeat(4,1fr);}}
@media(max-width:991px){.categories-wrapper .buttons{grid-template-columns:repeat(3,1fr);gap:15px;}.nav-arrow{width:40px;height:40px;font-size:1.2rem;}.categories-wrapper .buttons .button{height:110px;}}
@media(max-width:768px){.categories-wrapper .buttons{grid-template-columns:repeat(2,1fr);gap:12px;}.nav-arrow{width:35px;height:35px;font-size:1rem;}.categories-wrapper .buttons .button{height:100px;}.categories-wrapper .buttons .button span{font-size:12px;}.categories-wrapper .buttons .button i{font-size:24px;}.modal-content{width:90%;max-width:400px;height:auto;max-height:80vh;border-radius:10px;padding:20px;overflow-y:auto;}.modal{align-items:center;justify-content:center;padding:10px;}.section-icon{font-size:2rem;}.section-title,#worker-header h4,#worker-containers h4,.container.text-center h4,#howItWorks h4{font-size:1.6rem;}.announcement-title{font-size:1.4rem;letter-spacing:-0.3px;}.announcement-header{margin:35px 0 25px;padding:0 10px;}}
@media(max-width:480px){.categories-wrapper{gap:5px;padding:0 5px;}.nav-arrow{width:30px;height:30px;font-size:.9rem;}.categories-wrapper .buttons{gap:10px;}.categories-wrapper .buttons .button{height:90px;padding:8px;}.section-title,#worker-header h4,#worker-containers h4,.container.text-center h4,#howItWorks h4{font-size:1.4rem;letter-spacing:-0.3px;}.section-icon{font-size:1.6rem;}.announcement-title{font-size:1.2rem;letter-spacing:-0.2px;}.announcement-header{margin:25px 0 18px;padding:0 8px;}.section-subtitle,#worker-header p.text-muted,#worker-containers p.text-muted,.container.text-center p.text-muted,#howItWorks p.text-muted{font-size:0.95rem;}}
@media(max-width:390px){.announcement-title{font-size:1.1rem;letter-spacing:-0.1px;}.announcement-header{padding:0 6px;}}
@media(max-width:360px){.announcement-title{font-size:1rem;}.announcement-header{padding:0 5px;}}
#workers-pagination{display:flex;justify-content:center;gap:6px;margin-top:18px;list-style:none;padding-left:0;}
#workers-pagination .page-item{margin:0 4px;}
#workers-pagination .page-link{cursor:pointer;}
.step-card{border-radius:15px;transition:.3s;}
.step-card:hover{transform:translateY(-8px);box-shadow:0 8px 18px rgba(0,0,0,.15);}
#howItWorks .icon{display:flex!important;justify-content:center!important;align-items:center!important;width:100%;text-align:center;}
#howItWorks .icon i{transition:.4s;display:inline-block;}
#howItWorks .step-card:hover .icon i{transform:scale(1.2) rotate(10deg);}
.announcement-image{display:block;width:100%;max-width:100%;height:auto;min-height:400px;max-height:600px;object-fit:cover;object-position:center;margin:0;border:none;border-radius:0;transition:.4s;box-shadow:none;}
.announcement-image:hover{transform:scale(1.02);box-shadow:0 4px 12px rgba(0,0,0,.15);}
@media(max-width:1024px){.announcement-image{min-height:320px;max-height:500px;}}
@media(max-width:768px){.announcement-image{min-height:250px;max-height:400px;}}
@media(max-width:480px){.announcement-image{min-height:200px;max-height:320px;}}
.carousel-item{position:relative;}
.carousel-item img{filter:brightness(30%);}
.carousel-caption{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;padding:15px;max-width:90%;width:500px;}
.carousel-caption h1{font-size:3rem;font-weight:bold;color:#fff;margin-bottom:10px;}
.carousel-caption p{font-size:.80rem;color:#ddd;margin:0 auto;max-width:90%;}
.animate-text{opacity:0;transform:translateY(30px);animation:fadeUp 1s ease forwards;}
.animate-text:nth-child(2){animation-delay:.3s;}
@keyframes fadeUp{to{opacity:1;transform:translateY(0);}}
@media(max-width:768px){.carousel-caption{max-width:90%;padding:10px;}.carousel-caption h1{font-size:1.8rem;}.carousel-caption p{font-size:1rem;}}
@media(max-width:576px){.carousel-caption{top:60%;transform:translate(-50%,-60%);max-width:95%;padding:5px 15px;}.carousel-caption h1{font-size:1.4rem;}.carousel-caption p{font-size:.95rem;line-height:1.4;}}
.labor-card .card{height:auto;display:flex;flex-direction:row;box-shadow:0 1px 4px rgba(0,0,0,.1);transition:transform .3s ease,box-shadow .3s ease;position:relative;border-radius:8px;overflow:hidden;background:#fff;}
.labor-card .card::before{content:'';position:absolute;top:0;left:0;width:50%;height:100%;background:none;z-index:1;pointer-events:none;}
.labor-card .card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 12px 24px rgba(2,125,141,0.15),0 0 0 1px rgba(2,125,141,0.1);}
.labor-card .card-img-top{width:140px;height:140px;object-fit:cover;object-position:center;flex-shrink:0;border-radius:8px;margin:8px;transition:all 0.3s ease;position:relative;z-index:2;}
.labor-card .card:hover .card-img-top{transform:scale(1.05);box-shadow:0 0 20px rgba(2,125,141,0.3);}
.labor-card .card-body{flex:1;padding:12px;display:flex;flex-direction:column;justify-content:space-between;gap:8px;position:relative;z-index:2;}
.labor-card .name-rating{display:flex;flex-direction:column;gap:4px;}
.labor-card .name-rating h5{margin:0;font-size:1rem;font-weight:600;color:#333;line-height:1.3;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;transition:color 0.3s ease;}
.labor-card .card:hover .name-rating h5{color:#027d8d;}
.labor-card #rating{display:flex;align-items:center;gap:2px;margin-top:2px;}
@keyframes ratingPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.labor-card #rating .fa-star,.labor-card #rating .fa-star-half-stroke{font-size:.75rem;}
.labor-card #services{margin:0;padding:0;font-size:1.2rem;line-height:1.4;display:flex;flex-wrap:wrap;gap:4px;}
.labor-card #location{margin:0;padding:0;font-size:.8rem;color:#888;display:flex;align-items:center;gap:4px;}
.labor-card #location::before{content:"📍";font-size:.9rem;}
.labor-card #verification{position:absolute;top:8px;right:0;color:white;font-size:.7rem;padding:4px 12px;background-color:#4CAF50;border-top-left-radius:12px;border-bottom-left-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,.2);z-index:3;font-weight:600;transition:all 0.3s ease;}
.labor-card .card:hover #verification{box-shadow:0 4px 12px rgba(76,175,80,0.4);transform:translateX(-3px);}
#workers-container .row{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;padding:0;}
#workers-container .col{padding:0;}
#workers-container .row.single-item{grid-template-columns:1fr;max-width:600px;margin:0 auto;}
.labor-card .card-link{text-decoration:none;color:inherit;display:block;}
.worker-card.pre-animate{opacity:0;transform:translateY(20px);transition:opacity .4s ease,transform .4s ease;}
.worker-card.animate-fadeIn{opacity:1;transform:translateY(0);}
.text-center .btn-primary{padding:12px 32px;border-radius:8px;font-weight:600;background:linear-gradient(135deg,#0891b2 0%,#06b6d4 100%);border:none;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(8,145,178,0.2);}
.text-center .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(8,145,178,0.3);}
@media(max-width:768px){#workers-container .row{grid-template-columns:1fr;gap:12px;}.labor-card .card-img-top{width:120px;height:120px;}.labor-card .card-body{padding:10px;}.labor-card .name-rating h5{font-size:.9rem;}.labor-card #services{font-size:1rem;}}
@media(min-width:1200px){#workers-container .row{grid-template-columns:repeat(2,1fr);gap:20px;}}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.animate-fadeIn{animation:fadeIn .8s ease forwards;}
#barangayAnnouncements h4{font-weight:700;color:#00796b;margin-bottom:30px;text-align:center;}
#barangayAnnouncements h4::after{content:"";display:block;width:60px;height:3px;background:#00796b;margin:10px auto 0;border-radius:2px;}
.announcement-card{border:none;box-shadow:0 4px 15px rgba(0,0,0,.08);border-radius:12px;overflow:hidden;transition:transform .3s ease;}
.announcement-card:hover{transform:translateY(-5px);}
.announcement-card img{border-bottom:2px solid #f0f0f0;}
#howItWorks{padding-top:30px;padding-bottom:100px;}
@media(max-width:768px){.announcement-card{width:100%;}}
body{background:#eeeeee!important;min-height:100vh;}
body>footer,footer.bg-dark{background:#212529!important;}
.container:not(footer .container),.container-fluid:not(footer .container-fluid){background:#eeeeee!important;}
.categories-wrapper{background:#eeeeee!important;}
.categories-wrapper .buttons .button{background:rgba(255,255,255,0.9)!important;backdrop-filter:blur(10px);}
.categories-wrapper .buttons .button:hover,.categories-wrapper .buttons .button.active{background:linear-gradient(135deg,#027d8d,#0293a1)!important;}
.labor-card .card{background:rgba(255,255,255,0.95)!important;backdrop-filter:blur(10px);}
.bg-white:not(footer),.bg-white:not(footer *){background:#eeeeee!important;}
.announcement-card{background:rgba(255,255,255,0.95)!important;backdrop-filter:blur(10px);}
#howItWorks{background:#eeeeee!important;}
#howItWorks .step-card{background:rgba(255,255,255,0.95)!important;backdrop-filter:blur(10px);}
.nav-arrow{background:rgba(255,255,255,0.95)!important;backdrop-filter:blur(10px);}
.card:not(footer .card){background:rgba(255,255,255,0.95)!important;backdrop-filter:blur(10px);}
footer,footer *{background:inherit!important;}
footer.bg-dark{background:#212529!important;}
footer .container{background:transparent!important;}
.no-workers-container{padding:60px 20px!important;animation:fadeInUp .6s ease;}
.no-workers-icon-wrapper{position:relative;width:120px;height:120px;margin:0 auto 25px;}
.search-icon-circle{width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,#e0f7fa,#b2ebf2);display:flex;align-items:center;justify-content:center;animation:pulse 2s ease-in-out infinite;box-shadow:0 8px 20px rgba(2,125,141,0.15);}
.search-icon-circle i{font-size:3.5rem;color:#027d8d;animation:iconRotate 3s ease-in-out infinite;}
.floating-dots{position:absolute;top:0;left:0;width:100%;height:100%;}
.dot{position:absolute;width:12px;height:12px;border-radius:50%;background:#027d8d;opacity:0;animation:floatDot 2s ease-in-out infinite;}
.dot-1{top:10%;left:20%;animation-delay:0s;}
.dot-2{top:70%;right:15%;animation-delay:.7s;}
.dot-3{bottom:15%;left:15%;animation-delay:1.4s;}
.no-workers-title{font-size:1.5rem;font-weight:600;color:#2c3e50;margin-bottom:12px;animation:fadeInUp .8s ease .2s both;}
.no-workers-text{font-size:1rem;color:#6c757d;line-height:1.6;max-width:400px;margin:0 auto;animation:fadeInUp .8s ease .4s both;}
@keyframes pulse{0%,100%{transform:scale(1);box-shadow:0 8px 20px rgba(2,125,141,0.15);}50%{transform:scale(1.05);box-shadow:0 12px 30px rgba(2,125,141,0.25);}}
@keyframes iconRotate{0%,100%{transform:rotate(0deg);}25%{transform:rotate(-10deg);}75%{transform:rotate(10deg);}}
@keyframes floatDot{0%{opacity:0;transform:translateY(0) scale(0);}50%{opacity:1;transform:translateY(-20px) scale(1);}100%{opacity:0;transform:translateY(-40px) scale(0);}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:768px){.no-workers-icon-wrapper{width:100px;height:100px;}.search-icon-circle{width:100px;height:100px;}.search-icon-circle i{font-size:3rem;}.no-workers-title{font-size:1.3rem;}.no-workers-text{font-size:0.95rem;}}
</style>

</head>
<body>

<!-- CAROUSEL -->
<div class="container-fluid p-0">
    <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="image/bg2.png" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h1 class="display-5 text-white animate-text">Welcome to Servify</h1>
                    <p class="text-white animate-text">Connecting you with the right laborer, creating opportunities and maximizing potential earnings.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="image/electrician.jpg" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h5 class="text-white">Find Electricians</h5>
                    <p class="text-white">Get electrical services for your needs.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="image/plumber.png" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h5 class="text-white">Hire Plumbers</h5>
                    <p class="text-white">Reliable plumbing solutions for your home and business.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="image/catering.jpeg" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h5 class="text-white">Book Caterers</h5>
                    <p class="text-white">Delicious catering services for all your events.</p>
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
<div class="container text-center mt-5" id="worker-header">
  <h4 class="fw-bold">Browse Categories</h4>
  <p class="text-muted">Find the right laborer for your needs</p>
</div>
<div class="categories-wrapper d-flex align-items-center justify-content-center">
  <button id="prev-btn" class="nav-arrow"><i class="bi bi-chevron-left"></i></button>
  <div class="buttons-container flex-grow-1">
    <div class="buttons" id="category-buttons"></div>
  </div>
  <button id="next-btn" class="nav-arrow"><i class="bi bi-chevron-right"></i></button>
</div>

<!-- Laborer's Section -->
<div id="worker-containers" class="container text-center mt-5">
  <h4 class="fw-bold">Search for Laborers</h4>
  <p class="text-muted">Connect with skilled laborers in your area</p>
</div>

<div class="text-center mt-3">
  <a href="view/browse.php" class="btn btn-primary" id="browseBtn">Browse More</a>
</div>

<!-- Existing Laborers Container -->
<div id="workers-container" class="container p-4"></div>

<!-- Workers pagination -->
<nav class="d-flex justify-content-center">
  <ul id="workers-pagination" class="pagination" style="display:none;"></ul>
</nav>

<!-- Barangay Announcements Display -->
<div class="bg-white p-4 rounded-2xl mb-10" style="margin-top: 50px;">
  <div class="announcement-header">
    <h4 class="announcement-title">🚨 Barangay Announcements 🚨</h4>
  </div>
  <?php
  $ann_query = "SELECT * FROM barangay_announcements ORDER BY date_posted DESC";
  $ann_result = $conn->query($ann_query);
  if ($ann_result && $ann_result->num_rows > 0):
      while ($ann = $ann_result->fetch_assoc()):
  ?>
    <div class="mx-auto mb-5" style="max-width: 980px;">
      <div class="card border-0 shadow-sm overflow-hidden">
        <?php if (!empty($ann['image_path'])): ?>
  <img src="<?php echo htmlspecialchars($ann['image_path']); ?>" alt="Announcement Image" class="announcement-image">
<?php endif; ?>
        <div class="card-body text-center">
          <h4 class="card-title fw-semibold mb-2"><?php echo htmlspecialchars($ann['title']); ?></h4>
          <p class="card-text text-muted mb-3"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
          <p class="small text-secondary mb-0">📅 Posted on <?php echo date('F j, Y, g:i A', strtotime($ann['date_posted'])); ?></p>
        </div>
      </div>
    </div>
    <?php endwhile; else: echo '<p class="text-center text-muted">No announcements available at the moment.</p>'; endif; ?>
</div>

<!-- HOW IT WORKS SECTION -->
<section id="howItWorks">
  <div class="container text-center">
    <h4 class="fw-bold">How It Works</h4>
    <p class="text-muted mb-5">Connecting you with reliable laborers in just a few simple steps.</p>
    <div class="row g-4 justify-content-center">
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="100">
          <div class="icon mb-3">
            <i class="bi bi-person-plus-fill fs-1 text-primary"></i>
          </div>
          <h5 class="fw-semibold">1. Create an Account</h5>
          <p class="text-muted small">Sign up easily as a user or laborer and start connecting today.</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="200">
          <div class="icon mb-3">
            <i class="bi bi-search fs-1 text-success"></i>
          </div>
          <h5 class="fw-semibold">2. Find a Laborer</h5>
          <p class="text-muted small">Browse skilled workers by category or location in just a few clicks.</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="300">
          <div class="icon mb-3">
            <i class="bi bi-hand-thumbs-up-fill fs-1 text-warning"></i>
          </div>
          <h5 class="fw-semibold">3. Hire & Transact</h5>
          <p class="text-muted small">Connect directly, agree on terms, and complete your task smoothly.</p>
        </div>
      </div>
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
    <p>Servify is a community-based platform that connects residents with local workers for various services. We provide the digital space, while Barangay Staff and Administrators help oversee the smooth operation of the platform.</p>
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
    <p>Servify collects only the necessary information to operate the platform, such as account details and contact information. Your data will not be shared without consent, except when required by law or for community safety.</p>
    <div style="margin-top: 20px; display: flex; align-items: center; gap: 10px;">
      <input type="checkbox" id="acceptCheckbox">
      <label for="acceptCheckbox" style="margin: 0;">I accept and agree to the Terms of Use and Privacy Policy of Servify.</label>
    </div>
    <button id="agreeBtn" class="btn accept-btn" disabled>Accept & Continue</button>
  </div>
</div>

<script>
// ================== DISCLAIMER MODAL ==================
document.addEventListener("DOMContentLoaded", function () {
  const checkbox = document.getElementById("acceptCheckbox");
  const agreeBtn = document.getElementById("agreeBtn");
  const modal = document.getElementById("disclaimerModal");
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

// ================== CATEGORIES ==================
document.addEventListener("DOMContentLoaded", function () {
  const categoriesContainer = document.getElementById("category-buttons");
  const prevBtn = document.getElementById("prev-btn");
  const nextBtn = document.getElementById("next-btn");
  let allCategories = [];
  let currentCategoryPage = 1;
  const categoriesPerPage = 6;
  let totalCategoryPages = 1;

  function loadAllCategories() {
    fetch("view/fetch_categories.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `page=1&limit=1000`,
    })
      .then((res) => res.json())
      .then((data) => {
        allCategories = data.categories;
        totalCategoryPages = Math.ceil(allCategories.length / categoriesPerPage);
        displayCategoriesPage(1);
        updateCategoryArrows();
      })
      .catch((err) => console.error("Error loading categories:", err));
  }

  function displayCategoriesPage(page) {
    const startIndex = (page - 1) * categoriesPerPage;
    const endIndex = startIndex + categoriesPerPage;
    const categoriesToShow = allCategories.slice(startIndex, endIndex);
    categoriesContainer.innerHTML = categoriesToShow
      .map(
        (cat) => `
        <button class="button" data-job-id="${cat.id}">
          <span class="d-block fs-3">${cat.emoji}</span>
          <span>${cat.name}</span>
        </button>
      `
      )
      .join("");
    currentCategoryPage = page;
    updateCategoryArrows();
    attachCategoryClick();
  }

  function updateCategoryArrows() {
    prevBtn.disabled = currentCategoryPage <= 1;
    nextBtn.disabled = currentCategoryPage >= totalCategoryPages;
  }

  prevBtn.addEventListener("click", () => {
    if (currentCategoryPage > 1) {
      displayCategoriesPage(currentCategoryPage - 1);
    }
  });

  nextBtn.addEventListener("click", () => {
    if (currentCategoryPage < totalCategoryPages) {
      displayCategoriesPage(currentCategoryPage + 1);
    }
  });

  loadAllCategories();
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
    workersContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    fetch("view/fetch_workers.php", {
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
          
          // Check if only one worker exists and center it
          const workerRows = workersContainer.querySelectorAll('.row');
          workerRows.forEach(row => {
            const cards = row.querySelectorAll('.labor-card');
            if (cards.length === 1) {
              row.classList.add('single-item');
            }
          });
          
          const workerCards = workersContainer.querySelectorAll(".worker-card");
          workerCards.forEach((card, index) => {
            setTimeout(() => {
              card.classList.add("animate-fadeIn");
              card.classList.remove("pre-animate");
            }, index * 100);
          });
          if (job_id === "all") shuffleElements(workersContainer);
          setupWorkersPagination(parsed.total_pages || 1, parsed.current_page || 1);
        } else {
          workersContainer.innerHTML = text;
          
          // Check if only one worker exists and center it
          const workerRows = workersContainer.querySelectorAll('.row');
          workerRows.forEach(row => {
            const cards = row.querySelectorAll('.labor-card');
            if (cards.length === 1) {
              row.classList.add('single-item');
            }
          });
          
          if (job_id === "all") shuffleElements(workersContainer);
          workersPagination.style.display = "none";
        }
      })
      .catch((error) => {
        console.error("Error fetching workers:", error);
        workersContainer.innerHTML = '<div class="text-center py-5"><p class="text-danger">Error loading workers. Please try again.</p></div>';
      });
  }

  function setupWorkersPagination(totalPages, activePage) {
    workersPagination.innerHTML = "";
    workersPagination.style.display = totalPages > 1 ? "" : "none";
    const makePageItem = (label, page, isActive, isDisabled) => {
      const li = document.createElement("li");
      li.className = "page-item" + (isActive ? " active" : "") + (isDisabled ? " disabled" : "");
      li.innerHTML = `<a class="page-link" href="#" data-page="${page}">${label}</a>`;
      return li;
    };
    workersPagination.appendChild(makePageItem("Prev", Math.max(1, activePage - 1), false, activePage <= 1));
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
      workersPagination.appendChild(makePageItem(p, p, p === activePage, false));
    }
    if (end < totalPages) {
      if (end < totalPages - 1) {
        const dots = document.createElement("li");
        dots.className = "page-item disabled";
        dots.innerHTML = `<span class="page-link">…</span>`;
        workersPagination.appendChild(dots);
      }
      workersPagination.appendChild(makePageItem(totalPages, totalPages, false, false));
    }
    workersPagination.appendChild(makePageItem("Next", Math.min(totalPages, activePage + 1), false, activePage >= totalPages));
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

  window.attachCategoryClick = function () {
    const categoryButtons = document.querySelectorAll(".button");
    const workersContainer = document.getElementById("workers-container");
    const workerContainersSection = document.getElementById("worker-containers");
    categoryButtons.forEach((btn) => {
      btn.addEventListener("click", function () {
        categoryButtons.forEach((b) => b.classList.remove("active"));
        this.classList.add("active");
        const job_id = this.getAttribute("data-job-id");
        currentJobId = job_id;
        currentPage = 1;
        fetchLaborers(job_id, currentPage);
        const browseBtn = document.getElementById("browseBtn");
        if (browseBtn) {
          browseBtn.href = `view/browse.php?jobs[]=${job_id}`;
        }
        setTimeout(() => {
          const yOffset = -80;
          const element = workerContainersSection;
          const y = element.getBoundingClientRect().top + window.pageYOffset + yOffset;
          window.scrollTo({ top: y, behavior: "smooth" });
        }, 300);
      });
    });
  };

  fetchLaborers(null, 1);
});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'view/footer.php'; ?>
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 1500,
    once: true
  });
</script>   
</body>
</html>