<?php

declare(strict_types=1);

namespace App\Repository\Category;

use Eva\Database\ConnectionStoreInterface;
use PDO;

final readonly class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private ConnectionStoreInterface $connectionStore)
    {
    }

    public function findAllWithArticles(): array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            select distinct
                c.id as category_id,
                c.name as category_name,
                c.description as category_description
            from categories c
            inner join article_category ac on ac.category_id = c.id
            order by c.id asc
            SQL,
        );
        $stmt->execute();

        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categoryId = (int) $row['category_id'];
            $categories[] = [
                'id' => $categoryId,
                'name' => $row['category_name'],
                'description' => $row['category_description'],
            ];
        }

        $stmt->closeCursor();

        return $categories;
    }

    public function findById(int $id): null|array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            select id, name, description
            from categories
            where id = :id
            SQL,
            ['id' => $id],
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (false === $row) {
            return null;
        }

        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
        ];
    }
}
