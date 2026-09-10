<?php


namespace Asantibanez\LivewireCharts\Models\Traits;

use Asantibanez\LivewireCharts\Formatters;
use InvalidArgumentException;

trait HasJsonConfig
{
    private $jsonConfig;

    /**
     * Set additional Apex chart properties using dot-notation keys.
     *
     * Values may be scalars (int, float, bool, string), arrays, or a
     * formatter constant from \Asantibanez\LivewireCharts\Formatters, e.g.
     *   Formatters::CURRENCY  →  'formatter:currency'
     *
     * String values — including strings nested inside array values — that look
     * like JavaScript callback syntax (a leading "function" keyword, a
     * parenthesised arrow expression, or a bare single-parameter arrow such as
     * "val =>") are rejected with an InvalidArgumentException — use a
     * Formatters constant instead.
     * Plain strings that do not open with callback syntax are accepted.
     *
     * @param  array $jsonConfig
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setJsonConfig($jsonConfig)
    {
        foreach ($jsonConfig as $key => $value) {
            $this->validateUnsafeKey($key);
            $this->validateJsonConfigEntry($key, $value);
        }

        $this->jsonConfig = $jsonConfig;

        return $this;
    }

    /**
     * Reject dot-notation keys containing path segments that would allow
     * prototype pollution when the JS helper expands them into a nested object.
     * None of __proto__, constructor, or prototype are legitimate Apex option
     * names. Rejecting them here surfaces the error at the PHP call site
     * (developer-experience parity with the JS guard in helpers.js).
     *
     * @param  string $key  Dot-notation key
     *
     * @throws InvalidArgumentException
     */
    private function validateUnsafeKey($key)
    {
        $unsafeSegments = ['__proto__', 'constructor', 'prototype'];
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (in_array($segment, $unsafeSegments, true)) {
                throw new InvalidArgumentException(
                    "livewire-charts: unsafe jsonConfig key \"" . $key . "\". " .
                    "Path segments __proto__, constructor, and prototype are not allowed."
                );
            }
        }
    }

    /**
     * Recursively validate a single jsonConfig entry.
     *
     * Arrays are walked depth-first so that nested string leaves are subject
     * to the same callback-syntax and formatter-reference checks as top-level
     * string values.  Non-string scalars (int, float, bool) are passed through
     * without checks.
     *
     * @param  string $key   Dot-notation key (extended with ".index" for array elements)
     * @param  mixed  $value Value to validate
     *
     * @throws InvalidArgumentException
     */
    private function validateJsonConfigEntry($key, $value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->validateJsonConfigEntry($key . '.' . $k, $v);
            }
            return;
        }

        if (!is_string($value)) {
            return;
        }

        // Reject strings that open with JS callback syntax, with or without
        // a leading block or line comment. The leading separator token
        // ^(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)* consumes any combination of
        // whitespace, block comments (/* … */), and line comments (// …\n)
        // before the callback keyword, so neither comment style can be used
        // to shift the callback past the start anchor.
        // (?![\w$]) after "function" is a negative lookahead that fails when
        // the next character is any JS identifier character (letters, digits,
        // underscore, or $). This correctly excludes identifiers such as
        // "functionality", "function_foo", "function123", and "function$format"
        // while still matching the "function" keyword when followed by
        // whitespace, "(", "*", or a comment. Using \b instead would fire on
        // the boundary before "$" (since "$" is \W in PCRE) and incorrectly
        // treat "function$format" as the function keyword.
        // \*? after the first separator group matches the optional generator
        // star, covering function*, function *, async function*, and named
        // generator forms such as function* name(...).
        // The paren-arrow branch uses the PCRE recursive group (?P<P>...|(?P>P))
        // instead of [^)]*  so that arrow parameters containing nested
        // parentheses (e.g. default values: (val = fn()) => val) are still
        // caught regardless of nesting depth.
        // The separator token is also used before => in both arrow branches so
        // that block/line comments between the parameter list and => are caught.
        // The same separator token appears in the "async" optional prefix and
        // between "function" and "(". The async lookahead (?=[\s(\/]) ensures
        // "asyncfunction(...)" is not misidentified as the async keyword.
        // Identifier alternatives require [a-zA-Z_$] as the first character
        // so digit-leading strings like "2024 => 2025" are not rejected.
        if (preg_match('/^(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)*(async(?=[\s(\/])(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)*)?(function(?![\w$])(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)*\*?(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)*[\w$]*(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)*\(|(?P<P>\((?:[^()]*|(?P>P))*\))(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)*=>|[a-zA-Z_$][\w$]*(?:\s|\/\*[\s\S]*?\*\/|\/\/[^\n]*)*=>)/', $value)) {
            throw new InvalidArgumentException(
                "livewire-charts: setJsonConfig() no longer accepts raw JavaScript strings. " .
                "Use a Formatters constant instead, e.g. Formatters::CURRENCY. " .
                "See \\Asantibanez\\LivewireCharts\\Formatters for all available options."
            );
        }

        // Validate formatter: references against the known registry
        if (strpos($value, 'formatter:') === 0) {
            $name = substr($value, strlen('formatter:'));
            if (!in_array($name, Formatters::known(), true)) {
                throw new InvalidArgumentException(
                    "livewire-charts: unknown formatter \"" . $name . "\" for key \"" . $key . "\". " .
                    "Available: " . implode(', ', Formatters::known()) . "."
                );
            }
        }
    }

    protected function initJsonConfig()
    {
        $this->jsonConfig = [];
    }

    private function defaultJsonConfig()
    {
        return [];
    }

    protected function jsonConfigFromArray($array)
    {
        $this->jsonConfig = data_get($array, 'jsonConfig', $this->defaultJsonConfig());
    }

    protected function jsonConfigToArray()
    {
        return [
            'jsonConfig' => $this->jsonConfig,
        ];
    }
}
