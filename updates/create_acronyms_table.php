<?php namespace Pensoft\EndangeredMap\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAcronymsTable Migration
 */
class CreateAcronymsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pensoft_endangeredmap_acronyms'))
        {
            Schema::create('pensoft_endangeredmap_acronyms', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->string('acronym', 50)->unique();
                $table->string('meaning', 255);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('pensoft_endangeredmap_acronyms'))
        {
            Schema::dropIfExists('pensoft_endangeredmap_acronyms');
        }
    }
}
