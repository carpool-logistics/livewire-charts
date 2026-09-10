# Changelog

## 5.0.0 - 2026-09-02

**Security / breaking change**

- Removed `eval()`-based `jsonConfig` handling in `helpers.js` (Oneleet findings [JS] CWE-95).
  `setJsonConfig()` no longer accepts raw JavaScript strings (e.g. arrow functions / `function` literals).
  Pass a `Formatters::*` constant instead — see README "Advanced Usage - Custom Json Configs".
- Added `\Asantibanez\LivewireCharts\Formatters` class with `CURRENCY`, `PERCENT`, `INTEGER`, `DECIMAL` constants.
- `setJsonConfig()` now throws `InvalidArgumentException` on raw JS strings or unknown `formatter:` names.
- `fromArray()` / `jsonConfigFromArray()` now restore `jsonConfig` through `setJsonConfig()`, so the same
  validation applies when a chart model is reconstructed from an array.
- `setJsonConfig()` now rejects non-array values (`null`, scalars) with `InvalidArgumentException`.
- Added `.npmrc` with `ignore-scripts=true` to prevent lifecycle-script execution on `npm install` / `npm ci`
  (Oneleet finding [npm] supply-chain risk).
- Fixed prototype-pollution vulnerability in `addPathToObjectWithValue` (`helpers.js`): dot-notation
  path segments `__proto__`, `constructor`, and `prototype` are now rejected with an error; the reduce
  accumulator uses `Object.prototype.hasOwnProperty.call` instead of `=== undefined` to avoid inheriting
  host-object properties. Mirrored in `setJsonConfig()` for developer-experience parity.
- `Formatters::known()` is now reflection-derived from the class constants, so the validated list cannot
  drift from the constants by construction. Added a parity test that enforces set equality between the
  `Formatters::*` constants and the keys of the frozen `formatters.js` registry, closing the remaining
  gap where a PHP constant could be accepted with no JS implementation behind it.
- CI workflow (`phpunit.yml`) now runs Vitest (JS tests) in addition to PHPUnit, with Node 20 and
  `npm ci --ignore-scripts`. Workflow renamed to `Tests`; `pull_request` trigger added.

## 4.1.0 - 2024-08-22
- Added Radial chart

## 4.0.0 - 2024-08-22
- Added Laravel 11 support

## 3.1.0 - 2023-11-07
- Added missing jsonConfig (thanks to @stijnvanouplines)
- Changed 'emit' functions in Area,Line, and Radar chart to 'dispatch' (thanks to @redsquirrelstudio)

## 3.0.0 - 2023-07-25
- Added Livewire v3 support
- Added ability to set chart theme (thanks to @syntaxlexx)
- Added ability to configure chart via JSON properties

## 2.5.0 - 2023-02-01
- Added tooltip override via `extras.tooltip`

## 2.4.2 - 2023-02-01
- Fixed hover title on column chart

## 2.4.1 - 2022-02-18
- Fixed xAxis auto-categories for MultiColumnChart 

## 2.4.0 - 2022-02-14
- Added Radar Chart (thanks to @AlexHupe)
- Added Tree Map Chart
- Added "donut" type to Pie Chart (thanks to @nicko170)

## 2.3.0 - 2021-02-12
- Added `init()` method for better boot and $wire hoisting
- Added color customization for columns, multiline and pie chart

## 2.2.0 - 2020-12-16
- Added support for PHP 8
- Updated docs 
- Added base tests for components and CI

## 2.1.0 - 2020-11-30
- Added stroke width customization
- Added stroke support for Area Chart

## 2.0.0 - 2020-11-30
- Added build system for JS charts code
- Added Blade directive to include assets
- Updated installation and advanced usage instructions

## 1.5.2 - 2020-11-09

- Fixed infinite growing charts

## 1.5.1 - 2020-11-09

- Removed unused Alpine directives

## 1.5.0 - 2020-11-09

- Added `LivewireCharts` facade to create any chart model
- Added `sparklined` Apex Chart feature to all charts (https://apexcharts.com/javascript-chart-demos/sparklines/basic/)

## 1.4.0 - 2020-11-07

- Added Multi Column Chart 
- Added Stacked Multi Column Chart 
- Added support for custom X Axis categories

## 1.3.0 - 2020-11-03

- Added Multi Line Chart 
- Added Data Labels configuration for all charts
- Refactored title and animation to traits

## 1.2.0 - 2020-10-25

- Fixed column chart growing infinitely 
- Improved customization API
- Added Legend configuration
- Refactored configuration traits

## 1.1.0 - 2020-10-20

- Added Area Chart

## 1.0.0 - 2020-10-19

- Initial Release
