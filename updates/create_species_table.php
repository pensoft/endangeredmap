<?php namespace Pensoft\EndangeredMap\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateSpeciesTable Migration
 */
class CreateSpeciesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pensoft_endangeredmap_species'))
        {
            Schema::create('pensoft_endangeredmap_species', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->string('internal_name', 500)->nullable();
                $table->string('family', 255)->nullable();
                $table->string('subfamily', 255)->nullable();
                $table->string('tribe', 255)->nullable();
                $table->string('genus', 255)->nullable();
                $table->string('subgenus', 255)->nullable();
                $table->string('species', 255)->nullable();
                $table->string('taxonomic_authority', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('pensoft_endangeredmap_species'))
        {
            Schema::dropIfExists('pensoft_endangeredmap_species');
        }
    }
}
