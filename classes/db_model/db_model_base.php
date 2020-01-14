<?php


namespace mod_observation\db_model;

use coding_exception;
use mod_observation\interfaces\crud;
use mod_observation\traits\record_mapper;
use stdClass;

abstract class db_model_base implements crud
{
    use record_mapper;

    /**
     * Table name in database
     * Has to be defined in child class
     */
    protected const TABLE = null;

    /**
     * @var int
     */
    protected $id;


    /**
     * db_model_base constructor.
     * @param int|object|null $id_or_record
     * @throws coding_exception
     */
    public function __construct($id_or_record = null)
    {
        if (is_null(static::TABLE))
        {
            throw new coding_exception('TABLE not initialised for ' . get_class($this));
        }

        self::create_from_id_or_map_to_record($id_or_record);
    }

    /**
     * Fetch record from database.
     * @param int $id
     * @return stdClass|false false if record not found
     */
    public static function fetch_record_from_id(int $id)
    {
        global $DB;
        return $DB->get_record(static::TABLE, array('id' => $id));
    }

    /**
     * Create DB entry from current state and set id
     *
     * @return bool|int new record id or false if failed
     */
    public function create()
    {
        global $DB;

        // TODO: Check if creation failed
        $id = $DB->insert_record(static::TABLE, self::get_record_from_object());
        $this->id = $id;

        return $id;
    }

    /**
     * Read latest values from DB and refresh current object
     *
     * @return object
     */
    public function read()
    {
        global $DB;
        $this->map_to_record($DB->get_record(static::TABLE, ['id' => $this->id]));

        return $this;
    }

    /**
     * Save current state to DB
     *
     * @return bool
     */
    public function update()
    {
        global $DB;
        return $DB->update_record(static::TABLE, $this->get_record_from_object());
    }

    /**
     * Delete current object from DB
     *
     * @return bool
     */
    public function delete()
    {
        global $DB;
        return $DB->delete_records(static::TABLE, ['id' => $this->id]);
    }
}