<?php

echo "\n";
echo "\033[36m=============================================\033[0m\n";
echo "\033[32mVeltoPHP V.2 has been installed successfully!\033[0m\n";
echo "\033[36m=============================================\033[0m\n\n";

echo "Welcome to VeltoPHP 2.0 \n";
echo "Let's get you started...\n\n";

// Copy .env.example to .env if it doesn't already exist
$envExample = __DIR__ . '/.env.example';
$env = __DIR__ . '/.env';

if (!file_exists($env) && file_exists($envExample)) {
    if (copy($envExample, $env)) {
        echo "✅ .env file created from .env.example\n";
    } else {
        echo "❌ Failed to create .env file.\n";
    }
} elseif (file_exists($env)) {
    echo "ℹ️  .env file already exists, skipping copy.\n";
} else {
    echo "❌ .env.example not found, cannot create .env file.\n";
}

$input = readline("Do you want to run database migrations now? (yes/no) [yes]: ");
$input = strtolower(trim($input)) ?: 'yes';

if ($input === 'yes' || $input === 'y') {
    echo "\n Running migrations...\n\n";

    $phpBinary = PHP_BINARY;
    $command = "{$phpBinary} velto migrate";

    // Cross-platform shell execution
    if (stripos(PHP_OS, 'WIN') === 0) {
        $command = "php velto migrate";
    }

    passthru($command, $exitCode);

    if ($exitCode === 0) {
        echo "\n✅ Migrations completed successfully.\n";
    } else {
        echo "\n❌ Something went wrong during migration.\n";
    }
} else {
    echo "\n⚠️  You can run migrations later with: \033[33mphp velto migrate\033[0m\n";
}

echo "\n Go to your project directory and run `php velto start`. \n\n";
echo "\n You're all set. Happy coding with \033[36mVeltoPHP 2.0\033[0m!\n\n";
