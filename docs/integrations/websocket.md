# WebSocket Runtime Bootstrap

`nextphp/websocket` включает базовый сервер и адаптеры `Ratchet`/`Swoole`.

## Bootstrap script

В репозитории есть пример:

```bash
php bin/ws-server swoole
php bin/ws-server ratchet
```

Скрипт показывает lifecycle:

- инициализация адаптера;
- открытие соединения;
- broadcast сообщения;
- закрытие соединения.

Для production нужно подменить demo-connection на реальный runtime event loop выбранного драйвера.
