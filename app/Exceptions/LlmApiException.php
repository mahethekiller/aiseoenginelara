<?php

namespace App\Exceptions;

use Exception;

class LlmApiException extends Exception
{
    protected string $category;

    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, string $category = 'unknown')
    {
        parent::__construct($message, $code, $previous);
        $this->category = $category;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function toStructuredArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'category' => $this->category,
        ];
    }

    public static function fromThrowable(\Throwable $e, string $provider = 'AI'): self
    {
        $message = $e->getMessage();
        $category = 'unknown';
        $friendlyMessage = 'An unexpected error occurred during generation: '.$message;

        if (str_contains($message, 'cURL error 28') || str_contains($message, 'Could not resolve') || str_contains($message, 'Failed to connect') || str_contains($message, 'timeout') || str_contains($message, 'Timeout')) {
            $category = 'network';
            $friendlyMessage = 'Network Connection Issue: Could not connect to the remote host. Please check your internet connection or target website status.';
        } elseif (str_contains($message, '401') || str_contains($message, 'Unauthorized') || str_contains($message, 'invalid_api_key') || str_contains($message, 'API key') || str_contains($message, 'authentication') || str_contains($message, 'Authorization')) {
            $category = 'auth';
            $friendlyMessage = 'Authentication Failed: The API key for '.ucfirst($provider).' is invalid, expired, or missing. Please check your settings credentials.';
        } elseif (str_contains($message, '429') || str_contains($message, 'Rate limit') || str_contains($message, 'Too Many Requests')) {
            $category = 'rate_limit';
            $friendlyMessage = 'Rate Limit Exceeded: Too many requests sent to '.ucfirst($provider).' recently. Please wait a few seconds before retrying.';
        } elseif (str_contains($message, 'quota') || str_contains($message, 'credit') || str_contains($message, 'balance') || str_contains($message, 'billing')) {
            $category = 'quota';
            $friendlyMessage = 'Quota Exceeded: Your '.ucfirst($provider).' billing balance is depleted. Please verify your payment methods with the provider.';
        }

        return new self($friendlyMessage, 0, $e, $category);
    }
}
