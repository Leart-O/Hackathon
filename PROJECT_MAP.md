# Apollo AI (Hackathon) — Complete Project Map

> **Generated from codebase analysis** of `C:\xampp\htdocs\Hackathon`  
> **Evidence-based:** All claims below refer to files present in the repository unless marked *Unknown* or *Not in repo*.

---

## Project Overview

**Apollo AI** is a hackathon-style **PHP + MySQL web application** for AI-assisted learning. Users sign up, configure a personal **Hugging Face** API token, then use:

- **AI chat** with persisted conversation history
- **Quiz generator** (multiple-choice, client-graded)
- **Flashcard generator** (flip-card UI)

The product is branded **“Apollo AI”** in HTML titles and navigation (`index.php`, `main.php`, etc.). The repository folder is named `Hackathon`; the MySQL database is named `hackathon`.

There is **no PHP framework** (no Laravel, Symfony, Slim, etc.), **no Composer autoloading**, **no central router**, and **no ORM**. Each `.php` file at the document root is both an **entry point** and, for most pages, a **combined controller + view**.

| Attribute | Value |
|-----------|--------|
| Language | PHP (session-based) |
| Database | MySQL / MariaDB via PDO |
| AI provider | Hugging Face **Router** — OpenAI-compatible chat completions |
| Auth | PHP sessions (`$_SESSION['user_id']`) |
| Frontend | Bootstrap 5, jQuery, inline + page-specific JavaScript |
| Deployment target | XAMPP (`localhost`, `db.php` uses `root` with empty password) |

**Domain:** EdTech / study assistant — academic topics only (enforced via system prompts in `main.php`).

---

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Browser (HTML + JS)                              │
│  index.php  login.php  signup.php  main.php  quiz.php  flashcards.php   │
│       │          │          │          │         │            │          │
│       │          └──────────┴──────────┴─────────┴────────────┘          │
│       │                    session: user_id                               │
└───────┼───────────────────────────────────────────────────────────────────┘
        │ POST (form / fetch)                    │
        ▼                                        ▼
┌───────────────┐                      ┌─────────────────────┐
│   db.php      │◄─────────────────────│  huggingface_api.php │
│  PDO → MySQL  │                      │ callHuggingFaceAPI() │
└───────────────┘                      └──────────┬──────────┘
        │                                           │ HTTPS POST
        ▼                                           ▼
┌───────────────┐                      ┌─────────────────────────────┐
│ hackathon DB  │                      │ router.huggingface.co       │
│ users         │                      │ /v1/chat/completions        │
│ conversations │                      └─────────────────────────────┘
│ messages      │
└───────────────┘

External (client-side only):
  - Pixabay API (index.php) — topic images
  - CDN: marked, MathJax, Google Fonts, polyfill.io
```

**Architectural style:** Classic **page-oriented PHP** with a single shared helper (`callHuggingFaceAPI`) and one DB bootstrap (`db.php`).

**Not present:** API versioning, middleware stack, queue workers, cron, WebSockets, REST resource naming, dependency injection, unified validation layer, automated test suite.

---

## Repository Structure

```
Hackathon/
├── index.php              # Public landing + blocked AI POST for guests
├── login.php              # Email/password login
├── signup.php             # Registration
├── logout.php             # Session destroy
├── main.php               # Authenticated chat dashboard
├── quiz.php               # Quiz generation + explain JSON API + UI
├── flashcards.php         # Flashcard generation JSON API + UI
├── settings.php           # HF token + model per user
├── db.php                 # PDO connection singleton (inline $db)
├── huggingface_api.php    # Only server-side “service” function
├── migrate.php            # One-off ALTER TABLE for HF columns
├── debug_db.php           # CLI-style DB diagnostic (plain text)
├── test_huggingface.php   # CLI integration test
├── test_token.php         # Browser HF endpoint experiments (logged-in)
├── QUICKSTART.php         # Redirects to index.php (comments only)
├── hackathon.sql          # Schema + sample data dump
├── api_debug.log          # Append-only API debug log (runtime)
├── css/                   # bootstrap.min.css, bootstrap-icons.css, style.css
├── js/                    # jQuery, Bootstrap, custom.js, main.js, click-scroll.js
└── *.md                   # Setup/integration docs (not executed at runtime)
```

**Referenced but not in repository (broken assets):**

- `images/` — referenced extensively in `index.php` (e.g. `images/topics/...`, `images/faq_graphic.jpg`). *No `images/` directory exists in the repo scan.*

**No:** `.htaccess`, `composer.json`, `.env`, `vendor/`, `tests/`, `src/`, namespaces, PSR-4 autoload.

---

## Execution / Boot Flow

### Web server entry

Under XAMPP, the app is served from `http://localhost/Hackathon/` (document root subdirectory). Apache maps each URL to a matching `.php` file directly.

