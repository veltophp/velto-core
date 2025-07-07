<?php

use Velto\Core\View\View;
use Velto\Core\View\ViewResponse;
use Velto\Core\Env\Env;
use Velto\Core\Request\Request;
use Velto\Core\Route\Route;
use Velto\Core\Mail\Mail;
use Velto\Core\Session\Session;
use Modules\Auth\Models\Auth;
use Velto\Core\App\RedirectResponse;
use Velto\Core\Support\Uvid;



// use League\CommonMark\CommonMarkConverter;




// if (!function_exists('markdown')) {
//     function markdown(string $text): string
//     {
//         $converter = new CommonMarkConverter([
//             'html_input' => 'strip',
//             'allow_unsafe_links' => false,
//         ]);

//         return $converter->convert($text)->getContent();
//     }
// }

if (!function_exists('uvid')) {
    function uvid(int $length = 32): string {
        return Uvid::generate($length);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/public/' . ltrim($path, '/');
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = []): ViewResponse
    {
        return View::render($view, $data);
    }
}

if (!function_exists('abort')) {
    function abort(int $code = 500, string $message = 'Server Error'): void
    {
        if (php_sapi_name() === 'cli') {
            echo "❌ {$code} - {$message}\n";
            exit(1);
        }

        http_response_code($code);

        if (Env::isDebug()) {

            $debugView =BASE_PATH . "/resources/views/errors/debug.vel.php";

            if (file_exists($debugView)) {
                include $debugView;
                exit;
            }
        } else {

            $errorView = BASE_PATH . "/resources/views/errors/{$code}.vel.php";

            if (file_exists($errorView)) {
                include $errorView;
            } else {
                echo "<h1>$code | Server Error</h1>";
                echo "<p>$message</p>";
            }
            exit;
        }
    }

}

