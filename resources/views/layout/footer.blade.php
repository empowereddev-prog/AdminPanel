   <!-- Footer Section Start -->
   <footer>
        <div class="container">
          <div class="footer-wrapper">
            <div class="column column1">
              <div class="contents">
                <h4>{{__('messages.footer_legal') }}</h4>
                <ul>
                  <!-- <li><a href="#faqs">{{__('messages.footer_faq') }}</a></li> -->
                  <li><a href="{{ route('terms',['language'=>$language])}}">{{__('messages.footer_t&c') }}</a></li>
                  <li><a href="{{ route('privacy-policy',['language'=>$language]) }}">{{__('messages.footer_privacy_policy') }}</a></li>
                </ul>
              </div>
            </div>
            <div class="column column2">
              <div class="contents">
                <h4>{{__('messages.footer_address') }}</h4>
                <ul>
                  <li>
                  <!-- {{__('messages.footer_address_desc') }}<br />
                  {{__('messages.footer_country') }} -->
                 {{$address['value'] }}
                   </li>
                  <li>
                    <a href="mailto:aenote@gmail.com"
                      ><img
                        src="assets/images/envelope.png"
                        alt=""
                      />{{$email['value'] }}</a
                    >
                  </li>
                </ul>
              </div>
            </div>
            <div class="column column3">
              <div class="contents">
                <div class="logo">
                  <img src="assets/images/em.jpg" alt="AE Logo" />
                </div>
                <p class="slogan">{{__('messages.footer_slogan') }}</p>
              </div>
            </div>
          </div>
          <hr class="seperator" />
          <div class="social">
            <div class="content-wrapper">
              <div class="left-panel">
                <ul>
                  <li><a href="https://linkedin.com/in/jinquan-guo-6b99b02bb" title="LinkedIn" target="_blank">
                    <img src="assets/images/linkedin.svg" title="LinkedIn">
                  </a></li>
                  <li><a href="https://instagram.com/aedison_tech/"  title="Instagram" target="_blank">
                    <img src="assets/images/insta.svg" title="Instagram">
                  </a></li>
                  <!--<li><a href="#"  title="Facebook">-->
                  <!--<img src="assets/images/fb.svg" title="Facebook">-->
                  <!--</a></li>-->
                </ul>
              </div>
              <div class="right-panel">
                <p>{{__('messages.footer_copyright') }}</p>
              </div>
            </div>
          </div>
        </div>
      </footer>
      <!-- Footer Section End -->
