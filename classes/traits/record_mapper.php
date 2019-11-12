<?php


namespace mod_observation\traits;

use coding_exception;
use stdClass;

trait record_mapper
{
    /**
     * Attempts to map existing database record values to class by either fetching record
     * from database by id or mapping to provided values or existing object
     * @param $id_or_record
     * @throws coding_exception
     */
    private function create_from_id_or_map_to_record($id_or_record)
    {
        if (!is_null($id_or_record) && !empty($id_or_record))
        {
            if (is_object($id_or_record))
            {
                $this->map_to_record($id_or_record);
            }
            else if (is_numeric($id_or_record))
            {
                $this->map_to_record(
                    $this->fetch_record_from_id($id_or_record));
            }
            else
            {
                throw new coding_exception('Incorrect constructor argument passed ("'
                                           . json_encode($id_or_record) . '") when initializing ' . __CLASS__);
            }
        }
        // else
        // {
        //     throw new coding_exception('No data provided when attempting to initialize "'
        //                                . __CLASS__ . '" object');
        // }
    }

    /**
     * Map class properties to database record or existing object
     *
     * @param object $record
     * @throws coding_exception
     */
    private function map_to_record($record)
    {
        if (!is_null($record) && !empty($record))
        {
            foreach ($record as $key => $val)
            {
                if (property_exists($this, $key))
                {
                    $this->$key = $val;
                }
            }
        }
        else
        {
            throw new coding_exception('Cannot map supplied record to ' . get_class($this) . ' - no data provided');
        }
    }

    /**
     * Create and return a moodle database record from current object
     *
     * @return stdClass record
     */
    public function get_record_from_object()
    {
        $rec = [];
        foreach ($this as $key => $val)
            $rec[$key] = $val;

        return (object)$rec;
    }
}