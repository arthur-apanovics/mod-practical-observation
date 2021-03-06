<?php

namespace mod_observation\interfaces;

use stdClass;

interface crud
{
    /**
     * Fetch record from database by it's id.
     * @param int $id
     * @return stdClass|false false if record not found
     */
    public static function read_or_null(int $id);

    /**
     * Create DB entry from current state
     *
     * @return bool|int new record id or false if failed
     */
    public function create();

    /**
     * Read latest values from DB and refresh current object
     *
     * @return object
     */
    public function refresh();

    /**
     * Save current state to DB
     *
     * @return bool
     */
    public function update();

    /**
     * Delete current object from DB
     *
     * @return bool
     */
    public function delete();
}