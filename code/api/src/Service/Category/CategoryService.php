<?php

declare(strict_types=1);

namespace App\Service\Category;

use App\Exception\Application\NotFoundException;
use App\Repository\Category\CategoryRepositoryInterface;

final class CategoryService
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
    }

    public function getIndex(): array
    {
        return $this->categoryRepository->findAllWithArticles();
    }

    public function getById(int $id): array
    {
        $category = $this->categoryRepository->findById($id);
        if (null === $category) {
            throw new NotFoundException();
        }

        return $category;
    }
}
