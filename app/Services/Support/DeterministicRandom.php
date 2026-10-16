<?php

namespace App\Services\Support;

final class DeterministicRandom
{
    private int $counter = 0;

    public function __construct(private readonly int $seed) {}

    public function integer(int $minimum, int $maximum): int
    {
        if ($minimum > $maximum) {
            throw new \InvalidArgumentException('The minimum must not exceed the maximum.');
        }

        $range = $maximum - $minimum + 1;
        $value = hexdec(substr(hash('sha256', $this->seed.':'.$this->counter++), 0, 8));

        return $minimum + ($value % $range);
    }

    /**
     * @template T
     *
     * @param  non-empty-array<T>  $values
     * @return T
     */
    public function pick(array $values): mixed
    {
        return $values[$this->integer(0, count($values) - 1)];
    }

    /**
     * @template T
     *
     * @param  array<T>  $values
     * @return array<T>
     */
    public function shuffle(array $values): array
    {
        for ($index = count($values) - 1; $index > 0; $index--) {
            $swapWith = $this->integer(0, $index);
            [$values[$index], $values[$swapWith]] = [$values[$swapWith], $values[$index]];
        }

        return $values;
    }
}
