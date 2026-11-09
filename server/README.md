# Laravel backend

API и панель управления проекта КиберЛуки. React находится в `../client`.

Полная инструкция запуска, импорта Strapi, ролей и тестов — в [README проекта](../README.md).

- API: `/api/*`
- Админка: `/admin`
- Создание администратора: `php artisan cyberluki:admin`
- Импорт в пустую базу: `php artisan cyberluki:import-strapi ../backend/.tmp/data.db --uploads=../backend/public/uploads`
- Проверки: `php artisan test`

Зависимости JavaScript в backend не нужны: Blade-панель использует локальный `public/admin.css`.
