<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadedEventController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreatedEventController;
use App\Http\Controllers\ImportedEventController;
use App\Http\Controllers\MaterialGeneratorValueController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\SeriesEventController;
use App\Http\Controllers\SingleEventController;
use App\Http\Controllers\UserController;

Route::middleware('api')->prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-with-profile-and-organisation', [AuthController::class, 'registerWithProfileAndOrganisation']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/validateUser', [AuthController::class, 'validateUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/me', [AuthController::class, 'me']);
});

Route::prefix('series-events')->group(function () {
    Route::get('/', [SeriesEventController::class, 'index']);
    Route::post('/', [SeriesEventController::class, 'create']);
    Route::get('/{seriesEvent}', [SeriesEventController::class, 'show']);
});

Route::prefix('uploaded-events')->group(function () {
    Route::get('/', [UploadedEventController::class, 'index'])->name('uploadEvents.index');
    Route::get('/{uploadedEvent}', [UploadedEventController::class, 'show'])->name('uploadEvents.show');
    Route::get('/create', [UploadedEventController::class, 'create'])->name('uploadEvents.create');
    Route::get('/create_url', [UploadedEventController::class, 'create_url'])->name('uploadEvents.create_url');
    Route::post('/upload', [UploadedEventController::class, 'upload'])->name('uploadEvents.try');
    Route::post('/accept', [UploadedEventController::class, 'accept_upload'])->name('uploadEvents.accept');
});

Route::prefix('imported-events')->group(function () {
    Route::get('/', [ImportedEventController::class, 'index']);
    Route::post('/', [ImportedEventController::class, 'store']);
    Route::get('/{importedEvent}', [ImportedEventController::class, 'show']);
    Route::post('/{importedEvent}/status', [ImportedEventController::class, 'changeStatus']);
});

Route::prefix('single-events')->group(function () {
    Route::get('/', [SingleEventController::class, 'index']);
    Route::get('/{singleEvent}', [SingleEventController::class, 'show']);
    Route::post('/', [SingleEventController::class, 'store']);
});

Route::prefix('created-events')->group(function () {
    Route::get('/statistics/{group_id}', [EventController::class, 'index']);
    Route::post('/findEvent', [CreatedEventController::class, 'getEventId']);
    Route::post('/{createdEvent}', [CreatedEventController::class, 'update']);
    Route::delete('/{createdEvent}', [CreatedEventController::class, 'delete']);
    Route::post('/update-status/{createdEvent}', [CreatedEventController::class, 'updateStatus']);
    Route::get('/{createdEvent}', [CreatedEventController::class, 'show']);
    Route::get('/{createdEvent}/image', [CreatedEventController::class, 'fetchEventImage']);
});

Route::prefix('admin')->name('admin.user.')->group(function () {
    Route::get('', [AdminController::class, 'user_index'])->name('index');
    Route::get('export/users', [AdminController::class, 'export_user_emails']);
    Route::get('mobilizonAccessData', [AdminController::class, 'mobilizonAccessData']);
    Route::post('user', [AdminController::class, 'create_user'])->name('create');
    Route::get('user/{user}', [AdminController::class, 'show_user'])->name('show');
    Route::patch('user/{user}', [AdminController::class, 'update_user'])->name('update');
    Route::delete('user/{user}', [AdminController::class, 'delete_user'])->name('delete');
});

Route::prefix('approval-requests')->group(function () {
    Route::get('/events', [ApprovalRequestController::class, 'indexEvents']);
    Route::get('/organisations', [ApprovalRequestController::class, 'indexOrganisations']);
    Route::get('/{approvalRequest}', [ApprovalRequestController::class, 'show'])->where('approvalRequest', '[0-9]+');
    Route::get('/{approvalRequest}/file', [ApprovalRequestController::class, 'serveFile']);
    Route::get('/{approvalRequest}/external-file', [ApprovalRequestController::class, 'serveExternalFile']);
    Route::post('/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve']);
    Route::post('/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject']);
});


Route::prefix('notifications')->group(function () {
    Route::post('/', [NotificationController::class, 'create']);
    Route::patch('/', [NotificationController::class, 'update']);
    Route::post('/confirm', [NotificationController::class, 'confirmEmailNotification']);
    Route::post('/disallow', [NotificationController::class, 'addEmailDisallowNotification']);
    Route::post('/unsubscribe', [NotificationController::class, 'unsubscribeEmailNotification'])->name('unsubscribe');
    Route::get('/', [NotificationController::class, 'show']);
});

Route::prefix('material-generator-values')->group(function () {
    Route::post('/', [MaterialGeneratorValueController::class, 'upsert']);
    Route::get('/', [MaterialGeneratorValueController::class, 'show']);
    Route::get('/image', [MaterialGeneratorValueController::class, 'getImage']);
});

Route::prefix('user')->group(function () {
    Route::get('/instance', [UserController::class, 'instance']);
    Route::post('/registerPerson', [UserController::class, 'registerPerson']);
    Route::post('/updateProfile', [UserController::class, 'updateProfile']);
    Route::post('/updatePassword', [UserController::class, 'updatePassword']);
});

Route::prefix('organisations')->group(function () {
    Route::get('/myOrganisations', [OrganisationController::class, 'myOrganisations']);
    Route::post('/requestOrganisationCreation', [OrganisationController::class, 'requestOrganisationCreation']);
    Route::get('/requestedOrganisationsFromMe', [OrganisationController::class, 'indexRequestedOrganisationFromMe']);
    Route::get('/requestedOrganisations', [OrganisationController::class, 'indexRequestedOrganisation']);
    Route::get('/', [OrganisationController::class, 'indexOrganisation']);
    Route::get('/group', [OrganisationController::class, 'showGroup']);
    Route::get('/group/{group_id}', [OrganisationController::class, 'showGroupById']);
    Route::post('/group', [OrganisationController::class, 'updateGroup']);
    Route::get('/avatar', [OrganisationController::class, 'getOrganisationAvatar']);
    Route::post('/invite', [OrganisationController::class, 'inviteMember']);
    Route::get('/mostUsedTags', [OrganisationController::class, 'getMostUsedTags']);
    Route::post('/rejectInvitation', [OrganisationController::class, 'rejectInvitation']);
    Route::post('/acceptInvitation', [OrganisationController::class, 'acceptInvitation']);
    Route::post('/removeMember', [OrganisationController::class, 'removeMember']);
    Route::post('/leave', [OrganisationController::class, 'leaveOrganiation']);
    Route::get('/mobilizon-groups', [OrganisationController::class, 'loadMobilizonGroups']);
});
