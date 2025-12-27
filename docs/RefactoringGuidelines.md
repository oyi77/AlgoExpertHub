# Refactoring Guidelines: Service Layer Pattern

To improve codebase quality and maintainability, we follow the **Service Layer Pattern**. This ensures business logic is separated from HTTP concerns.

## Core Principles

1.  **Thin Controllers**: Controllers should only handle HTTP requests, input validation (via Form Requests), and returning responses.
2.  **Service Classes**: All business logic, database operations, and external API calls must reside in Service classes.
3.  **Standardized Responses**: Services must return a standardized array format using `successResponse` and `errorResponse` from `BaseService`.
4.  **Strict Typing**: All new or refactored PHP files MUST inclusion `declare(strict_types=1);` and have full type hinting.
5.  **Transactions**: Operations that modify multiple database tables must be wrapped in `executeInTransaction`.

## Implementation Example

### [BEFORE] Fat Controller
```php
public function store(Request $request)
{
    $request->validate([...]);
    $item = Item::create($request->all());
    // ... more logic
    return redirect()->back();
}
```

### [AFTER] Thin Controller
```php
public function store(StoreItemRequest $request)
{
    $result = $this->itemService->create($request->validated());
    return redirect()->back()->with($result['type'], $result['message']);
}
```

### [AFTER] Service Layer
```php
public function create(array $data): array
{
    return $this->executeInTransaction(function () use ($data) {
        $item = Item::create($data);
        return $this->successResponse('Item created', ['item' => $item]);
    });
}
```

## Error Handling
Always use `try-catch` blocks within services or rely on `executeInTransaction` which handles exceptions automatically and logs errors for you.
