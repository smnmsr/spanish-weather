# Spanish Weather Explorer - Project Idea & UX

## Overview

A web-based tool for exploring and comparing historical and current weather data from Spanish meteorological stations using the official AEMET (Agencia Estatal de Meteorología) OpenData API.

https://opendata.aemet.es/AEMET_OpenData_specification.json

**Primary Use Case**: Help users make informed decisions about travel, holidays, events, or relocations by analyzing long-term weather patterns and trends across different Spanish locations.

## Core Concept

Users can select one or more weather stations (or specify coordinates) and explore weather patterns across different time dimensions:

- **Short-term**: Recent weather (last 24h-30 days) for immediate planning
- **Longitudinal Analysis**: Multi-year trends (e.g., "April weather over the last 10 years") to understand seasonal patterns
- **Comparative**: Compare different locations or different time periods to make informed decisions

## Key Features

### 1. Location Selection

- **Weather Station Selection**
    - Interactive map of Spain showing all available AEMET weather stations
    - Search functionality by station name, province, or code
    - Multi-select capability to compare multiple stations
    - Station details: name, location, altitude, type (automatic, complete, pluviometric, thermometric)

- **Coordinate-based Selection**
    - Allow users to click on map or enter coordinates (latitude/longitude)
    - System finds nearest weather station(s) to the specified coordinates
    - Option to select multiple coordinate points

### 2. Time Range Selection

#### Analysis Types

- **Current/Recent Data**: For immediate trip planning
    - Last 24 hours (hourly detail)
    - Last 7 days
    - Last 30 days
    - Current month
- **Longitudinal Analysis**: For understanding patterns and trends
    - **Same Period Across Years**: "April during the last 10 years"
    - **Specific Month/Season Over Years**: Compare how March has evolved from 2015-2025
    - **Year-over-Year Comparison**: See 2024 vs 2023 vs 2022
    - **Decade Trends**: Understand climate evolution over 10+ years

#### Time Selection Interface

- **Quick Presets**:
    - Last 24 hours (for recent conditions)
    - Last week/month (for short-term patterns)
    - This month, last 10 years (longitudinal - "Is April usually rainy?")
    - Same week, last 5 years (longitudinal - "Weather during Easter week")
- **Custom Range Builder**:
    - **Simple Range**: Start date → End date
    - **Recurring Period**: Select a date range (e.g., April 1-15) and apply it across multiple years
    - **Multi-Year Same Period**: "Show me March data from 2015, 2016, 2017... 2025"

#### Examples of Typical Queries

```
🏖️ "I want to visit Costa del Sol in August - show me August weather
   for the last 10 years in Málaga"

🎿 "When is the best time to visit the Pyrenees? - compare December,
   January, February across last 5 years"

🚴 "Planning a bike tour in April - show me April temperatures and
   precipitation patterns in Girona over the past decade"

📊 "Is Barcelona getting warmer? - show temperature trends for
   summer months (June-August) from 2010-2025"
```

### 3. Data Visualization & Comparison

#### Available Metrics (from AEMET API)

- **Temperature**: min, max, average
- **Precipitation**: total, intensity
- **Wind**: speed, direction, gusts
- **Humidity**: relative humidity
- **Pressure**: atmospheric pressure
- **Other**: solar radiation, cloud cover (when available)

#### Visualization Types

- **Line Charts**:
    - Temporal evolution of selected variables
    - Multi-year overlay (e.g., temperature curves for April 2015-2025, each year as a separate line)
    - Trend lines showing long-term changes
- **Bar Charts**:
    - Precipitation comparisons across years
    - Average monthly values across different years
- **Box Plots**:
    - Temperature/precipitation distribution across years
    - Shows median, quartiles, and outliers for each period
- **Heatmaps**:
    - Month-by-month view across multiple years
    - Quick visual identification of patterns
- **Multi-line Overlay**:
    - Compare same metric across different stations
    - Compare same period across different years
- **Statistics Tables**:
    - Mean, median, min, max values per year/period
    - Total precipitation by period
    - Temperature ranges
    - Days with precipitation
    - Year-over-year % changes
