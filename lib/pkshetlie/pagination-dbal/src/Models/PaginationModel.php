<?php

namespace  Pkshetlie\PaginationDbal\Models;



use Symfony\Component\HttpFoundation\Request;

class PaginationModel
{
    private static int $increment = 0;

    private array $entities;
    private int $count;
    private int $pages;
    private int $current;
    private array $currentOrder;
    private int $identifier;
    private bool $isPartial = false;
    private int $lastEntityId;
    private OrderModel $orderModel;

    public function __construct()
    {
        self::$increment += 1;
        $this->identifier = self::$increment;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function setEntity($key, $entity)
    {
        $this->entities[$key] = $entity;

        return $this;
    }

    public function setEntities(array $entities): self
    {
        $this->entities = $entities;

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function setPages(int $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    public function getCurrent(): int
    {
        return $this->current;
    }

    public function setCurrent(int $current): self
    {
        $this->current = $current;

        return $this;
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public static function getStaticIdentifier(): int
    {
        return self::$increment;
    }

    public function getLastEntityId(): int
    {
        return $this->lastEntityId;
    }

    public function setLastEntityId(int $lastEntityId): self
    {
        $this->lastEntityId = $lastEntityId;

        return $this;
    }

    public function isPartial(): bool
    {
        return $this->isPartial;
    }

    public function setIsPartial(bool $isPartial): self
    {
        $this->isPartial = $isPartial;

        return $this;
    }

    public function setOrderModel(OrderModel $orderModel): self
    {
        $this->orderModel = $orderModel;
        return $this;
    }

    public function getOrderModel(): OrderModel
    {
        return $this->orderModel;
    }
}