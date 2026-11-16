# Reelhouse

A movie discovery and social platform for the **Web2 CCE project**: browse and rate films, keep a
watchlist, follow friends, plan movie nights and watch films together in live screenings with a chat.

Built with **Laravel 12 (PHP)** micro-services, a **React** frontend and **MySQL**, all running in **Docker**.

## Start it

You need **Docker Desktop** running.

```bash
cd Reelhouse
copy .env.example .env        # Windows   (Mac/Linux: cp .env.example .env)
docker compose up --build
```

Open **http://localhost:8080**. After you pull new code, run `docker compose up --build` again.

The full documentation comes with the next versions.
