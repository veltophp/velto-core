<?php

function getLocalIP() {
    $output = [];
    exec("ipconfig getifaddr en0", $output);
    if (!empty($output[0])) {
        return trim($output[0]);
    }
    exec("ifconfig | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}'", $output);
    return trim($output[0] ?? '127.0.0.1');
}

function isPortUsed($host, $port) {
    $sock = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($sock) {
        fclose($sock);
        return true;
    }
    return false;
}

function showQr($text) {
    if (shell_exec('which qrencode')) {
        echo "\n📸 Scan QR code to open:\n\n";
        system("qrencode -t ANSI256 -l L -v 1 -s 1 '$text'");
        echo "\n\n";
    } else {
        echo "\n⚠️  QR code display not supported (qrencode not found).\n";
        echo "MacOS:  brew install qrencode\n";
        echo "Linux : sudo apt install qrencode\n";
        echo "🔗 Open this manually: $text\n";
    }
}

function getAllViewFiles($dir) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = "$dir/$item";
        if (is_dir($path)) {
            $files = array_merge($files, getAllViewFiles($path));
        } elseif (is_file($path)) {
            $files[] = $path;
        }
    }
    return $files;
}

function monitorViews(string $viewPath, string $reloadFile)
{
    $lastModified = [];

    while (true) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewPath)
        );

        $changed = false;

        foreach ($files as $file) {
            if ($file->isFile()) {
                $path = $file->getRealPath();
                $mod = filemtime($path);

                if (!isset($lastModified[$path]) || $mod > $lastModified[$path]) {
                    $lastModified[$path] = $mod;
                    echo "🔄 File changed: $path\n";
                    $changed = true;
                }
            }
        }

        if ($changed) {
            // "Touch" reload file (update timestamp)
            file_put_contents($reloadFile, time());
        }

        sleep(1); // wait before checking again
    }
}


// ==== CLI Parsing ====
$argv = $_SERVER['argv'] ?? [];
$useLocalIP = in_array('local-ip', $argv);
$enableWatch = in_array('watch', $argv);

$publicPath = realpath(__DIR__ . '/../../public');
$viewPath = realpath(__DIR__ . '/../../views');
$host = $useLocalIP ? getLocalIP() : 'localhost';
$port = $useLocalIP ? 8080 : 8000;

if ($useLocalIP) {
    while (isPortUsed($host, $port)) $port++;
}

$url = "http://$host:$port";
$reloadPath = "$publicPath/.reload";

echo "🔧 Starting Velto development server at $url\n";
echo "📂 Serving from: $publicPath\n";

if ($useLocalIP) {
    echo "\n🌐 Access from other devices at:\n👉 $url\n";
    showQr($url);
}

// Fork: one process for server, one for watch (if enabled)
$pid = pcntl_fork();

if ($pid === -1) {
    die("❌ Fork failed!\n");
} elseif ($pid > 0) {
    // Parent: watcher
    if ($enableWatch) {
        monitorViews($viewPath, $reloadPath);
    } else {
        pcntl_wait($status); // Wait if no watch
    }
} else {
    // Child: run PHP server
    exec("php -S $host:$port -t $publicPath");
}
