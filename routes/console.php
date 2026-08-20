<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lịch chạy Batch Cào Data & Gemini AI Phân Tích Giá lúc 12:00 PM (12h trưa hàng ngày)
Schedule::command('prices:analyze')->dailyAt('12:00');
