# بوح · Bawh

**A space for what we could not say — مساحة لما لم نستطع قوله**

Bawh is a Laravel web platform for sharing short quotes with feelings. It is the final project of the
Al-Qimmah Bootcamp (Tuwaiq Club · UQU), Programming & Development track, built by **Noorulhuda Mazen Abushal**.

## Features

- **Core (QuoteIt):** browse quotes as a guest, sign up, log in / out, share, edit and delete your own quotes.
- **Feelings:** 7 feelings with colors, filter, search, sort by newest or most felt.
- **Reactions:** “I felt you”, “You’re not alone”, “This touched me”, and “Like me” saves.
- **Anonymous bawh**, user profiles, follow / unfollow, a “Following” tab.
- **A letter that won’t be sent:** keep it, let it go, or share it without your name.
- **Feeling mirror:** write how you feel and get matching quotes (AI with a dictionary fallback).
- Quote of the day, random quote envelope, question of the day with anonymous answers.
- Story pictures (1080×1920), feelings map, writing streak and badges, rain / waves sounds.
- Arabic / English, Night / Dawn / Auto themes, text size, reduce motion.
- **Safety:** support message with the MOH psychological consultations number (920033360), reports.
- **Admin dashboard:** site numbers, reports, delete any public content, manage users and admins.

## Run it

Needs PHP 8.4+, Composer and Node.js.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=BawhDemoSeeder   # optional: demo users, quotes, letters, answers
composer run dev                              # then open http://localhost:8000
```

- Demo accounts (after the seeder): e.g. `sara@bawh.test` / `password123`.
- Make an admin: `php artisan bawh:admin you@example.com`, then open `/admin`.
- AI (optional): add `GROQ_API_KEY=...` to `.env`, then check it with `php artisan ai:check`.
- The database is SQLite (`database/database.sqlite`). To use MySQL, change `DB_CONNECTION` and the `DB_*` values in `.env`.

## Built with

Laravel 13 · PHP · Blade (`@extends`, `@yield`, components) · Tailwind CSS · JavaScript · SQLite · Vite
