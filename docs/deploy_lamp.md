# Deploy on LAMP

1. Build frontend: `cd frontend && npm run build`.
2. Copy `frontend/dist/*` to `public_html/`.
3. Copy `api/` outside public assets.
4. Create `data/app.db`, set writable permissions for web user.
5. Ensure `pdo_sqlite` is enabled.
6. Open `/api/init.php` once.
7. Verify auth, tasks, and `/admin/logs`.
