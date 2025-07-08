<?php

namespace Velto\Core\View;

class ViewResponse
{
    protected string $view;
    protected array $data = [];

    public function __construct(string $view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function with(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function compact(...$args): self
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $filePath = $trace[0]['file'] ?? null;
        $callLine = $trace[0]['line'] ?? null;

        $sourceCode = $this->readCompactCallSource($filePath, $callLine);
        $varNames = $this->extractVariableNames($sourceCode);

        foreach ($args as $index => $value) {
            $name = $varNames[$index] ?? 'var_' . $index;
            $this->data[$name] = $value;
        }

        return $this;
    }

    protected function readCompactCallSource(?string $filePath, ?int $startLine): string
    {
        if (!$filePath || !$startLine || !is_file($filePath)) {
            return '';
        }

        $lines = file($filePath);
        $code = '';
        $braceCount = 0;

        for ($i = $startLine - 1; $i < count($lines); $i++) {
            $code .= $lines[$i];

            // Hitung tanda kurung untuk mendeteksi akhir dari argumen
            $braceCount += substr_count($lines[$i], '(');
            $braceCount -= substr_count($lines[$i], ')');

            if ($braceCount <= 0 && str_contains($lines[$i], ')')) {
                break;
            }
        }

        // Ambil hanya bagian dalam tanda kurung dari ->compact(...)
        if (preg_match('/->compact\s*\((.*)\)/s', $code, $match)) {
            return $match[1] ?? '';
        }

        return '';
    }

    protected function extractVariableNames(string $argsString): array
    {
        $tokens = token_get_all('<?php ' . $argsString);
        $names = [];

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_VARIABLE) {
                $names[] = ltrim($token[1], '$');
            }
        }

        return $names;
    }


    // public function compact(...$args): self
    // {
    //     // Jika argumen pertama berupa string, kita anggap user salah pakai
    //     if (is_string($args[0] ?? null)) {
    //         http_response_code(500);
    //         throw new \Exception("ViewResponse::compact() only supports passing variables directly, like compact(\$data). Using compact('data') is not supported.");
    //     }

    //     // Ambil nama variabel dari baris pemanggil
    //     $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    //     $line = file($trace[0]['file'])[$trace[0]['line'] - 1];

    //     if (preg_match('/->compact\((.*?)\)/', $line, $matches)) {
    //         $varNames = $this->extractVariableNames($matches[1]);

    //         foreach ($args as $index => $value) {
    //             $name = $varNames[$index] ?? 'var_' . $index;
    //             $this->data[$name] = $value;
    //         }
    //     }

    //     return $this;
    // }

    // protected function extractVariableNames(string $argsString): array
    // {
    //     $tokens = token_get_all('<?php ' . $argsString);
    //     $names = [];
        
    //     foreach ($tokens as $token) {
    //         if (is_array($token) && $token[0] === T_VARIABLE) {
    //             $names[] = substr($token[1], 1);
    //         }
    //     }
        
    //     return $names;
    // }

    public function render(): string
    {
        return View::renderRaw($this->view, $this->data);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}