# Backend Assistant Protocol (Frontend ↔ Backend)

Agreed format for communication between the **frontend assistant** (Android POS / Cursor) and the **Smart-Dining AI Assistant** backend (POST https://zima-uat.site:8004/api/ai/ask). Following this reduces timeouts and ambiguity.

---

## 1. Request format (frontend → backend)

Prefix the prompt with optional headers, then the actual ask:

[Type: schema|implement|query|read|confirm|report]
[Context: one-line summary of current feature/screen]
[Ref: previous related prompt summary, if any]

<the actual ask — one main task>

**Types:** schema (design/plan), implement (code/endpoint), query (DB/stats), read (show code/routes), confirm (we did X / issues), report (stats report). One main ask per prompt. Max 4000 chars.

## 2. Response format (backend → frontend)

Start with: Status: done | partial | blocked. Then Reason: (if partial/blocked). Then markdown + fenced code blocks for JSON/SQL/PHP/specs. Use consistent endpoint spec shape (method, path, body, response).

## 3. Backend capabilities

Allowed: read/write code, migrations, tinker, routes, tests. Not: .env, composer.json, core, install packages. Single-prompt: one controller + one model + one migration + routes. Proxy 600s.

## 4. Do: Use [Type:] and [Context:], one task per prompt, include file paths in Context, schema then implement for big features, send confirm after implementing. Don't: 3+ unrelated endpoints, vague asks, omit Context when file known.

## 5. Prompt templates: implement (new endpoint), query (DB question), read (code/routes), confirm (implemented + issues), schema (design only).