- **Trend Indicators**:
    - Temperature trends (warming/cooling) with statistical significance
    - Precipitation anomalies vs historical average
    - "Best" and "worst" years for specific criteria
    - Probability distributions (e.g., "70% chance of rain in April based on last 10 years")

### 4. Comparative Analysis

- Side-by-side comparison of up to 4 stations
- Time period comparison (same location, different time periods)
- Year-over-year evolution for same location
- Difference calculations between stations and periods
- Correlation analysis for selected metrics
- **Answer specific questions**:
    - "Which location has the most stable temperatures in spring?"
    - "When is the driest period in southern Spain?"
    - "Is there a trend toward hotter summers?"
- Export comparison data as CSV/JSON

## Real-World Use Cases

### Holiday Planning

**Scenario**: "I want to plan a beach holiday in Spain next July"

- Select coastal stations: Málaga, Valencia, Barcelona, Palma de Mallorca
- View "July" data from last 10 years
- Compare average temperatures, rainy days, wind conditions
- Identify the most reliable weather pattern

### Event Planning

**Scenario**: "Planning an outdoor wedding in Seville in May"

- Select Seville station
- Analyze May weather from last 15 years
- See probability of rain, typical temperatures
- Identify best/worst years to understand variability

### Relocation Decision

**Scenario**: "Considering moving to Madrid or Barcelona - which has milder winters?"

- Compare both cities
- Focus on November-February over 10 years
- Analyze temperature ranges, rainy days, sunshine hours

### Sports & Activities

**Scenario**: "Best time for cycling in northern Spain?"

- Select multiple northern stations
- Compare spring months (March-May) across years
- Find period with optimal temperature and least rain

### Climate Research

**Scenario**: "Is the Mediterranean coast experiencing more extreme heat?"

- Select coastal stations
- Analyze summer maximum temperatures 2000-2025
- View trend lines and statistical analysis

## User Experience Flow

### Initial Screen

```
┌─────────────────────────────────────────────────────────┐
│  Spanish Weather Explorer                     [Help] [?]│
├─────────────────────────────────────────────────────────┤
│                                                          │
│  [Interactive Map of Spain with Station Markers]        │
│                                                          │
│  Selected Stations: [None]                 [+ Add]      │
│                                                          │
│  ┌─ or ─────────────────────────────────────────────┐  │
│  │ Enter Coordinates:                                │  │
│  │ Latitude: [____] Longitude: [____]  [Find Station]│  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  [Continue to Time Selection →]                         │
└─────────────────────────────────────────────────────────┘
```

### Time Range Selection

```
┌─────────────────────────────────────────────────────────┐
│  ← Back to Stations                                      │
├─────────────────────────────────────────────────────────┤
│  Selected Stations:                                      │
│  • Madrid - Retiro (28079)                              │
│  • Barcelona (08181B)                                   │
│  • Sevilla - Aeropuerto (5783)                          │
│                                                          │
│  ┌─ Analysis Type ──────────────────────────────────┐  │
│  │ ○ Recent/Current  ● Longitudinal (Multi-Year)    │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  ┌─ Longitudinal Analysis ──────────────────────────┐  │
│  │  Select Period to Analyze:                        │  │
│  │  Month: [April ▼]  across  Years: 2015-2025      │  │
│  │                                                    │  │
│  │  Quick Presets:                                    │  │
│  │  • This month, last 10 years                      │  │
│  │  • Summer (Jun-Aug), last 5 years                 │  │
│  │  • Easter week, last 10 years                     │  │
│  │  • Custom period builder...                       │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  Data Granularity:                                       │
│  ○ Hourly  ● Daily  ○ Monthly Averages                 │
│                                                          │
│  [View Analysis & Charts →]                             │
└─────────────────────────────────────────────────────────┘
```

### Results Dashboard (Longitudinal Analysis)

