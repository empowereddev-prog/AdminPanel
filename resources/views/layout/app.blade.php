<!-- resources/views/main.blade.php -->
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EmpowerEd Child Healthcare</title>
    <link rel="shortcut icon" href="{{ url('assets/images/new.png') }}" type="image/png">

    <!-- Swiper CSS CDN -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    />
    <!-- AOS CSS CDN -->
    <link href="
    https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.min.css
    " rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/web.css" />
  </head>
<body>
  <div class="wrapper">
  @include('layout/header')
    @if(session('success'))
      <div class="alert alert-success" id="success-alert">
      {{ session('success') }}
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger" id="error-alert">
      {{ session('error') }}
      </div>
    @endif
  @yield('content')
  @include('layout/footer')
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- AOS CDN -->
    <script src="
    https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.min.js
    "></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

    <script src="{{ url('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('assets/plugins/simplebar/simplebar.min.js') }}"></script>
    <!-- <script src="{{ url('assets/https://unpkg.com/hotkeys-js/dist/hotkeys.min.js') }}"></script> -->
    <script src="{{ url('assets/plugins/apexcharts/apexcharts.js') }}"></script>
    <script src="{{ url('assets/plugins/DataTables/DataTables-1.10.18/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ url('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.min.js') }}"></script>
    <script src="{{ url('assets/plugins/jvectormap/jquery-jvectormap-world-mill.js') }}"></script>
    <script src="{{ url('assets/plugins/jvectormap/jquery-jvectormap-us-aea.js') }}"></script>
    <!-- Custom Script -->
    <script>
  // Function to initialize tab functionality
  function initTabs() {
    const tabs = document.querySelectorAll("#tabbings .active");
    const contents = document.querySelectorAll(".content");
    const priceElement = document.getElementById('price');
    const taxElement = document.getElementById('tax');
    const totalElement = document.getElementById('total');
    const totalTermsElement = document.getElementById('total-terms');
    const tax = 10.60; // Example tax value
     
    function updatePrice() {
      const selectedLicense = document.querySelector('#tabbings .active').getAttribute('data-license');
      const selectedCycle = document.querySelector('input[name="billing-cycle"]:checked')?.value;

      if (selectedLicense && selectedCycle) {
        const selectedContent = document.querySelector(`.content.open[data-license="${selectedLicense}"]`);
        console.log(selectedContent);
        const monthlyAmount = parseFloat(selectedContent.querySelector('.monthly-amount').getAttribute('data-amount'));
        const annualAmount = parseFloat(selectedContent.querySelector('.annual-amount').getAttribute('data-amount'));

        let price, priceLabel;
        if (selectedCycle === 'monthly') {
          price = monthlyAmount;
          priceLabel = ' /month';
        } else if (selectedCycle === 'annual') {
          price = annualAmount;
          priceLabel = ' /year';
        }

        const total = price + tax;

        priceElement.innerText = `${price.toFixed(2)}${priceLabel}`;
        taxElement.innerText = tax.toFixed(2);
        totalElement.innerText = total.toFixed(2);
        totalTermsElement.innerText = total.toFixed(2);
      }
    }
    // tabs.forEach((tab, index) => {
    //   // console.log(tab,index);
    //   tab.addEventListener("click", () => {
    //     const activeTab = document.querySelector("#tabbings .active");

    //     const openContent = document.querySelector(".content.open");
    //     if (activeTab) activeTab.classList.remove("active");
    //     if (openContent) openContent.classList.remove("open");

    //     tab.classList.add("active");
    //     contents[index].classList.add("open");

    //     updatePrice(); // Update price when tab changes
    //     const selectedPlan = plans[index]; // Assuming plans is an array of plan data
    //     // alert(selectedPlan);
    //     // Redirect to the place-order page with plan data
    //     const queryParams = new URLSearchParams(selectedPlan).toString();
    //     window.location.href = `/place-order?${queryParams}`;
    //   });
    // });
  }

  // Function to initialize price update
  function initPriceUpdate() {
    const priceElement = document.getElementById('price');
    const taxElement = document.getElementById('tax');
    const totalElement = document.getElementById('total');
    const totalTermsElement = document.getElementById('total-terms');
    const tax = 10.60; // Example tax value

    function updatePrice() {
      const selectedLicense = document.querySelector('#tabbings .active').getAttribute('data-license');
      const selectedCycle = document.querySelector('input[name="billing-cycle"]:checked')?.value;

      if (selectedLicense && selectedCycle) {
        const selectedContent = document.querySelector(`.content.open[data-license="${selectedLicense}"]`);
        console.log(selectedContent);
        const monthlyAmount = parseFloat(selectedContent.querySelector('.monthly-amount').getAttribute('data-amount'));
        const annualAmount = parseFloat(selectedContent.querySelector('.annual-amount').getAttribute('data-amount'));

        let price, priceLabel;
        if (selectedCycle === 'monthly') {
          price = monthlyAmount;
          priceLabel = ' /month';
        } else if (selectedCycle === 'annual') {
          price = annualAmount;
          priceLabel = ' /year';
        }

        const total = price + tax;

        priceElement.innerText = `${price.toFixed(2)}${priceLabel}`;
        taxElement.innerText = tax.toFixed(2);
        totalElement.innerText = total.toFixed(2);
        totalTermsElement.innerText = total.toFixed(2);
      }
    }

    // Event listener for license tab buttons
    document.querySelectorAll('#tabbings button').forEach(button => {
      button.addEventListener('click', updatePrice);
    });

    // Event listener for billing cycle radio buttons
    document.querySelectorAll('input[name="billing-cycle"]').forEach(radio => {
      radio.addEventListener('change', updatePrice);
    });

    // Initialize price on page load
    updatePrice();
  }

  // Function to initialize sticky header
  function initStickyHeader() {
    $(window).scroll(function () {
      if ($(this).scrollTop() > 100) {
        $("#header").addClass("sticky");
      } else {
        $("#header").removeClass("sticky");
      }
    });
  }

  // Function to initialize responsive menu
  function initResponsiveMenu() {
    $("#toggle-navbar").on("click", function () {
      $(this).toggleClass("active");
      $("#navigations").toggleClass("active");
    });
  }

  // Function to initialize accordion
  function initAccordion() {
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
  }

  // Function to initialize testimonial swiper
  function initSwiper() {
    new Swiper("#testimonialSwiper", {
      spaceBetween: 30,
      autoplay: true,
      navigation: {
        nextEl: "#next-testimonial",
        prevEl: "#prev-testimonial",
      },
    });
  }

  // Function to initialize contact form
  function initContactForm() {
    $("#message").on("keyup", function () {
      const length = $(this).val().length;
      if (length >= 500) alert("Max length exceeded!");
      $("#current-length").text(length);
    });
  }

  // Initialize all functionalities on DOMContentLoaded
  window.addEventListener("DOMContentLoaded", () => {
    initTabs();
    initPriceUpdate();
    initStickyHeader();
    initResponsiveMenu();
    initAccordion();
    initSwiper();
    initContactForm();
    AOS.init({disable: 'mobile'});
  });
  
</script>

    
</body>
</html>

