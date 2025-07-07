<?php

namespace Velto\Core\Middleware;

use Velto\Core\Request\Request;
use Velto\Core\Session\Session;

class Guest
{

    public static function handle(Request $request, callable $next)
    {

        if (Session::has('user')) {
  
            return to_route('home');
            exit;
        }
        
        return $next($request);
    }
}