```
┌─────────────────────────────────────────────────────────┐
│  ← Modify Selection          [Export CSV] [Export JSON] │
├─────────────────────────────────────────────────────────┤
│  Madrid | Barcelona | Sevilla                           │
│  Analysis: April weather trends (2015-2025)             │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─ April Temperatures (2015-2025) ─────────────────┐  │
│  │  25°C ┼───────────────────────────────────────    │  │
│  │  20°C ┼─────╱╲───╱╲──╱╲╲───╱╲─────╱╲───  2015   │  │
│  │  15°C ┼───╱────╱────╱─────╱────╱─────╱─  2016   │  │
│  │  10°C ┼─────────────────────────────────  ...    │  │
│  │   5°C ┼─────────────────────────────────  2025   │  │
│  │       └─────────────────────────────────          │  │
│  │        [Trend: +0.3°C per year]                   │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  ┌─ April Statistics Summary ──────────────────────┐  │
│  │              Madrid   Barcelona   Sevilla         │  │
│  │  10yr Avg    16.2°C    17.1°C      19.8°C        │  │
│  │  Warmest yr  18.9°C    19.5°C      22.1°C (2022) │  │
│  │  Coldest yr  13.8°C    15.2°C      17.3°C (2017) │  │
│  │  Avg Precip  42 mm     38 mm       35 mm         │  │
│  │  Rainy days  7 days    8 days      5 days        │  │
│  │  Trend       ↑warming  ↑warming    ↑warming      │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  ┌─ Insights & Recommendations ─────────────────────┐  │
│  │  📊 April has warmed by 3°C over the last decade  │  │
│  │  ☀️ 2022 was the warmest April in 10 years        │  │
│  │  🌧️ Average 5-8 rainy days expected in April      │  │
│  │  ✅ Barcelona most stable (lowest variance)       │  │
│  │  📈 All locations show warming trend              │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  [Show Year-by-Year Detail ▼] [Show Precipitation ▼]   │
└─────────────────────────────────────────────────────────┘
```

## Technical Architecture (Simple Web App)

### Frontend (HTML + JavaScript)

- **HTML5** for structure
- **Tailwind CSS** via CDN (`@tailwindcss/browser@4`) for styling and responsive design
- **Vanilla JavaScript** (or lightweight library like Chart.js for visualizations)
- **Leaflet.js** for interactive maps
- **LocalStorage** for saving recent searches/favorites

### Backend (PHP)

- **API Proxy**: PHP scripts to handle AEMET API requests
    - Manages API key securely (not exposed to client)
    - Handles AEMET's two-step data retrieval process:
        1. Request data URL
        2. Fetch actual data from returned URL
    - Caches frequent requests to reduce API calls
    - Rate limiting compliance

- **Endpoints**:
    - `/api/stations.php` - Get list of stations or station details
    - `/api/weather-data.php` - Get weather data for date range or multi-year periods
    - `/api/nearest-station.php` - Find nearest station to coordinates
    - `/api/longitudinal-data.php` - Get same period across multiple years (e.g., all Aprils from 2015-2025)
    - `/api/trend-analysis.php` - Calculate trends and statistics for multi-year data

### Data Flow

```
User → Frontend → PHP Backend → AEMET API
         ↓                           ↓
    Visualization ← Data Processing ← JSON Response
```

## AEMET API Integration

### Key API Endpoints to Use

1. **Inventario de Estaciones** (`/api/valores/climatologicos/inventarioestaciones/todasestaciones`)
    - Get list of all weather stations with coordinates

2. **Datos Observación** (`/api/observacion/convencional/todas` or `/api/observacion/convencional/datos/estacion/{idema}`)
    - Recent observational data (last 24h)

3. **Climatologías Diarias** (`/api/valores/climatologicos/diarios/datos/fechaini/{start}/fechafin/{end}/estacion/{idema}`)
    - Historical daily climate data for date ranges

4. **Normales Climatológicas** (`/api/valores/climatologicos/normales/estacion/{idema}`)
    - 1991-2020 climate normals for comparison

### API Authentication

- Requires API key in header: `api_key: YOUR_KEY`
- Users must register at https://opendata.aemet.es/ to get their key
- App can either:
    - Ask user to provide their own API key (stored in browser)
    - Use a server-side key (PHP manages this securely)

## Additional Features (Nice to Have)

### V1 (MVP)

- [x] Station selection (map + search)
- [x] Time range selection (both recent and longitudinal)
- [x] Longitudinal analysis (same period across multiple years)
- [x] Basic charts (temperature, precipitation with multi-year overlay)
- [x] Trend calculations and indicators
- [x] Simple statistics with year-over-year comparison
- [x] CSV export

