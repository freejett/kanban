# Deploy on LAMP

1. Build frontend directly into public root: `cd frontend && npm run build` (outDir -> `../public_html`).
2. Deploy `public_html/` as document root.
3. Keep `api/` and `data/` outside document root (private).
4. Create `data/app.db`, set writable permissions for web user.
5. Ensure `pdo_sqlite` is enabled.
6. Open `/api/init.php` once from public domain.
7. Verify auth, tasks, `/admin/logs`, `/admin/users`.
