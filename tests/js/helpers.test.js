import { describe, it, expect } from 'vitest'
import { mergedOptionsWithJsonConfig, addPathToObjectWithValue } from '../../resources/js/helpers.js'
import { formatters } from '../../resources/js/formatters.js'

// ---------------------------------------------------------------------------
// resolveConfigValue — nested array / object recursion (finding 2)
// ---------------------------------------------------------------------------

describe('mergedOptionsWithJsonConfig — nested array values', () => {
    it('resolves formatter:currency inside an array value', () => {
        const result = mergedOptionsWithJsonConfig({}, {
            'colors': ['formatter:currency', '#FF0000'],
        })
        // The first element should be resolved to the currency function reference.
        expect(result.colors[0]).toBe(formatters.currency)
        expect(result.colors[1]).toBe('#FF0000')
    })

    it('passes plain strings inside an array value through unchanged', () => {
        const result = mergedOptionsWithJsonConfig({}, {
            'colors': ['#FF0000', '#00FF00'],
        })
        expect(result.colors).toEqual(['#FF0000', '#00FF00'])
    })

    it('throws for an unknown formatter name nested inside an array value', () => {
        expect(() =>
            mergedOptionsWithJsonConfig({}, {
                'colors': ['formatter:nonexistent'],
            })
        ).toThrow(/unknown formatter/)
    })
})

describe('mergedOptionsWithJsonConfig — nested object values', () => {
    it('resolves formatter:currency inside a plain-object value', () => {
        const result = mergedOptionsWithJsonConfig({}, {
            'nested': { formatter: 'formatter:currency' },
        })
        expect(result.nested.formatter).toBe(formatters.currency)
    })

    it('passes non-string scalars inside a plain-object value through unchanged', () => {
        const result = mergedOptionsWithJsonConfig({}, {
            'nested': { min: 0, enabled: true },
        })
        expect(result.nested.min).toBe(0)
        expect(result.nested.enabled).toBe(true)
    })
})

// ---------------------------------------------------------------------------
// No-eval contract — raw JS callback strings must NOT become functions
//
// jsonConfig values reach the JS resolver directly from the Livewire wire
// (public $columnChartModel is client-tamperable). The PHP InvalidArgumentException
// is a developer-experience guard; it is NOT the security boundary. These tests
// are the actual regression contract.
// ---------------------------------------------------------------------------

describe('resolveConfigValue — raw JS callback strings are passed through as plain strings, not evaluated', () => {
    it('passes the old README arrow-function example through byte-for-byte unchanged', () => {
        const raw = '(val) => `$${val} million dollars baby!`'
        const result = mergedOptionsWithJsonConfig({}, {
            'tooltip.y.formatter': raw,
        })
        expect(result.tooltip.y.formatter).toBe(raw)
        expect(typeof result.tooltip.y.formatter).toBe('string')
        expect(result.tooltip.y.formatter).not.toBeInstanceOf(Function)
    })

    it('passes a function-keyword literal through byte-for-byte unchanged', () => {
        const raw = 'function (val) { return val }'
        const result = mergedOptionsWithJsonConfig({}, {
            'tooltip.y.formatter': raw,
        })
        expect(result.tooltip.y.formatter).toBe(raw)
        expect(typeof result.tooltip.y.formatter).toBe('string')
        expect(result.tooltip.y.formatter).not.toBeInstanceOf(Function)
    })

    // The assertions above prove the value is not *converted* to a function.
    // This one proves it is never *executed*: the payload is a self-invoking
    // expression, so if eval()/new Function() were ever reintroduced the side
    // effect would fire and this test would fail regardless of the return type.
    it('never executes a self-invoking callback string (side-effect probe)', () => {
        delete globalThis.__livewireChartsPwned

        const result = mergedOptionsWithJsonConfig({}, {
            'tooltip.y.formatter': '(() => { globalThis.__livewireChartsPwned = true })()',
        })

        expect(globalThis.__livewireChartsPwned).toBeUndefined()
        expect(typeof result.tooltip.y.formatter).toBe('string')
    })

    it('never executes a self-invoking callback string nested inside an array', () => {
        delete globalThis.__livewireChartsPwnedNested

        mergedOptionsWithJsonConfig({}, {
            'colors': ['(() => { globalThis.__livewireChartsPwnedNested = true })()'],
        })

        expect(globalThis.__livewireChartsPwnedNested).toBeUndefined()
    })
})

// ---------------------------------------------------------------------------
// Formatter registry — own-property lookup must reject prototype-chain names
//
// If resolveConfigValue ever regresses to `name in formatters` or `formatters[name]`
// instead of Object.prototype.hasOwnProperty.call(formatters, name), these
// prototype-inherited names would silently resolve to built-in functions.
// ---------------------------------------------------------------------------

describe('resolveConfigValue — formatter: references to prototype-chain names throw', () => {
    it('throws for formatter:constructor', () => {
        expect(() =>
            mergedOptionsWithJsonConfig({}, { 'x': 'formatter:constructor' })
        ).toThrow(/unknown formatter/)
    })

    it('throws for formatter:toString', () => {
        expect(() =>
            mergedOptionsWithJsonConfig({}, { 'x': 'formatter:toString' })
        ).toThrow(/unknown formatter/)
    })

    it('throws for formatter:hasOwnProperty', () => {
        expect(() =>
            mergedOptionsWithJsonConfig({}, { 'x': 'formatter:hasOwnProperty' })
        ).toThrow(/unknown formatter/)
    })
})

// ---------------------------------------------------------------------------
// formatters registry — Object.freeze must prevent runtime injection
// ---------------------------------------------------------------------------

describe('formatters — registry is frozen against runtime injection', () => {
    it('throws a TypeError when assigning a new key to the frozen registry (strict ES module)', () => {
        // Object.freeze in strict mode (all ES modules run in strict mode) throws
        // TypeError on any write attempt. The throw itself proves no injection occurred.
        expect(() => {
            formatters['injected'] = () => 'PWNED'
        }).toThrow(TypeError)
        expect(formatters['injected']).toBeUndefined()
    })
})

// ---------------------------------------------------------------------------
// Prototype-pollution — addPathToObjectWithValue must reject unsafe path segments
// ---------------------------------------------------------------------------

describe('addPathToObjectWithValue — prototype pollution prevention', () => {
    it('throws for a __proto__ path segment', () => {
        expect(() =>
            addPathToObjectWithValue({}, '__proto__.polluted', 'PWNED')
        ).toThrow(/unsafe jsonConfig key/)
        expect(({}).polluted).toBeUndefined()
    })

    it('throws for a constructor.prototype path', () => {
        expect(() =>
            addPathToObjectWithValue({}, 'constructor.prototype.polluted2', 'PWNED')
        ).toThrow(/unsafe jsonConfig key/)
        expect(({}).polluted2).toBeUndefined()
    })

    it('throws for a bare prototype segment', () => {
        expect(() =>
            addPathToObjectWithValue({}, 'prototype.polluted3', 'PWNED')
        ).toThrow(/unsafe jsonConfig key/)
        expect(({}).polluted3).toBeUndefined()
    })

    it('still sets a legitimate nested key correctly', () => {
        const result = addPathToObjectWithValue({}, 'tooltip.y.formatter', 'hello')
        expect(result.tooltip.y.formatter).toBe('hello')
    })
})
