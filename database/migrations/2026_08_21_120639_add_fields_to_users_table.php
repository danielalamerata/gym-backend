
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre')->after('id');
            $table->string('apellido')->after('nombre');
            $table->string('dni')->unique()->after('apellido');
            $table->string('telefono')->nullable()->after('dni');
            $table->enum('rol', ['admin', 'cliente'])->default('cliente')->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'apellido', 'dni', 'telefono', 'rol']);
        });
    }
};