### Per-request bootstrap pattern

| File | Session | Requires `db.php` | Requires `huggingface_api.php` |
|------|---------|-------------------|--------------------------------|
| `index.php` | `session_start()` at top | No | No |
| `login.php` | On successful POST only | Yes | No |
| `signup.php` | On successful POST only | Yes | No |
| `main.php` | Yes | Yes | Yes |
| `quiz.php` | Yes | Yes (after auth) | Yes |
| `flashcards.php` | Yes | Yes (POST branch only) | Yes |
| `settings.php` | Yes | Yes | No |
| `logout.php` | Yes | No | No |
| `migrate.php` | No | Yes | No |
| `debug_db.php` | No | Yes | No |
| `test_huggingface.php` | No | Yes | Yes |
| `test_token.php` | Yes (if missing) | Yes | No |

### Database connection (`db.php`)

```php
$db = new PDO('mysql:host=localhost;dbname=hackathon;charset=utf8', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

On failure: `die('Database connection failed: ...')` — no retry, no env-based config.

### Schema setup

1. Import `hackathon.sql` via phpMyAdmin or CLI, **or**
2. Run `migrate.php` to add `huggingface_token` and `huggingface_model` columns if upgrading an older `users` table.

---

## Request Lifecycle

There is **no global front controller**. Lifecycle is **per file**:

1. **Optional** `session_start()`
2. **Optional** auth guard: `if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }`
3. **Early exit handlers** for POST JSON APIs (set `Content-Type: application/json`, echo JSON, `exit`)
4. **Business logic** (queries, `callHuggingFaceAPI`, redirects)
5. **HTML output** (PHP templates inline in same file)

### Redirect-after-POST (PRG)

`main.php` uses redirect after posting a chat message:

```
POST main.php (question) → INSERT messages → call HF → INSERT assistant → 
  header("Location: main.php?conversation_id=$id")
