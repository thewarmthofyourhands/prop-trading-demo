<?php

declare(strict_types=1);

namespace App\Repository\Category;

interface CategoryRepositoryInterface
{
    public function findAllWithArticles(): array;

    public function findById(int $id): null|array;
}
