<?php

return [
    // Production hosting can keep student photos outside the public URL space.
    // Development remains backward compatible unless explicitly enabled.
    'protected_student_photos' => (bool) env('PROTECTED_STUDENT_PHOTOS', false),

    // Keep enabled for local/full-privilege databases. InfinityFree free
    // hosting denies CREATE TRIGGER, so production must explicitly disable it.
    'database_triggers' => (bool) env('DB_SUPPORTS_TRIGGERS', true),
];
