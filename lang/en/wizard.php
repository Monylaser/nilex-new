<?php

return [

    'page_title'      => 'Add a New Listing',
    'header_title'    => 'Add Your New Listing',
    'header_subtitle' => 'Complete the four steps and publish your listing in minutes',

    'steps' => [
        'category' => 'Choose Category',
        'details'  => 'Listing Details',
        'images'   => 'Add Photos',
        'review'   => 'Contact & Review',
    ],

    'common' => [
        'select_placeholder' => 'Select...',
        'edit'               => 'Edit',
        'yes'                => 'Yes',
        'currency'           => 'EGP',
        'points_unit'        => 'points',
        'prev'               => 'Back',
        'next'               => 'Next',
        'autosave'           => 'Your data is saved automatically as you type',
    ],

    'step1' => [
        'title'      => 'Choose Category',
        'subtitle'   => 'Select the right category for your listing',
        'choose_sub' => 'Choose Subcategory',
    ],

    'step2' => [
        'title'             => 'Listing Details',
        'subtitle'          => 'Write a clear description to attract buyers',
        'ai_title'          => 'AI Assistant',
        'ai_desc'           => 'Write a short description of your product and let AI write the title, description, and suggested price automatically.',
        'ai_placeholder'    => 'Example: Used iPhone 13 in excellent condition, black',
        'ai_generating'     => 'Generating...',
        'ai_generate_btn'   => '✨ Generate with AI',
        'title_label'       => 'Listing Title',
        'title_placeholder' => 'Example: iPhone 15 Pro Max 256GB',
        'desc_label'        => 'Detailed Description',
        'desc_placeholder'  => 'Write the product details, its condition, reason for selling, and any important info (at least 20 characters)',
        'char_suffix'       => 'characters',
        'condition_label'   => 'Condition',
        'condition_new'     => 'New',
        'condition_used'    => 'Used',
        'price_label'       => 'Price',
        'price_type_label'  => 'Price Type',
        'extra_specs'       => 'Additional Specifications',
    ],

    'car' => [
        'section_title'              => 'Car Specifications',
        'brand_label'                => 'Brand',
        'brand_placeholder'          => 'Select brand',
        'brand_other_label'          => 'Enter brand name',
        'brand_other_placeholder'    => 'Example: MG, BYD, Opel...',
        'model_label'                => 'Model',
        'model_placeholder'          => 'Select model',
        'model_placeholder_no_brand' => 'Select brand first',
        'fuel_label'                 => 'Fuel Type',
        'fuel_placeholder'           => 'Select fuel type',
        'transmission_label'         => 'Transmission',
        'transmission_placeholder'   => 'Select transmission',
        'year_label'                 => 'Year',
        'year_placeholder'           => 'Select year',
        'condition_label'            => 'Car Condition',
        'condition_placeholder'      => 'Select car condition',
        'mileage_label'              => 'Mileage',
        'mileage_placeholder'        => 'Example: 85000',
        'mileage_unit'               => 'km',
        'color_label'                => 'Car Color',
        'color_placeholder'          => 'Example: white, black...',
    ],

    'realestate' => [
        'section_title'             => 'Property Specifications',
        'property_type_label'       => 'Property Type',
        'property_type_placeholder' => 'Select property type',
        'listing_type_label'        => 'Listing Type',
        'listing_type_placeholder'  => 'Select listing type',
        'rooms_label'               => 'Number of Rooms',
        'rooms_placeholder'         => 'Select number of rooms',
        'bathrooms_label'           => 'Number of Bathrooms',
        'bathrooms_placeholder'     => 'Select number of bathrooms',
        'floor_label'               => 'Floor',
        'floor_placeholder'         => 'Select floor',
        'finishing_label'           => 'Finishing Type',
        'finishing_placeholder'     => 'Select finishing type',
        'area_label'                => 'Area',
        'area_placeholder'          => 'Example: 120',
        'area_unit'                 => 'm²',
        'compound_label'            => 'In a compound?',
    ],

    'step3' => [
        'title'            => 'Add Photos',
        'subtitle'         => 'Clear photos boost your chances of selling (up to 10 photos, 5 MB each)',
        'drop_text'        => 'Drag photos here or click to choose',
        'formats_hint'     => 'JPEG, PNG, WEBP — max 5 MB per photo',
        'warning_no_image' => 'We recommend adding at least one photo to boost your chances of selling.',
        'count_suffix'     => 'photos',
        'main_badge'       => 'Main',
    ],

    'step4' => [
        'title'                   => 'Contact Info & Review',
        'subtitle'                => 'Review your details before publishing',
        'phone_label'             => 'Contact Number',
        'verify_phone_hint'       => 'Verify your phone number and earn 50 points',
        'governorate_label'       => 'Governorate',
        'governorate_placeholder' => 'Select governorate',
        'city_label'              => 'City',
        'city_placeholder'        => 'Select city',
        'city_placeholder_no_gov' => 'Select governorate first',
        'checklist_title'         => 'Review Checklist',
        'summary_category'        => 'Category',
        'summary_details'         => 'Listing Details',
        'summary_images'          => 'Photos',
        'no_images'               => 'No photos added',
        'submitting'              => 'Publishing...',
        'submit_btn'              => 'Publish Listing Now 🚀',
        'submit_hint'             => 'You will earn 3 points when you publish this listing',
    ],

    'feature' => [
        'title'          => 'Want to feature your listing?',
        'current_points' => 'Your current points:',
        'desc'           => 'Featured listings appear at the top and get more views.',
        'insufficient'   => '(insufficient points)',
        'deduct_prefix'  => 'You will be charged',
        'deduct_suffix'  => 'points right after the listing is published.',
        'opt_0'          => 'No feature (free)',
        'opt_1'          => '1 day',
        'opt_3'          => '3 days',
        'opt_7'          => '7 days',
        'opt_14'         => '14 days',
    ],

    // Listing-edit mode (reuses the same wizard view). Only the chrome differs;
    // category is locked, no points, slug preserved, and any save → re-moderation.
    'edit' => [
        'page_title'           => 'Edit Listing',
        'header_title'         => 'Edit Your Listing',
        'header_subtitle'      => 'Update your listing details — it will be re-reviewed before showing again',
        'remoderation_notice'  => 'Note: any edit returns the listing to the review queue before it shows again.',
        'category_locked'      => 'The category cannot be changed after the listing is published.',
        'existing_images_title' => 'Current Photos',
        'existing_images_hint' => 'You can delete current photos. New photos you upload are added after them.',
        'img_existing_badge'   => 'Current',
        'img_new_badge'        => 'New',
        'img_delete_existing'  => 'Delete photo',
        'submitting'           => 'Saving...',
        'submit_btn'           => 'Save Changes',
        'submit_hint'          => 'Your listing will be re-reviewed after saving',
    ],

    // Select-option labels for the listing wizard (B.3b). Keys are locale-neutral
    // and match the `value` stored in custom_fields_values — except `condition`,
    // whose stored value remains an Arabic literal (see create.blade.php).
    'options' => [
        'fuel' => [
            'petrol'   => 'Petrol',
            'diesel'   => 'Diesel',
            'electric' => 'Electric',
            'hybrid'   => 'Hybrid',
            'gas'      => 'Gas (CNG/LPG)',
        ],
        'transmission' => [
            'automatic' => 'Automatic',
            'manual'    => 'Manual',
        ],
        'condition' => [
            'fabrica'           => 'Factory (unpainted)',
            'excellent'         => 'Excellent condition',
            'good'              => 'Good condition',
            'fair'              => 'Fair condition',
            'needs_maintenance' => 'Needs maintenance',
        ],
        'property_type' => [
            'apartment' => 'Apartment',
            'villa'     => 'Villa',
            'duplex'    => 'Duplex',
            'studio'    => 'Studio',
            'chalet'    => 'Chalet',
            'office'    => 'Office',
            'shop'      => 'Shop',
            'warehouse' => 'Warehouse',
            'land'      => 'Land',
            'building'  => 'Building',
        ],
        'listing_type' => [
            'sale' => 'For Sale',
            'rent' => 'For Rent',
        ],
        'rooms' => [
            '1'  => '1 room',
            '2'  => '2 rooms',
            '3'  => '3 rooms',
            '4'  => '4 rooms',
            '5'  => '5 rooms',
            '6+' => '6+ rooms',
        ],
        'bathrooms' => [
            '1'  => '1 bathroom',
            '2'  => '2 bathrooms',
            '3'  => '3 bathrooms',
            '4+' => '4 or more',
        ],
        'floor' => [
            'ground'  => 'Ground',
            '1'       => '1st',
            '2'       => '2nd',
            '3'       => '3rd',
            '4'       => '4th',
            '5'       => '5th',
            '6+'      => '6th or higher',
            'rooftop' => 'Rooftop',
        ],
        'finishing' => [
            'super_lux'  => 'Super Lux',
            'lux'        => 'Lux',
            'semi_lux'   => 'Semi Lux',
            'core_shell' => 'Core & Shell',
            'unfinished' => 'Unfinished',
            'furnished'  => 'Furnished',
        ],
        'compound' => [
            'yes' => 'Yes',
            'no'  => 'No',
        ],
        'price_type' => [
            'fixed'      => 'Fixed price',
            'negotiable' => 'Negotiable',
            'on_contact' => 'On contact',
        ],
    ],

    // Client-side validation messages (B.3c). Mostly fixed per-field keys; the
    // dynamic custom-field case uses `field_required` with a :field placeholder.
    'validation' => [
        'category_required'        => 'You must choose a category to continue',
        'subcategory_required'     => 'Please choose a subcategory',
        'title_required'           => 'Listing title is required',
        'title_max'                => 'Title must not exceed 255 characters',
        'desc_min'                 => 'Description must be at least 20 characters',
        'price_invalid'            => 'Enter a valid price',
        'price_too_high'           => 'The entered price is too high, please check the number',
        'field_required'           => ':field is required',
        'car_brand_required'       => 'Brand is required',
        'car_brand_other_required' => 'Enter the brand name',
        'car_model_required'       => 'Model is required',
        'fuel_required'            => 'Fuel type is required',
        'transmission_required'    => 'Transmission is required',
        'year_required'            => 'Year is required',
        'condition_required'       => 'Car condition is required',
        'property_type_required'   => 'Property type is required',
        'listing_type_required'    => 'Listing type is required',
        'phone_required'           => 'Contact number is required',
    ],

    // AI assistant (Gemini) messages (B.3c). `session_expired` is shared from
    // the `errors` group below.
    'ai' => [
        'prompt_too_short'  => 'Write a short description (at least 3 characters) first.',
        'success'           => 'Data generated ✨ review and edit it as you like.',
        'failed'            => 'Could not generate the listing right now, you can continue manually.',
        'invalid_prompt'    => 'Write a valid short description first (at least 3 characters).',
        'connection_failed' => 'Could not connect to the AI assistant, you can continue manually.',
    ],

    // Submit / network / image error strings (B.3c). `:name` is the filename.
    'errors' => [
        'max_images'         => 'The maximum allowed is 10 photos',
        'unsupported_format' => 'Unsupported format: :name',
        'image_too_large'    => 'Image larger than 5 MB: :name',
        'session_expired'    => 'Your session has expired, please refresh the page and try again.',
        'unexpected'         => 'An unexpected error occurred, please try again',
        'network'            => 'Could not connect to the server, check your internet connection',
        'fix_errors'         => 'Please correct the highlighted errors',
    ],

    // Completion checklist labels (B.3c).
    'checklist' => [
        'category'    => 'Category selected',
        'title'       => 'Title entered',
        'description' => 'Description entered',
        'price'       => 'Price set',
        'images'      => 'Photos added',
        'phone'       => 'Contact number entered',
    ],

    // Server-side messages (B.3d) consumed by HomeController via __(). These are
    // the locale-aware replacements for the literal Arabic strings previously
    // passed into validate() and into the success/AI responses.
    'server' => [
        'price_max'              => 'The entered price is too high, please check the number',
        'car_brand_required'     => 'Brand is required',
        'car_model_required'     => 'Model is required',
        'car_model_not_in_brand' => 'The selected model does not belong to this brand',
        'fuel_required'          => 'Fuel type is required',
        'transmission_required'  => 'Transmission is required',
        'year_required'          => 'Year is required',
        'condition_required'     => 'Car condition is required',
        'car_brand_other_required' => 'Enter the brand name',
        'property_type_required' => 'Property type is required',
        'listing_type_required'  => 'Listing type is required',
        // Dynamic custom-field required message (:field is the locale-aware label).
        'field_required'         => ':field is required',
        // Success flash (non-AJAX fallback path only — AJAX redirects instead).
        'created_success'        => 'Your listing was saved successfully, and you earned 3 points! 🚀',
        // AI assistant failure (overrides the JS fallback when present).
        'ai_failed'              => 'Could not generate the listing right now (possibly due to usage limits). You can fill in the data manually and continue.',
        // Server-side image validation (used by HomeController::update — the edit flow).
        'image_invalid'          => 'Each uploaded file must be a valid image.',
        'image_mimes'            => 'Supported image formats: JPG, PNG, WEBP.',
        'image_max'              => 'Each image must not exceed 5 MB.',
        'images_max'             => 'The maximum allowed is 10 photos.',
    ],

];
