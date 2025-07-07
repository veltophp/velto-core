<?php

echo "\n";
echo "\033[36m=============================================\033[0m\n";
echo "\033[32mVeltoPHP V.2 has been installed successfully!\033[0m\n";
echo "\033[36m=============================================\033[0m\n\n";

echo "Welcome to VeltoPHP 2.0 \n";
echo "Let's get you started...\n\n";

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