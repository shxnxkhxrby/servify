// ================== DISCLAIMER MODAL ==================
document.addEventListener("DOMContentLoaded", function () {
  const checkbox = document.getElementById("acceptCheckbox");
  const agreeBtn = document.getElementById("agreeBtn");
  const modal = document.getElementById("disclaimerModal");

  if (checkbox && agreeBtn && modal) {
    if (!localStorage.getItem("acceptedTerms")) {
      modal.style.display = "flex";
    }

    checkbox.addEventListener("change", function () {
      agreeBtn.disabled = !this.checked;
    });

    agreeBtn.addEventListener("click", function () {
      localStorage.setItem("acceptedTerms", "true");
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
  const categoriesPerPage = 10;
  let totalCategoryPages = 1;

  function loadCategories(page = 1) {
    fetch("fetch_categories.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `page=${page}&limit=${categoriesPerPage}`,
    })
      .then((res) => res.json())
      .then((data) => {
        // 🚫 Removed the "All" button
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

  loadCategories(); // Initial load
});

// ================== WORKERS FETCHING & PAGINATION ==================
document.addEventListener("DOMContentLoaded", function () {
  const filterBySelect = document.getElementById("filter_by_select");
  const sortOrderSelect = document.getElementById("sort_order_select");
  const workersContainer = document.getElementById("workers-container");
  const workersPagination = document.getElementById("workers-pagination");

  let currentJobId = null; // 🚫 default is null (not "all")
  let currentPage = 1;

  function fetchLaborers(job_id = null, page = 1) {
    const filterBy = filterBySelect ? filterBySelect.value : "labor";
    const sortOrder = sortOrderSelect ? sortOrderSelect.value : "ASC";

    const params = new URLSearchParams();
    if (job_id) {
      params.append("job_id", job_id);
    }
    params.append("filter_by", filterBy);
    params.append("sort_order", sortOrder);
    params.append("page", page);

    fetch("fetch_workers.php", {
      method: "POST",
      body: params,
    })
      .then((response) => response.json())
      .then((parsed) => {
        if (parsed && parsed.html !== undefined) {
          workersContainer.innerHTML = parsed.html;
          setupWorkersPagination(parsed.total_pages || 1, parsed.current_page || 1);
        } else {
          workersContainer.innerHTML = "<p>No workers available.</p>";
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

    for (let p = start; p <= end; p++) {
      workersPagination.appendChild(makePageItem(p, p, p === activePage, false));
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

  // Attach worker fetching to category buttons
  window.attachCategoryClick = function () {
    const categoryButtons = document.querySelectorAll(".button");
    categoryButtons.forEach((btn) => {
      btn.addEventListener("click", function () {
        categoryButtons.forEach((b) => b.classList.remove("active"));
        this.classList.add("active");
        currentJobId = this.getAttribute("data-job-id");
        currentPage = 1;
        fetchLaborers(currentJobId, currentPage);
      });
    });
  };

  // ✅ Initial load → 5 random workers (job_id = null)
  fetchLaborers(null, 1);
});