```

Rename/delete conversation also redirect to `main.php` or `main.php?conversation_id=...`.

### JSON AJAX pattern

`quiz.php`, `flashcards.php`, and guest `index.php` POST use:

- Client: `fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(...) })`
- Server: `header('Content-Type: application/json'); echo json_encode(...); exit;`

---

## API Reference

There is **no separate REST API layer**. “Endpoints” are **POST handlers on the same PHP pages** that return JSON.

### Summary table

| URL | Method | Auth | Content-Type | Purpose |
|-----|--------|------|--------------|---------|
| `index.php` | POST | No | `application/x-www-form-urlencoded` → JSON | Guest prompt — always rejects |
| `main.php` | POST | Yes | `application/x-www-form-urlencoded` | Chat, rename, delete, import (HTML form) |
| `main.php` | GET | Yes | HTML | View conversation |
| `quiz.php` | POST | Yes | form → JSON | Generate quiz OR explain answer |
| `flashcards.php` | POST | Yes | form → JSON | Generate flashcards |
| `settings.php` | POST | Yes | form → HTML | Save token or model |
| `login.php` | POST | No | form → redirect | Login |
| `signup.php` | POST | No | form → redirect | Register |

---

### `index.php` — Guest prompt (blocked)

**Trigger:** `POST` with `prompt` set (from landing page JS).

**Request body:**

| Field | Type | Required |
|-------|------|----------|
| `prompt` | string | Yes |

**Response** (`application/json`):

```json
{
  "success": false,
  "error": "Please log in or sign up to use the AI features."
}
```

**HTTP status:** 200 (default PHP; no explicit status codes set anywhere in project).

**Note:** Front-end JS in `index.php` (lines 857–892) expects on success: `json.result`, `json.conversation_data` for “Read more” → `main.php`. **The server never returns these fields** — guest flow always errors. Even logged-in users on `index.php` still get the same error because there is no branch checking `$_SESSION['user_id']` for AI.

---

### `main.php` — Chat (HTML form)

**Trigger:** `POST` with non-empty `question`.

| Field | Type | Description |
|-------|------|-------------|
| `question` | string | User message |
| `conversation_id` | int \| empty | Existing conversation; if empty, creates new row |

**Flow:**

1. `INSERT INTO conversations (user_id)` if no `conversation_id`
2. `INSERT INTO messages` role `user`
3. Load all messages for conversation
4. Build `$api_messages` with system prompt + history
5. `callHuggingFaceAPI($api_messages, $user_id, $db, 500)`
6. `INSERT INTO messages` role `assistant`
7. Redirect to `GET main.php?conversation_id={id}`

**Other POST actions on `main.php`:**

| Fields | Action |
|--------|--------|
| `rename_conversation_id`, `new_title` | `UPDATE conversations SET title` (owner check via `user_id`) |
| `delete_conversation_id` | `DELETE FROM conversations` (cascade deletes messages) |
| `conversation_data` | JSON array of `{role, content}` — see *Known bug* below |

---

### `quiz.php` — Generate quiz

**Trigger:** `POST` with `summary` (no `action` field).

| Field | Type |
|-------|------|
| `summary` | string — study topic text |

**Success response:**

```json
{
  "quiz": [
    {
      "question": "...",
      "options": ["A text", "B text", "C text", "D text"],
      "answer": "A"
    }
  ]
}
```

`answer` is a single letter **A–D** indexing into `options`.

**Error response:**

```json
{ "error": "message string" }
```

**AI prompt constraints** (`quiz.php` lines 56–59): System instructs model to output JSON wrapped in `<json>...</json>` tags. Server strips tags via regex, then `json_decode`. If decode fails, returns `error` with stripped text or generic message.

**`max_tokens`:** 1000 for generation.

---

### `quiz.php` — Explain correct answer

**Trigger:** `POST` with `action=explain`.

| Field | Type |
|-------|------|
| `action` | `"explain"` |
| `question` | string |
| `correct` | string — correct answer text |

**Success:**

```json
{ "explanation": "1-2 sentence explanation" }
```

**Error:**

```json
{ "error": "..." }
```

**`max_tokens`:** 150.

---

### `flashcards.php` — Generate flashcards

**Trigger:** `POST` with `summary`.

**Success:**

```json
{
  "cards": [
    { "question": "...", "answer": "..." }
  ]
}
```

**Error:** `{ "error": "..." }`

Same `<json>` extraction pattern as quiz. **`max_tokens`:** 1000.

---

### `callHuggingFaceAPI` — External API contract

**File:** `huggingface_api.php`  
**Function:** `callHuggingFaceAPI($messages, $user_id, $db, $max_tokens = 500)`

**Upstream request:**

```
POST https://router.huggingface.co/v1/chat/completions
Authorization: Bearer {users.huggingface_token}
Content-Type: application/json

