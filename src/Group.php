<?php

declare(strict_types=1);

namespace ReactParallel\Pool\Test;

use ReactParallel\Contracts\GroupInterface;

use function bin2hex;
use function random_bytes;

final readonly class Group implements GroupInterface
{
    private const int BYTES = 16;

    private function __construct(private string $id)
    {
    }

    public static function create(): self
    {
        return new self(bin2hex(random_bytes(self::BYTES)));
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
