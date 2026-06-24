<?php

return [

    // Listing detail page (show.blade.php) — page-chrome strings (B.4).
    // Specs (labels + option values) reuse wizard.options/car/realestate; the
    // generic currency / home-link reuse ui.*. Only page-specific strings and
    // the two cf-label overrides (color, compound) live here.
    'detail' => [
        'brand'                    => 'Nilex',
        'no_images'                => 'No photos',
        'featured'                 => 'Featured listing',
        'views_suffix'             => 'views',
        'specs_heading'            => 'Listing details',
        'condition_heading'        => 'Condition',
        'condition_new'            => 'New',
        'condition_used'           => 'Used',
        'description_heading'       => 'Listing description',
        'make_offer'               => 'Make an offer to the seller',
        'seller'                   => 'Seller',
        'member_since'             => 'Member since',
        'asking_price'             => 'Asking price',
        'reveal_phone'             => 'Show contact number',
        'loading'                  => 'Loading...',
        'login_to_view'            => 'You must log in to view the number',
        'whatsapp'                 => 'Contact on WhatsApp',
        'category'                 => 'Category',
        'location'                 => 'Location',
        'publish_date'             => 'Published on',
        'views'                    => 'Views',
        'reveal_phone_short'       => 'Show seller number',
        'loading_short'            => 'Loading...',
        'call'                     => 'Call',
        'whatsapp_short'           => 'WhatsApp',
        'offer_title'              => 'Submit an offer',
        'offer_asking'             => 'Asking price:',
        'offer_amount_label'       => 'Your proposed price',
        'offer_amount_placeholder' => 'Enter your price here...',
        'offer_msg_label'          => 'Message to seller (optional)',
        'offer_msg_placeholder'    => 'Example: I am ready to buy today...',
        'offer_submit'             => 'Send offer',
        'offer_submitting'         => 'Sending...',
        'offer_cancel'             => 'Cancel',
        'offer_error'              => 'An error occurred',

        // cf-label overrides (the rest reuse wizard.car.* / wizard.realestate.*).
        'label_color'              => 'Color',
        'label_compound'           => 'Compound',
    ],

];