{
  "model": "{users.huggingface_model}",
  "messages": [ { "role": "system|user|assistant", "content": "..." }, ... ],
  "max_tokens": <int>,
  "temperature": 0.7,
  "stream": false
}
```

**Returns:** `string` — assistant message content from `choices[0].message.content`.

**Throws:** `Exception` with human-readable messages for missing token, cURL errors, HTTP ≥ 400 (401, 404, 503, 429 mapped to hints).

**Side effect:** Appends request metadata to `api_debug.log` (endpoint, model, HTTP code, first 500 chars of response).

**Default model if DB null:** `mistralai/Mistral-7B-Instruct-v0.2` (in code); DB schema default is `Qwen/Qwen2.5-7B-Instruct`.

---

## Core Business Logic

### 1. Academic-only assistant (`main.php`)

Every chat request prepends a **system message** restricting answers to school/academic/AI-education topics. Off-topic queries should receive exactly:

> "Sorry, I can only answer questions about academic topics."

This is **prompt-based enforcement only** — not validated server-side after the model responds.

### 2. Per-user AI credentials

Each user must store `huggingface_token` (and optionally `huggingface_model`) via `settings.php`. Without a token, all AI features throw and surface errors to the user.

### 3. Conversation ownership

- Conversations are listed with `WHERE user_id = $user_id`.
- Rename/delete use prepared statements with `user_id` check.
- **Fetching messages by `conversation_id`** uses interpolated SQL without verifying the conversation belongs to the current user — *authorization gap* (see Security Notes).

### 4. Quiz grading (client-side)

Grading happens entirely in browser JavaScript (`quiz.php` `renderQuiz` → Submit handler):

- Correct option index: `"ABCD".indexOf(q.answer.toUpperCase())`
- Compares to selected `data-idx` on `.quiz-option`
- Shows per-question feedback; wrong answers can request AI explanation via `action=explain`

**No server-side score persistence.**

### 5. Flashcards (client-side navigation)

Generated cards are held in JS array `flashcards`; UI supports flip, prev/next. **No DB persistence** for flashcard sets.

### 6. Guest vs authenticated AI

| Page | AI behavior |
|------|-------------|
| `index.php` | POST always returns login error |
| `main.php` | Full chat + DB persistence |
| `quiz.php`, `flashcards.php` | Require session |

### 7. Marketing content

`index.php` “Browse Topics” tabs (Biology, World History, etc.) are **static HTML** — not loaded from database and not linked to chat/quiz flows.

---

## Data Model / Database

**Database name:** `hackathon`  
**Engine:** InnoDB  
**Charset:** utf8mb4  

### ER diagram (logical)

```
users (1) ──────< (N) conversations (1) ──────< (N) messages
```

### Table: `users`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | int(11) | PK, AUTO_INCREMENT | |
| `username` | varchar(50) | UNIQUE, NOT NULL | |
| `email` | varchar(100) | UNIQUE, NOT NULL | Login identifier |
| `password_hash` | varchar(255) | NOT NULL | `password_hash(..., PASSWORD_BCRYPT)` |
| `created_at` | timestamp | DEFAULT current_timestamp() | |
| `huggingface_token` | varchar(500) | NULL | Plain text API token |
| `huggingface_model` | varchar(255) | DEFAULT `Qwen/Qwen2.5-7B-Instruct` | |

### Table: `conversations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | int(11) PK AI | |
| `user_id` | int(11) FK → users.id ON DELETE CASCADE | |
| `title` | varchar(255) | Default `'Untitled Conversation'` |
| `created_at` | timestamp | |

### Table: `messages`

| Column | Type | Notes |
|--------|------|-------|
| `id` | int(11) PK AI | |
| `conversation_id` | int(11) FK → conversations.id ON DELETE CASCADE | |
| `role` | enum('user','assistant') | |
| `content` | text | |
| `created_at` | timestamp | |

### ORM / query style

- **PDO** with mix of **prepared statements** (inserts, settings updates) and **string-interpolated queries** (several reads in `main.php`) — see Security Notes.

### Migrations / seeds

| Artifact | Role |
|----------|------|
| `hackathon.sql` | Full dump: CREATE TABLE, indexes, FKs, sample users/conversations/messages |
| `migrate.php` | `ALTER TABLE users ADD COLUMN huggingface_token ...` and `huggingface_model ... DEFAULT 'gpt2'` |

**Inconsistency:** `migrate.php` default model is `gpt2`; `hackathon.sql` default is `Qwen/Qwen2.5-7B-Instruct`.

### Sample data warning

`hackathon.sql` includes a real-looking `huggingface_token` for user id 3. **Treat as compromised if this dump was ever shared.**

---

## Key Classes and Functions

The codebase has **no PHP classes**. One shared function:

### `callHuggingFaceAPI($messages, $user_id, $db, $max_tokens = 500)`

**Location:** `huggingface_api.php`

**Call chain:**

```
main.php | quiz.php | flashcards.php | test_huggingface.php
    → callHuggingFaceAPI(...)
        → SELECT huggingface_token, huggingface_model FROM users WHERE id = ?
        → curl POST router.huggingface.co/v1/chat/completions
        → parse choices[0].message.content
        → return string (or throw Exception)
```

### JavaScript functions (client)

| Function | File | Role |
|----------|------|------|
| `renderQuiz(quiz)` | `quiz.php` inline | Build MCQ DOM, attach submit/explain handlers |
| `renderFlashcard()` | `flashcards.php` inline | Single-card flip UI |
| `showRenameForm` / `hideRenameForm` | `main.php` inline | Conversation title edit |
| jQuery IIFE | `js/custom.js` | Navbar collapse, smooth scroll, timeline animation |
| textarea auto-resize | `js/main.js` | Grow textareas on input |

---

## Important Flows End-to-End

### Flow A: User registration

