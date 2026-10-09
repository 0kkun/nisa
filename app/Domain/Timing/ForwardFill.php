<?php

declare(strict_types=1);

namespace App\Domain\Timing;

/**
 * 日付昇順に問い合わせると、その日以前で直近の値を返す
 */
final class ForwardFill
{
    /** @var list<string> */
    private array $dates;

    /** @var list<float> */
    private array $values;

    private int $index = -1;

    /**
     * @param  array<string, float>  $series  日付昇順
     */
    public function __construct(array $series)
    {
        $this->dates = array_keys($series);
        $this->values = array_values($series);
    }

    public function at(string $date): ?float
    {
        while (isset($this->dates[$this->index + 1]) && $this->dates[$this->index + 1] <= $date) {
            $this->index++;
        }

        return $this->index >= 0 ? $this->values[$this->index] : null;
    }
}
