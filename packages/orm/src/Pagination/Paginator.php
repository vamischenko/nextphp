<?php

declare(strict_types=1);

namespace Nextphp\Orm\Pagination;

/**
 * Simple offset-based paginator.
 *
 * @template T
 */
/**
  * @psalm-immutable
 */
final class Paginator
{
    /** @var list<T> */
    private readonly array $items;

    /**
     * @param list<T> $items      rows for the current page
     * @param int     $total      total number of rows (without pagination)
     * @param int     $perPage    rows per page
     * @param int     $currentPage 1-based
       * @psalm-mutation-free
     */
    public function __construct(
        array $items,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $currentPage = 1,
    ) {
        $this->items = $items;
    }

    // -------------------------------------------------------------------------
    // Items
    // -------------------------------------------------------------------------

    /** @return list<T> */
    public function items(): array
    {
        return $this->items;
    }

    /**
      * @psalm-mutation-free
     */
    public function count(): int
    {
        return count($this->items);
    }

    // -------------------------------------------------------------------------
    // Pagination meta
    // -------------------------------------------------------------------------

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
      * @psalm-mutation-free
     */
    public function lastPage(): int
    {
        if ($this->perPage <= 0 || $this->total === 0) {
            return 1;
        }
        return (int) ceil($this->total / $this->perPage);
    }

    /**
      * @psalm-mutation-free
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    /**
      * @psalm-mutation-free
     */
    public function onFirstPage(): bool
    {
        return $this->currentPage <= 1;
    }

    /**
      * @psalm-mutation-free
     */
    public function previousPage(): ?int
    {
        return $this->currentPage > 1 ? $this->currentPage - 1 : null;
    }

    /**
      * @psalm-mutation-free
     */
    public function nextPage(): ?int
    {
        return $this->hasMorePages() ? $this->currentPage + 1 : null;
    }

    /**
      * @psalm-mutation-free
     */
    public function from(): int
    {
        if ($this->total === 0) {
            return 0;
        }
        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    /**
      * @psalm-mutation-free
     */
    public function to(): int
    {
        return min($this->currentPage * $this->perPage, $this->total);
    }

    /**
      * @psalm-mutation-free
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
      * @psalm-mutation-free
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    // -------------------------------------------------------------------------
    // Array / JSON
    // -------------------------------------------------------------------------

    /**
     * @return array{
     *   current_page: int,
     *   per_page: int,
     *   total: int,
     *   last_page: int,
     *   from: int,
     *   to: int,
     *   has_more: bool,
     *   data: list<T>
     * }
       * @psalm-mutation-free
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page'     => $this->perPage,
            'total'        => $this->total,
            'last_page'    => $this->lastPage(),
            'from'         => $this->from(),
            'to'           => $this->to(),
            'has_more'     => $this->hasMorePages(),
            'data'         => $this->items,
        ];
    }

    /**
     * @psalm-mutation-free
     */
    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
