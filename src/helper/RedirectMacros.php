<?php

use Velto\Core\App\RedirectResponse;
use Velto\Core\Session\Session;

if (!function_exists('registerVeltoAlertRedirectMacros')) {
    function registerVeltoAlertRedirectMacros(): void
    {
        RedirectResponse::macro('successAlert', function ($message) {
            Session::flash('_success_alert', is_array($message) ? implode(', ', $message) : $message);
            return $this;
        });

        RedirectResponse::macro('errorAlert', function ($message) {
            Session::flash('_error_alert', $message);
            return $this;
        });        
        

        RedirectResponse::macro('infoAlert', function ($message) {
            Session::flash('_info_alert', is_array($message) ? implode(', ', $message) : $message);
            return $this;
        });
    }
}