### V2 (Future)

- [ ] Weather alerts integration
- [ ] Precipitation radar overlay on map
- [ ] Advanced statistical analysis (probability distributions, confidence intervals)
- [ ] Save favorite station combinations and queries
- [ ] Share analysis via URL
- [ ] Mobile-responsive design improvements
- [ ] Download charts as images
- [ ] Seasonal pattern detection
- [ ] "Best time to visit" recommendations based on user preferences

### V3 (Advanced)

- [ ] Machine learning predictions based on historical patterns
- [ ] Climate change impact visualization (comparing 1990-2000 vs 2010-2020)
- [ ] Extreme weather events timeline and frequency analysis
- [ ] Integration with other data sources (air quality, UV index)
- [ ] Custom alerts ("notify me when April 2026 data is available")
- [ ] Interactive scenario builder ("What if I only want temps between 20-25°C?")

## Design Principles

- **Simple & Clean**: Focus on data visualization, minimize clutter
- **Responsive**: Works on desktop, tablet, and mobile
- **Fast**: Cache data when possible, show loading states
- **Educational**: Help users understand weather patterns and make informed decisions
- **Accessible**: Clear labels, color-blind friendly palettes
- **Insight-Driven**: Not just data display, but actionable insights ("April is getting warmer", "Barcelona is your best bet for stable weather")
- **Flexible Time Analysis**: Support both short-term (24h) and long-term (10+ years) analysis equally well

## Color Scheme (Tailwind CSS)

- **Primary**: Blue tones (sky-500, blue-600) for water, sky theme
- **Temperature**: Custom gradient using Tailwind's color system
    - Hot: red-500 to orange-500
    - Cold: blue-400 to cyan-500
- **Precipitation**: Blue shades (blue-100 to blue-700)
- **Wind**: Gray tones (gray-400 to gray-600)
- **Backgrounds**: Light neutrals (gray-50, slate-100) for readability
- **Custom theme colors** defined in `@theme` for brand-specific colors

## File Structure (Proposed)

```
spanish-weather-explorer/
├── index.html              # Main page (includes Tailwind CSS via CDN)
├── js/
│   ├── app.js             # Main application logic
│   ├── map.js             # Map interactions
│   ├── charts.js          # Chart rendering
│   └── api.js             # API communication
├── php/
│   ├── config.php         # API key and settings
│   ├── stations.php       # Station data endpoint
│   ├── weather-data.php   # Weather data endpoint
│   ├── longitudinal-data.php  # Multi-year same-period data
│   ├── trend-analysis.php     # Statistical analysis and trends
│   └── cache/             # Data cache directory
├── assets/
│   └── images/            # Icons, logos
└── IDEA.md                # This document

Note: Tailwind CSS is loaded via CDN, no separate CSS files needed.
Custom theme colors can be defined in the HTML using <style type="text/tailwindcss">.
```

## Success Metrics

- Users can successfully select stations and view data
- Both 24-hour recent data and 10-year longitudinal analysis work smoothly
- Charts load within 2 seconds even for multi-year data
- Trend calculations are accurate and meaningful
- Users receive actionable insights ("Best month for your trip: May")
- Data is accurate and matches AEMET official sources
- Mobile experience is smooth
- Users can export and share their analyses
- Tool helps users make confident decisions about travel, events, and planning

---

## Notes for Implementation

1. AEMET API has a two-step process: first call returns a URL, second call to that URL gets data
2. API rate limits should be respected (use caching) - especially important for multi-year queries
3. Some historical data might not be available for all stations
4. Date formats in API: ISO 8601 (YYYY-MM-DDTHH:MM:SSUTC)
5. Station IDs (idema) are the unique identifiers
6. Not all stations have all metrics - handle missing data gracefully
7. **Longitudinal queries**: For multi-year analysis, backend should batch requests efficiently (e.g., request all Aprils 2015-2025 in parallel)
8. **Caching strategy**: Cache multi-year historical data aggressively (it won't change), but recent data (24h) should have shorter cache times
9. **Performance**: Consider pre-calculating common trend analyses or using server-side computation for statistical analysis
10. **Data aggregation**: When showing "April across 10 years", aggregate daily data into meaningful monthly statistics on the backend
