<?php


namespace mod_observation\interfaces;


interface templateable
{
    /**
     * Exports object data for use in a mustache template
     *
     * @return array = ['property' => 'value']
     */
    public function export_template_data(): array ;
}