<?php

namespace Velto\Core\Support;

class Collection implements \IteratorAggregate
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function sortByDesc(callable|string $key): self
    {
        $items = $this->items;

        usort($items, function ($a, $b) use ($key) {
            $valA = is_callable($key) ? $key($a) : ($a->{$key} ?? null);
            $valB = is_callable($key) ? $key($b) : ($b->{$key} ?? null);
            return $valB <=> $valA;
        });

        return new self($items);
    }

    public function values(): self
    {
        return new self(array_values($this->items));
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }
        return $this;
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