if (!function_exists('dd')) {
    function dd(...$vars): never
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        
        echo '<div style="
            background: #1a1a1a;
            color: #e2e2e2;
            padding: 25px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            margin: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 999999;
            overflow-y: auto;
        ">';
        
        echo '<div style="
            max-width: 1200px;
            margin: 0 auto;
        ">';
        
        // Header
        echo '<div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        ">';
        echo '<div style="display: flex; align-items: center;">';
        echo '<svg style="width: 24px; height: 24px; margin-right: 10px; color: #fb503b;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>';
        echo '<h1 style="
            color: #fb503b;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        ">Debug</h1>';
        echo '</div>';
        echo '<div style="color: #888; font-size: 14px;">VeltoPHP V.2</div>';
        echo '</div>';
        
        // Variables
        foreach ($vars as $i => $var) {
            $varId = 'var-' . uniqid();
            echo '<div style="margin-bottom: 30px;">';
            echo '<div style="
                color: #b3b3b3;
                font-size: 14px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                cursor: pointer;
                user-select: none;
            " onclick="toggleVar(\''.$varId.'\', this)">
                <svg id="icon-'.$varId.'" style="width: 16px; height: 16px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                Variable #' . ($i + 1) . ' (' . gettype($var) . ')
            </div>';
            
            echo '<div id="'.$varId.'" style="display: block;">';
            echo formatVariable($var, 0);
            echo '</div>';
            echo '</div>';
        }
        
        // Footer
        echo '<div style="
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #333;
            color: #888;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
        ">';
        echo '<div>';
        echo '<span style="color: #fb503b;">Script terminated</span> by dd() call';
        echo '</div>';
        echo '<div>';
        echo 'Called from: ' . $backtrace['file'] . ' on line ' . $backtrace['line'];
        echo '</div>';
        echo '</div>';
        
        // JavaScript for toggling
        echo '<script>
            function toggleVar(id, element) {
                var target = document.getElementById(id);
                var icon = document.getElementById("icon-"+id);
                
                if (target.style.display === "none") {
                    target.style.display = "block";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>\';
                    if (element.textContent.includes("Server Data")) {
                        element.textContent = element.textContent.replace("(Click to show)", "(Click to hide)");
                    }
                } else {
                    target.style.display = "none";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>\';
                    if (element.textContent.includes("Server Data")) {
                        element.textContent = element.textContent.replace("(Click to hide)", "(Click to show)");
                    }
                }
            }
            
            function toggleNested(id, element) {
                var target = document.getElementById(id);
                var icon = element.querySelector(\'svg\');
                
                if (target.style.display === "none") {
                    target.style.display = "block";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>\';
                } else {
                    target.style.display = "none";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>\';
                }
            }
        </script>';
        
        echo '</div>'; // Close max-width container
        echo '</div>'; // Close main container
        die();
    }
    
    function formatVariable($var, $depth = 0) {
        $output = '';
        $type = gettype($var);
        $id = 'nested-' . uniqid();
        
        switch ($type) {
            case 'array':
                $count = count($var);
                $output .= '<div style="margin-left: ' . ($depth * 20) . 'px;">';
                $output .= '<div style="display: flex; align-items: center; cursor: pointer; user-select: none;" onclick="toggleNested(\''.$id.'\', this)">';
                $output .= '<svg style="width: 14px; height: 14px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>';
                $output .= 'array:'.$count.' [' . ($count > 0 ? '…' : '') . ']';
                $output .= '</div>';
                
                $output .= '<div id="'.$id.'" style="display: ' . ($depth < 2 ? 'block' : 'none') . '; margin-left: 20px;">';
                foreach ($var as $key => $value) {
                    $output .= '<div style="margin-bottom: 5px;">';
                    $output .= '<span style="color: #888;">' . htmlspecialchars($key) . '</span> => ';
                    $output .= formatVariable($value, $depth + 1);
                    $output .= '</div>';
                }
                $output .= '</div>';
                $output .= '</div>';
                break;
                
            case 'object':
                $className = get_class($var);
                $output .= '<div style="margin-left: ' . ($depth * 20) . 'px;">';
                $output .= '<div style="display: flex; align-items: center; cursor: pointer; user-select: none;" onclick="toggleNested(\''.$id.'\', this)">';
                $output .= '<svg style="width: 14px; height: 14px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>';
                $output .= 'object:'.$className;
                $output .= '</div>';
                
                $output .= '<div id="'.$id.'" style="display: ' . ($depth < 2 ? 'block' : 'none') . '; margin-left: 20px;">';
                
                if ($var instanceof \Traversable || $var instanceof \stdClass) {
                    foreach ($var as $key => $value) {
                        $output .= '<div style="margin-bottom: 5px;">';
                        $output .= '<span style="color: #888;">' . htmlspecialchars($key) . '</span> => ';
                        $output .= formatVariable($value, $depth + 1);
                        $output .= '</div>';
                    }
                } else {
                    $reflect = new ReflectionObject($var);
                    $props = $reflect->getProperties();
                    
                    foreach ($props as $prop) {
                        $prop->setAccessible(true);
                        $output .= '<div style="margin-bottom: 5px;">';
                        $output .= '<span style="color: #888;">' . $prop->getName() . '</span> => ';
                        $output .= formatVariable($prop->getValue($var), $depth + 1);
                        $output .= '</div>';
                    }
                }
                
                $output .= '</div>';
                $output .= '</div>';
                break;
                
            case 'string':
                $output .= '<span style="color: #4fd1c5;">\'' . htmlspecialchars($var) . '\'</span>';
                $output .= ' <span style="color: #888;">(length=' . strlen($var) . ')</span>';
                break;
                
            case 'integer':
            case 'double':
                $output .= '<span style="color: #f6ad55;">' . $var . '</span>';
                break;
                
            case 'boolean':
                $output .= '<span style="color: #68d391;">' . ($var ? 'true' : 'false') . '</span>';
                break;
                
            case 'NULL':
                $output .= '<span style="color: #a0aec0;">null</span>';
                break;
                
            case 'resource':
                $output .= '<span style="color: #f687b3;">resource</span> of type (' . get_resource_type($var) . ')';
                break;
                
            default:
                $output .= '<span>' . $type . '</span>';
        }
        
        return $output;
    }
}

if (!function_exists('dump')) {
    function dump(...$vars): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        
        echo '<div style="
            background: whte;
            color:rgb(47, 37, 37);
            padding: 25px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            margin: 20px;
            border-radius: 5px;
            font-size: 14px;
            line-height: 1.5;
            position: relative;
            z-index: 99999;
        ">';
        
        echo '<div style="
            max-width: 1200px;
            margin: 0 auto;
        ">';
        
        // Header
        echo '<div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        ">';
        echo '<div style="display: flex; align-items: center;">';
        echo '<svg style="width: 24px; height: 24px; margin-right: 10px; color: #fb503b;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>';
        echo '<h1 style="
            color: #fb503b;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        ">Debug</h1>';
        echo '</div>';
        echo '<div style="color: #888; font-size: 14px;">VeltoPHP V.2</div>';
        echo '</div>';
        
        // Variables
        foreach ($vars as $i => $var) {
            $varId = 'dump-var-' . uniqid();
            echo '<div style="margin-bottom: 30px;">';
            echo '<div style="
                color: #b3b3b3;
                font-size: 14px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                cursor: pointer;
                user-select: none;
            " onclick="toggleDumpVar(\''.$varId.'\', this)">
                <svg id="dump-icon-'.$varId.'" style="width: 16px; height: 16px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                Variable #' . ($i + 1) . ' (' . gettype($var) . ')
            </div>';
            
            echo '<div id="'.$varId.'" style="display: block;">';
            echo formatDumpVariable($var, 0);
            echo '</div>';
            echo '</div>';
        }
                
        // Footer
        echo '<div style="
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #333;
            color: #888;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
        ">';
        echo '<div>';
        echo 'Dump output (script continues)';
        echo '</div>';
        echo '<div>';
        echo 'Called from: ' . $backtrace['file'] . ' on line ' . $backtrace['line'];
        echo '</div>';
        echo '</div>';
        
        // JavaScript for toggling
        echo '<script>
            function toggleDumpVar(id, element) {
                var target = document.getElementById(id);
                var icon = document.getElementById("dump-icon-"+id);
                
                if (target.style.display === "none") {
                    target.style.display = "block";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>\';
                    if (element.textContent.includes("Server Data")) {
                        element.textContent = element.textContent.replace("(Click to show)", "(Click to hide)");
                    }
                } else {
                    target.style.display = "none";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>\';
                    if (element.textContent.includes("Server Data")) {
                        element.textContent = element.textContent.replace("(Click to hide)", "(Click to show)");
                    }
                }
            }
            
            function toggleDumpNested(id, element) {
                var target = document.getElementById(id);
                var icon = element.querySelector(\'svg\');
                
                if (target.style.display === "none") {
                    target.style.display = "block";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>\';
                } else {
                    target.style.display = "none";
                    icon.innerHTML = \'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>\';
                }
            }
        </script>';
        
        echo '</div>'; // Close max-width container
        echo '</div>'; // Close main container
    }
    
    function formatDumpVariable($var, $depth = 0) {
        $output = '';
        $type = gettype($var);
        $id = 'dump-nested-' . uniqid();
        
        switch ($type) {
            case 'array':
                $count = count($var);
                $output .= '<div style="margin-left: ' . ($depth * 20) . 'px;">';
                $output .= '<div style="display: flex; align-items: center; cursor: pointer; user-select: none;" onclick="toggleDumpNested(\''.$id.'\', this)">';
                $output .= '<svg style="width: 14px; height: 14px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>';
                $output .= 'array:'.$count.' [' . ($count > 0 ? '…' : '') . ']';
                $output .= '</div>';
                
                $output .= '<div id="'.$id.'" style="display: ' . ($depth < 2 ? 'block' : 'none') . '; margin-left: 20px;">';
                foreach ($var as $key => $value) {
                    $output .= '<div style="margin-bottom: 5px;">';
                    $output .= '<span style="color: #888;">' . htmlspecialchars($key) . '</span> => ';
                    $output .= formatDumpVariable($value, $depth + 1);
                    $output .= '</div>';
                }
                $output .= '</div>';
                $output .= '</div>';
                break;
                
            case 'object':
                $className = get_class($var);
                $output .= '<div style="margin-left: ' . ($depth * 20) . 'px;">';
                $output .= '<div style="display: flex; align-items: center; cursor: pointer; user-select: none;" onclick="toggleDumpNested(\''.$id.'\', this)">';
                $output .= '<svg style="width: 14px; height: 14px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>';
                $output .= 'object:'.$className;
                $output .= '</div>';
                
                $output .= '<div id="'.$id.'" style="display: ' . ($depth < 2 ? 'block' : 'none') . '; margin-left: 20px;">';
                
                if ($var instanceof \Traversable || $var instanceof \stdClass) {
                    foreach ($var as $key => $value) {
                        $output .= '<div style="margin-bottom: 5px;">';
                        $output .= '<span style="color: #888;">' . htmlspecialchars($key) . '</span> => ';
                        $output .= formatDumpVariable($value, $depth + 1);
                        $output .= '</div>';
                    }
                } else {
                    $reflect = new ReflectionObject($var);
                    $props = $reflect->getProperties();
                    
                    foreach ($props as $prop) {
                        $prop->setAccessible(true);
                        $output .= '<div style="margin-bottom: 5px;">';
                        $output .= '<span style="color: #888;">' . $prop->getName() . '</span> => ';
                        $output .= formatDumpVariable($prop->getValue($var), $depth + 1);
                        $output .= '</div>';
                    }
                }
                
                $output .= '</div>';
                $output .= '</div>';
                break;
                
            case 'string':
                $output .= '<span style="color:rgb(49, 184, 171);">\'' . htmlspecialchars($var) . '\'</span>';
                $output .= ' <span style="color: #888;">(length=' . strlen($var) . ')</span>';
                break;
                
            case 'integer':
            case 'double':
                $output .= '<span style="color: #f6ad55;">' . $var . '</span>';
                break;
                
            case 'boolean':
                $output .= '<span style="color: #68d391;">' . ($var ? 'true' : 'false') . '</span>';
                break;
                
            case 'NULL':
                $output .= '<span style="color: #a0aec0;">null</span>';
                break;
                
            case 'resource':
                $output .= '<span style="color: #f687b3;">resource</span> of type (' . get_resource_type($var) . ')';
                break;
                
            default:
                $output .= '<span>' . $type . '</span>';
        }
        
        return $output;
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        static $request;

        if (!$request) {
            $request = new Request();
        }

        return $request;
    }
}

if (!function_exists('validate')) {
    function validate(array $data, array $rules): ?array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $rulesArr = explode('|', $rule);
            $value = $data[$field] ?? null;

            $isNullable = in_array('nullable', $rulesArr, true);

            if (
                $isNullable &&
                is_array($value) &&
                array_key_exists('error', $value) &&
                (int)$value['error'] === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }

            if (
                $isNullable &&
                ($value === null || $value === '' || (is_array($value) && empty($value)))
            ) {
                continue; 
            }

            foreach ($rulesArr as $ruleItem) {
                [$ruleName, $param] = array_pad(explode(':', $ruleItem, 2), 2, null);

                switch ($ruleName) {
                    case 'nullable':

                        break;

                    case 'required':
                        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                            $errors[$field][] = "$field is required.";
                        }
                        break;

                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = "$field must be a string.";
                        }
                        break;

                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field][] = "$field must be an integer.";
                        }
                        break;

                    case 'boolean':
                        if (!is_bool($value) && !in_array($value, ['true', 'false', 0, 1, '0', '1'], true)) {
                            $errors[$field][] = "$field must be a boolean.";
                        }
                        break;

                    case 'array':
                        if (!is_array($value)) {
                            $errors[$field][] = "$field must be an array.";
                        }
                        break;

                    case 'min':
                        if (is_string($value) && strlen($value) < (int)$param) {
                            $errors[$field][] = "$field must be at least $param characters.";
                        }
                        if (is_numeric($value) && $value < (int)$param) {
                            $errors[$field][] = "$field must be at least $param.";
                        }
                        break;

                    case 'max':
                        if (is_string($value) && strlen($value) > (int)$param) {
                            $errors[$field][] = "$field must not exceed $param characters.";
                        }
                        if (is_numeric($value) && $value > (int)$param) {
                            $errors[$field][] = "$field must not exceed $param.";
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "$field must be a valid email address.";
                        }
                        break;

                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field][] = "$field must be numeric.";
                        }
                        break;

                    case 'confirmed':
                        if (($data[$field . '_confirmation'] ?? null) !== $value) {
                            $errors[$field][] = "$field confirmation does not match.";
                        }
                        break;

                    case 'same':
                        if (($data[$param] ?? null) !== $value) {
                            $errors[$field][] = "$field must be the same as $param.";
                        }
                        break;

                    case 'in':
                        $options = explode(',', $param);
                        if (!in_array($value, $options, true)) {
                            $errors[$field][] = "$field must be one of the following values: " . implode(', ', $options) . '.';
                        }
                        break;

                    case 'image':
                        if (
                            !is_array($value)
                            || !isset($value['tmp_name'])
                            || !is_uploaded_file($value['tmp_name'])
                        ) {
                            $errors[$field][] = "$field must be a valid uploaded image file.";
                        } else {
                            $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            $mimeType = mime_content_type($value['tmp_name'] ?? '');
                            if (!in_array($mimeType, $allowedMime)) {
                                $errors[$field][] = "$field must be an image (jpeg, png, gif, webp).";
                            }
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        return !empty($errors) ? $errors : null;
    }
}


