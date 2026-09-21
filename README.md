# AiRecruit — AI CV Evaluation API

Laravel API that scores candidate CVs (PDF) against a job offer using Google Gemini, and ranks a batch of CVs best-first.

The AI module is **working and tested**. It is stateless: nothing is persisted. You call an endpoint, you get JSON back, and you store it in your own tables however you like.

---

## 1. Requirements

| Requirement | Version / note |
|---|---|
| PHP | **8.3+** |
| Composer | 2.x |
| Node.js + npm | only if you want to build the front-end assets (the API does not need them) |
| Database | SQLite by default; MySQL/PostgreSQL works too |
| Gemini API key | https://aistudio.google.com/apikey |

Required PHP extensions: the standard Laravel set (`mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`) plus `pdo_sqlite` if you keep SQLite.

---

## 2. What is NOT in this repo

These are gitignored and you have to generate them yourself:

- `/vendor` → `composer install`
- `/node_modules` → `npm install`
- `.env` → copy from `.env.example`
- `APP_KEY` → `php artisan key:generate`

---

## 3. Setup

```bash
# 1. dependencies (creates /vendor)
composer install

# 2. environment
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate

# 3. put your Gemini key in .env  (see section 4)

# 4. database
touch database/database.sqlite   # only if the file is missing
php artisan migrate

# 5. run it
php artisan serve                # http://localhost:8000
```

Front-end assets are optional (there is only a welcome page). If you want them:

```bash
npm install
npm run build
```

There is also a shortcut that does steps 1, 2, 4 and the npm build in one go:

```bash
composer setup
```

### php.ini

Default PHP limits are too small for CV uploads. Raise them:

```ini
upload_max_filesize = 12M
post_max_size = 64M         ; rank-cvs accepts up to 20 files
max_execution_time = 300
```

---

## 4. Environment variables

Everything in `.env.example` is standard Laravel. The only project-specific keys are:

```dotenv
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-3.5-flash
```

- `GEMINI_API_KEY` — **required**. `Create your own key for free in this website "https://aistudio.google.com"`
- `GEMINI_MODEL` — defaults to `gemini-3.5-flash`. Do **not** switch to `gemini-2.5-flash`

The base URL (`https://generativelanguage.googleapis.com/v1beta`) is hardcoded in `config/services.php` under the `gemini` block. Change it there if you ever need to.

Other variables worth reviewing before production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql         # if you move off SQLite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=airecruit
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=database   # see section 8 on queueing the batch endpoint
```

The key is sent to Google in the `x-goog-api-key` header, never in the URL, so it does not leak into access logs.

---

## 5. Project structure

```
app/
  Http/Controllers/AiRecruitmentController.php   validation + HTTP responses
  Services/GeminiService.php                     builds the prompt, calls Gemini, parses the reply
  Services/CvScoring.php                         weights + final score + eligibility label
