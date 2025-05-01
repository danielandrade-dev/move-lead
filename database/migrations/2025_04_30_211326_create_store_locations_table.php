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
        Schema::create('store_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nome do ponto (Ex: "Filial Centro", "Ponto Shopping X")
            $table->text('address');
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);
            $table->integer('coverage_radius')->default(10); // Raio em KM (mínimo 10)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Adiciona o índice espacial usando PostGIS
        DB::statement('ALTER TABLE store_locations ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX store_locations_geom_idx ON store_locations USING GIST(geom)');
        DB::statement('
            CREATE OR REPLACE FUNCTION store_locations_update_geom()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');
        DB::statement('
            CREATE TRIGGER store_locations_update_geom_trigger
            BEFORE INSERT OR UPDATE ON store_locations
            FOR EACH ROW
            EXECUTE FUNCTION store_locations_update_geom();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS store_locations_update_geom_trigger ON store_locations');
        DB::statement('DROP FUNCTION IF EXISTS store_locations_update_geom()');
        Schema::dropIfExists('store_locations');
    }
};
