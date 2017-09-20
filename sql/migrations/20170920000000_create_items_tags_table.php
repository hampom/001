<?php


use Phinx\Migration\AbstractMigration;

class CreateItemsTagsTable extends AbstractMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = $this->table('items_tags');
        $table->addColumn('item_id', 'integer')
              ->addColumn('tag_id', 'integer')
              ->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropTable('items_tags');
    }
}