if (!function_exists('url')) {
    function url($path = '/')
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        $fullPath = trim($path, '/');
        return rtrim("{$scheme}://{$host}{$basePath}", '/') . '/' . $fullPath;
    }
}

if (!function_exists('redirect')) {

    function redirect(string $to = '/', array $data = [])
    {
        Session::start();

        if (!empty($data)) {
            $_SESSION['_flash'] = $data;
        }

        return new class($to) {
            protected $to;

            public function __construct($to)
            {
                $this->to = $to;
            }

            public function to($to = null)
            {
                if ($to !== null) {
                    $this->to = $to;
                }
                header('Location: ' . $this->to);
                exit;
            }

            public function back()
            {
                $referer = $_SERVER['HTTP_REFERER'] ?? null;

                if (!$referer || filter_var($referer, FILTER_VALIDATE_URL) === false) {
                    $referer = '/';
                }

                header('Location: ' . $referer);
                exit;
            }

            public function route($name, $params = [])
            {
                $this->to = route($name, $params);
                header('Location: ' . $this->to);
                exit;
            }

            public function __destruct()
            {
                header('Location: ' . $this->to);
                exit;
            }
        };
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        $routes = Route::getRouteNames();

        if (!isset($routes[$name])) {
            throw new RuntimeException("Please check your Route or Link. Route with name [$name] not found.");
        }

        $uri = $routes[$name];

        // Cek apakah params associative atau indexed
        if (array_keys($params) === range(0, count($params) - 1)) {
            // Indexed array, ganti parameter berdasarkan urutan placeholder di URI
            preg_match_all('/\{(\w+)\}/', $uri, $matches);
            $placeholders = $matches[1];

            foreach ($params as $index => $value) {
                if (isset($placeholders[$index])) {
                    $uri = str_replace("{" . $placeholders[$index] . "}", urlencode($value), $uri);
                }
            }
        } else {
            // Associative array, ganti berdasarkan key
            foreach ($params as $key => $value) {
                $uri = str_replace("{" . $key . "}", urlencode($value), $uri);
            }
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $baseUrl = $scheme . '://' . $host;

        return rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
    }
}

if (!function_exists('to_route')) {
    function to_route(string $name, array $params = [], array $data = []): void
    {
        Session::start();

        if (!empty($data)) {
            $_SESSION['_flash'] = $data;
        }

        // Gunakan fungsi `route()` kamu yang sudah ada
        $url = route($name, $params);

        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        Session::start(); 

        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(string $token): bool
    {
        Session::start();

        return isset($_SESSION['_token']) && hash_equals($_SESSION['_token'], $token);
    }
}

if (!function_exists('active')) {
    function active(string|array $path, string $activeClass = 'active'): string
    {
        $currentPath = $_SERVER['REQUEST_URI'];

        if (is_array($path)) {
            foreach ($path as $p) {
                if ($currentPath === $p || (trim($p, '/') !== '' && str_starts_with($currentPath, trim($p, '/')))) {
                    return $activeClass;
                }
            }
        } else {
            if ($currentPath === $path || (trim($path, '/') !== '' && str_starts_with($currentPath, trim($path, '/')))) {
                return $activeClass;
            }
        }

        return '';
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash a password using BCRYPT.
     *
     * @param string $value
     * @return string
     */
    function bcrypt(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}

if (!function_exists('hash_check')) {
    /**
     * Verify a plain password against a hash.
     *
     * @param string $plain
     * @param string $hashed
     * @return bool
     */
    function hash_check(string $plain, string $hashed): bool
    {
        return password_verify($plain, $hashed);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('velto_log')) {
    function velto_log(string $message, string $filename = 'velto.log'): void
    {
        $logDir = BASE_PATH . '/storage/log';
        $logFile = $logDir . '/' . $filename;

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $formatted = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

        file_put_contents($logFile, $formatted, FILE_APPEND);
    }
}

if (!function_exists('is_email_verification_enabled')) {
    function is_email_verification_enabled(): bool
    {
        return (bool) Env::get('EMAIL_VERIFICATION');
    }

}

if (!function_exists('send_verification_code')) {
    function send_verification_code(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $email = strtolower($email);
        $code = random_int(10000, 99999);

        Session::set("verification_code.{$email}", $code);
        Session::set("verification_code_time.{$email}", time());

        Mail::send(
            $email,
            'Your Verification Code',
            'verification-code',
            ['code' => $code]
        );
    }
}

if (!function_exists('session')) {

    class SessionHelper
    {
        public function __construct()
        {
            Session::start();
        }

        public function __get(string $key)
        {
            return Session::get($key);
        }

        public function __set(string $key, $value)
        {
            Session::set($key, $value);
        }

        public function has(string $key): bool
        {
            return Session::has($key);
        }

        public function forget(string $key): void
        {
            Session::forget($key);
        }

        public function flush(): void
        {
            Session::flush();
        }

        public function all(): array
        {
            return Session::all();
        }
    }

    function session(): SessionHelper
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new SessionHelper();
        }

        return $instance;
    }
}

if (!function_exists('isMaintenance')) {
    function isMaintenance(): bool
    {
        return file_exists(BASE_PATH . '/.maintenance');
    }
}

if (!function_exists('wysiwyg')) {

    function wysiwyg(string $selector = 'editor', string $textareaName = 'content', ?string $content = '')
    {
        return <<<HTML
        <div class="wysiwyg-editor-wrapper mt-2" data-editor-id="{$selector}">
            <!-- Toolbar with more options -->
            <div class="flex flex-wrap items-center gap-1 bg-gray-100 dark:bg-gray-800 p-2 rounded-t-lg border-b border-gray-300 dark:border-gray-600">
                <!-- Text formatting -->
                <div class="flex items-center gap-1 mr-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="bold" title="Bold (Ctrl+B)">
                        <span class="font-semibold text-xl">B</span>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="italic" title="Italic (Ctrl+I)">
                        <span class="font-semibold text-xl italic">I</span>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="underline" title="Underline (Ctrl+U)">
                        <span class="font-semibold text-xl underline">U</span>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="strikeThrough" title="Strikethrough">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                        </svg>
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-1 bg-gray-100 dark:bg-gray-800 p-2 rounded-t-lg border-b border-gray-300 dark:border-gray-600">
                    <!-- Format selector -->
                    <select class="format-selector bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="p" class="text-base">Paragraph</option>
                        <option value="h1" class="text-4xl font-bold">Heading 1</option>
                        <option value="h2" class="text-3xl font-bold">Heading 2</option>
                        <option value="h3" class="text-2xl font-bold">Heading 3</option>
                        <option value="h4" class="text-xl font-bold">Heading 4</option>
                        <option value="h5" class="text-lg font-bold">Heading 5</option>
                        <option value="h6" class="text-base font-bold">Heading 6</option>
                    </select>
                </div>

                <!-- Text alignment -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="justifyLeft" title="Align left">
                        <i class="fa fa-align-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="justifyCenter" title="Align center">
                        <i class="fa fa-align-center" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="justifyRight" title="Align right">
                        <i class="fa fa-align-right" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Lists and indentation -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="indent" title="Indent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="outdent" title="Outdent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                </div>

                <!-- Links and images -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="insertImage" title="Insert image">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="createLink" title="Insert link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </button>
                </div>

                <!-- Text color and highlight -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <div class="relative group">
                        <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition flex items-center" title="Text color">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </button>
                        <div class="absolute z-10 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded-md p-2 w-40">
                            <div class="grid grid-cols-5 gap-1">
                                <button type="button" class="w-6 h-6 rounded-full bg-black" data-command="foreColor" data-value="black" title="Black"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-white border border-gray-300" data-command="foreColor" data-value="white" title="White"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-red-500" data-command="foreColor" data-value="red" title="Red"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-blue-500" data-command="foreColor" data-value="blue" title="Blue"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-green-500" data-command="foreColor" data-value="green" title="Green"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-yellow-500" data-command="foreColor" data-value="yellow" title="Yellow"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-purple-500" data-command="foreColor" data-value="purple" title="Purple"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-pink-500" data-command="foreColor" data-value="pink" title="Pink"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-orange-500" data-command="foreColor" data-value="orange" title="Orange"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-gray-500" data-command="foreColor" data-value="gray" title="Gray"></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clear formatting -->
                <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition ml-auto" data-command="removeFormat" title="Clear formatting">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <!-- Editable area with placeholder -->
            <div id="{$selector}-editable" contenteditable="true" class="min-h-[20rem] p-4 rounded-b-lg border border-t-0 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-black dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 wysiwyg-content">
                {$content}
            </div>

            <!-- Status bar -->
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex justify-between items-center">
                <div class="editor-status">Velto WYSIWYG basic text editor.</div>
            </div>

            <!-- Hidden textarea to hold result -->
            <textarea id="{$selector}-textarea" name="{$textareaName}" class="hidden">{$content}</textarea>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editor = document.getElementById('{$selector}-editable');
                const textarea = document.getElementById('{$selector}-textarea');
                const wrapper = document.querySelector(`[data-editor-id="{$selector}"]`);
                const statusBar = wrapper.querySelector('.editor-status');
                const formatSelector = wrapper.querySelector('.format-selector');

                // Initialize content
                if (textarea && textarea.value.trim()) {
                    editor.innerHTML = textarea.value;
                } else {
                    editor.innerHTML = '<p><br></p>'; // Ensure empty paragraph for better editing
                }

                // Update textarea on changes
                function updateTextarea() {
                    textarea.value = editor.innerHTML;
                    updateActiveButtons();
                    updateStatus();
                }

                // Update button states
                function updateActiveButtons() {
                    wrapper.querySelectorAll('[data-command]').forEach(button => {
                        const command = button.getAttribute('data-command');
                        const isActive = document.queryCommandState(command);
                        button.classList.toggle('bg-gray-300', isActive);
                        button.classList.toggle('dark:bg-gray-600', isActive);
                    });
                }

                // Format selector change event
                formatSelector.addEventListener('change', function() {
                    const format = formatSelector.value;
                    let className = '';
                    switch (format) {
                        case 'h1':
                            className = 'text-4xl font-bold';
                            break;
                        case 'h2':
                            className = 'text-3xl font-bold';
                            break;
                        case 'h3':
                            className = 'text-2xl font-bold';
                            break;
                        case 'h4':
                            className = 'text-xl font-bold';
                            break;
                        case 'h5':
                            className = 'text-lg font-bold';
                            break;
                        case 'h6':
                            className = 'text-base font-bold';
                            break;
                    }
                    document.execCommand('formatBlock', false, format);
                    const selectedElement = window.getSelection().anchorNode.parentNode;
                    if (selectedElement.tagName.toLowerCase() === format) {
                        selectedElement.className = className;
                    }
                    updateTextarea();
                });

                // Event listeners
                editor.addEventListener('input', updateTextarea);
                editor.addEventListener('keyup', updateTextarea);

                // Paste event
                editor.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const clipboardData = e.clipboardData || window.clipboardData;
                    const htmlData = clipboardData.getData('text/html');

                    if (htmlData) {
                        // Sisipkan HTML langsung jika tersedia
                        document.execCommand('insertHTML', false, htmlData);
                    } else {
                        // Jika tidak ada HTML, sisipkan teks biasa
                        const textData = clipboardData.getData('text/plain');
                        document.execCommand('insertText', false, textData);
                    }
                    updateTextarea();
                });

                // Toolbar button actions
                wrapper.querySelectorAll('[data-command]').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const command = this.getAttribute('data-command');
                        const value = this.getAttribute('data-value');

                        if (command === 'createLink') {
                            const url = prompt('Enter the URL:', 'https://');
                            if (url) document.execCommand(command, false, url);
                        } else if (command === 'insertImage') {
                            const url = prompt('Enter the image URL:', 'https://');
                            if (url) document.execCommand(command, false, url);
                        } else {
                            document.execCommand(command, false, value || null);
                        }

                        editor.focus();
                        updateTextarea();
                    });
                });

                // Keyboard shortcuts
                editor.addEventListener('keydown', function(e) {
                    // Bold - Ctrl+B
                    if (e.ctrlKey && e.key === 'b') {
                        e.preventDefault();
                        document.execCommand('bold', false, null);
                        updateTextarea();
                    }

                    // Italic - Ctrl+I
                    if (e.ctrlKey && e.key === 'i') {
                        e.preventDefault();
                        document.execCommand('italic', false, null);
                        updateTextarea();
                    }

                    // Underline - Ctrl+U
                    if (e.ctrlKey && e.key === 'u') {
                        e.preventDefault();
                        document.execCommand('underline', false, null);
                        updateTextarea();
                    }
                });

                // Initial updates
                updateActiveButtons();
                updateStatus();

                // Prevent default button behavior
                wrapper.querySelectorAll('button').forEach(button => {
                    button.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                    });
                });
            });
        </script>
