<?php

use App\Http\Controllers\Api\Chat\ConversationMediaController;
use App\Http\Controllers\Api\Chat\ConversationSettingController;
use App\Http\Controllers\Api\Chat\CreateGroupController;
use App\Http\Controllers\Api\Chat\DeleteMessageController;
use App\Http\Controllers\Api\Chat\GetConversationController;
use App\Http\Controllers\Api\Chat\GetMessageController;
use App\Http\Controllers\Api\Chat\GroupAdminManageController;
use App\Http\Controllers\Api\Chat\GroupDeleteController;
use App\Http\Controllers\Api\Chat\GroupInfoController;
use App\Http\Controllers\Api\Chat\GroupMediaController;
use App\Http\Controllers\Api\Chat\GroupParticipantController;
use App\Http\Controllers\Api\Chat\GroupParticipantManageController;
use App\Http\Controllers\Api\Chat\GroupSettingsController;
use App\Http\Controllers\Api\Chat\ReactMessageController;
use App\Http\Controllers\Api\Chat\SendMessageController;
use App\Http\Controllers\Api\Chat\MyConnectionController;
use App\Http\Controllers\Api\Chat\NotificationController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::prefix('v1')->group(function () {
        Route::post('/message/send', SendMessageController::class);
        Route::get('/conversations', GetConversationController::class);
        Route::get('/chat/messages', GetMessageController::class);
        Route::delete('/chat/message/{message_id}/delete', DeleteMessageController::class);

        Route::prefix('group')->group(function () {
            Route::post('/create', CreateGroupController::class);
            Route::get('/{group_id}/media', GroupMediaController::class);
            Route::get('/{group_id}/participants', GroupParticipantController::class);
            Route::post('/{group_id}/delete', GroupDeleteController::class);

            Route::controller(GroupSettingsController::class)->group(function () {
                Route::post('/{group_id}/settings/permissions', 'permissionsToggle');
                Route::post('/{group_id}/settings/group-type', 'groupTypeToggle');
            });

            Route::controller(GroupInfoController::class)->group(function () {
                Route::get('/{group_id}/info', 'getInfo');
                Route::post('/{group_id}/update-info', 'updateInfo');
            });

            Route::controller(GroupParticipantManageController::class)->group(function () {
                Route::post('/{group_id}/add-participate', 'addParticipate');
                Route::post('/{group_id}/remove-participate', 'removeParticipate');
                Route::post('/{group_id}/leave', 'leaveGroup');
            });

            Route::controller(GroupAdminManageController::class)->group(function () {
                Route::post('/{group_id}/promote-as/admin', 'promoteAsAdmin');
                Route::post('/{group_id}/demote-as/member', 'demoteAsMember');
            });
        });

        Route::controller(ConversationSettingController::class)->group(function () {
            Route::post('conversation/{conversation_id}/setting/notification', 'notificationSetting');
        });

        Route::get('/conversation/{conversation_id}/media', ConversationMediaController::class);

        Route::controller(ReactMessageController::class)->prefix('message')->group(function () {
            Route::post('/{message_id}/react', 'reactToggle');
        });

        Route::get('my-connections', MyConnectionController::class);

        Route::controller(NotificationController::class)->group(function () {
            Route::get('notifications', 'getNotifications');
            Route::post('notifications/all/mark-as-read', 'allMarkAsRead');
            Route::post('notification/{notification_id}/mark-as-read', 'markAsRead');
            Route::post('notification/{notification_id}/mark-as-unread', 'markAsUnread');
        });
    });
});
