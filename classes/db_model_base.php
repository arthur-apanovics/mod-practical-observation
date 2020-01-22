<?php


namespace mod_observation;

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
    public const TABLE = null;

    /**
     * Column name constants, used to avoid strings in get/set methods.
     * Also have to be defined in child class
     */
    public const COL_ID = 'id';

    /**
     * @var int
     */
    protected $id;

    public function get($prop)
    {
        if (property_exists($this, $this->$prop))
        {
            return $this->$prop;
        }
        else
        {
            throw new coding_exception("Property '$prop' does not exist in " . __CLASS__);
        }
    }

    public function set(string $prop, $value, bool $save = false): self
    {
        if (property_exists($this, $this->$prop))
        {
            $arg_type    = gettype($value);
            $target_type = gettype($this->$prop);
            if ($arg_type == $target_type)
            {
                $this->$prop = $value;

                if ($save)
                {
                    $this->update();
                }

                return $this;
            }
            else
            {
                throw new coding_exception(
                    "Incorrect data type provided for property '$prop' - expected '$target_type', got '$arg_type' in"
                    . __CLASS__);
            }
        }
        else
        {
            throw new coding_exception("Cannot set non-existent property '$prop' in " . __CLASS__ );
        }
    }

    /**
     * db_model_base constructor.
     * @param int|object|null $id_or_record
     * @throws coding_exception
     * @throws \dml_missing_record_exception
     */
    public function __construct($id_or_record = null)
    {
        if (is_null(static::TABLE))
        {
            throw new coding_exception('TABLE not declared for ' . get_class($this));
        }

        self::create_from_id_or_map_to_record($id_or_record);
    }

    /**
     * Null if object not saved
     *
     * @return int|null
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Fetch record from database.
     * @param int $id
     * @return stdClass|false false if record not found
     *
     * @throws \dml_exception
     * @deprecated Use read(id) instead.
     */
    public static function read(int $id)
    {
        global $DB;

        return $DB->get_record(static::TABLE, array('id' => $id));
    }

    /**
     * Create DB entry from current state and set id
     *
     * @return bool|int new record id or false if failed
     * @throws \dml_exception
     */
    public function create()
    {
        global $DB;

        $id = $DB->insert_record(static::TABLE, self::get_record_from_object());
        $this->id = $id;

        return $id;
    }

    /**
     * Reads latest values or returns new object from DB.
     * If called without parameters - refresh current object with latest values from DB.
     * If $id parameter supplied - reads values from DB by provided id
     * and maps them to a NEW instance of current object.
     *
     * @return object
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function refresh()
    {
        global $DB;

        $this->validate();

        $this->map_to_record(
            $DB->get_record(static::TABLE, ['id' => $this->id])
        );

        return $this;
    }

    /**
     * Save current state to DB
     *
     * @return bool
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function update()
    {
        global $DB;

        $this->validate();

        return $DB->update_record(static::TABLE, $this->get_record_from_object());
    }

    /**
     * Delete current object from DB
     *
     * @return bool
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function delete()
    {
        global $DB;

        $this->validate();

        return $DB->delete_records(static::TABLE, ['id' => $this->id]);
    }

    private function validate(): void
    {
        if (empty($this->id) || $this->id < 0)
        {
            throw new coding_exception('Cannot refresh object that has not been saved to database');
        }
    }
}