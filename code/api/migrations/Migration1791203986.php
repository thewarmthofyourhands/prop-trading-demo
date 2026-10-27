<?php

declare(strict_types=1);

namespace Migrations;

use Eva\Database\Migrations\AbstractMigration;

class Migration1791203986 extends AbstractMigration
{
    public function up(): void
    {
        $sql = <<<SQL
        create table users (
            id bigint auto_increment primary key,
            email varchar(255) not null,
            created_at datetime not null,
            unique index uniq_users_email (email)
        );

        create table challenges (
            id bigint auto_increment primary key,
            name varchar(255) not null,
            account_size int not null,
            price int not null,
            currency varchar(16) not null,
            type varchar(32) not null,
            rules json not null,
            created_at datetime not null
        );

        create table orders (
            id bigint auto_increment primary key,
            user_id bigint not null,
            challenge_id bigint not null,
            amount int not null,
            created_at datetime not null,
            constraint fk_orders_user foreign key (user_id) references users (id),
            constraint fk_orders_challenge foreign key (challenge_id) references challenges (id),
            index idx_orders_user_id (user_id),
            index idx_orders_challenge_id (challenge_id)
        );

        create table payments (
            id bigint auto_increment primary key,
            order_id bigint not null,
            transaction_id varchar(255) not null,
            amount int not null,
            paid_at datetime not null,
            constraint fk_payments_order foreign key (order_id) references orders (id),
            unique index uniq_payments_order_id (order_id)
        );
        SQL;

        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = <<<SQL
        drop table payments;
        drop table orders;
        drop table challenges;
        drop table users;
        SQL;

        $this->execute($sql);
    }
}
