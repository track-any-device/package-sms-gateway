<?php

namespace TrackAnyDevice\SmsGateway;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TrackAnyDevice\SmsGateway\Contracts\SmsGatewayContract;

class SmsGatewayService implements SmsGatewayContract
{
    private string $url;

    private string $apiKey;

    private int $timeout;

    public function __construct()
    {
        $this->url = rtrim((string) config('sms.gateway.url'), '/');
        $this->apiKey = (string) config('sms.gateway.api_key');
        $this->timeout = (int) config('sms.gateway.timeout', 15);
    }

    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->url}/send", ['to' => $to, 'message' => $message]);

            if (! $response->ok()) {
                Log::error('SMS gateway send failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();
            if (($data['status'] ?? '') !== 'success') {
                Log::error('SMS gateway returned error', ['to' => $to, 'response' => $data]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS gateway exception', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function health(): bool
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->url}/health");

            return $response->ok() && ($response->json('status') === 'ok');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, array{index: string, sender: string, message: string, date: string}>
     */
    public function inbox(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->url}/inbox");

            if (! $response->ok()) {
                Log::error('SMS gateway inbox fetch failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $response->json('messages', []);
        } catch (\Throwable $e) {
            Log::error('SMS gateway inbox fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function deleteMessage(int|string $index): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->delete("{$this->url}/inbox/".urlencode((string) $index));

            return $response->ok();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{signal: string, network: string, operator: string, sim: string}|null */
    public function status(): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->url}/status");

            return $response->ok() ? $response->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function parseGatewayDate(string $date): ?CarbonImmutable
    {
        $result = CarbonImmutable::createFromFormat('Y/m/d H:i:sO', $date);

        if ($result === false) {
            Log::warning('SMS gateway: unparseable date string', ['date' => $date]);

            return null;
        }

        return $result;
    }
}
