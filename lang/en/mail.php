<?php

/**
 * Automated email copy — the counterpart of lang/id/mail.php. The language
 * follows the `locale` stored on the submission, not the current request.
 */
return [
    'result' => [
        'subject' => 'Your carbon footprint results are ready, :name!',
        'preheader' => 'Your score, annual emission estimate, and the links to open or download your report.',

        'brand' => 'Tokio Marine Green Campaign',
        'greeting' => 'Hi, :name! 👋',
        'intro' => 'Thanks for calculating your carbon footprint. Here is the summary:',

        'score_label' => 'Your score',
        'total_label' => 'Estimated annual emissions',
        'ton_unit' => 'tonnes CO₂ / year',

        'recommendation_heading' => 'Recommended actions for you:',

        'cta_result' => 'View Full Results',
        'cta_report' => 'Download Report (PDF)',
        'cta_help' => 'Buttons not working? Copy this link into your browser:',

        'download_hint' => 'The "Download Report" link opens your report sheet and brings up the print dialog — choose :action to keep a copy.',
        'download_hint_action' => 'Save as PDF',
        'keep_link' => 'Keep this email. The link above is the only way back to your results, and it stays valid for the duration of the campaign.',
        'private_link' => 'This link is personal — anyone who has it can see your results, so share it sparingly.',

        'footer_auto' => 'This is an automated message, please do not reply.',
        'footer_reason' => 'You are receiving this because you filled in the carbon calculator at :app.',
    ],
];
