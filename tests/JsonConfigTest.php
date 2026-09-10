<?php

namespace Asantibanez\LivewireCharts\Tests;

use Asantibanez\LivewireCharts\Formatters;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use InvalidArgumentException;

class JsonConfigTest extends TestCase
{
    private function makeModel(): LineChartModel
    {
        return new LineChartModel();
    }

    // -------------------------------------------------------------------------
    // Plain scalar values — accepted
    // -------------------------------------------------------------------------

    /** @test */
    public function accepts_integer_values()
    {
        $model = $this->makeModel()->setJsonConfig([
            'plotOptions.pie.startAngle' => -90,
            'plotOptions.pie.endAngle'   => 90,
        ]);

        $this->assertSame(-90, $model->toArray()['jsonConfig']['plotOptions.pie.startAngle']);
        $this->assertSame(90,  $model->toArray()['jsonConfig']['plotOptions.pie.endAngle']);
    }

    /** @test */
    public function accepts_float_values()
    {
        $model = $this->makeModel()->setJsonConfig([
            'chart.zoom.autoScaleYaxis' => 1.5,
        ]);

        $this->assertSame(1.5, $model->toArray()['jsonConfig']['chart.zoom.autoScaleYaxis']);
    }

    /** @test */
    public function accepts_boolean_values()
    {
        $model = $this->makeModel()->setJsonConfig([
            'chart.toolbar.show' => false,
            'dataLabels.enabled' => true,
        ]);

        $this->assertFalse($model->toArray()['jsonConfig']['chart.toolbar.show']);
        $this->assertTrue($model->toArray()['jsonConfig']['dataLabels.enabled']);
    }

    /** @test */
    public function accepts_plain_string_values()
    {
        $model = $this->makeModel()->setJsonConfig([
            'chart.type' => 'bar',
        ]);

        $this->assertSame('bar', $model->toArray()['jsonConfig']['chart.type']);
    }

    /** @test */
    public function accepts_array_values()
    {
        $model = $this->makeModel()->setJsonConfig([
            'colors' => ['#FF0000', '#00FF00'],
        ]);

        $this->assertSame(['#FF0000', '#00FF00'], $model->toArray()['jsonConfig']['colors']);
    }

    // -------------------------------------------------------------------------
    // Valid formatter constants — accepted
    // -------------------------------------------------------------------------