HTML;
    }
}

if (!class_exists('Auth')) {

    class_alias(Auth::class, 'Auth');
}

if (!function_exists('env')) {
    function env(string $key, $default = false): bool
    {
        $value = Env::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            return in_array($lower, ['true', '1', 'yes', 'on']);
        }

        return (bool) $value;
    }
}

if (!function_exists('flash')) {
    function flash(): object
    {
        Session::start();

        return new class {
            private string $target = '_default';

            public function to(string $formId): self
            {
                $this->target = $formId;
                return $this;
            }

            public function error(string|array $message): void
            {
                foreach ($this->flatten($message) as $msg) {
                    $_SESSION['flash_messages'][$this->target]['error'][] = $msg;
                }
            }

            public function success(string|array $message): void
            {
                foreach ($this->flatten($message) as $msg) {
                    $_SESSION['flash_messages'][$this->target]['success'][] = $msg;
                }
            }

            private function flatten(array|string|null $messages): array
            {
                $result = [];

                if (is_string($messages)) {
                    return [$messages];
                }

                if (is_array($messages)) {
                    foreach ($messages as $item) {
                        if (is_array($item)) {
                            $result = array_merge($result, $this->flatten($item));
                        } elseif (!is_null($item)) {
                            $result[] = (string) $item;
                        }
                    }
                }

                return $result;
            }


            public function get(?string $target = null): array
            {
                return $_SESSION['flash_messages'][$target ?? '_default'] ?? [];
            }

            public function clear(?string $target = null): void
            {
                if ($target) {
                    unset($_SESSION['flash_messages'][$target]);
                } else {
                    unset($_SESSION['flash_messages']);
                }
            }

            public function display(?string $target = null): string
            {
                $messages = $this->get($target);
                if (empty($messages)) return '';

                $output = '<div class="velto-flash-container space-y-2" data-target="'.$target.'">';
                foreach ($messages as $type => $msgs) {
                    foreach ($msgs as $msg) {
                        $bgColor = $type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-blue-100 border-blue-400 text-blue-700';
                        $output .= '<div class="velto-flash-message border px-4 py-3 rounded relative '.$bgColor.'" data-type="'.$type.'">'.$msg.'</div>';
                    }
                }
                $output .= '</div>';

                $output .= '
                <script>
                    (function() {
                        const container = document.querySelector(`[data-target="'.htmlspecialchars($target, ENT_QUOTES).'"]`);
                        if (container) {
                            setTimeout(() => {
                                container.style.transition = "opacity 0.5s";
                                container.style.opacity = "0";
                                setTimeout(() => container.remove(), 500);
                            }, 5000);
                        }
                    })();
                </script>';

                $this->clear($target);
                return $output;
            }
        };
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return rtrim(BASE_PATH . '/public/' . ltrim($path, '/'), '/');
    }
}

