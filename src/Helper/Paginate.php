<?php

use Velto\Core\Model\PaginatedResult;

function paginate($data): string
{
    if (!$data instanceof PaginatedResult) {
        return '';
    }

    $current = $data->current_page;
    $last = $data->last_page;
    $query = $_GET;
    $baseUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $start = max(1, $current - 2);
    $end = min($last, $current + 2);

    ob_start();
    ?>
    <div class="mt-8">
        <div class="flex justify-center gap-1">
            <?php if ($current > 1): ?>
                <?php $query['page'] = $current - 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                    class="flex text-xs items-center justify-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:border-red-500 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            <?php endif; ?>

            <?php if ($start > 1): ?>
                <?php $query['page'] = 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                    class="px-3 py-1 rounded-lg <?= $current === 1 ? 'border border-red-500' : 'bg-white border border-gray-300 text-gray-700 hover:border-red-500' ?>">
                    1
                </a>
                <?php if ($start > 2): ?>
                    <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php $query['page'] = $i; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                    class="px-3 py-1 rounded-lg <?= $i === $current ? 'border border-red-500' : 'bg-white border border-gray-300 text-gray-700 hover:border-red-500' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $last): ?>
                <?php if ($end < $last - 1): ?>
                    <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>
                <?php $query['page'] = $last; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                    class="px-3 py-1 rounded-lg <?= $last === $current ? 'border border-red-500' : 'bg-white border border-gray-300 text-gray-700 hover:border-red-500' ?>">
                    <?= $last ?>
                </a>
            <?php endif; ?>

            <?php if ($current < $last): ?>
                <?php $query['page'] = $current + 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                    class="flex text-xs items-center justify-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:border-red-500 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
