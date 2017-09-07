<?php


use Phinx\Migration\AbstractMigration;

class AddSchedule extends AbstractMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = $this->table('items');
        $table->addColumn('schedule', 'boolean', ['default' => false])
              ->addColumn('startAt', 'time')
              ->addColumn('endAt', 'time')
              ->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = $this->table('items');
        $table->removeColumn('schedule')
              ->removeColumn('startAt')
              ->removeColumn('endAt')
              ->save();
    }
}
