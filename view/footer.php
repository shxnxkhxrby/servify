<!-- footer.php -->
<footer class="bg-dark text-light pt-5 pb-4 mt-5">
  <div class="container">
    <div class="row">
      
      <!-- Brand / About Section -->
      <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
        <h4 class="mb-3 fw-bold">Servify</h4>
        <p class="text-light opacity-75 pe-lg-3">
          Connecting users with reliable laborers for any task, anytime. Simple, fast, and secure.
        </p>
      </div>
      
      <!-- Quick Links Section -->
      <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
        <h5 class="mb-3 fw-semibold">Quick Links</h5>
        <ul class="list-unstyled">
          <li class="mb-2">
            <a href="../index.php" class="text-light text-decoration-none opacity-75 d-inline-block transition">
              <i class="bi bi-chevron-right small"></i> Home
            </a>
          </li>
          <li class="mb-2">
            <a href="../view/profile.php" class="text-light text-decoration-none opacity-75 d-inline-block transition">
              <i class="bi bi-chevron-right small"></i> Profile
            </a>
          </li>
          <li class="mb-2">
            <a href="../view/contact.php" class="text-light text-decoration-none opacity-75 d-inline-block transition">
              <i class="bi bi-chevron-right small"></i> Contact Us
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Contact Information Section -->
      <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
        <h5 class="mb-3 fw-semibold">Contact Info</h5>
        <ul class="list-unstyled">
          <li class="mb-2 d-flex align-items-start">
            <i class="bi bi-envelope me-2 mt-1 opacity-75"></i>
            <a href="mailto:shanekherby2828@gmail.com" class="text-light text-decoration-none opacity-75">
              shanekherby2828@gmail.com
            </a>
          </li>
          <li class="mb-2 d-flex align-items-start">
            <i class="bi bi-telephone me-2 mt-1 opacity-75"></i>
            <a href="tel:+639654736744" class="text-light text-decoration-none opacity-75">
              +63 965 473 6744
            </a>
          </li>
          <li class="mb-2 d-flex align-items-start">
            <i class="bi bi-geo-alt me-2 mt-1 opacity-75"></i>
            <span class="text-light opacity-75">
              Sta Rita Guiguinto Bulacan
            </span>
          </li>
        </ul>
      </div>
      
      <!-- Social Media Section -->
      <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
        <h5 class="mb-3 fw-semibold">Follow Us</h5>
        <div class="d-flex gap-3">
          <a href="https://www.facebook.com/barangay.starita22" class="text-light d-flex align-items-center justify-content-center social-icon" 
             style="width: 40px; height: 40px; border: 1px solid rgba(255,255,255,0.2); border-radius: 50%; transition: all 0.3s ease;">
            <i class="bi bi-facebook"></i>
          </a>
          <a href="https://www.instagram.com/sta_rita_/" class="text-light d-flex align-items-center justify-content-center social-icon" 
             style="width: 40px; height: 40px; border: 1px solid rgba(255,255,255,0.2); border-radius: 50%; transition: all 0.3s ease;">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="https://www.linkedin.com/in/shane-kherby-sahagun-279ab636a/" class="text-light d-flex align-items-center justify-content-center social-icon" 
             style="width: 40px; height: 40px; border: 1px solid rgba(255,255,255,0.2); border-radius: 50%; transition: all 0.3s ease;">
            <i class="bi bi-linkedin"></i>
          </a>
        </div>
      </div>
      
    </div>
    
    <!-- Divider -->
    <hr class="border-light opacity-25 my-4">
    
    <!-- Copyright Section -->
    <div class="row">
      <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
        <p class="mb-0 opacity-75">
          &copy; <?php echo date("Y"); ?> Servify. All rights reserved.
        </p>
      </div>
    </div>
    
  </div>
</footer>

<style>
  footer a:hover {
    opacity: 1 !important;
    transform: translateX(3px);
  }
  
  footer .social-icon:hover {
    background-color: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.4) !important;
    transform: translateY(-3px) !important;
  }
  
  footer .transition {
    transition: all 0.3s ease;
  }
</style>