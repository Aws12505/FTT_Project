<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_id')->unique();                  // e.g. "54-562"
            $table->string('full_name');                          // e.g. "Adler Test"
            $table->timestamp('date_submitted')->nullable();      // Entry.DateSubmitted
            $table->string('time_off_type')->nullable();          // WhatTypeOfTimeOffAreYouRequesting
            $table->date('day1')->nullable();
            $table->date('day2')->nullable();
            $table->date('day3')->nullable();
            $table->date('day4')->nullable();
            $table->date('day5')->nullable();
            $table->date('day6')->nullable();
            $table->date('day7')->nullable();
            $table->string('acceptance_rejection')->nullable();   // CorrespondenceInternalUseOnly.AcceptanceRejection
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off_requests');
    }
};
