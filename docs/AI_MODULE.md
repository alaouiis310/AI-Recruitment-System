# AI CV evaluation module (Gemini)

## What it does
- `POST /api/evaluate-cv`  one CV vs a job offer -> score 1-100 + details
- `POST /api/rank-cvs`     many CVs vs a job offer -> ranked top list
- Nothing is saved. Store the returned JSON in your own tables.

## Files to integrate
- `app/Services/GeminiService.php`, `app/Services/CvScoring.php`
- `app/Http/Controllers/AiRecruitmentController.php`
- `routes/api.php` (3 routes), `config/services.php` (`gemini` block), `.env.example`

## Setup
1. In `.env`: `GEMINI_API_KEY=...` and `GEMINI_MODEL=gemini-3.5-flash`
   (`gemini-2.5-flash` shuts down on 2026-10-16, so do not use it).
2. php.ini: raise `upload_max_filesize` and `post_max_size` (default 8M is too small for many CVs).
3. Run tests: `php artisan test`

## POST /api/rank-cvs  (multipart/form-data)
| field | notes |
|---|---|
| `cv_files[]` | 1-20 PDFs, max 10 MB each |
| `job_description` | required text |
| `job_title` | optional |
| `top` | optional. Only return the best N. Default: everyone is ranked |

Response:
```json
{
  "status": "success",
  "total_received": 3, "total_evaluated": 3, "top": 2,
  "ranking": [
    {
      "rank": 1, "cv_file": "bob.pdf", "candidate_name": "Bob",
      "match_score": 95,
      "score_breakdown": {"skills_score": 95, "experience_score": 95, "education_score": 95, "additional_score": 95},
      "eligibility_status": "Strong match",
      "meets_mandatory_requirements": true,
      "matching_skills": ["PHP"], "missing_skills": [],
      "strengths": ["Strong PHP"], "weaknesses": ["No cloud experience"], "red_flags": [],
      "summary_feedback": "..."
    }
  ],
  "failed": [ {"cv_file": "x.pdf", "error": "..."} ]
}
```

## How the score works
Gemini rates 4 criteria (0-100): skills 40%, experience 35%, education 15%, extras 10%.
The final 1-100 score is computed in PHP (`CvScoring::WEIGHTS`), so weights are easy to tune.

## Notes for the integrator
- CVs are evaluated one after another (~5-15 s each). For big batches, call `GeminiService::evaluateCv()`
  from a queued job per CV instead of the HTTP request.
- Endpoints are public. Put them behind `auth:sanctum`.
- CVs are sent to Google. Use a paid-tier Gemini key for real candidate data (check Google's data terms).
- The AI is a shortlisting aid, keep a human making the hiring decision.
