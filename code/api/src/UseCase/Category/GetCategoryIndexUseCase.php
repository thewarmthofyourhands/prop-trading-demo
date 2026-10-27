<?php

declare(strict_types=1);

namespace App\UseCase\Category;

use App\Dto\Common\Sort;
use App\Service\Article\ArticleService;
use App\Service\Category\CategoryService;

final class GetCategoryIndexUseCase
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ArticleService $articleService,
    ) {
    }

    public function execute(): array
    {
        $categories = $this->categoryService->getIndex();
        $categoryIds = array_column($categories, 'id');

        $articlesByCategoryId = $this->articleService->getTopByCategoryIds($categoryIds, 3, new Sort('published_at', 'desc'));

        foreach ($categories as &$category) {
            $categoryId = $category['id'];
            $category['articles'] = $articlesByCategoryId[$categoryId] ?? [];
        }
        unset($category);

        $categories = array_values(array_filter(
            $categories,
            static fn (array $category): bool => $category['articles'] !== []
        ));

        return $categories;
    }
}
