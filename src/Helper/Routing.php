<?php

use Velto\Core\App\RedirectResponse;

if (!function_exists('redirect_response')) {
    function redirect_response(string $to = '/'): RedirectResponse
    {
        return new RedirectResponse($to);
    }
}

if (!function_exists('to_route_response')) {
    function to_route_response(string $name, array $params = []): RedirectResponse
    {
        return new RedirectResponse(route($name, $params));
    }
}
