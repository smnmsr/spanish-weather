# Analysis Type Addition Workflow

This document outlines the workflow for adding or extending visualization features for a new or existing analysis type (e.g., current observations, daily values, forecasts, etc.).

## Phase 1: Data Discovery

1. **Identify Available Data Fields**
    - Research the AEMET OpenData API endpoint for the analysis type
    - Document all available fields and their units/ranges
    - Check existing AemetService implementation for data retrieval patterns
    - Example: Current observations API provides 40+ fields including `ta` (temperature), `prec` (precipitation), `vv` (wind speed), `vmax` (wind gust), `dv` (wind direction), `pres` (pressure), `inso` (sunshine)

2. **Map Fields to Dimensions**
    - Determine which API fields can be aggregated into visualization dimensions
    - Define unit conversions if needed (e.g., minutes → hours for sunshine)
    - Example: `vv` + `vmax` → wind dimension with gust overlay

## Phase 2: Feature Planning

1. **Determine Chart Requirements**
    - Identify which dimensions should be visualized
    - Decide on chart types: line (trend), bar (discrete values), area (stacked data)
    - Plan gust/overlay requirements for layered data
    - Example: Wind chart needed both mean line and gust area

2. **Document German UI Labels**
    - Define chart titles (e.g., "Windrichtung")
    - Define descriptions including metrics (e.g., "Mittelwert (Linie), Böen (Fläche)")
    - Map dimension names to German labels for consistent UX

## Phase 3: Backend Data Mapping

1. **Update Type Definitions** (`resources/js/types/index.ts`)
    - Extend `DimensionKey` union with new dimensions
    - Add new fields to `ChartDataPoint` interface
    - Add German labels to `DimensionLabels` record

2. **Implement Data Parsing** (`resources/js/pages/Stations/Tool.vue`)
    - Map API response fields to ChartDataPoint properties
    - Apply unit conversions (e.g., `sunshine: obs.inso / 60`)
    - Use `parseValue()` helper for CSV decimal handling
    - Handle null/undefined values gracefully

## Phase 4: Frontend Chart Components

1. **Configure Chart Dimensions** (`resources/js/components/ChartByDimension.vue`)
    - Add dimension to `dimensionConfig` with label, unit, color, type
    - Implement special rendering logic if needed (e.g., gust overlay area)
    - Update `yDomain` calculation to account for new data ranges

2. **Update Results Layout** (`resources/js/pages/Stations/Tool/ResultsSection.vue`)
    - Add new dimensions to `chartSlides` computed property
    - Include chart descriptions explaining metrics
    - Add dimensions to partial data detection
    - Update adjectives record for German labels

## Phase 5: Test Fixture Enhancement

1. **Enrich Mock AEMET Data** (`tests/Helpers/AemetFixtures.php`)
    - Add all relevant API fields to observation fixtures
    - Ensure values match real API response format and ranges
    - Include realistic temporal progression (e.g., 08:00–12:00 with diurnal patterns)
    - Verify field names match actual API (e.g., `vmax`, `dv`, `inso`)

2. **Validate Data Consistency**
    - Cross-reference mock data against real API calls
    - Use Tinker to probe live API and confirm field existence
    - Ensure unit ranges are realistic (e.g., wind 0–30 km/h, pressure 1000–1030 hPa)

## Phase 6: Test Assertions

1. **Basic Data Display Tests**
    - Assert station names appear
    - Assert chart titles visible (e.g., assertSee('Windrichtung'))
    - Assert chart descriptions visible (e.g., assertSee('Mittelwert (Linie), Böen (Fläche)'))

2. **SVG Rendering Tests**
    - Use `assertScript()` with DOM queries to verify charts render
    - Check for SVG elements: `document.querySelectorAll('svg').length >= N`
    - Verify special features (e.g., gust area): `document.querySelector('svg [color*="rgba"]')`

3. **Legend Tests**
    - Target unique legend container class combination: `.mt-4.flex.flex-wrap.justify-center span`
    - Assert legend item count matches station count
    - Use `>= ` comparisons to account for multiple charts

4. **Multi-Chart Validation**
    - Test with all stations and all new dimensions together
    - Verify no JavaScript errors: `assertNoJavaScriptErrors()`
    - Test on multiple devices/themes (desktop/mobile, light/dark)

## Phase 7: Code Quality

1. **Formatting & Linting**

    ```bash
    vendor/bin/pint --dirty        # Format PHP
    npm run format                 # Format JS/TS/Vue
    npm run lint                   # ESLint with auto-fix
    ```

2. **Run Tests**

    ```bash
    ./vendor/bin/pest tests/Browser/[AnalysisTypeTest].php
    ```

3. **Verify No Regressions**
    ```bash
    ./vendor/bin/pest  # Run full test suite
    ```

## Checklist for New Analysis Type

- [ ] Data discovery complete (all available fields documented)
- [ ] Feature plan approved (chart types and dimensions decided)
- [ ] Type definitions updated (`DimensionKey`, `ChartDataPoint`)
- [ ] Data parsing implemented with unit conversions
- [ ] Chart configurations added with German labels
- [ ] Results layout updated with new dimensions
- [ ] Mock AEMET fixtures enriched with all fields
- [ ] Mock data validated against real API format
- [ ] Browser tests verify data display
- [ ] Browser tests verify SVG rendering
- [ ] Browser tests verify legend accuracy
- [ ] Code formatted and linted
- [ ] All tests passing (no regressions)
- [ ] Multi-device/theme testing complete

## Key Files to Modify

| File                                                  | Purpose                                         |
| ----------------------------------------------------- | ----------------------------------------------- |
| `resources/js/types/index.ts`                         | Type definitions for dimensions and data points |
| `resources/js/pages/Stations/Tool.vue`                | Data parsing and mapping from API response      |
| `resources/js/components/ChartByDimension.vue`        | Chart rendering configuration                   |
| `resources/js/pages/Stations/Tool/ResultsSection.vue` | Results layout and slides                       |
| `tests/Helpers/AemetFixtures.php`                     | Mock AEMET API responses                        |
| `tests/Browser/[AnalysisType]E2ETest.php`             | Browser test assertions                         |

## Notes

- Always use German labels for user-facing UI text
- Use `parseValue()` helper for AEMET's comma-decimal strings
- Unovis charts render as SVG; validate via DOM queries in tests
- Mock fixtures must exactly match real AEMET API schema
- Use `assertScript()` for complex DOM validation in Pest browser tests
- Test with real data (Tinker) before finalizing mock fixtures
