<?php
declare(strict_types=1);

namespace App\Dto;

class GenerationResult
{
    public bool $success;
    public ?string $path;
    public ?string $message;

    public function __construct(bool $success, ?string $path = null, ?string $message = null)
    {
        $this->success = $success;
        $this->path = $path;
        $this->message = $message;
    }
}
