<?php

declare(strict_types=1);

namespace Migrations;

use Eva\Database\Migrations\AbstractMigration;

class Migration1792952837 extends AbstractMigration
{
    public function up(): void
    {
        $sql = <<<SQL
        create table trading_accounts (
            id bigint auto_increment primary key,
            platform varchar(32) not null,
            balance int not null,
            currency varchar(16) not null,
            leverage int not null
        );

        create table challenge_accounts (
            id bigint auto_increment primary key,
            user_id bigint not null,
            challenge_id bigint not null,
            trading_account_id bigint not null,
            status varchar(32) not null,
            created_at datetime not null,
            constraint fk_challenge_accounts_user foreign key (user_id) references users (id),
            constraint fk_challenge_accounts_challenge foreign key (challenge_id) references challenges (id),
            constraint fk_challenge_accounts_trading_account foreign key (trading_account_id) references trading_accounts (id),
            index idx_challenge_accounts_user_id (user_id),
            index idx_challenge_accounts_challenge_id (challenge_id)
        );

        create table challenge_account_progress (
            challenge_account_id bigint primary key,
            profit int not null,
            loss int not null,
            daily_loss int not null,
            trading_days_completed int not null,
            constraint fk_challenge_account_progress_account foreign key (challenge_account_id) references challenge_accounts (id)
        );
        SQL;

        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = <<<SQL
        drop table challenge_account_progress;
        drop table challenge_accounts;
        drop table trading_accounts;
        SQL;

        $this->execute($sql);
    }
}