```
GET signup.php
  → User submits username, email, password
POST signup.php
  → password_hash(PASSWORD_BCRYPT)
  → INSERT INTO users (username, email, password_hash)
  → session_start(); $_SESSION['user_id'] = lastInsertId()
  → redirect main.php
```

**Gaps:** No email verification; duplicate username/email causes PDO exception (not caught — likely white screen or error); **no HF token** required at signup — user must visit Settings before chat works.

---

### Flow B: Login

```
POST login.php
  → SELECT * FROM users WHERE email = ?
  → password_verify(password, password_hash)
  → session_start(); $_SESSION['user_id'] = id
  → redirect main.php
```

**Session variable:** Only `user_id` is stored — not username or email.

---

### Flow C: Chat message (authenticated)

```
GET main.php?conversation_id=N (optional)
  → Load conversations for sidebar
  → Load messages if conversation_id set

POST main.php (question, conversation_id)
  → Create conversation if needed
  → INSERT user message
  → Build messages array + system prompt
  → callHuggingFaceAPI(..., max_tokens=500)
  → INSERT assistant message (includes raw error text on exception)
  → redirect GET main.php?conversation_id=N
```

**Display:** Messages rendered with `htmlspecialchars` + `nl2br` — plain text, not Markdown (unlike landing page).

---

### Flow D: Quiz generation and study

```
GET quiz.php (auth check) → HTML + JS

User enters summary → POST quiz.php { summary }
  → HF with JSON-in-tags prompt
  → json_decode quiz array
  → { quiz: [...] } to browser

renderQuiz() → user selects options → Submit (client-only grading)

If wrong → "Show explanation" → POST quiz.php { action: explain, question, correct }
  → HF short explanation → display under question
```

---

### Flow E: Flashcards

```
POST flashcards.php { summary }
  → HF → { cards: [...] }

Client: renderFlashcard() cycle with flip / prev / next
```

---

### Flow F: Configure Hugging Face

```
GET settings.php → show masked token field + model field

POST huggingface_token → UPDATE users SET huggingface_token = ?

POST huggingface_model → UPDATE users SET huggingface_model = ?
```

Two **separate forms** — submitting one does not update the other field.

---

### Flow G: Landing page prompt (guest)

```
User submits #prompt-form
  → fetch POST index.php { prompt }
  → { success: false, error: "Please log in..." }
  → Error displayed

On hypothetical success (not implemented):
  → marked.parse(json.result), MathJax, Pixabay image fetch
  → "Read more" POST conversation_data to main.php
```

**Pixabay** (client-side, `index.php` line 863):

```
GET https://pixabay.com/api/?key=50494584-87d85c2a13020ebc5144f3a07&q={topic}&...
```

API key is **exposed in browser source**.

---

## External Dependencies and Integrations

### Server-side

| Service | Endpoint | Used by |
|---------|----------|---------|
| Hugging Face Router | `https://router.huggingface.co/v1/chat/completions` | `huggingface_api.php` |

**Historical note:** `api_debug.log` and `CHANGELOG.md` reference older providers (OpenAI, Together.ai, `api-inference.huggingface.co`). **Current code** uses Router + OpenAI-compatible payload only.

### Client-side CDN

| Resource | URL |
|----------|-----|
| marked (Markdown) | `cdn.jsdelivr.net/npm/marked` |
| MathJax 3 | `cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js` |
| polyfill.io | `polyfill.io/v3/polyfill.min.js?features=es6` |
| Google Fonts | Montserrat, Open Sans |

### Client-side third-party API

| Service | Purpose | Key location |
|---------|---------|--------------|
| Pixabay | Topic illustration on landing | Hardcoded in `index.php` JS |

### Local static assets

- `css/bootstrap.min.css`, `css/bootstrap-icons.css`, `css/style.css`
- `js/jquery.min.js`, `js/bootstrap.bundle.min.js`, `js/jquery.sticky.js`, `js/click-scroll.js`, `js/custom.js`, `js/main.js`

---

## Configuration and Environment Variables

**There is no `.env` file.** All configuration is **hardcoded** or **per-user in MySQL**.

