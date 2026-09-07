<!-- <style type="text/css">
    .navbar-nav .nav-item .nav-link[aria-current="page"] {
        color: #000000;
    }
</style> -->
<header class="header" id="header">
        <div class="container">
          <nav class="navbar">
            <a href="/" class="logo">
              <img src="assets/images/em.jpg" alt="AE Notes" />
            </a>

            <ul class="navigations" id="navigations">
              <li class="nav-items">
                <a href="{{route('website',['language' =>$language])}}" class="nav-links"> {{__('messages.header_home') }}</a>
              </li>
              <li class="nav-items">
                <a href="#about" class="nav-links">{{__('messages.header_about') }}</a>
              </li>
              <li class="nav-items">
                <a href="#features" class="nav-links">{{__('messages.header_features') }}</a>
              </li>
              <li class="nav-items">
                <a href="#pricing" class="nav-links">{{__('messages.header_pricing') }}</a>
              </li>
              <li class="nav-items">
                <a href="#contact" class="nav-links">{{__('messages.header_contact_us') }}</a>
              </li>
            </ul>

            <!-- <select class="language-switcher" id="language-switcher">
              <option value="English">English</option>
            </select> -->
            <select class="language-switcher" id="language-switcher">
                <option value="en" {{ session('locale') == 'en' ? 'selected' : '' }}>English</option>
                <option value="zh_CN" {{ session('locale') == 'zh_CN' ? 'selected' : '' }}>简体中文</option>
                <option value="zh_TW" {{ session('locale') == 'zh_TW' ? 'selected' : '' }}>繁體中文</option>
            </select>


            <button class="toggle-navbar" id="toggle-navbar">
              <span class="bar"></span>
              <span class="bar"></span>
              <span class="bar"></span>
            </button>
          </nav>
        </div>
      </header>


<script>

document.getElementById('language-switcher').addEventListener('change', function () {
    var selectedLanguage = this.value;
    var currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('language', selectedLanguage); // Set or update the language parameter
    window.location.href = currentUrl.toString(); // Redirect to the updated URL
});
// document.getElementById('language-switcher').addEventListener('change', function () {
//         var selectedLanguage = this.value;
//         // Redirect to the same page with the selected language as a query parameter
//         window.location.href = window.location.pathname + window.location.search + '?language=' + selectedLanguage;
//         // console.log(selectedLanguage);
//     });
</script>
