<?php defined('ABSPATH') || exit; ?>
<section class="individual" aria-labelledby="individual-title" id="individual-subscriptions">
<div class="individual-inner">
<div class="individual-copy">
<span class="eyebrow"><i></i> COMING SOON</span>
<h2 id="individual-title">Individual subscriptions<br><span>Coming soon!</span></h2>
<p class="intro">Want to access EmpowerED independently,<br class="desktop-br"> without a school subscription?</p>
<p class="description">Individual subscriptions are coming soon! Register your interest by joining our waitlist below.</p>
<form class="waitlist" data-registration-open="<?php echo $waitlist_registration_open ? 'true' : 'false'; ?>" data-endpoint="https://admin.empoweredhealth.asia/api/individual-subscription-waitlist"><label class="sr-only" for="waitlist-email">Email address</label><div class="waitlist-honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div><div class="email-wrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/></svg><input id="waitlist-email" type="email" <?php disabled(!$waitlist_registration_open); ?> placeholder="Enter your email address" required maxlength="254" autocomplete="email" aria-describedby="waitlist-status waitlist-privacy"></div><button type="submit" <?php disabled(!$waitlist_registration_open); ?>>Join the Waitlist <span aria-hidden="true">↗</span></button></form>
<p id="waitlist-privacy" class="waitlist-privacy"><?php echo $waitlist_registration_open ? 'We’ll store your email to record your interest in individual subscriptions.' : 'Registration opens soon. Please check back shortly.'; ?> <a href="https://empoweredhealth.asia/privacy-policy/">Privacy Policy</a>.</p><p id="waitlist-status" class="form-status" role="status" aria-live="polite" aria-atomic="true"></p>
<div class="benefits"><div><span>✓</span> Register<br>your interest</div><div><span>☆</span> Be among<br>the first</div><div><span>♡</span> No commitment<br>required</div></div>
</div>
<div class="individual-art"><div class="art-circle"></div><div class="star star-one">✳</div><div class="star star-two">✳</div><img class="teens" src="https://empoweredhealth.asia/wp-content/uploads/2025/09/Group-1261153370.png" alt="Three EmpowerED teens smiling together"><div class="floating-tag tag-top">Expert-led.<br>Teen-approved.</div><div class="floating-tag tag-bottom"><span>♡</span> Your pace.<br>Your space.</div></div>
</div><svg class="wave" viewBox="0 0 1440 65" preserveAspectRatio="none" aria-hidden="true"><path d="M0 24C210-30 375 80 695 38s489-53 745-7v34H0Z" fill="#AADDE2"/></svg>
</section>