| Setting | Location | Default |
|---------|----------|---------|
| DB host | `db.php` | `localhost` |
| DB name | `db.php` | `hackathon` |
| DB user | `db.php` | `root` |
| DB password | `db.php` | `''` (empty) |
| HF token | `users.huggingface_token` | NULL until Settings |
| HF model | `users.huggingface_model` | `Qwen/Qwen2.5-7B-Instruct` (SQL) / `gpt2` (migrate.php) |
| API timeout | `huggingface_api.php` | 120 seconds cURL |
| Temperature | `huggingface_api.php` | 0.7 |
| Debug log | `api_debug.log` | Append on each HF call |

**PHP extensions required:** `pdo_mysql`, `curl`, `json`, `session`.

---

## Error Handling and Edge Cases

### Hugging Face errors

Caught in `main.php`, `quiz.php`, `flashcards.php` as `Exception`:

- **main.php:** Logs to `api_debug.log`, stores `'Error: ' . $message` as assistant message content (user sees error in chat bubble).
- **quiz.php / flashcards.php:** Returns JSON `{ "error": "..." }`.

`callHuggingFaceAPI` maps HTTP codes 401, 404, 503, 429 to specific messages.

### Database connection

Fatal `die()` on connect failure — no graceful UI.

### JSON parsing (quiz / flashcards)

1. Try extract `<json>...</json>` via regex  
2. Else use full response string  
3. `json_decode` — on failure return model’s plain-text refusal or generic error  

**Edge case:** Model returns valid JSON without tags — may still work if entire response is JSON.

### Quiz answer letter

Client assumes `answer` is one of `A-D`. If model returns full option text or lowercase inconsistently, grading may break (`indexOf` → -1).

### `conversation_data` import bug (`main.php` lines 82–88)

When POST `conversation_data` is present, code inserts messages with `$conversation_id` from GET/POST — but if user lands without `conversation_id`, **`$conversation_id` may be null**, causing failed inserts or wrong FK. The index “Read more” flow is also **disconnected** because `index.php` never returns `conversation_data`.

### Signup failure

`execute()` failure sets `$error = 'Error creating account.'` but duplicate key errors from UNIQUE constraints are not distinguished.

### Empty quiz selection

Submitting without selecting an option counts as incorrect; correct option still highlighted.

---

## Security Notes

| Topic | Severity | Details |
|-------|----------|---------|
| **SQL injection** | High | `main.php` interpolates `$user_id`, `$conversation_id` directly in SQL strings (lines 17, 24–25, 35, 44). Malicious session or POST could exploit. |
| **IDOR on messages** | Medium | Loading messages by `conversation_id` without `JOIN` verifying `conversations.user_id = session user`. |
| **Secrets in repo** | High | `hackathon.sql` sample token; `index.php` Pixabay key in client JS. |
| **Password storage** | OK | bcrypt via `password_hash` / `password_verify`. |
| **Session fixation** | Low | No explicit `session_regenerate_id` on login. |
| **CSRF** | Medium | No tokens on state-changing forms. |
| **XSS** | Low–Medium | Chat output escaped with `htmlspecialchars`; quiz questions inserted via JS template literals **without escaping** — malicious model output could inject HTML in quiz UI. |
| **Token storage** | Medium | HF tokens stored **plaintext** in DB. |
| **HTTPS** | Unknown | Not enforced in code (deployment concern). |
| **Auth on debug tools** | Medium | `migrate.php`, `debug_db.php`, `test_huggingface.php` appear **unauthenticated** if web-accessible. |
| **Error disclosure** | Low | `api_debug.log` may accumulate response fragments; readable if web server serves `.log` files. |

---

## Testing and Gaps

### What exists

| File | Type | Coverage |
|------|------|----------|
| `test_huggingface.php` | CLI script | DB connect, token presence, single HF call |
| `test_token.php` | Browser | Probes multiple HF endpoints with user token |
| `debug_db.php` | Browser/CLI | Schema and user listing |

### What is missing

- No PHPUnit / Pest / Codeception
- No CI configuration
- No automated HTTP/API tests
- No frontend tests
- No load or security tests

**Documentation checklists** in `INTEGRATION_SUMMARY.md` and `SETUP_CHECKLIST.md` are manual QA guides only.

---

## Unknowns / Ambiguities

