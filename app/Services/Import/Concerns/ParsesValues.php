<?php

namespace App\Services\Import\Concerns;

use App\Models\User;
use App\Services\Import\RowError;
use Carbon\Carbon;

/** Shared checks for import rows; each throws RowError with a readable reason. */
trait ParsesValues
{
    private array $usersByEmail = [];

    protected function required(array $row, string $column): string
    {
        $value = $row[$column] ?? '';
        if ($value === '') {
            throw new RowError("\"{$column}\" is empty.");
        }

        return $value;
    }

    protected function date(string $value, string $column): string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat('!' . $format, $value);
            } catch (\Throwable) {
                continue; // not this format
            }
            // Reject dates that only parse by rolling over, such as month 13.
            if ($parsed !== false && $parsed->format($format) === $value) {
                return $parsed->toDateString();
            }
        }

        throw new RowError("\"{$column}\" must be a date like 2026-11-17 (got \"{$value}\").");
    }

    protected function time(string $value, string $column): string
    {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $value)) {
            throw new RowError("\"{$column}\" must be a 24-hour time like 09:30 (got \"{$value}\").");
        }

        return Carbon::createFromFormat('H:i', substr($value, 0, 5))->format('H:i:s');
    }

    protected function yesNo(string $value, string $column, bool $default): bool
    {
        if ($value === '') {
            return $default;
        }

        return match (strtolower($value)) {
            'yes', 'y', 'true', '1', 'active' => true,
            'no', 'n', 'false', '0', 'inactive' => false,
            default => throw new RowError("\"{$column}\" must be yes or no (got \"{$value}\")."),
        };
    }

    protected function oneOf(string $value, array $allowed, string $column): string
    {
        $lower = strtolower($value);
        if (!in_array($lower, $allowed, true)) {
            throw new RowError("\"{$column}\" must be one of: " . implode(', ', $allowed) . " (got \"{$value}\").");
        }

        return $lower;
    }

    protected function userByEmail(string $email, string $column = 'email'): User
    {
        $key = strtolower($email);

        if (!array_key_exists($key, $this->usersByEmail)) {
            $this->usersByEmail[$key] = User::whereRaw('LOWER(email) = ?', [$key])->first();
        }

        return $this->usersByEmail[$key]
            ?? throw new RowError("No staff member has the email \"{$email}\" in the \"{$column}\" column.");
    }

    protected function once(array &$seen, string $key, string $what): void
    {
        if (isset($seen[$key])) {
            throw new RowError("{$what} appears more than once in this file.");
        }
        $seen[$key] = true;
    }
}