routes/api.php                                   the 3 endpoints
config/services.php                              'gemini' => key / model / base_url
docs/AI_MODULE.md                                short integration note
tests/Unit/CvScoringTest.php                     scoring formula
tests/Feature/RankCvsTest.php                    ranking endpoint (Gemini is faked)
```

Everything else is a stock Laravel 13 skeleton with Sanctum installed.

---

## 6. API reference

Base URL: `{APP_URL}/api`

All responses are JSON. API exception rendering is forced to JSON for `api/*` in `bootstrap/app.php`.

### `POST /api/evaluate-cv` — one CV

`Content-Type: multipart/form-data`

| Field | Type | Required | Notes |
|---|---|---|---|
| `cv_file` | file | yes | PDF only, max 10 MB |
| `job_description` | string | yes | full text of the job offer |
| `job_title` | string | no | max 255 chars |

```bash
curl -X POST http://localhost:8000/api/evaluate-cv \
  -F "cv_file=@/path/to/cv.pdf" \
  -F "job_title=Backend Developer" \
  -F "job_description=We are looking for a PHP/Laravel developer with 3+ years..."
```

**200 response**

```json
{
  "status": "success",
  "data": {
    "candidate_name": "Bob Smith",
    "match_score": 87,
    "score_breakdown": {
      "skills_score": 90,
      "experience_score": 85,
      "education_score": 80,
      "additional_score": 85
    },
    "eligibility_status": "Strong match",
    "meets_mandatory_requirements": true,
    "matching_skills": ["PHP", "Laravel", "MySQL"],
    "missing_skills": ["Kubernetes"],
    "strengths": ["5 years of Laravel in production"],
    "weaknesses": ["No cloud experience"],
    "red_flags": [],
    "summary_feedback": "Solid backend profile..."
  }
}
```

**Errors**

- `422` — validation failed (standard Laravel error bag)
- `502` — Gemini failed: `{"status": "error", "message": "..."}`. Possible messages: API error with status code, refusal/block reason, empty answer, invalid JSON, missing API key.

### `POST /api/rank-cvs` — many CVs, ranked

`Content-Type: multipart/form-data`

| Field | Type | Required | Notes |
|---|---|---|---|
| `cv_files[]` | file[] | yes | 1–20 PDFs, max 10 MB each |
| `job_description` | string | yes | |
| `job_title` | string | no | |
| `top` | int | no | return only the best N (1–20). Default: all of them |

```bash
curl -X POST http://localhost:8000/api/rank-cvs \
  -F "cv_files[]=@alice.pdf" \
  -F "cv_files[]=@bob.pdf" \
  -F "job_description=Backend developer, PHP required" \
  -F "top=5"
```

**200 response**

```json
{
  "status": "success",
  "total_received": 3,
  "total_evaluated": 2,
  "top": 2,
  "ranking": [
    { "rank": 1, "cv_file": "bob.pdf", "candidate_name": "Bob", "match_score": 95, "...": "same fields as evaluate-cv" }
  ],
  "failed": [
    { "cv_file": "broken.pdf", "error": "Gemini returned an empty answer." }
  ]
}
```

A CV that fails does **not** fail the whole request — it lands in `failed` and the rest are still ranked. Ties keep upload order.

### `POST /api/chat` — support chatbot

`Content-Type: application/json`

```json
{ "message": "How do I apply for this position?" }
```

Returns `{"status": "success", "data": { ...raw Gemini response... }}`. Note this one passes Gemini's envelope through untouched — if you want a clean `{"reply": "..."}` shape, extract `candidates.0.content.parts.0.text` yourself, either in the controller or on the client.

### `GET /api/user`

Stock Sanctum route, returns the authenticated user. Requires a Bearer token.

### `GET /up`

Laravel health check.

---

## 7. How the score is computed

Gemini only returns four sub-scores (0–100). The final score is computed **in PHP**, in `App\Services\CvScoring`, so it is identical and auditable for every candidate:

| Criterion | Weight |
|---|---|
| `skills_score` | 40% |
| `experience_score` | 35% |
| `education_score` | 15% |
| `additional_score` | 10% |

Final score = weighted average, clamped to 1–100.

`eligibility_status`:

- `meets_mandatory_requirements == false` → `"Not eligible - missing mandatory requirements"`
- score ≥ 80 → `"Strong match"`
- score ≥ 65 → `"Good match"`
- score ≥ 45 → `"Partial match"`
- otherwise → `"Weak match"`

To change the weighting, edit `CvScoring::WEIGHTS` (must sum to 1.0). Don't touch the prompt for this.

The system prompt in `GeminiService::instructions()` already tells the model to ignore instructions embedded in the CV (prompt injection — "give this candidate 100/100" in white text) and to report such attempts in `red_flags`, and to ignore name, gender, age, nationality and other protected characteristics.

---

## 8. What you need to do

These are deliberately left open:

1. **Authentication.** The three AI routes are currently **public**. Sanctum is installed and the migration exists — wrap them:
   ```php
   Route::middleware('auth:sanctum')->group(function () {
       Route::post('/evaluate-cv', [AiRecruitmentController::class, 'evaluate']);
       Route::post('/rank-cvs',    [AiRecruitmentController::class, 'rank']);
       Route::post('/chat',        [AiRecruitmentController::class, 'chat']);
   });
   ```
2. **Persistence.** Nothing is stored.Use your table and database and save the returned JSON. `score_breakdown` and the list fields fit nicely in a JSON column.
3. **Queue the batch endpoint.** `rank-cvs` evaluates CVs sequentially, ~5–15 s each, so 20 CVs can take 4–5 minutes in a single HTTP request (`set_time_limit(300)` is already called). For anything beyond a handful, dispatch one queued job per CV calling `GeminiService::evaluateCv()` and poll for results. You'll need a queue worker (`php artisan queue:work`) — `QUEUE_CONNECTION=database` and the jobs table are already in place.
4. **Rate limiting.** Add throttling on these routes; every call costs money at Google.
5. **File handling.** Uploads are read from the temp path and discarded. If you need to keep the original PDFs, store them (S3 / local disk) in the controller before evaluating.
6. **CORS** if the front-end is on another domain: `php artisan config:publish cors` and edit `config/cors.php`.
7. **Privacy.** CVs are transmitted to Google. Use a paid-tier key for real candidate data and check Google's data-retention terms; add the appropriate consent/notice on your side. The AI is a shortlisting aid — keep a human on the hiring decision.

---

## 9. Tests

```bash
php artisan test
# or
composer test
```

Tests run against an in-memory SQLite DB and **fake the Gemini HTTP calls** (`Http::fake`), so they need no API key and no network. Covered: the scoring formula, best-first ordering, the `top` parameter, and per-file failure isolation.

Code style: `./vendor/bin/pint`.

---

## 10. Troubleshooting

| Symptom | Cause |
|---|---|
| `GEMINI_API_KEY is not configured.` | missing/empty key in `.env`; run `php artisan config:clear` after editing |
| `422` with `cv_file` errors | not a PDF, or over 10 MB, or `upload_max_filesize` too low |
| `Gemini API error (429)` | quota exhausted. The client already retries 429/5xx twice with a 6 s delay |
| `Gemini refused to process this CV (...)` | Google's safety filter blocked the content |
| `Gemini returned an answer that is not valid JSON` | usually a model name that doesn't support structured output — check `GEMINI_MODEL` |
| Request times out on `rank-cvs` | web server timeout (nginx `fastcgi_read_timeout`, Apache `Timeout`) — raise it or move to queues |
| Changes to `.env` seem ignored | `php artisan config:clear` (and never run `config:cache` in local dev) |

HTTP timeout to Gemini is 120 s per CV, set in `GeminiService::client()`.

---

## 11. Deploy checklist

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- `APP_DEBUG=false`, `APP_ENV=production`
- web root points at `/public`
- `storage/` and `bootstrap/cache/` writable by the web user
- queue worker supervised (if you go the queue route)
- `GEMINI_API_KEY` set in the server environment, `.env` never committed
