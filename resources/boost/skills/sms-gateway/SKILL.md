---
name: sms-gateway
description: Work with the track-any-device/sms-gateway package — sending SMS, reading the inbox, deleting messages, checking device health and status, and writing tests for gateway interactions. Use when the user is working with SmsGatewayContract, SmsGatewayService, or any SMS gateway feature in a TAD Laravel app.
---

# SMS Gateway — track-any-device/sms-gateway

Use this skill whenever you are working with the `track-any-device/sms-gateway` package: sending SMS, reading the inbox, checking device health/status, or writing tests that involve the gateway.

---

## Package overview

The package exposes a single service bound to `SmsGatewayContract`. Always inject the contract, not the concrete class, so tests can mock it cleanly.

```php
use TrackAnyDevice\SmsGateway\Contracts\SmsGatewayContract;
```

---

## Required .env keys

```env
SMS_GATEWAY_URL=http://192.168.1.100:8080
SMS_GATEWAY_API_KEY=your-api-key
SMS_GATEWAY_TIMEOUT=15          # optional, default 15
```

Publish the config once if you need to customise defaults:

```bash
php artisan vendor:publish --tag=sms-gateway-config
```

---

## Method reference

### `send(string $to, string $message): bool`

Posts to `POST /send`. Returns `true` on success, `false` on any error (HTTP, gateway, or network). Failures are logged via `Log::error`.

```php
$sent = $sms->send('+447700900000', 'Your parcel is on its way.');
```

---

### `health(): bool`

Hits `GET /health` (no API key required). Returns `true` only when the gateway responds `200` with `{"status":"ok"}`.

```php
if (! $sms->health()) {
    // gateway unreachable — alert or queue the message
}
```

---

### `inbox(): array`

Returns all messages from `GET /inbox`. Shape of each item:

```php
[
    'index'   => '1',           // used to delete the message
    'sender'  => '+447700900000',
    'message' => 'Reply text',
    'date'    => '2024/06/01 14:30:00+0100',
]
```

Returns `[]` on error (logged).

---

### `deleteMessage(int|string $index): bool`

Calls `DELETE /inbox/{index}`. Use the `index` value from `inbox()`.

```php
foreach ($sms->inbox() as $msg) {
    // process $msg
    $sms->deleteMessage($msg['index']);
}
```

---

### `status(): ?array`

Returns device status from `GET /status`, or `null` on failure.

```php
// ['signal' => '-73 dBm', 'network' => '4G', 'operator' => 'EE', 'sim' => 'ready']
$status = $sms->status();
```

---

### `parseGatewayDate(string $date): ?CarbonImmutable`

Converts the gateway's `Y/m/d H:i:sO` date string to `CarbonImmutable`. Returns `null` and logs a warning if the format does not match — always null-check the result.

```php
$received = $sms->parseGatewayDate($msg['date']);
if ($received !== null) {
    $age = $received->diffForHumans();
}
```

---

## Injecting in controllers, jobs, and commands

```php
// Controller
public function __construct(private SmsGatewayContract $sms) {}

// Job
public function __construct(private SmsGatewayContract $sms) {}

// Artisan command — resolve from container
$sms = app(SmsGatewayContract::class);
```

---

## Testing

Mock the contract — never let tests hit the real device.

```php
use TrackAnyDevice\SmsGateway\Contracts\SmsGatewayContract;

// Successful send
$this->mock(SmsGatewayContract::class)
    ->shouldReceive('send')
    ->once()
    ->with('+447700900000', 'Hello')
    ->andReturn(true);

// Failed send
$this->mock(SmsGatewayContract::class)
    ->shouldReceive('send')
    ->andReturn(false);

// Inbox with messages
$this->mock(SmsGatewayContract::class)
    ->shouldReceive('inbox')
    ->andReturn([
        ['index' => '1', 'sender' => '+447700900000', 'message' => 'Reply', 'date' => '2024/06/01 14:30:00+0100'],
    ]);
```

---

## Common patterns

### Queue a job when SMS fails

```php
if (! $sms->send($phone, $text)) {
    RetrySmsJob::dispatch($phone, $text)->delay(now()->addMinutes(5));
}
```

### Poll inbox and dispatch jobs per message

```php
foreach ($sms->inbox() as $msg) {
    ProcessInboundSmsJob::dispatch($msg);
    $sms->deleteMessage($msg['index']);
}
```

### Health check in a scheduled command

```php
// routes/console.php
Schedule::call(function () {
    if (! app(SmsGatewayContract::class)->health()) {
        Log::critical('SMS gateway is unreachable');
    }
})->everyFiveMinutes();
```

---

## Error handling notes

- All methods return `false` / `null` / `[]` on failure — they never throw.
- All failures are logged. Check `Log::error` for `'SMS gateway ...'` channel entries.
- `parseGatewayDate` returning `null` means the gateway returned an unexpected date format — log context will include the raw string.
