# chat-service

Real-time chat micro-service. REST endpoints to read/send messages, and Laravel Reverb (WebSockets) to push them instantly. Owns the `reelhouse_chat` database. Checks tokens and room access by calling the main API.

MVC: `routes/api.php` → `app/Http/Controllers` → `app/Models` / `app/Services`.

Runs inside Docker, see the README at the root of the project.
