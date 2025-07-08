<?php

if (!function_exists('deleteAlert')) {
    function deleteAlert(): string
    {
        return load_alert_view('delete');
    }
}

if (!function_exists('successAlert')) {
    function successAlert(): string
    {
        return load_alert_view('success');
    }
}

if (!function_exists('errorAlert')) {
    function errorAlert(): string
    {
        return load_alert_view('error');
    }
}

if (!function_exists('load_alert_view')) {
    function load_alert_view(string $name): string
    {
        $path = BASE_PATH . "/resources/Views/alerts/{$name}.php";

        if (!file_exists($path)) {
            return "<!-- VeltoAlert: File {$name} tidak ditemukan -->";
        }

        ob_start();
        include $path;
        return ob_get_clean();
    }
}
