# SumateraMall Semantic Explorer

SumateraMall is a bilingual Laravel application for discovering malls and stores in Sumatra's 10 provinces. The application queries an Apache Jena Fuseki RDF graph with SPARQL, uses Google Maps for maps and road routing, and can ground Gemini answers in bounded RDF results.

## Stack and prerequisites

- PHP 8.3+ and Composer
- MySQL 8.0+ (or MariaDB with compatible JSON and stored-program support)
- Node.js and npm
- Python 3 with pip for merging the supplied RDF data
- Java to run the bundled Apache Jena Fuseki distribution
- Google Cloud project with billing enabled for Google Maps APIs
- Gemini API key (optional; rule-based search replies remain available without one)

The project uses Laravel 13. Keep the framework versions in `composer.json` when installing dependencies.

## Local setup (Windows / Laragon)

Create a MySQL database and configure the local environment. From PowerShell in the project directory:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+$' -Quiet)) { php artisan key:generate }
npm install
php artisan migrate
npm run build
```

Set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` for the database you created. Do not copy `.env.example` over an existing `.env`; keep credentials in `.env`, which must not be committed.

### Configure Google Maps

1. In Google Cloud Console, enable **Maps JavaScript API** and **Routes API** and enable billing.
2. Create a browser key restricted by HTTP referrer and restrict it to Maps JavaScript API. Include the local hostnames you use (for example `localhost` and `127.0.0.1`) and the deployed site's exact referrers.
3. Create a separate server key restricted to Routes API and to the server's outbound IP where possible.
4. Put the keys in `.env`:

```dotenv
GOOGLE_MAPS_BROWSER_KEY=your-restricted-browser-key
GOOGLE_MAPS_SERVER_KEY=your-restricted-server-key
GOOGLE_MAPS_ROUTES_URL=https://routes.googleapis.com/directions/v2:computeRoutes
```

The browser key is used only to load maps and geocode a mall after the visitor requests it. Mall addresses are not bulk-geocoded on page load. The server key is used by Laravel for route planning and is never sent to the browser. The route planner accepts mall addresses, so RDF coordinates are not required. Check Google Cloud quota, billing, and API restrictions if a map or route cannot load.

### Configure Gemini (optional)

Create an API key in Google AI Studio and add it to `.env`:

```dotenv
GEMINI_API_KEY=your-server-side-gemini-key
GEMINI_MODEL=gemini-2.5-flash
GEMINI_API_ENDPOINT=https://generativelanguage.googleapis.com/v1beta/models
```

Keep the key server-side. When configured, Gemini receives at most 20 filtered RDF result rows and is instructed to answer only from those records. Without a key, the existing rule-based chatbot is used and the interface notes that AI is not configured. Requests are rate-limited.

## Merge and import the RDF dataset

The source files are under `data-sumateramall\datamall`. The merger preserves source triples, normalizes mall/store resources into the application's vocabulary, and writes `storage\app\sumateramall-merged.ttl`. Repairs are performed in memory; source files are not changed.

```powershell
python -m pip install -r scraper\requirements.txt
python scraper\merge_dataset.py
```

Start Fuseki in a separate terminal:

```powershell
Set-Location fuseki
.\fuseki-server.bat --update --mem /sumateramall
```

Back in the project root, add the merged graph to Fuseki's default graph:

```powershell
curl.exe -X POST -H "Content-Type: text/turtle" --data-binary "@storage\app\sumateramall-merged.ttl" "http://localhost:3030/sumateramall/data?default"
```

The default example uses an in-memory Fuseki dataset, which is cleared when Fuseki stops. For persistent use, configure Fuseki with a persistent TDB dataset and keep the `/sumateramall/query` and `/sumateramall/update` endpoints. `POST` adds triples to an existing graph; for a clean import, use a newly created dataset or back up and deliberately replace the target graph. Do not clear an existing dataset containing user data.

Configure non-default Fuseki endpoints in `.env`:

```dotenv
FUSEKI_URL=http://localhost:3030/sumateramall/query
FUSEKI_UPDATE_URL=http://localhost:3030/sumateramall/update
```

## MySQL academic DBMS components

After migrations, run `database\sql\sumateramall-academic-components.sql` once against the configured MySQL database. The script is repeatable and adds five components based on the existing `chat_logs` table:

1. `trg_chat_logs_validate_insert` validates chat-session/message data and fills a missing intent with `unknown`.
2. `fn_chat_count_by_session` returns the number of messages for a chat session.
3. `sp_get_chat_history` returns a session's latest chat entries with a validated result limit.
4. `vw_chat_intent_activity` reports daily interaction and session counts by intent.
5. `idx_chat_logs_session_created` supports session history lookups ordered by creation time.

Run the SQL with MySQL Workbench or a MySQL CLI client after selecting the application database. The DB user needs trigger, routine, and index privileges. The web application does not implement parking reservations.

## Run the application

In separate terminals:

```powershell
php artisan serve
npm run dev
```

Open `http://127.0.0.1:8000`. The language and light/dark theme choices persist in browser storage.

## Features and data limits

- The shared navigation contains Home, Team, and a store search. Store search ranks typo-tolerant matches across store, category, mall, city, floor, and address fields; result sets can be refined with filter bubbles.
- Mall, map, planner, budget, recommendation, and mall-detail views provide contextual province, city, category, rating, price, or operating-status filters where those values are available.
- Search, semantic recommendations, mall comparisons, and the chatbot's grounded context use Fuseki/SPARQL.
- Google Maps displays mall locations, geocoding an address only after a visitor chooses a mall. It also displays a computed route; a Google Maps directions link remains available if the browser map key is not configured.
- The route planner uses Google Routes API for optimized multi-stop driving routes, with a maximum of eight malls. Configure the server key to calculate routes.
- Nearby mosques, ATMs, and restrooms are fetched from OpenStreetMap Overpass only when requested. Public Overpass endpoints can be rate-limited or unavailable; configure a suitable service and user agent for production.
- Budget values are estimates derived from RDF price levels, or configurable fallback values (`BUDGET_PRICE_LEVEL_1..4`); they are not live Google prices.
- PDF and ICS itinerary exports require a route to be planned. Calendar events use a simple two-hour duration per mall.
- Accessibility is reported only when explicitly represented in RDF. Missing data does not mean unavailable.
- Crowd prediction is not enabled because no reliable crowd-data source is configured.

## Validation

```powershell
php artisan test
vendor\bin\pint --test
npm run build
python -m unittest discover -s scraper
```
