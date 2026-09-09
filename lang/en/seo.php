<?php

/**
 * English counterpart of lang/id/seo.php.
 *
 * Note that the site serves both languages from the same URL (the switcher
 * stores the choice in the session), so search engines only ever crawl the
 * Indonesian version. These strings exist for visitors who switch to English,
 * not as a second indexable page — see the SEO notes in deploy/DEPLOY.md.
 */
return [
    'site_name' => 'Tokio Marine Jaga Bumi',

    'default' => [
        'title' => 'Carbon Calculator | Tokio Marine Jaga Bumi',
        'description' => 'Measure your daily carbon footprint in 3 minutes with the Tokio Marine Jaga Bumi carbon calculator, then start acting on it from home.',
        'keywords' => 'tokio marine jaga bumi, jaga bumi, carbon calculator, kalkulator karbon, carbon footprint calculator, household emissions',
    ],

    'home' => [
        'title' => 'Carbon Calculator | Tokio Marine Jaga Bumi',
        'share_title' => 'Tokio Marine Jaga Bumi Carbon Calculator',
        'description' => 'Tokio Marine Jaga Bumi carbon calculator: measure the footprint of your transport, household electricity, and waste habits in 3 minutes — free, no account needed.',
        'keywords' => 'tokio marine jaga bumi, jaga bumi, carbon calculator, kalkulator karbon, carbon footprint indonesia, daily carbon emissions',
    ],

    'calculator' => [
        'title' => 'Measure Your Carbon Footprint | Jaga Bumi',
        'share_title' => 'Measure Your Carbon Footprint — Jaga Bumi',
        'description' => 'Answer a few quick questions about your transport, household electricity, and waste habits. You get your annual footprint score and what to do about it.',
        'keywords' => 'carbon calculator, kalkulator karbon, carbon footprint, emissions calculator, tokio marine jaga bumi',
    ],

    'result' => [
        'title' => 'Your Annual Carbon Footprint | Tokio Marine Jaga Bumi',
        'share_title' => 'My Carbon Footprint',
        'description' => 'Here is my annual carbon footprint from the Tokio Marine Jaga Bumi carbon calculator. Measure yours too — it only takes 3 minutes.',
    ],

    'report' => [
        'title' => 'Carbon Footprint Report | Tokio Marine Jaga Bumi',
        'share_title' => 'Carbon Footprint Report',
        'description' => 'The full annual carbon footprint report from the Tokio Marine Jaga Bumi carbon calculator.',
    ],

    'admin' => [
        'title' => 'Admin | Tokio Marine Jaga Bumi',
        'description' => '',
    ],

    'image_alt' => 'Tokio Marine Jaga Bumi carbon calculator — measure your footprint in 3 minutes',

    /** Same single source as the Indonesian file; see the note there. */
    'faq' => [
        [
            'q' => 'Are the results of this carbon calculator 100% accurate?',
            'a' => 'The calculation is an estimate based on average emission factors (IPCC and US EPA standards). It is designed to give you a sense of the scale of your daily footprint and raise awareness, not to serve as an absolute measurement.',
        ],
        [
            'q' => 'Why does it only cover road transport, household power, and appliances?',
            'a' => 'These three sectors are the main contributors to daily emissions at an individual level. Measuring fuel use and distance travelled, household electricity, and appliance intensity maps out where energy savings are most effective.',
        ],
        [
            'q' => 'What if I drive an electric vehicle (EV) or have solar panels at home?',
            'a' => 'The calculator adjusts emission factors for greener options. Electric vehicles and renewable energy produce a far lower estimate than petrol or conventional grid electricity.',
        ],
        [
            'q' => 'Is the personal data I enter safe?',
            'a' => 'Yes. What you enter is used only to calculate your emissions and personalise the recommendations on your result page. We protect your privacy and never share your personal data with third parties.',
        ],
        [
            'q' => 'What can I do once I know my estimated carbon emissions?',
            'a' => 'On the result page you get concrete recommendations matched to your impact band (light, moderate, or high) — from daily waste sorting tips to the nearest Rumah Pilah / Bank Sampah where you can start cutting what is left.',
        ],
    ],
];