if (!function_exists('imageName')) {
    function imageName($file): string
    {
        if (!is_array($file) || empty($file['name'])) {
            return '';
        }

        $original = pathinfo($file['name'], PATHINFO_FILENAME);
        $slug     = preg_replace('/[^a-z0-9]+/i', '_', strtolower($original));
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $date     = date('Ymd_His');

        return "{$slug}_{$date}.{$ext}";
    }
}

if (!function_exists('imageSave')) {
    function imageSave(string $imageName)
    {
        return new class($imageName) {
            protected string $filename;
            protected $file = null;

            public function __construct(string $filename)
            {
                $this->filename = $filename;
            }

            public function from($uploadedFile): static
            {
                $this->file = $uploadedFile;
                return $this;
            }

            public function to(string $relativePath): string
            {
                if ($this->file === null) {
                    throw new \RuntimeException('No uploaded file provided. Call from($file) first.');
                }

                $targetDir = public_path($relativePath);

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                if (is_object($this->file) && method_exists($this->file, 'move')) {
                    $this->file->move($targetDir, $this->filename);

                } else {

                    $tmpPath = $this->file['tmp_name'] ?? null;
                
                    if (!$tmpPath || !is_uploaded_file($tmpPath)) {
                        throw new \RuntimeException('Invalid uploaded file.');
                    }
                
                    move_uploaded_file($tmpPath, "{$targetDir}/{$this->filename}");
                }
                
                // return "{$relativePath}/{$this->filename}";
                return '/' . trim($relativePath, '/') . '/' . $this->filename;

            }
        };
    }
}

if (!function_exists('hasFile')) {
    function hasFile($request, string $key): bool
    {
        $file = method_exists($request, 'file') ? $request->file($key) : null;

        return $file !== null && is_uploaded_file($file['tmp_name'] ?? '');
    }
}

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

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('deleteImage')) {
    function deleteImage(?string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }

        $path = parse_url($imageUrl, PHP_URL_PATH);
        $fullPath = public_path($path);

        if (is_file($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

}


