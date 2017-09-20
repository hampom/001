<?php


use Phinx\Migration\AbstractMigration;

class CreateTagsTable extends AbstractMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = $this->table('tags');
        $table->addColumn('name', 'string')
              ->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropTable('tags');
    }
}
