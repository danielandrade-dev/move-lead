<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('segment_id')->constrained('segments');
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 20);
            $table->string('zip_code', 10);
            $table->string('city', 100);
            $table->string('state', 2);
            $table->text('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);
            $table->string('external_id', 255)->nullable();
            $table->string('external_source', 255)->nullable();
            $table->enum('status', ['new' ,'pending' ,'approved' ,'rejected','archived','sent'])->default('new');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Adiciona o índice espacial usando PostGIS
        DB::statement('ALTER TABLE leads ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX leads_geom_idx ON leads USING GIST(geom)');
        DB::statement('
            CREATE OR REPLACE FUNCTION leads_update_geom()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');
        DB::statement('
            CREATE TRIGGER leads_update_geom_trigger
            BEFORE INSERT OR UPDATE ON leads
            FOR EACH ROW
            EXECUTE FUNCTION leads_update_geom();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS leads_update_geom_trigger ON leads');
        DB::statement('DROP FUNCTION IF EXISTS leads_update_geom()');
        Schema::dropIfExists('leads');
    }
};
