<?php


namespace mod_ojt\traits;

use coding_exception;
use stdClass;

trait record_mapper
{
    private function createFromIdOrMapToRecord($id_or_record)
    {
        if (!is_null($id_or_record))
        {
            if ($id_or_record instanceof stdClass)
            {
                $this->mapToRecord($id_or_record);
            }
            else if ((int) $id_or_record !== 0)
            {
                $this->mapToRecord(
                    $this->getRecordFromId($id_or_record));
            }
            else
            {
                throw new coding_exception('Incorrect constructor argument passed ("' . strval($id_or_record) .
                                            '") when initializing ' . __CLASS__);
            }
        }
    }

    private function mapToRecord(stdClass $record)
    {
        if (!is_null($record) || !empty($record))
        {
            foreach ($record as $key => $val)
            {
                if (property_exists(__CLASS__, $key))
                {
                    $this->$key = $val;
                }
            }
        }
        else
        {
            throw new coding_exception('Cannot create new ' . get_class($this) . 'with id and data supplied');
        }
    }

    /**
     * Fetch record from database.
     * @param int $id
     * @return stdClass|false false if record not found
     */
    abstract protected function getRecordFromId(int $id);
}