1. **Original OpenRouter / OpenAI path:** Docs and `api_debug.log` show prior integrations; no OpenRouter code remains in PHP files analyzed.
2. **`images/` directory:** Referenced but absent — unclear if omitted from repo or never committed.
3. **Production deployment:** No Apache vhost, Docker, or HTTPS config in repo.
4. **Whether `index.php` should call HF for logged-in users:** UI suggests yes; implementation says no.
5. **Conversation title auto-generation:** Always default “Untitled Conversation” unless user renames — no AI-generated titles.
6. **Rate limiting / quotas:** Delegated entirely to Hugging Face; no app-level limits.
7. **`polyfill.io`:** Third-party CDN; availability/security depends on external service.
8. **PHP version:** `hackathon.sql` header says PHP 8.2.12; no `composer.json` platform constraint.

---

## Detailed Technical Walkthrough

### Walkthrough 1: First-time developer setup

1. Place files under `C:\xampp\htdocs\Hackathon`.
2. Start Apache + MySQL in XAMPP.
3. Create database `hackathon`; import `hackathon.sql`.
4. If needed, open `http://localhost/Hackathon/migrate.php` for HF columns.
5. Register via `signup.php` or use sample user from SQL dump.
6. Log in → `settings.php` → paste HF token from https://huggingface.co/settings/tokens.
7. Optional: run `php test_huggingface.php` from project directory.
8. Use `main.php` for chat, sidebar links for `quiz.php` / `flashcards.php`.

### Walkthrough 2: Code path for one chat turn

```
Browser: form POST main.php
  question="What is mitosis?"
  conversation_id="" 

main.php:29-37
  INSERT conversations (user_id=3) → id=19
  INSERT messages (conv=19, role=user, content=question)

main.php:44-45
  SELECT role, content FROM messages WHERE conversation_id=19

main.php:48-61
  $api_messages = [
    { role: system, content: academic-only prompt },
    { role: user, content: What is mitosis? }
  ]

huggingface_api.php:14-116
  SELECT token, model FROM users WHERE id=3
  POST router.../v1/chat/completions
  return assistant text

main.php:73-74
  INSERT messages (conv=19, role=assistant, content=response)

main.php:77-78
  Location: main.php?conversation_id=19

Browser: GET renders chat bubbles from DB
```

### Walkthrough 3: Quiz JSON extraction

```
quiz.php:67 → $response = callHuggingFaceAPI(..., 1000)

quiz.php:69-73
  if <json> tags → extract inner
  else $json_str = full response

quiz.php:75-80
  $quiz = json_decode($json_str, true)
  is_array($quiz) → echo { quiz: $quiz }
  else → echo { error: stripped text }
```

Model sometimes wraps JSON in markdown fences or adds prose — **parsing is fragile** by design.

### Walkthrough 4: Session and logout

```
logout.php:
  session_start()
  session_unset()
  session_destroy()
  redirect login.php
```

No “remember me”, no JWT, no API keys for clients.

### File responsibility matrix

| Concern | Primary file(s) |
|---------|-------------------|
| DB connection | `db.php` |
| AI calls | `huggingface_api.php` |
| Auth gate | Each protected page (duplicated check) |
| Chat persistence | `main.php` + tables `conversations`, `messages` |
| User credentials | `users` table, `settings.php` |
| Public marketing | `index.php` |
| Styling | `css/style.css` + Bootstrap |
| Nav UX | `js/custom.js` |

---

## Summary of How the Project Works

**Apollo AI** is a **multi-page PHP application** that uses **MySQL** to store users and chat history, and **Hugging Face’s Router API** (OpenAI-compatible chat completions) to power all AI features. Each user brings their own API token, configured in **Settings**.

**Authenticated users** chat on `main.php` (messages saved per conversation), generate quizzes on `quiz.php`, and flashcards on `flashcards.php`. **Guests** see a marketing landing page on `index.php` but cannot use AI there — the POST handler always tells them to log in.

The architecture is intentionally simple: **no framework**, one helper function for AI, and **inline HTML/JS** per page. Business rules are enforced mainly through **LLM system prompts** (academic-only chat; JSON formats for quiz/cards). **Grading and flashcard navigation** are entirely client-side.

**Operational dependencies:** XAMPP-style LAMP stack, working HF token per user, outbound HTTPS to `router.huggingface.co`, and optional CDNs (marked, MathJax, Pixabay).

**Before production use**, address SQL injection in `main.php`, authorization on conversation access, secret handling (tokens, API keys), missing static assets, and the broken landing-page → chat handoff (`conversation_data` / `index.php` AI).

---

*End of PROJECT_MAP.md*
