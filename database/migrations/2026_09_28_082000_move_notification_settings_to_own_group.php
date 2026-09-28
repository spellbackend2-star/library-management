<?php

use App\Models\CentralSetting;
use App\Services\CentralSettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The notification controls used to live in the "general" group.
     * Move any already stored rows into the dedicated "notification"
     * group so no saved value is lost.
     */
    public function up(): void
    {
        $connection = $this->centralConnection();

        if (! Schema::connection($connection)->hasTable('settings')) {
            return;
        }

        $keys = array_keys(CentralSettingService::NOTIFICATION_SETTINGS);

        CentralSetting::on($connection)
            ->where('group', CentralSettingService::GENERAL_GROUP)
            ->whereIn('key', $keys)
            ->update(['group' => CentralSettingService::NOTIFICATION_GROUP]);
    }

    public function down(): void
    {
        $connection = $this->centralConnection();

        if (! Schema::connection($connection)->hasTable('settings')) {
            return;
        }

        $keys = array_keys(CentralSettingService::NOTIFICATION_SETTINGS);

        CentralSetting::on($connection)
            ->where('group', CentralSettingService::NOTIFICATION_GROUP)
            ->whereIn('key', $keys)
            ->update(['group' => CentralSettingService::GENERAL_GROUP]);
    }

    /**
     * Central database connection name.
     */
    protected function centralConnection(): string
    {
        return config('tenancy.database.central_connection')
            ?? config('database.default');
    }
};
