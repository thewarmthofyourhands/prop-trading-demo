<?php

declare(strict_types=1);

namespace App\UseCase\Article;

use App\Service\Article\ArticleService;

final class GetArticleShowUseCase
{
    public function __construct(private readonly ArticleService $articleService)
    {
    }

    public function execute(int $id): array
    {
        $article = $this->articleService->getById($id);
        $article['related_articles'] = $this->articleService->getRelatedByArticleId($id);

        return $article;
    }
}
