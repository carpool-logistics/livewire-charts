import deepmerge from 'deepmerge'
import { formatters } from './formatters'

const UNSAFE_KEYS = ['__proto__', 'constructor', 'prototype']

export const addPathToObjectWithValue = (obj, path, value) => {
    const pList = path.split('.')

    if (pList.some(segment => UNSAFE_KEYS.includes(segment))) {
        throw new Error('livewire-charts: unsafe jsonConfig key "' + path + '".')
    }

    const key = pList.pop()
    const pointer = pList.reduce((accumulator, currentValue) => {
        if (!Object.prototype.hasOwnProperty.call(accumulator, currentValue)
            || typeof accumulator[currentValue] !== 'object'
            || accumulator[currentValue] === null) {
            accumulator[currentValue] = {}
        }
        return accumulator[currentValue]
    }, obj)

    pointer[key] = value
    return obj
}

const FORMATTER_PREFIX = 'formatter:'

/**
 * Resolve a single jsonConfig value.
 *
 * - Arrays and plain objects are traversed recursively so that nested
 *   "formatter:NAME" strings are resolved at every leaf.
 * - Plain scalars (number, boolean, non-prefixed string) are returned as-is.
 * - Strings starting with "formatter:" are resolved to a function from the
 *   package-owned formatters registry. An unknown name throws immediately so
 *   the misconfiguration surfaces at chart-render time rather than silently
 *   producing a broken chart.
 *
 * No eval / new Function is used.
 */
const resolveConfigValue = (key, value) => {
    if (Array.isArray(value)) {
        return value.map((item, index) => resolveConfigValue(key + '[' + index + ']', item))
    }

    if (value !== null && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value).map(([k, v]) => [k, resolveConfigValue(key + '.' + k, v)])
        )
    }

    if (typeof value !== 'string' || !value.startsWith(FORMATTER_PREFIX)) {
        return value
    }

    const name = value.slice(FORMATTER_PREFIX.length)

    if (!Object.prototype.hasOwnProperty.call(formatters, name)) {
        throw new Error(
            'livewire-charts: unknown formatter "' + name + '" for "' + key + '". ' +
            'Available: ' + Object.keys(formatters).join(', ') + '.'
        )
    }

    return formatters[name]
}

export const mergedOptionsWithJsonConfig = (options, jsonConfig) => {
    const customOptions = Object.keys(jsonConfig)
        .reduce(function (obj, key) {
            return addPathToObjectWithValue(obj, key, resolveConfigValue(key, jsonConfig[key]))
        }, {})

    return deepmerge(options, customOptions)
}
