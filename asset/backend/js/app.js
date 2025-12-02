(function ($) {
  'use strict';

  $('#search-input').on('input', function () {
    let filter = $(this).val().toLowerCase();
    let hasVisibleItems = false;

    $('#sidebar-menu > li').each(function () {
      if (!$(this).hasClass('no-data')) {
        let text = $(this).text().toLowerCase();
        if (text.includes(filter)) {
          $(this).show();
          hasVisibleItems = true;
        } else {
          $(this).hide();
        }
      }
    });

    if (hasVisibleItems) {
      $('.no-data').hide();
    } else {
      $('.no-data').show();
    }
  });

  // sidebar submenu collapsible js
  $(".sidebar-menu .dropdown > a").on("click", function(){
    var item = $(this).parent(".dropdown");
    item.siblings(".dropdown").children(".sidebar-submenu").slideUp();

    item.siblings(".dropdown").removeClass("dropdown-open");

    item.siblings(".dropdown").removeClass("open");

    item.children(".sidebar-submenu").slideToggle();

    item.toggleClass("dropdown-open");
  });

  // Large screen sidebar collapse js
  $(".sidebar-collapse-btn").on("click", function(){
    $(this).toggleClass("active");
    $(".sidebar").toggleClass("active");
    $(".dashboard-main").toggleClass("active");
  });

  // Mobile sidebar open js
  $(".sidebar-mobile-open-btn").on("click", function(){
    $(".sidebar").addClass("sidebar-open");
  });

  // Mobile sidebar close js
  $(".sidebar-close-btn").on("click", function(){
    $(".sidebar").removeClass("sidebar-open");
  });

  //to keep the current page active
  $(function () {
    var nk = window.location.href;
    $("ul#sidebar-menu a").removeClass("active-page").parent().removeClass("active-page");

    var activeLink = $("ul#sidebar-menu a").filter(function () {
      return nk === this.href; // Check for an exact match first
    }).first();

    if (activeLink.length === 0) { // If no exact match, check with startsWith
      activeLink = $("ul#sidebar-menu a").filter(function () {
        return nk.startsWith(this.href);
      }).first();
    }

    activeLink.addClass("active-page")
        .parent() // go to li
        .addClass("active-page");
    var o = activeLink.parent();
    while (o.is("li")) {
        o = o.parent().addClass("show").parent().addClass("open");
    }
  });

  $(function () {
    var nk = window.location.href;
    $("ul#sidebar-menu a").removeClass("active-page").parent().removeClass("active-page");

    var activeLink = $("ul#sidebar-menu a").filter(function () {
        return nk === this.href; // Check for an exact match first
    }).first();

    if (activeLink.length === 0) { // If no exact match, check with startsWith
        activeLink = $("ul#sidebar-menu a").filter(function () {
            return nk.startsWith(this.href);
        }).first();
    }

    activeLink.addClass("active-page")
        .parent() // go to li
        .addClass("active-page");
    var o = activeLink.parent();
    while (o.is("li")) {
        o = o.parent().addClass("show").parent().addClass("open");
    }
  });

  // Bootstrap tooltip js 
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
  const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

  // Responsive table js 
  $('.responsive-table').basictable({
    breakpoint: 991,
  });


  // referral tree js
  $(".expand-btn").each(function(){
    $(this).on("click", function(){
      $(this).toggleClass("active");
      
      let subReferralTree = $(this).parent(".single-referral").siblings(".sub-referral-tree");

      subReferralTree.slideToggle();
    });
  });

  // input value copy js
  $('.copy-btn').on("click", function() {
    // Find the input element within the same input-group
    var input = $(this).siblings('.form-control');
    
    // Select the input value
    input.select();
    
    // Copy the input value to the clipboard
    document.execCommand('copy');
    
    // Optionally, you can display a message or change the button text
    $(this).text('Copied!');
    
    // Reset the button text after a short delay
    setTimeout(() => {
        $(this).text('Copy');
    }, 2000);

    
});

})(jQuery);