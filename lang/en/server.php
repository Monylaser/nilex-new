<?php

/*
|--------------------------------------------------------------------------
| Server response messages (Phase C.7)
|--------------------------------------------------------------------------
|
| Centralized, locale-aware server-side flash / error / validation messages
| that controllers, form requests, services, middleware and Livewire
| components return directly. Grouped by feature for auditability.
|
| NOTE: SmartAdCreator (the `ai` group) is an admin/Filament component;
| its translations only take visual effect once Phase D enables the
| SetLocale middleware on the /admin panel.
|
*/

return [

    'auth' => [
        'otp_required'        => 'Please enter the verification code',
        'otp_digits'          => 'The code must be 4 digits',
        'verified_success'    => 'Your account has been verified successfully! 🎉',
        'otp_invalid'         => 'The code is incorrect or has expired.',
        'otp_resent'          => 'A new code has been sent successfully.',
        'otp_throttled'       => 'Too many attempts. Try again in :seconds seconds.',
        'contact_invalid'     => 'Please enter a valid email address or a valid Egyptian phone number (01XXXXXXXXX).',
        'email_taken'         => 'This email address is already registered.',
        'phone_taken'         => 'This phone number is already registered.',
        'social_error'        => 'An error occurred while trying to sign in with :provider',
        'device_limit'        => 'Sorry, you have reached the maximum number of accounts from this device.',
        'device_limit_detailed' => 'Sorry, you have reached the maximum number of accounts from this device (3 accounts maximum).',
        'login_success'       => 'Signed in successfully!',
        'ban_reason_default'  => 'Violation of platform policies',
        'banned'              => 'Your account has been suspended. Reason: :reason',
        'otp_gate'            => 'You must verify your account first to access this page.',
    ],

    'ads' => [
        'duration_missing'             => 'Campaign duration is not set.',
        'category_only_category_page'  => 'Category is only available for the category-page banner.',
        'self_service_disabled'        => 'Self-service advertising is currently disabled.',
        'unknown_placement'            => 'Unknown ad placement: :placement',
        'no_price'                     => 'No price configured for placement [:placement] and duration [:days] days.',
        'not_owner'                    => 'This campaign does not belong to your account.',
        'already_paid'                 => 'This campaign has already been paid.',
        'paymob_auth_failed'           => 'Failed to connect to the payment gateway. Please try again.',
        'paymob_order_failed'          => 'Could not create the payment order. Please try again.',
        'paymob_key_failed'            => 'Could not complete the payment. Please try again.',
    ],

    'payment' => [
        'refund_required' => 'You must accept the refund and return policy before completing the payment.',
    ],

    'dashboard' => [
        'offer_accepted'   => 'Offer accepted successfully! ✅',
        'offer_rejected'   => 'Offer rejected. ❌',
        'listing_deleted'  => 'Listing deleted successfully 🗑️',
        'already_featured' => 'This listing is already featured!',
        'featured_success' => 'Points deducted and listing featured successfully! 🚀',
        'feature_limit_plan'           => 'You have reached the maximum number of featured listings on your current plan.',
        'feature_limit_monthly'        => 'You have reached the monthly featuring limit on your current plan.',
        'feature_insufficient_points'  => 'Insufficient points — required: :cost points, available: :available points',
        'feature_unsupported_duration' => 'Unsupported featuring duration: :days days. Available values: :values',
    ],

    'offer' => [
        'own_listing'  => 'You cannot make an offer on your own listing!',
        'duplicate'    => 'You already have a pending offer for this listing.',
        'sent_success' => 'Your offer has been sent to the seller successfully! 🚀',
    ],

    'message' => [
        'self' => 'You cannot send a message to yourself.',
    ],

    // Post-registration phone verification (PhoneVerificationController)
    'phone' => [
        'required'     => 'Please enter your mobile number.',
        'invalid'      => 'Invalid Egyptian mobile number (must start with 01 and be 11 digits).',
        'already_taken'=> 'This phone number is already registered to another account.',
        'no_pending'   => 'There is no number awaiting confirmation. Send the verification code first.',
        'otp_invalid'  => 'The code is incorrect or has expired.',
    ],

    // Listing editing after publish (HomeController::edit/update)
    'listing' => [
        'updated_success'           => 'Your listing was updated and will be reviewed again before it shows. ✏️',
        'edit_blocked_sale_pending' => 'This listing cannot be edited because it has a sale awaiting confirmation.',
    ],

    'account' => [
        // Display name substituted for an anonymized account after deletion.
        'deleted_name'          => 'Deleted User',
        // Shown when someone tries to sign in to a deleted/anonymized account.
        'deleted_login_blocked' => 'This account has been deleted and cannot be signed in.',
    ],

    'sale_confirmation' => [
        'created'           => 'The sale was recorded and the listing closed. Awaiting buyer confirmation.',
        'invalid_buyer'     => 'The selected buyer is not valid for this listing.',
        'already_confirmed' => 'A sale for this listing has already been confirmed.',
        'select_required'   => 'Please select a buyer first.',
        'buyer_confirmed'   => 'Your purchase has been confirmed successfully. ✅',
        'confirm_failed'    => 'Could not confirm the purchase.',
    ],

    // Purchase-confirmation request notification (sent to the buyer — database + mail)
    'sale_confirmation_notif' => [
        'subject'    => 'Purchase confirmation on Nilex',
        'greeting'   => 'Hi :name',
        'line1'      => ':seller reported that you bought ":title" through the Nilex platform.',
        'line2'      => 'Please confirm the purchase so you can rate the seller and help other buyers.',
        'action'     => 'Confirm purchase',
        'line3'      => 'Thank you for using Nilex!',
        'db_message' => 'Confirm your purchase of ":title" from :seller',
    ],

    // Seller review (written by the buyer after confirming the purchase)
    'review' => [
        'submitted'       => 'Thank you! Your review has been submitted successfully. ⭐',
        'rating_required' => 'Please choose a rating from 1 to 5 stars.',
        'not_allowed'     => 'You cannot review this transaction.',
    ],

    'ai' => [
        'photo_max'              => 'Image size must not exceed 5 MB',
        'photo_image'            => 'The file must be an image',
        'photo_mimes'            => 'Supported formats: JPG, PNG, WEBP, GIF',
        'audio_max'              => 'Audio file size must not exceed 10 MB',
        'audio_mimes'            => 'Supported formats: MP3, WAV, M4A, OGG, WEBM',
        'note_max'              => 'Notes must not exceed 500 characters',
        'too_many_photos'        => 'You cannot upload more than :max images',
        'need_input'             => 'Please upload at least one image or record audio',
        'no_api_key'             => 'Gemini API key is missing from the .env file',
        'no_response'            => 'No response received from the AI',
        'unknown_error'          => 'Unknown error',
        'server_error'           => 'Server error: :message',
        'technical_error'        => '⚠️ Technical error: :message',
        'step_checking'          => 'Checking settings...',
        'step_preparing'         => 'Preparing data to send...',
        'step_processing_images' => 'Processing images...',
        'step_images_done'       => 'Images processed',
        'step_processing_audio'  => 'Processing audio recording...',
        'step_audio_done'        => 'Audio recording processed',
        'step_connecting'        => 'Connecting to the AI...',
        'step_analyzing'         => 'Analyzing results...',
        'step_sending'           => 'Sending data...',
        'step_success'           => 'Done successfully! ✅',
    ],

];
