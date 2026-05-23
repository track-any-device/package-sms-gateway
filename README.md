# track-any-device/sms-gateway

Laravel HTTP client package for the [Track Any Device](https://trackanydevice.com) SMS gateway device. Wraps the gateway REST API with a typed service class, automatic config merging, and a contract interface for easy mocking in tests.


---

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13

---

## Installation

```bash
composer require track-any-device/sms-gateway
```

The service provider is auto-discovered. No manual registration needed.

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=sms-gateway-config
```

This creates `config/sms.php`. Add the corresponding values to your `.env`:

```env
SMS_GATEWAY_URL=http://192.168.1.100:8080
SMS_GATEWAY_API_KEY=your-api-key
SMS_GATEWAY_TIMEOUT=15
```

| Key | Description | Default |
|---|---|---|
| `SMS_GATEWAY_URL` | Base URL of the gateway device | — |
| `SMS_GATEWAY_API_KEY` | API key sent as `X-API-Key` | — |
| `SMS_GATEWAY_TIMEOUT` | HTTP timeout in seconds | `15` |

---

## Usage

Inject `SmsGatewayContract` (or `SmsGatewayService`) wherever you need it:

```php
use TrackAnyDevice\SmsGateway\Contracts\SmsGatewayContract;

class NotificationController extends Controller
{
    public function __construct(private SmsGatewayContract $sms) {}

    public function notify(string $phone): void
    {
        $this->sms->send($phone, 'Your order has been dispatched.');
    }
}
```

### Send an SMS

```php
$sent = $sms->send('+447700900000', 'Hello from TAD!');

if (! $sent) {
    // false is returned on HTTP error, gateway error, or network exception
    // details are logged via Log::error
}
```

### Check gateway health

```php
if ($sms->health()) {
    // gateway is reachable and reports status: ok
}
```

### Read the inbox

```php
$messages = $sms->inbox();

// Each message:
// [
//     'index'   => '1',
//     'sender'  => '+447700900000',
//     'message' => 'Reply text',
//     'date'    => '2024/06/01 14:30:00+0100',
// ]

foreach ($messages as $msg) {
    $received = $sms->parseGatewayDate($msg['date']); // CarbonImmutable|null
    echo $msg['sender'].': '.$msg['message'];
}
```

### Delete a message

```php
$deleted = $sms->deleteMessage($msg['index']);
```

### Device status

```php
$status = $sms->status();

// Returns array or null:
// [
//     'signal'   => '-73 dBm',
//     'network'  => '4G',
//     'operator' => 'EE',
//     'sim'      => 'ready',
// ]
```

---

## Testing

Bind a mock against the contract in your test:

```php
use TrackAnyDevice\SmsGateway\Contracts\SmsGatewayContract;

$this->mock(SmsGatewayContract::class)
    ->shouldReceive('send')
    ->once()
    ->with('+447700900000', 'Hello from TAD!')
    ->andReturn(true);
```

---

## Logging

All failures are logged through Laravel's default logger:

| Event | Level |
|---|---|
| `send` HTTP error or gateway error | `error` |
| `send` network exception | `error` |
| `inbox` HTTP error | `error` |
| `inbox` network exception | `error` |
| `parseGatewayDate` bad format | `warning` |

---

## Licence

MIT
