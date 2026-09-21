<?php

return [
    'title' => 'Carbon Calculator',
    'to_complete' => ':percent% to complete',

    'score_card' => [
        'title' => 'Your Score',
        'empty' => 'Not answered yet',
    ],

    // Call-to-action card above the answer summary while the wizard is being
    // filled in — not a real score card (see score_card above, kept ready
    // for once it's wired up to the real calculation). Content changes per
    // step, one key per category (+ "personal" for the personal-data step).
    'cta' => [
        'transportasi' => [
            'heading' => 'Calculating Your Transport Footprint! 🚗',
            'body' => 'Your vehicle and daily distance matter a lot for emissions. 3 more steps to see your result!',
        ],
        'listrik_rumah' => [
            'heading' => 'Calculating Your Home Energy! ⚡',
            'body' => 'Electricity use hides a lot of emissions. 2 more steps to see your result!',
        ],
        'konsumsi_sampah' => [
            'heading' => 'Calculating Your Lifestyle! ♻️',
            'body' => 'Your consumption and waste habits shape your footprint too. 1 more step to see your result!',
        ],
        'personal' => [
            'heading' => 'Your Report Is Ready to Go! 🎉',
            'body' => 'Your lifestyle data is 100% complete! Add your name & email to reveal your full report.',
        ],
    ],

    'summary' => [
        'heading' => 'Your Answers',
        'empty' => 'Your answers will appear here as you fill them in.',
    ],

    'nav' => [
        'back' => 'Back',
        'back_home' => 'Back to Home',
        'back_to' => 'Back to :step',
        'continue' => 'Continue',
        'continue_to' => 'Continue to :step',
        'to_personal_data' => 'Add Your Details & See Results',
        'to_personal_data_short' => 'Your Details',
        'see_result' => 'See My Results',
        'see_result_short' => 'See Results',
        'processing' => 'Processing...',
        'leave_confirm_title' => 'Leave the calculator?',
        'leave_confirm_text' => 'Your answers so far haven\'t been saved and will be lost.',
        'leave_confirm_confirm' => 'Yes, leave',
        'leave_confirm_cancel' => 'Keep Filling In',
    ],

    'personal' => [
        'step_name' => 'Your Details',
        'title' => 'Your Details',
        'panel_title' => 'Almost Done! Save Your Results',
        'panel_description' => 'Add your details so we can send the full report and your emission-reduction recommendations straight to you.',
        'name' => 'Enter your full name',
        'name_placeholder' => 'e.g. Ahmad Jaelani',
        'email' => 'Enter your email',
        'email_placeholder' => 'e.g. ahmadjaelani@gmail.com',
        'whatsapp' => 'Enter your WhatsApp number',
        'whatsapp_placeholder' => 'e.g. 081234567896',
        // The field already shows a +62 prefix, so the in-field example drops the 0.
        'whatsapp_hint_placeholder' => '81234567890',
        'whatsapp_hint' => 'Type it without the +62 prefix. Example: 81234567890',
        'dob' => 'Enter your date of birth',
        'dob_placeholder' => 'Pick a date',
        'gender' => 'Your gender',
        'gender_male' => 'Male',
        'gender_female' => 'Female',
        'intent' => 'If there were a simple way to cut your emissions, would you give it a try?',
        'intent_belum_tahu' => 'Not sure yet',
        'intent_mungkin' => 'Maybe',
        'intent_tentu' => 'Absolutely!',
        'consent' => 'I agree to the :terms and :privacy. Your details are used only for this calculation and are never shared with third parties.',
        'consent_terms' => '[Terms & Conditions]',
        'consent_privacy' => '[Privacy Policy]',
    ],

    'validation' => [
        'form' => 'A few details still need fixing:',
        'incomplete' => 'Please answer every question in this step first.',
        'unanswered' => 'This question still needs an answer.',
    ],
];
