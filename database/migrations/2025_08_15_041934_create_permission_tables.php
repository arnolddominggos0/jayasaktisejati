<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // roles
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');                       // admin, editor, dst.
            $table->string('guard_name');                 // 'web'
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        // permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');                       // 'users.create', dst.
            $table->string('guard_name');                 // 'web'
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        // model_has_permissions
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->bigInteger('permission_id')->index();
            // Sesuaikan dengan tipe PK users.id = BIGINT
            $table->bigInteger('model_id');
            $table->string('model_type');

            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');

            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        // model_has_roles
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->bigInteger('role_id')->index();
            $table->bigInteger('model_id');              // users.id BIGINT
            $table->string('model_type');

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        // role_has_permissions
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->bigInteger('permission_id');
            $table->bigInteger('role_id');

            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            $table->primary(['permission_id','role_id'], 'role_has_permissions_permission_role_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
