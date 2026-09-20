<?php

return [

    /*
    |--------------------------------------------------------------------------
    | School Onboarding extras
    |--------------------------------------------------------------------------
    |
    | Gates the admin-panel surfaces that shipped alongside the contracted
    | School Onboarding work but were not part of that scope: the seat and
    | capacity meters, the teacher roster and staff import, the roster
    | operations (resend / revoke / restore / enable-disable), the mood and
    | progress dashboards, and the five school email templates beyond the
    | contracted ones.
    |
    | This hides UI only. Nothing is deleted and no behaviour changes: the
    | roster, seat and notifier services keep running exactly as before, so a
    | school that already has roster enforcement switched on stays enforced.
    | Set SHOW_SCHOOL_EXTRAS=true and run `php artisan config:clear` to bring
    | every one of those surfaces back.
    |
    */

    'school_extras' => env('SHOW_SCHOOL_EXTRAS', false),

];
