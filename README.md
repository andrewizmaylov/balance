<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Модуль учёта крипто-баланса

Модуль для зачисления и списания крипто-баланса пользователей с учётом комиссий и рисков. Реализован на PHP 8.2+ и Laravel 12 в соответствии с принципами DDD.

---

## Тестовое задание

Проект выполнен в рамках тестового задания на вакансию [PHP 8.4 Laravel-Developer](https://hh.ru/vacancy/130584385).

**Цель:** разработать модуль учёта крипто-баланса, который позволяет корректно и безопасно:
- зачислять средства (deposit);
- списывать средства (withdrawal, платежи, комиссии);
- учитывать специфику блокчейна, асинхронность и риски.

---

## Технологический стек

- **PHP** 8.2+
- **Laravel** 12
- **Pest** — тестирование
- **andreyizmaylov/base-domain-structure** — пакет для генерации DDD-структуры проекта

---

## Установка и запуск

```bash
git clone https://github.com/andrewizmaylov/balance
cd balance

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Запуск тестов

```bash
php artisan test
```

---

## Архитектура проекта

Проект организован по принципам **Domain-Driven Design (DDD)**. Контекст `Balance` расположен в `src/Balance/` и разделён на слои:

| Слой | Назначение |
|------|------------|
| **DomainLayer** | Сущности, сервисы, интерфейсы репозиториев и хранилищ |
| **ApplicationLayer** | Use Cases (сценарии применения) |
| **InfrastructureLayer** | Реализация репозиториев и хранилищ |
| **PresentationLayer** | HTTP-контроллеры, запросы, маршруты |

### Основные компоненты

- **CreateTransactionsService** — создание транзакций (основная, обратная, комиссии)
- **BalanceUpdateService** — валидация счетов и обновление балансов
- **UpdateBalanceUseCase** — оркестрация процесса обновления баланса

---

## Бизнес-логика

### Участники транзакции

В каждой операции участвуют три стороны:
1. **Плательщик (source)** — счёт, с которого списываются средства
2. **Получатель (destination)** — счёт, на который зачисляются средства
3. **Платформа** — получает комиссию за проведение операции (счёт с `id = 1`)

### Типы операций

- **Deposit** — зачисление: плательщик переводит средства получателю
- **Withdrawal** — списание/вывод: обратное направление перевода

### Комиссии

| Тип комиссии | Ставка | Описание |
|--------------|--------|----------|
| Withdrawal fee | 7,24% | Комиссия со стороны плательщика |
| Deposit fee | 2,14% | Комиссия со стороны получателя |

Комиссии вычисляются в `BalanceUpdateService` и зачисляются на платформенный счёт.

### Проводки при каждой операции

Для каждой транзакции создаётся **4 проводки** с общим `transaction_id`:

1. **Основная** — перевод суммы между source и destination
2. **Обратная** — зеркальная проводка (для учёта)
3. **Комиссия за вывод** — withdrawal fee на платформу
4. **Комиссия за зачисление** — deposit fee на платформу

### Обновление балансов

**Счёт плательщика (source):**
- `balance` уменьшается на `amount + withdrawalFee`
- `locked_balance` увеличивается на `withdrawalFee + amount`

**Счёт получателя (destination):**
- `balance` не меняется
- `locked_balance` изменяется: `locked_balance - depositFee + amount`

**Платформа:**
- `locked_balance` увеличивается на `depositFee + withdrawalFee`

### Статусы транзакций

- `request` — начальный статус при создании
- `pending` — транзакция создана и обработана

---

## API

### Обновление баланса

**Endpoint:** `POST /public/api/v1/BalanceTransaction/update-balance`

**Параметры запроса:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| source_account_id | integer | да | ID счёта плательщика |
| destination_account_id | integer | да | ID счёта получателя |
| coin | string | да | Валюта (например, `btc`) |
| amount | numeric | да | Сумма (> 0) |
| transaction_type | string | нет | `deposit` или `withdrawal` |
| transaction_id | string | нет | Внешний ID транзакции (UUID7) |
| chain_name | string | нет | Название сети (TRON и т.д.) |
| chain_type | string | нет | Тип сети |
| address | string | нет | Адрес кошелька |
| order_id | integer | нет | ID заказа |
| status | string | нет | Статус |

**Пример ответа (успех):**

```json
{
  "id": 1,
  "type": "BalanceTransaction",
  "attributes": {
    "id": 1,
    "source_account_id": 2,
    "destination_account_id": 3,
    "coin": "btc",
    "amount": 1285,
    "transaction_id": "01933...",
    "transaction_type": "deposit",
    "status": "pending"
  }
}
```

---

## Структура проекта

```
src/Balance/
├── ApplicationLayer/
│   └── UseCases/
│       └── UpdateBalanceUseCase.php
├── DomainLayer/
│   ├── Entities/
│   │   ├── Account.php
│   │   └── BalanceTransaction.php
│   ├── Repository/
│   │   ├── AccountRepositoryInterface.php
│   │   └── BalanceTransactionRepositoryInterface.php
│   ├── Services/
│   │   ├── BalanceUpdateService.php
│   │   └── CreateTransactionsService.php
│   ├── Storage/
│   │   ├── AccountStorageInterface.php
│   │   └── BalanceTransactionStorageInterface.php
│   └── ValueObjects/
│       └── Amount.php
├── InfrastructureLayer/
│   ├── Repository/
│   │   ├── AccountRepository.php
│   │   └── BalanceTransactionRepository.php
│   └── Storage/
│       ├── AccountStorage.php
│       └── BalanceTransactionStorage.php
└── PresentationLayer/
    └── HTTP/V1/
        ├── Controllers/
        │   └── UpdateBalanceController.php
        ├── Requests/
        │   └── UpdateBalanceRequest.php
        ├── Responders/
        │   └── BalanceTransactionResponder.php
        └── routes.php
```

---

## Тестирование

Тесты расположены в `tests/Src/Balance/` и охватывают:

- Создание 4 проводок при операции Deposit
- Создание 4 проводок при операции Withdrawal
- Корректность обновления балансов (source, destination, platform)
- Структуру всех транзакций (основная, обратная, комиссии)
- Формат JSON-ответа API

---

## Важные замечания

1. **Платформенный счёт** — должен существовать с `id = 1`. При использовании `RefreshDatabase` в тестах он создаётся первым.

2. **Валидация** — перед транзакцией проверяются:
   - наличие счетов;
   - активный статус счёта;
   - совпадение валюты;
   - достаточность средств (для плательщика: `balance - locked_balance >= amount + withdrawalFee`).

3. **Транзакционность** — вся операция выполняется в одной БД-транзакции.

4. **Разморозка** заблокированных активов будет происходить при наступлении отдельного доменного события. При этом в случае положительного сценария статус связанных транзакций изменится на `completed`, а замороженные суммы учтены в основном балансе.
