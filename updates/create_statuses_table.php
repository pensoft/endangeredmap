<?php namespace Pensoft\EndangeredMap\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateStatusesTable Migration
 */
class CreateStatusesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pensoft_endangeredmap_statuses'))
        {
            Schema::create('pensoft_endangeredmap_statuses', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->unsignedInteger('species_id');
                $table->string('country', 255);
                $table->string('status', 50);
                $table->timestamps();

                $table->foreign('species_id')
                    ->references('id')
                    ->on('pensoft_endangeredmap_species')
                    ->onDelete('cascade');

                $table->index('species_id');
                $table->index('country');
                $table->index('status');
                $table->index(['country', 'status']);
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('pensoft_endangeredmap_statuses'))
        {
            Schema::dropIfExists('pensoft_endangeredmap_statuses');
        }
    }
}
