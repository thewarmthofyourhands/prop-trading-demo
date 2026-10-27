<?php

declare(strict_types=1);

namespace App\UseCase\Article;

use App\Dto\Common\Pagination;
use App\Dto\Common\Sort;
use App\Service\Article\ArticleService;

final class GetArticlesByCategoryUseCase
{
    public function __construct(private readonly ArticleService $articleService)
    {
    }

    public function execute(int $categoryId, int $page, int $perPage, Sort $sort): array
    {
        $pagination = new Pagination($page, $perPage);

        return $this->articleService->getByCategory($categoryId, $pagination, $sort);
    }
}
