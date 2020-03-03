<?php


namespace mod_observation;

use coding_exception;
use dml_exception;
use dml_missing_record_exception;
use mod_observation\interfaces\crud;
use mod_observation\traits\record_mapper;
use ReflectionException;
use stdClass;

// avoid classloading exceptions
global $CFG;
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once($CFG->dirroot . '/mod/observation/classes/interface/crud.php');
require_once($CFG->dirroot . '/mod/observation/classes/interface/templateable.php');

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

    /**
     * db_model_base constructor.
     * @param int|object|null $id_or_record
     * @throws coding_exception
     * @throws dml_missing_record_exception
     */
    public function __construct($id_or_record = null)
    {
        if (is_null(static::TABLE))
        {
            throw new coding_exception('TABLE not declared for ' . static::class);
        }
        if (!is_null($id_or_record))
        {
            self::create_from_id_or_map_to_record($id_or_record);
        }
    }

    /**
     * Used to populate Moodle QuickForm fields with existing data.
     * Classes with editor elements HAVE TO override this method and return editor data
     * as ['field_name' => ['text' => 'field_text_value', 'format' => 'field_format_value']]
     *
     * @return stdClass|array
     */
    public function get_moodle_form_data()
    {
        return $this->to_record();
    }

    /**
     * Get value for specific property/column in class instance
     *
     * @param string $prop property/column name to get value for
     * @return mixed
     * @throws coding_exception
     */
    public function get(string $prop)
    {
        if (empty($prop))
        {
            throw new coding_exception(
                sprintf('Empty argument "prop" passed when accessing %s from %s', static::class, __METHOD__));
        }
        if (!property_exists($this, $prop))
        {
            throw new coding_exception("Property '$prop' does not exist in " . static::class);
        }

        return $this->$prop;
    }

    /**
     * Set a value for specific property/column in class instance
     *
     * @param string $prop property/column name to get value for
     * @param mixed  $value
     * @param bool   $save if true, changes will be saved immediately
     * @return static
     * @throws coding_exception
     * @throws dml_exception
     */
    public function set(string $prop, $value, bool $save = false): self
    {
        if (property_exists($this, $prop))
        {
            // not in PHP:
            // $arg_type    = gettype($value);
            // $target_type = gettype($this->$prop);
            // if ($arg_type == $target_type)
            // {
            // }
            // else
            // {
            //     throw new coding_exception(
            //         "Incorrect data type provided for property '$prop' - expected '$target_type', got '$arg_type' in"
            //         . static::class);
            // }

            $this->$prop = $value;

            if ($save)
            {
                $this->update();
            }

            return $this;
        }
        else
        {
            throw new coding_exception("Cannot set non-existent property '$prop' in " . static::class);
        }
    }

    /**
     * Null if object not saved to db
     *
     * @return int|null
     */
    public function get_id_or_null()
    {
        return $this->id;
    }

    /**
     * Fetch record from database
     * @param int $id
     * @return stdClass|false false if record not found
     *
     * @throws dml_exception
     */
    public static function read_record(int $id)
    {
        global $DB;

        return $DB->get_record(static::TABLE, array('id' => $id));
    }

    /**
     * Fetch record from database and instantiate class.
     * @param int $id
     * @return static|false false if record not found
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function read(int $id)
    {
        if ($record = self::read_record($id))
        {
            // overwrite with class instance
            $record = new static($record);
        }

        return $record;
    }

    /**
     * Create DB entry from current state and set id
     *
     * @return static
     * @throws dml_exception
     * @throws coding_exception
     */
    public function create()
    {
        global $DB;

        $this->id = $DB->insert_record(static::TABLE, self::to_record());

        return $this->refresh();
    }

    /**
     * Reads latest values or returns new object from DB.
     * If called without parameters - refresh current object with latest values from DB.
     * If $id parameter supplied - reads values from DB by provided id
     * and maps them to a NEW instance of current object.
     *
     * @return static
     * @throws dml_exception
     * @throws coding_exception
     */
    public function refresh()
    {
        global $DB;

        $this->validate();

        $this->map_to_record(
            $DB->get_record(static::TABLE, ['id' => $this->id]));

        return $this;
    }

    /**
     * Save current state to DB
     *
     * @return static
     * @throws coding_exception
     * @throws dml_exception
     */
    public function update()
    {
        global $DB;

        $this->validate();
        $DB->update_record(static::TABLE, $this->to_record());

        return $this->refresh();
    }

    /**
     * Delete current object from DB
     *
     * @return bool
     * @throws dml_exception
     * @throws coding_exception
     */
    public function delete()
    {
        global $DB;

        $this->validate();

        return $DB->delete_records(static::TABLE, ['id' => $this->id]);
    }

    /**
     * Fetches a single record by provided conditions and instantiates class instance
     *
     * @param array $conditions = [string => mixed]
     * @param bool  $must_exist if true, exception wil be thrown if no record found
     * @return static | null class instance if record exists or null if no record found (when must_exist = false)
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    protected static function read_by_condition_or_null(array $conditions, bool $must_exist = false)
    {
        global $DB;

        // validate criteria // todo validate column as well?
        if (empty($conditions))
        {
            throw new coding_exception(sprintf('No conditions provided for %s', __METHOD__));
        }

        // $DB->get_record will throw for us if record does not exist when MUST_EXIST passed
        $strictness = $must_exist ? MUST_EXIST : IGNORE_MISSING;
        $record = $DB->get_record(static::TABLE, $conditions, '*', $strictness);

        return !empty($record) ? new static($record) : null;
    }

    /**
     * Fetches all records that meet provided conditions and instantiates class instances for those records
     *
     * @param array  $conditions = [string => mixed]
     * @param string $sort an order to sort the results in (optional, a valid SQL ORDER BY parameter).
     * @return static[]
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function read_all_by_condition(array $conditions, string $sort = null): array
    {
        global $DB;

        // validate criteria
        if (empty($conditions))
        {
            throw new coding_exception(sprintf('No conditions provided for %s', __METHOD__));
        }

        $constants = (new \ReflectionClass(static::class))->getConstants();
        foreach ($conditions as $column => $value)
        {
            if (!in_array($column, $constants))
            {
                throw new coding_exception(
                    sprintf('Cannot filter by non-existent column "%s" in %s', $column, static::class));
            }
        }

        return static::to_class_instances(
            $DB->get_records(static::TABLE, $conditions, $sort));
    }

    /**
     * Fetches all records by running provided SQL query string
     *
     * @param string     $sql
     * @param array|null $params
     * @return static[]
     * @throws dml_exception
     */
    protected static function read_all_by_sql(string $sql, array $params = null): array
    {
        global $DB;

        return static::to_class_instances(
            $DB->get_records_sql($sql, $params));
    }

    /**
     * Convert array of database records to array of class instances of those records
     *
     * @param stdClass[]|false $records db records (bool also accepted)
     * @return static[]
     */
    protected static function to_class_instances($records): array
    {
        if (empty($records))
        {
            return [];
        }

        return array_map(
            function ($rec)
            {
                return new static($rec);
            },
            $records);
    }

    private function validate(): void
    {
        if (empty($this->id) || $this->id < 0)
        {
            throw new coding_exception('Cannot refresh object that has not been saved to database');
        }
    }
}
