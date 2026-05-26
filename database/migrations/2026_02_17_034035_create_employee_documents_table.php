<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            // Links to employee
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            // Links to a specific employment period, nullable for general documents
            $table->unsignedBigInteger('employment_detail_id')->nullable();
            $table->foreign('employment_detail_id')->references('id')->on('employment_details')->onDelete('cascade');

            // Document info
            $table->string('document_type'); // e.g., 'Appointment', 'SSS', 'TIN', etc.
            $table->string('file_path'); // location of uploaded file
            $table->date('issue_date')->nullable(); // optional
            $table->date('expiry_date')->nullable(); // optional for docs that expire
            $table->string('status')->default('active'); // active, expired, archived
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
