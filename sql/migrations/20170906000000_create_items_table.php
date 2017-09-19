<?php

use Phinx\Migration\AbstractMigration;

class CreateItemsTable extends AbstractMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = $this->table('items');
        $table->addColumn('user_id', 'integer', ['signed' => false ])
              ->addColumn('title', 'string')
              ->addColumn('desc', 'text')
              ->addColumn('date', 'date')
              ->addColumn('done_date', 'date')
              ->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropTable('items');
    }
}
