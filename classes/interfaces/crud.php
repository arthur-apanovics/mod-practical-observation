<?php


// namespace mod_ojt\models;
namespace mod_ojt\interfaces;

interface crud
{
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
    public function read();

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