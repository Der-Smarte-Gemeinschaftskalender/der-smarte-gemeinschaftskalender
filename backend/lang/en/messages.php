<?php

declare(strict_types=1);

return [
    'general' => [
        'unexpectedError'            => 'Unexpected error',
        'unexpectedErrorWithMessage' => 'Unexpected error: :message',
        'unknownError'               => 'Unknown error',
        'groupIdRequired'            => 'The Mobilizon group ID is required.',
        'notInGroup'                 => 'You are not a member of the group or the group does not exist.',
        'imageNotFound'              => 'Image not found',
        'imageFetchFailed'           => 'Failed to fetch image',
    ],

    'auth' => [
        'unauthorized'                      => 'Unauthorized',
        'userValidationFailed'              => 'User validation failed',
        'registrationUnavailable'           => 'Registration is currently not possible. Please try again later.',
        'strictModeOnly'                    => 'Only available in strict mode',
        'profileValidationFailed'           => 'Profile validation failed',
        'organisationValidationFailed'      => 'Organisation validation failed',
        'organisationUsernameEqualsProfile' => 'The organisation username must not be the same as the profile username',
        'organisationUsernameTaken'         => 'Organisation username already exists',
        'mobilizonLoginFailed'              => 'User Mobilizon login failed',
        'profileCreationFailed'             => 'Profile creation failed: :message',
        'personNotAvailable'                => 'Mobilizon profile not available after creation',
        'invalidEmail'                      => 'Please enter a valid email address',
        'resetLinkSent'                     => 'If this email address exists in our system, you will receive an email with a link to reset your password.',
        'resetLinkFailed'                   => 'Error sending the password reset link',
        'userValidated'                     => 'User validated successfully',
        'invalidInput'                      => 'Missing or invalid input',
        'invalidToken'                      => 'Invalid or expired token',
        'passwordReset'                     => 'Password reset successfully',
        'passwordResetFailed'               => 'Error resetting the password',
        'loggedOut'                         => 'Successfully logged out',
    ],

    'events' => [
        'notFound'             => 'Event not found',
        'imageSaveFailed'      => 'The image could not be saved. The file may be corrupted or in an unsupported format. The event was not created - please choose a different image (JPG, PNG, GIF or WEBP).',
        'imageTooLarge'        => 'The image exceeds the maximum allowed file size of :size. Please choose a smaller image.',
        'imageUploadFailed'    => 'The image could not be uploaded. Please try again.',
        'createFailed'         => 'Failed to create event',
        'deleted'              => 'Event deleted successfully',
        'deleteFailed'         => 'Error deleting the event with Mobilizon ID :id: :error',
    ],

    'series' => [
        'invalidInterval'     => 'Invalid interval.',
        'noEventCreated'      => 'No event was created. Please check your interval settings.',
        'allEventsOnHolidays' => 'No events were created. All dates fall on public holidays or school holidays.',
        'imageSaveFailed'     => 'The image could not be saved. The file may be corrupted or in an unsupported format. The recurring event was not created - please choose a different image (JPG, PNG, GIF or WEBP).',
        'holidaysFetchFailed' => 'Error fetching public holidays. Please try again later.',
        'deleted'             => 'All events of the series were deleted successfully.',
    ],

    'uploads' => [
        'noData'           => 'No data uploaded. Please upload a valid ICS file.',
        'noEventsSelected' => 'No events selected or already created.',
        'eventsCreated'    => 'Events created successfully.',
        'deleted'          => 'Uploaded file and associated events were deleted successfully.',
        'icalParseError'   => 'Error parsing iCal data: :message',
    ],

    'imports' => [
        'invalidMobilizonFields' => 'Invalid Mobilizon fields',
        'urlRequired'            => 'URL is required',
        'deleted'                => 'Deleted successfully.',
    ],

    'organisations' => [
        'usernameTaken'           => 'An organisation with this username cannot be created because it is already taken.',
        'usernameUsedByUser'      => 'An organisation with this username cannot be created because it is already used by a user.',
        'mobilizonCreateFailed'   => 'The organisation could not be created in Mobilizon.',
        'updateFailed'            => 'Failed to update organisation',
        'inviterNotFound'         => 'Inviting user not found',
        'acceptInvitationFailed'  => 'Failed to accept invitation in Mobilizon',
        'removeMemberFailed'      => 'Error removing the member from the organisation',
        'removeMemberMailFailed'  => 'The member was removed from the organisation, but there was a problem sending the notification email.',
        'memberRemoved'           => 'Member removed from the organisation successfully',
        'leaveFailed'             => 'Error leaving the organisation',
        'left'                    => 'Successfully left the organisation',
        'recommendedUpdated'      => 'Updated successfully',
        'recommendedUpdateFailed' => 'Error updating the featured status',
        'notFound'                => 'Organisation not found',
    ],

    'users' => [
        'personRegistered'     => 'Profile registered successfully',
        'fillAllFields'        => 'Please fill in all fields',
        'emailTaken'           => 'This email address is already taken',
        'profileUpdated'       => 'Profile updated successfully',
        'profileUpdateFailed'  => 'Error updating the profile: :message',
        'passwordsMismatch'    => 'The new passwords do not match',
        'currentPasswordWrong' => 'Current password is incorrect',
        'passwordUpdated'      => 'Password updated successfully',
    ],

    'admin' => [
        'accessDenied'         => 'Access denied. Admin rights required.',
        'userValidationFailed' => 'Validation of the new user failed',
        'roleChangeFailed'     => 'Failed to change the role of the new user',
        'userCreated'          => 'Created successfully',
        'userUpdated'          => 'User updated successfully',
        'userDeleted'          => 'User deleted successfully',
        'userDeleteFailed'     => 'Error deleting the user',
    ],

    'approval' => [
        'fileNotFound'              => 'File not found',
        'fileUrlNotFound'           => 'File URL not found',
        'externalFileFetchFailed'   => 'Error fetching the external file',
        'alreadyHandled'            => 'Request has already been handled',
        'approved'                  => 'Request approved successfully',
        'approveFailed'             => 'Error approving the request',
        'approveError'              => 'Failed to approve request',
        'rejected'                  => 'Request rejected successfully',
        'rejectFailed'              => 'Error rejecting the request',
        'invalidTypeOrMethod'       => 'Invalid request type or method',
        'createdEventIdMissing'     => 'Event ID not found in the request data',
        'unsupportedType'           => 'Unsupported request type',
        'organisationProcessFailed' => 'Failed to process organisation',
        'unknownEventType'          => 'Unknown event type for approval request: :type',
        'pendingExistsOrganisation' => 'There is already a pending request for this organisation.',
        'pendingExistsEvent'        => 'There is already a pending request for this event.',
        'requested'                 => 'Approval requested for :type :action',
        'createFailed'              => 'Error creating the approval request.',
    ],

    'notifications' => [
        'saveFailed'              => 'Error saving the notification.',
        'createFailed'            => 'Error creating the notification.',
        'subscribed'              => 'Subscribed successfully.',
        'tokenRequired'           => 'Token is required',
        'noSubscription'          => 'This email has no subscription.',
        'updated'                 => 'Notification updated successfully.',
        'updateFailed'            => 'Error updating the notification.',
        'tokenNotFound'           => 'Verification token not found.',
        'confirmed'               => 'Notification confirmed successfully.',
        'confirmFailed'           => 'Error confirming the notification.',
        'declined'                => 'Notification declined successfully.',
        'declineFailed'           => 'Error declining the notification.',
        'unsubscribed'            => 'Unsubscribed successfully.',
        'unsubscribeFailed'       => 'Error unsubscribing.',
        'subscriptionFound'       => 'Subscription found.',
        'subscriptionFetchFailed' => 'Error fetching the subscription.',
    ],

    'tiles' => [
        'invalidCoordinates' => 'Invalid coordinates',
        'serverConfigError'  => 'Server configuration error',
        'fetchFailed'        => 'Error fetching the tile',
    ],

    'emailTemplates' => [
        'notFound' => 'Template not found',
        'labels'   => [
            'VALIDATION'               => 'Email validation',
            'PASSWORD_RESET'           => 'Password reset',
            'APPROVAL_REQUEST_SENT'    => 'Approval request sent',
            'APPROVAL_REQUEST_HANDLED' => 'Approval request handled',
            'ORGANISATION_INVITATION'  => 'Organisation invitation',
            'ORGANISATION_REMOVAL'     => 'Removed from organisation',
        ],
    ],

    'materialGenerator' => [
        'templateDeleted' => 'Template was deleted',
        'groupRequired'   => 'mobilizon_group_id or mobilizon_preferredusername is required.',
    ],

    'mobilizon' => [
        'invalidResponse' => 'Mobilizon returned an invalid response (HTTP :status)',
        'tooManyRequests' => 'Too many requests – maximum number of retries exceeded',
    ],
];
