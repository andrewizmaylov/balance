<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Модуль учёта крипто-баланса

Модуль для зачисления и списания крипто-баланса пользователей с учётом комиссий и рисков.  
Реализован на PHP 8.2+ и Laravel 12 в соответствии с принципами DDD.

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

Проект организован по принципам **Domain-Driven Design (DDD)**.  
Контекст `Balance` расположен в `src/Balance/` и разделён на слои:

| Слой | Назначение |
|------|------------|
| **DomainLayer** | Сущности, сервисы, интерфейсы репозиториев и хранилищ |
| **ApplicationLayer** | Use Cases (сценарии применения) |
| **InfrastructureLayer** | Реализация репозиториев и хранилищ |
| **PresentationLayer** | HTTP-контроллеры, запросы, маршруты |

### Основные компоненты

- **CreateTransactionsService** — создание транзакций (основная, обратная, комиссии)
- **BalanceUpdateService** — валидация счетов и обновление балансов
- **CheckTransactionService** — проверка возможности действий над транзакциями


- **PutOrderUseCase** — оркестрация процесса размещения заявки на перевод средств
- **CompleteOrderUseCase** — оркестрация процесса перевода заказа в статус Completed
- **CancelOrderUseCase** — оркестрация процесса перевода заказа в статус Canceled
- **DisputeOrderUseCase** — оркестрация процесса перевода заказа в статус Dispute
- **ReleaseCoinsUseCase** — оркестрация процесса завершения процесса перевода, финализации балансов и перевода заказа в статус Fulfilled

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

## Механизм работы

### Размещение заявки PutOrder

**Счёт плательщика (source):**
- `balance` уменьшается на `amount + withdrawalFee`
- `locked_balance` увеличивается на `withdrawalFee + amount`

**Счёт получателя (destination):**
- `balance` не меняется
- `locked_balance` изменяется: `locked_balance - depositFee + amount`

**Платформа:**
- `locked_balance` увеличивается на `depositFee + withdrawalFee`


### Удачное завершение ReleaseCoins

**Счёт плательщика (source):**
- `locked_balance` уменьшается на `withdrawalFee + amount`

**Счёт получателя (destination):**
- `balance` изменяется: `balance - depositFee + amount`
- `locked_balance` изменяется: `locked_balance + depositFee - amount`

**Платформа:**
- `balance` увеличивается на `depositFee + withdrawalFee`
- `locked_balance` уменьшается на `depositFee + withdrawalFee`

### Отмена CancelOrder

**Счёт плательщика (source):**
- `balance` увеличивается до исходного на `amount + withdrawalFee`
- `locked_balance` уменьшается на `withdrawalFee + amount`

**Счёт получателя (destination):**
- `balance` не меняется
- `locked_balance` изменяется: `locked_balance + depositFee - amount`

**Платформа:**
- `locked_balance` уменьшается на `depositFee + withdrawalFee`

### Статусы транзакций
- `request` — начальный статус при создании
- `pending` — транзакция создана и обработана
- `confirmed` — транзакция подтверждена
- `canceled` — транзакция отменена
- `fulfilled` — транзакция полностью исполнена

---

## API

### Размещение заявки PutOrder

**Endpoint:** `POST /public/api/v1/BalanceTransaction/put-order`

**Параметры запроса:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|----------|
| source_account_id | integer | да          | ID счёта плательщика |
| destination_account_id | integer | да          | ID счёта получателя |
| coin | string | да          | Валюта (например, `btc`) |
| amount | numeric | да          | Сумма (> 0) |
| transaction_type | string | нет         | `deposit` или `withdrawal` |
| transaction_id | string | да        | Внешний ID транзакции (UUID7) |
| chain_name | string | нет         | Название сети (TRON и т.д.) |
| chain_type | string | нет         | Тип сети |
| address | string | нет         | Адрес кошелька |
| order_id | integer | нет         | ID заказа |
| status | string | нет         | Статус |

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

### Изменение статусов CompleteOrder, CancelOrder и тд

**Endpoint:** `PATCH /public/api/v1/BalanceTransaction/complete-order/{transaction_id}`

**Параметры запроса:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|----------|
| transaction_id | string | да        | Внешний ID транзакции (UUID7) |

---

## Структура проекта

```
src/Balance/
├── ApplicationLayer/
│   └── UseCases/
│       └── PutOrderUseCase.php
│       └── CancelOrderUseCase.php
│       └── CompleteOrderUseCase.php
│       └── DisputeOrderUseCase.php
│       └── ReleaseCoinsUseCase.php
├── DomainLayer/
│   ├── Entities/
│   │   ├── Account.php
│   │   └── BalanceTransaction.php
│   ├── Exceptions/
│   ├── Repository/
│   │   ├── AccountRepositoryInterface.php
│   │   └── BalanceTransactionRepositoryInterface.php
│   ├── Services/
│   │   ├── BalanceUpdateService.php
│   │   ├── CheckTransactionService.php
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

Тесты расположены в `tests/Src/Balance/` и покрывают PutOrderController:

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

3. **Транзакционность** — все пользовательские сценарии выполняется в одной БД-транзакции.  
Результатом выполнения сценария являются создание или обработка 4 записей в таблице `balance_transactions` объединенных единым `transaction_id`.  
По примеру работы биржи ByBit 'размещенный ордер' должен быть исполнен в установленные временные интервалы или отменен.  
Участники транзакции должны убедиться в том, что им поступила оплата по известной транзакции и произвести дополнительные действия на платформе.  
Список действий - пользовательских сценариев описан в 4 дополнительных UseCases без реализации. 
