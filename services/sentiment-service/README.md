# sentiment-service

Sentiment micro-service, no database and no external API. `app/Services/SentimentAnalyzer.php` scores review text with a word list, handling negations ("not good"), boosters ("very") and "but".

MVC: `routes/api.php` → `app/Http/Controllers` → `app/Models` / `app/Services`.

Runs inside Docker, see the README at the root of the project.
