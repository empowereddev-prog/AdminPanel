<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EmpowerEd Child Healthcare</title>
    <link rel="shortcut icon" href="{{ url('assets/images/new.png') }}" type="image/x-icon">

    <!-- Swiper CSS CDN -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    />
    <!-- AOS CSS CDN -->
    <link
      href="
    https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.min.css
    "
      rel="stylesheet"
    />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/web.css" />
  </head>
  <body>
    <div class="wrapper">
      <!-- Navbar -->
      <header class="header sticky" id="header">
        
      </header>

<!-- Terms & Conditions Section Start -->
<section class="page help-support spacer-bottom" id="help-support">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <input type="hidden" value="{{$language}}"/>
                @if (!empty($data) && !empty($data->content))
                            <p>{!! $data->content !!}</p>
                        @else
                            <h6 style="text-align: center;">No data found.</h6>
                        @endif
            </div>
        </div>
    </div>
</section>
<!-- Terms & Conditions Section End -->
<!-- Footer Section Start -->
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- AOS CDN -->
    <script src="
            https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.min.js
            "></script>
    <!-- Custom Script -->
    <script src="assets/js/script.js" defer></script>
    <script>
      AOS.init({ disable: "mobile" });
    </script>
    <script>
   
    document.getElementById('language-switcher').addEventListener('change', function () {
                var selectedLanguage = this.value;
                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('language', selectedLanguage); // Set or update the language parameter
                window.location.href = currentUrl.toString(); // Redirect to the updated URL
            });

            
   </script>
  </body>
 </html>