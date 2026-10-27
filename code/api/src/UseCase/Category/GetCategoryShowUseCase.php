<?php

declare(strict_types=1);

namespace App\UseCase\Category;

use App\Service\Category\CategoryService;

final class GetCategoryShowUseCase
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function execute(int $id): array
    {
        return $this->categoryService->getById($id);
    }
}
