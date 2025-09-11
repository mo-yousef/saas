jQuery(document).ready(function ($) {
  "use strict";

  // User Menu Toggle
  const userMenu = $(".user-menu");
  const userMenuToggle = $(".user-menu-toggle");
  const userDropdown = $(".user-dropdown-menu");

  userMenuToggle.on("click", function (e) {
    e.stopPropagation();
    userMenu.toggleClass("active");
  });

  $(document).on("click", function (e) {
    if (
      userMenu.hasClass("active") &&
      !$(e.target).closest(".user-menu").length
    ) {
      userMenu.removeClass("active");
    }
  });

  // Live Search
  const searchInput = $("#NORDBOOKING-live-search");
  const searchResults = $("#NORDBOOKING-search-results");
  const searchResultsList = $("#search-results-list");
  let searchTimeout;

  searchInput.on("focus", function () {
    searchResults.addClass("open");
  });

  searchInput.on("blur", function () {
    // Delay hiding to allow click on results
    setTimeout(function () {
      searchResults.removeClass("open");
    }, 200);
  });

  searchInput.on("keyup", function () {
    clearTimeout(searchTimeout);
    const query = $(this).val();

    if (query.length < 2) {
      searchResultsList.html("");
      return;
    }

    searchTimeout = setTimeout(function () {
      searchResultsList.html("<p>Searching...</p>");

      $.ajax({
        url: nordbooking_dashboard_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_dashboard_live_search",
          nonce: nordbooking_dashboard_params.nonce,
          query: query,
        },
        success: function (response) {
          if (response.success) {
            let html = "";
            if (response.data.results.length) {
              response.data.results.forEach(function (item) {
                html += `<a href="${item.url}" class="search-result-item">
                                            <span class="result-type">${item.type}</span>
                                            <span class="result-title">${item.title}</span>
                                         </a>`;
              });
            } else {
              html = '<p class="no-results">No results found.</p>';
            }
            searchResultsList.html(html);
          } else {
            searchResultsList.html('<p class="no-results">Search failed.</p>');
          }
        },
      });
    }, 300);
  });

  // Mobile Sidebar Toggle
  const mobileNavToggle = $("#NORDBOOKING-mobile-nav-toggle");
  const sidebar = $(".nordbooking-dashboard-sidebar");
  const body = $("body");

  mobileNavToggle.on("click", function (e) {
    e.stopPropagation();
    sidebar.toggleClass("open");
    body.toggleClass("NORDBOOKING-sidebar-open");
  });

  $(document).on("click", function (e) {
    if (
      sidebar.hasClass("open") &&
      !$(e.target).closest(".nordbooking-dashboard-sidebar").length
    ) {
      sidebar.removeClass("open");
      body.removeClass("NORDBOOKING-sidebar-open");
    }
  });
});
