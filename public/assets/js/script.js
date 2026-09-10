window.addEventListener("DOMContentLoaded", () => {
  // Tab Script
  const tabs = document.querySelectorAll("#tabbings button"),
    contents = document.querySelectorAll(".content");
  tabs.forEach((tab, index) => {
    tab.addEventListener("click", function () {
      let active = document.querySelector(".active");
      active.className = active.className.replace("active", "");
      tab.className = "active";
      let open = document.querySelector(".open");
      open.className = open.className.replace("open", "");
      contents[index].classList.add("open");
    });
  });
});

// Responsive Menu Script
$("#toggle-navbar").on("click", function () {
  $(this).toggleClass("active");
  $("#navigations").toggleClass("active");
});

$(".nav-items").on("click", function(){
  $("#navigations").removeClass("active");
});

// Accordian Script
$(document).ready(function () {
  $(".accordian-header").click(function (event) {
    event.stopPropagation();
    $(this)
      .toggleClass("active")
      .next(".accordian-contents")
      .slideToggle()
      .parent()
      .siblings()
      .find(".accordian-contents")
      .slideUp()
      .prev()
      .removeClass("active");
  });
});

//   Testimonial Swiper Configs
var swiper = new Swiper("#testimonialSwiper", {
  spaceBetween: 30,
  autoplay: true,
  navigation: {
    nextEl: "#next-testimonial",
    prevEl: "#prev-testimonial",
  },
});

// Contact Form Script
$(document).ready(function () {
  $("#message").on("keyup", function () {
    var length = $(this).val().length;
    if (length >= 500) alert("Maxlength exceed!");
    $("#current-length").text(length);
  });
});
