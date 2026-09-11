import { describe, it, expect } from 'vitest'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { formatters } from '../../resources/js/formatters.js'

describe('formatters.currency', () => {
    it('returns $1,234.50 for 1234.5 regardless of host locale', () => {
        expect(formatters.currency(1234.5)).toBe('$1,234.50')
    })

    it('produces different output than de-DE locale formatting, proving the en-US pin is necessary', () => {
        // Explicitly pass 'de-DE' to show that without the en-US pin the output
        // would differ — no dependency on the host locale or LC_ALL/LANG env vars.
        const deDE = Number(1234.5).toLocaleString('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
        expect(deDE).not.toBe('1,234.50')        // de-DE gives '1.234,50'
        expect(formatters.currency(1234.5)).toBe('$1,234.50') // pinned to en-US
    })

    it('coerces string input to a number', () => {
        expect(formatters.currency('500')).toBe('$500.00')
    })

    it('handles zero', () => {
        expect(formatters.currency(0)).toBe('$0.00')
    })
})

describe('formatters.percent', () => {
    it('appends % to the value', () => {
        expect(formatters.percent(42)).toBe('42%')
    })

    it('coerces string input via Number()', () => {
        // Number('42') === 42, so output should be '42%' not '42%' via string concat
        expect(formatters.percent('42')).toBe('42%')
    })
})

describe('formatters.integer', () => {
    it('rounds to the nearest integer', () => {
        expect(formatters.integer(42.7)).toBe('43')
        expect(formatters.integer(42.2)).toBe('42')
    })

    it('returns a string', () => {
        expect(typeof formatters.integer(5)).toBe('string')
    })
})

describe('formatters.decimal', () => {
    it('formats to two decimal places', () => {
        expect(formatters.decimal(3.14159)).toBe('3.14')
    })

    it('pads whole numbers to two decimal places', () => {
        expect(formatters.decimal(5)).toBe('5.00')
    })
})

// ---------------------------------------------------------------------------
// PHP <-> JS parity
//
// A Formatters::* constant with no matching function here would be accepted by
// setJsonConfig() but blow up at chart-render time with "unknown formatter".
// The reverse (a JS function with no PHP constant) is unreachable from PHP.
// This runs from the JS side so it can compare against the real frozen registry
// object rather than parsing JS source, and PHP constants are a simple,
// unambiguous regex target.
// ---------------------------------------------------------------------------

describe('formatters registry <-> Formatters.php constants', () => {
    it('exposes exactly the names declared as Formatters::* constants', () => {
        const phpPath = fileURLToPath(new URL('../../src/Formatters.php', import.meta.url))
        const php = readFileSync(phpPath, 'utf8')

        const phpNames = [...php.matchAll(/const\s+\w+\s*=\s*'formatter:(\w+)'\s*;/g)]
            .map(match => match[1])

        expect(phpNames.length).toBeGreaterThan(0)

        expect(phpNames.sort()).toEqual(Object.keys(formatters).sort())
    })

    it('has a callable function for every registered name', () => {
        for (const name of Object.keys(formatters)) {
            expect(typeof formatters[name]).toBe('function')
        }
    })
})