    /** @test */
    public function accepts_currency_formatter_constant()
    {
        $model = $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => Formatters::CURRENCY,
        ]);

        $this->assertSame(Formatters::CURRENCY, $model->toArray()['jsonConfig']['tooltip.y.formatter']);
    }

    /** @test */
    public function accepts_all_formatter_constants()
    {
        $constants = [
            Formatters::CURRENCY,
            Formatters::PERCENT,
            Formatters::INTEGER,
            Formatters::DECIMAL,
        ];

        foreach ($constants as $constant) {
            $model = $this->makeModel()->setJsonConfig(['tooltip.y.formatter' => $constant]);
            $this->assertSame($constant, $model->toArray()['jsonConfig']['tooltip.y.formatter']);
        }
    }

    // -------------------------------------------------------------------------
    // Raw JS strings — rejected
    // -------------------------------------------------------------------------

    /** @test */
    public function rejects_arrow_function_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => '(val) => `$${val} million dollars baby!`',
        ]);
    }

    /** @test */
    public function rejects_function_keyword_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'function(val) { return val + " units"; }',
        ]);
    }

    /** @test */
    public function rejects_function_with_block_comment_before_params()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'function /* legacy formatter */ (value) { return value; }',
        ]);
    }

    /** @test */
    public function rejects_bare_identifier_arrow_function()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'val => val * 2',
        ]);
    }

    /** @test */
    public function rejects_async_bare_identifier_arrow_function()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'async val => await transform(val)',
        ]);
    }

    /** @test */
    public function rejects_async_arrow_with_parenthesised_params()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'async(val) => val * 2',
        ]);
    }

    /** @test */
    public function rejects_async_arrow_with_empty_params()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'async() => computedValue',
        ]);
    }

    /** @test */
    public function accepts_digit_leading_string_with_arrow_token()
    {
        // "2024 => 2025" is not valid JS (digits cannot start an identifier)
        // and must not be rejected as a callback.
        $model = $this->makeModel()->setJsonConfig([
            'xaxis.title.text' => '2024 => 2025',
        ]);

        $this->assertSame('2024 => 2025', $model->toArray()['jsonConfig']['xaxis.title.text']);
    }

    /** @test */
    public function accepts_identifier_starting_with_async_as_plain_string()
    {
        // "asyncfunction(value)" is a plain identifier call, not async keyword syntax.
        // The "async" prefix must not be consumed as the async keyword when it is
        // immediately followed by another identifier character.
        $model = $this->makeModel()->setJsonConfig([
            'xaxis.title.text' => 'asyncfunction(value)',
        ]);

        $this->assertSame('asyncfunction(value)', $model->toArray()['jsonConfig']['xaxis.title.text']);
    }

    /** @test */
    public function accepts_identifier_starting_with_function_as_plain_string()
    {
        // "functionality (beta)" starts with the substring "function" but is
        // a plain identifier, not a JS function keyword. The (?![\w$]) lookahead
        // must prevent it from being matched as a callback.
        $model = $this->makeModel()->setJsonConfig([
            'subtitle.text' => 'functionality (beta)',
        ]);

        $this->assertSame('functionality (beta)', $model->toArray()['jsonConfig']['subtitle.text']);
    }

    /** @test */
    public function accepts_dollar_suffixed_identifier_starting_with_function()
    {
        // "function$format(value)" is a valid JS identifier call, not a
        // "function" keyword expression. "$" is \W in PCRE so \b would
        // incorrectly match here; (?![\w$]) must prevent the false positive.
        $model = $this->makeModel()->setJsonConfig([
            'xaxis.title.text' => 'function$format(value)',
        ]);

        $this->assertSame('function$format(value)', $model->toArray()['jsonConfig']['xaxis.title.text']);
    }

    /** @test */
    public function rejects_function_with_line_comment_before_parenthesis()
    {
        // A line comment (// …) between "function" and "(" must not bypass
        // the callback guard — it is still a JS function expression.
        $this->expectException(\InvalidArgumentException::class);

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => "function // legacy\n(value) { return value; }",
        ]);
    }

    /** @test */
    public function rejects_async_arrow_with_block_comment_before_params()
    {
        // A block comment between "async" and "(param)" must not bypass
        // the callback guard.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'async /* legacy */ (value) => value',
        ]);
    }

    /** @test */
    public function rejects_function_preceded_by_block_comment()
    {
        // A block comment before the "function" keyword must not shift
        // the callback past the start-anchor guard.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => '/* legacy */ function(value) { return value; }',
        ]);
    }

    /** @test */
    public function rejects_function_preceded_by_line_comment()
    {
        // A line comment before the "function" keyword must not bypass the guard.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => "// legacy\nfunction(value) { return value; }",
        ]);
    }

    /** @test */
    public function rejects_generator_function_string()
    {
        // "function*" is a generator callback and must be rejected.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'function*(val) { yield val; }',
        ]);
    }

    /** @test */
    public function rejects_generator_function_with_space_around_star()
    {
        // "function * " (spaces around the star) must also be rejected.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'function * (val) { yield val; }',
        ]);
    }

    /** @test */
    public function rejects_async_generator_function_string()
    {
        // "async function*" is an async generator callback and must be rejected.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'async function*(val) { yield val; }',
        ]);
    }

    /** @test */
    public function rejects_paren_arrow_with_block_comment_before_arrow()
    {
        // A block comment between "(value)" and "=>" must not bypass the guard.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => '(value) /* formatter */ => value',
        ]);
    }

    /** @test */
    public function rejects_bare_arrow_with_block_comment_before_arrow()
    {
        // A block comment between the identifier and "=>" must not bypass the guard.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'value /* formatter */ => value',
        ]);
    }

    /** @test */
    public function rejects_paren_arrow_with_nested_parens_in_default_value()
    {
        // Arrow parameters containing nested parentheses (default values with
        // function calls) must still be detected as callbacks.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => '(val = getDefault()) => val',
        ]);
    }

    /** @test */
    public function rejects_paren_arrow_with_deeply_nested_parens_in_default_value()
    {
        // Two levels of nesting in a default value must also be detected.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => '(a = fn(b(c))) => a',
        ]);
    }

    // -------------------------------------------------------------------------
    // Nested array values — validation traverses every string leaf
    // -------------------------------------------------------------------------

    /** @test */
    public function accepts_array_value_containing_only_plain_strings()
    {
        // Arrays of plain strings (e.g. colour palettes) must still be accepted.
        $model = $this->makeModel()->setJsonConfig([
            'colors' => ['#FF0000', '#00FF00', '#0000FF'],
        ]);

        $this->assertSame(['#FF0000', '#00FF00', '#0000FF'], $model->toArray()['jsonConfig']['colors']);
    }

    /** @test */
    public function rejects_callback_string_nested_inside_array_value()
    {
        // A callback string inside an array value must trigger the same guard
        // as a top-level callback string.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer accepts raw JavaScript');

        $this->makeModel()->setJsonConfig([
            'colors' => ['(val) => val', '#FF0000'],
        ]);
    }

    /** @test */
    public function rejects_unknown_formatter_nested_inside_array_value()
    {
        // An unknown "formatter:" reference inside an array value must also be
        // rejected with the "unknown formatter" message.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown formatter');

        $this->makeModel()->setJsonConfig([
            'colors' => ['formatter:nonexistent', '#FF0000'],
        ]);
    }

    /** @test */
    public function accepts_valid_formatter_constant_nested_inside_array_value()
    {
        // A known "formatter:" reference inside an array value must be accepted.
        $model = $this->makeModel()->setJsonConfig([
            'someKey' => [Formatters::CURRENCY, 'plain'],
        ]);

        $this->assertSame([Formatters::CURRENCY, 'plain'], $model->toArray()['jsonConfig']['someKey']);
    }

    // -------------------------------------------------------------------------
    // Unknown formatter name — rejected
    // -------------------------------------------------------------------------

    /** @test */
    public function rejects_unknown_formatter_prefix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown formatter');

        $this->makeModel()->setJsonConfig([
            'tooltip.y.formatter' => 'formatter:nonexistent',
        ]);
    }

    /** @test */
    public function error_message_lists_available_formatters()
    {
        try {
            $this->makeModel()->setJsonConfig([
                'tooltip.y.formatter' => 'formatter:nonexistent',
            ]);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            foreach (Formatters::known() as $name) {
                $this->assertStringContainsString($name, $e->getMessage());
            }
        }
    }

    // -------------------------------------------------------------------------
    // Prose strings containing "function" or "=>" — accepted (regression for
    // the previous substring blacklist that rejected these)
    // -------------------------------------------------------------------------

    /** @test */
    public function accepts_string_containing_function_as_prose()
    {
        $model = $this->makeModel()->setJsonConfig([
            'yaxis.title.text' => 'Liver function tests',
        ]);

        $this->assertSame('Liver function tests', $model->toArray()['jsonConfig']['yaxis.title.text']);
    }

    /** @test */
    public function accepts_string_containing_both_function_and_arrow_as_prose()
    {
        // Both "=>" and "function" appear in the string but neither is at the
        // START in callback syntax, so both must be accepted.
        $model = $this->makeModel()->setJsonConfig([
            'noData.text' => 'See the docs for => arrows and function references',
        ]);

        $this->assertSame(
            'See the docs for => arrows and function references',
            $model->toArray()['jsonConfig']['noData.text']
        );
    }

    /** @test */
    public function accepts_string_with_arrow_inside_prose()
    {
        // A string that contains "=>" but does NOT start with an identifier
        // followed by "=>" is accepted.
        $model = $this->makeModel()->setJsonConfig([
            'subtitle.text' => 'Direction: north => south',
        ]);

        $this->assertSame('Direction: north => south', $model->toArray()['jsonConfig']['subtitle.text']);
    }

    // -------------------------------------------------------------------------
    // Unsafe path-segment rejection (prototype-pollution parity with helpers.js)
    // -------------------------------------------------------------------------

    /** @test */
    public function rejects_key_with_proto_segment()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe jsonConfig key');

        $this->makeModel()->setJsonConfig([
            '__proto__.polluted' => 'PWNED',
        ]);
    }

    /** @test */
    public function rejects_key_with_constructor_segment()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe jsonConfig key');

        $this->makeModel()->setJsonConfig([
            'constructor.prototype.polluted' => 'PWNED',
        ]);
    }

    /** @test */
    public function rejects_key_with_bare_prototype_segment()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe jsonConfig key');

        $this->makeModel()->setJsonConfig([
            'prototype.polluted' => 'PWNED',
        ]);
    }

    // -------------------------------------------------------------------------
    // Formatters::known() is derived from the class constants
    //
    // known() is reflection-derived, so it cannot drift from the constants by
    // construction. This asserts that property holds and that the "formatter:"
    // prefix is stripped. The complementary constants-to-formatters.js parity
    // check lives in tests/js/formatters.test.js, which can compare against the
    // real frozen registry object instead of parsing JS source from PHP.
    // -------------------------------------------------------------------------

    /** @test */
    public function known_is_derived_from_the_class_constants()
    {
        $constants = (new \ReflectionClass(Formatters::class))->getConstants();

        $expected = [];
        foreach ($constants as $value) {
            $this->assertStringStartsWith(
                'formatter:',
                $value,
                'Every Formatters constant must use the "formatter:" prefix'
            );
            $expected[] = substr($value, strlen('formatter:'));
        }

        $this->assertNotEmpty($expected, 'Formatters must declare at least one constant');

        $this->assertEqualsCanonicalizing($expected, Formatters::known());
    }

    /** @test */
    public function every_known_formatter_is_accepted_by_set_json_config()
    {
        foreach (Formatters::known() as $name) {
            $model = $this->makeModel()->setJsonConfig([
                'tooltip.y.formatter' => 'formatter:' . $name,
            ]);

            $this->assertSame(
                'formatter:' . $name,
                $model->toArray()['jsonConfig']['tooltip.y.formatter'],
                'Formatters::known() returned "' . $name . '" but setJsonConfig() rejected it'
            );
        }
    }
}
