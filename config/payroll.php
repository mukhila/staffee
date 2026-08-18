<?php
return [
    // Statutory gratuity ceiling (0 = no ceiling). Override per country via .env.
    'gratuity_ceiling' => env('GRATUITY_CEILING', 0),
];
