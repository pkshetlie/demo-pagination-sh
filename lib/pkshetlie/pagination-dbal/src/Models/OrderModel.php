<?php

namespace Pkshetlie\PaginationDbal\Models;

class OrderModel
{

    private array $aliasesColumns;

    public function __construct(array $aliasesAndColumns)
    {
        $this->aliasesColumns = $aliasesAndColumns;
    }

    public function getAliases(): array
    {
        return array_keys($this->aliasesColumns);
    }

    public function getColumn(string $alias): string
    {
        return $this->aliasesColumns[$alias];
    }

    public function getAll(): array
    {
        return $this->aliasesColumns;
    }

    public function aliasExists($alias): bool
    {
        return isset($this->aliasesColumns[$alias]);
    }
}