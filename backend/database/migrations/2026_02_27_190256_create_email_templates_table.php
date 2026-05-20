<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;

return new class extends Migration
{
    public function up(): void
    {    
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('on_event')->nullable();
            $table->string('subject', 70); 
            $table->text('body');
            $table->timestamps();
        });

        foreach (EmailTemplateType::cases() as $type) {
            EmailTemplate::create([
                'on_event' => $type->value,
                'subject' => $type->getSubject(),
                'body' => $type->getBody(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
