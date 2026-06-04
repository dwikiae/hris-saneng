<?php

namespace App\Application\Instance;

use RuntimeException;

class ModuleLifecycleException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(string $message, private readonly array $meta = [], private readonly int $status = 422)
    {
        parent::__construct($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    public function status(): int
    {
        return $this->status;
    }
}
