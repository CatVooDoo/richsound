<?php

declare(strict_types=1);

class TestRunner
{
    private int $passed  = 0;
    private int $failed  = 0;
    private int $errors  = 0;
    private string $currentSuite = '';

    // ── ANSI colours ──────────────────────────────────────────────────────────
    private function green(string $s): string { return "\033[32m$s\033[0m"; }
    private function red(string $s): string   { return "\033[31m$s\033[0m"; }
    private function yellow(string $s): string{ return "\033[33m$s\033[0m"; }
    private function bold(string $s): string  { return "\033[1m$s\033[0m"; }

    // ── Suite header ──────────────────────────────────────────────────────────
    public function suite(string $name): void
    {
        $this->currentSuite = $name;
        echo PHP_EOL . $this->bold("  $name") . PHP_EOL;
    }

    // ── Run a single test ─────────────────────────────────────────────────────
    public function test(string $name, callable $fn): void
    {
        try {
            $fn($this);
            $this->passed++;
            echo "    " . $this->green('✓') . " $name" . PHP_EOL;
        } catch (AssertionError $e) {
            $this->failed++;
            echo "    " . $this->red('✗') . " $name" . PHP_EOL;
            echo "      " . $this->red($e->getMessage()) . PHP_EOL;
        } catch (Throwable $e) {
            $this->errors++;
            echo "    " . $this->yellow('!') . " $name — " . $this->yellow(get_class($e) . ': ' . $e->getMessage()) . PHP_EOL;
        }
    }

    // ── Assertions ────────────────────────────────────────────────────────────
    public function assertEqual(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionError($msg ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true));
        }
    }

    public function assertNotEqual(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected === $actual) {
            throw new AssertionError($msg ?: "Expected values to differ, both are " . var_export($actual, true));
        }
    }

    public function assertTrue(mixed $value, string $msg = ''): void
    {
        if ($value !== true) {
            throw new AssertionError($msg ?: "Expected true, got " . var_export($value, true));
        }
    }

    public function assertFalse(mixed $value, string $msg = ''): void
    {
        if ($value !== false) {
            throw new AssertionError($msg ?: "Expected false, got " . var_export($value, true));
        }
    }

    public function assertNull(mixed $value, string $msg = ''): void
    {
        if ($value !== null) {
            throw new AssertionError($msg ?: "Expected null, got " . var_export($value, true));
        }
    }

    public function assertNotNull(mixed $value, string $msg = ''): void
    {
        if ($value === null) {
            throw new AssertionError($msg ?: "Expected non-null value");
        }
    }

    public function assertCount(int $expected, array $array, string $msg = ''): void
    {
        $actual = count($array);
        if ($expected !== $actual) {
            throw new AssertionError($msg ?: "Expected count $expected, got $actual");
        }
    }

    public function assertGreaterThan(int $min, int $actual, string $msg = ''): void
    {
        if ($actual <= $min) {
            throw new AssertionError($msg ?: "Expected $actual > $min");
        }
    }

    public function assertContains(mixed $needle, array $haystack, string $msg = ''): void
    {
        if (!in_array($needle, $haystack, true)) {
            throw new AssertionError($msg ?: var_export($needle, true) . " not found in array");
        }
    }

    public function assertNotContains(mixed $needle, array $haystack, string $msg = ''): void
    {
        if (in_array($needle, $haystack, true)) {
            throw new AssertionError($msg ?: var_export($needle, true) . " unexpectedly found in array");
        }
    }

    public function assertStringContains(string $needle, string $haystack, string $msg = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new AssertionError($msg ?: "\"$needle\" not found in string");
        }
    }

    // ── Summary ───────────────────────────────────────────────────────────────
    public function summary(): void
    {
        $total = $this->passed + $this->failed + $this->errors;
        echo PHP_EOL;
        echo $this->bold("  Results: ") . $total . " tests" . PHP_EOL;
        echo "  " . $this->green("Passed:  {$this->passed}") . PHP_EOL;
        if ($this->failed > 0) {
            echo "  " . $this->red("Failed:  {$this->failed}") . PHP_EOL;
        }
        if ($this->errors > 0) {
            echo "  " . $this->yellow("Errors:  {$this->errors}") . PHP_EOL;
        }
        echo PHP_EOL;
    }

    public function hasFailed(): bool
    {
        return $this->failed > 0 || $this->errors > 0;
    }
}
