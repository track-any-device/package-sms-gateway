<?php

namespace TrackAnyDevice\SmsGateway\Contracts;

use Carbon\CarbonImmutable;

interface SmsGatewayContract
{
    public function send(string $to, string $message): bool;

    public function health(): bool;

    /** @return array<int, array{index: string, sender: string, message: string, date: string}> */
    public function inbox(): array;

    public function deleteMessage(int|string $index): bool;

    /** @return array{signal: string, network: string, operator: string, sim: string}|null */
    public function status(): ?array;

    public function parseGatewayDate(string $date): ?CarbonImmutable;
}
