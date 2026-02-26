<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop work orders (vehicle/mechanical services)
        Schema::dropIfExists('work_order_items');
        Schema::dropIfExists('work_orders');

        // Drop vehicle-inventory pivot
        Schema::dropIfExists('inventory_vehicle_model');

        // Alter inventory: remove vehicle columns, add wine/spirits columns
        $driver = Schema::getConnection()->getDriverName();
        if (Schema::hasColumn('inventory', 'vehicle_make_id')) {
            try {
                if ($driver === 'mysql') {
                    $fk = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'inventory' AND COLUMN_NAME = 'vehicle_make_id' AND REFERENCED_TABLE_NAME IS NOT NULL", [Schema::getConnection()->getDatabaseName()]);
                    if ($fk) {
                        DB::statement('ALTER TABLE inventory DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                    }
                } else {
                    Schema::table('inventory', fn (Blueprint $t) => $t->dropForeign(['vehicle_make_id']));
                }
            } catch (\Throwable $e) {
                // FK may already be dropped
            }
        }
        if (Schema::hasColumn('inventory', 'vehicle_model_id')) {
            try {
                if ($driver === 'mysql') {
                    $fk = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'inventory' AND COLUMN_NAME = 'vehicle_model_id' AND REFERENCED_TABLE_NAME IS NOT NULL", [Schema::getConnection()->getDatabaseName()]);
                    if ($fk) {
                        DB::statement('ALTER TABLE inventory DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                    }
                } else {
                    Schema::table('inventory', fn (Blueprint $t) => $t->dropForeign(['vehicle_model_id']));
                }
            } catch (\Throwable $e) {
                // FK may already be dropped
            }
        }
        // SQLite: drop composite index before dropping columns (index references them)
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            try {
                DB::statement('DROP INDEX IF EXISTS inventory_category_id_vehicle_make_id_vehicle_model_id_index');
            } catch (\Throwable $e) {
                // Index may have different name or not exist
            }
        }

        Schema::table('inventory', function (Blueprint $table) {
            if (Schema::hasColumn('inventory', 'vehicle_make_id')) {
                $table->dropColumn('vehicle_make_id');
            }
            if (Schema::hasColumn('inventory', 'vehicle_model_id')) {
                $table->dropColumn('vehicle_model_id');
            }
            if (Schema::hasColumn('inventory', 'year_range')) {
                $table->dropColumn('year_range');
            }
            if (!Schema::hasColumn('inventory', 'volume_ml')) {
                $table->unsignedInteger('volume_ml')->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('inventory', 'alcohol_percentage')) {
                $table->decimal('alcohol_percentage', 5, 2)->nullable()->after('volume_ml');
            }
            if (!Schema::hasColumn('inventory', 'country_of_origin')) {
                $table->string('country_of_origin')->nullable()->after('alcohol_percentage');
            }
        });

        // Drop vehicle tables
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_makes');
    }

    public function down(): void
    {
        Schema::create('vehicle_makes', function (Blueprint $table) {
            $table->id();
            $table->string('make_name');
            $table->timestamps();
        });

        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_make_id')->constrained()->onDelete('cascade');
            $table->string('model_name');
            $table->integer('year_start')->nullable();
            $table->integer('year_end')->nullable();
            $table->timestamps();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn(['volume_ml', 'alcohol_percentage', 'country_of_origin']);
            $table->foreignId('vehicle_make_id')->nullable()->after('category_id')->constrained()->onDelete('set null');
            $table->foreignId('vehicle_model_id')->nullable()->after('vehicle_make_id')->constrained()->onDelete('set null');
            $table->string('year_range')->nullable()->after('vehicle_model_id');
        });

        Schema::create('inventory_vehicle_model', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_model_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vehicle_make_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->onDelete('set null');
            $table->string('vehicle_registration')->nullable();
            $table->string('vehicle_year')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->nullable()->constrained('inventory')->onDelete('set null');
            $table->string('item_description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->enum('type', ['part', 'labor', 'other'])->default('part');
            $table->timestamps();
        });
    }
};
