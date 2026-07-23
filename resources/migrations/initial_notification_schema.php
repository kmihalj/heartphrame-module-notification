<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;

return new class implements ReversibleMigrationInterface {
    /**
     * HR: Kreira prenosivu tablicu korisničkih obavijesti bez ovisnosti o
     *     određenom SQL poslužitelju.
     * EN: Creates the portable user-notification table without depending on a
     *     specific SQL server.
     */
    public function up(Database $db): void
    {
        $schema = $db->schema();
        if ($schema->hasTable(ModuleNotification::TABLE_NOTIFICATIONS)) {
            return;
        }

        $schema->create(ModuleNotification::TABLE_NOTIFICATIONS, static function (Blueprint $table): void {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->string('notification_key', 128)->index();
            $table->string('title', 255);
            $table->longText('message');
            $table->string('link_url', 1024)->nullable();
            $table->string('source_module', 128)->index();
            $table->string('source_reference', 190)->nullable()->index();
            $table->string('dedup_key', 190)->nullable();
            $table->longText('data_json')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['user_id', 'dedup_key'],
                'notification_user_dedup_unique',
            );
            $table->index(
                ['user_id', 'read_at', 'created_at'],
                'notification_user_inbox_idx',
            );
        });
    }

    /**
     * HR: Uklanja samo tablicu koja pripada Notification modulu.
     * EN: Removes only the table owned by the Notification module.
     */
    public function down(Database $db): void
    {
        $db->schema()->dropIfExists(ModuleNotification::TABLE_NOTIFICATIONS);
    }
};
