<?php
/*
 * Copyright (c) 2014 Joachim Basmaison This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 */
namespace WpCustomFieldChart;

class ErrorMissingAttribute extends \ErrorException {};
class ErrorInvalidValue extends \ErrorException {};
class ErrorAddingSameField extends \ErrorException {};
class ErrorNonExistingField extends \ErrorException {};

class FieldArray {
    private $pool = array();

    function __construct($fields=Null) {
           $this->populate($fields);
    }

    function add(Field $field) {
        if (key_exists($field->name, $this->pool)) {
            throw new ErrorAddingSameField($field->name);
        }
        $this->pool[$field->name] = $field;
    }

    function get($name) {
        if (!key_exists($name, $this->pool)) {
            throw new ErrorNonExistingField($name);
        }
        return $this->pool[$name];
    }

    function populate($fields) {
        foreach($fields as $field) {
            $this->add($field);
        }
    }

    function getValue($name) {
        return $this->get($name)->getValue();
    }

    function setValue($name, $value) {
        $this->get($name)->setValue($value);
    }
    function isNull($name) {
    	return $this->get($name)->isNull();
    }

    function getPool() {
        return $this->pool;
    }
}

class Field
{

    public $name = Null;
    public $required = Null;
    public $default = Null;
    public $match = Null;
    public $info = Null;
    public $callback = Null;
    public $value = Null;
    public $is_valid = False;

    function __construct($name, $required, $default = Null, $match = Null,
        $info = Null, $callback=Null)
    {
        $this->name = $name;
        $this->required = $required;
        $this->default = $default;
        $this->match = $match;
        $this->info = $info;
        $this->callback = $callback;
    }

    function getValue() {
        return $this->value;
    }

    function setValue($value) {
        $this->value = $this->validate($value);
    }

    function isNull() {
        return is_null($this->value);
    }

    function validate($value)
    {
        $this->is_valid = False;
        if ($value == '') {
            $value = Null;
        }
        if (is_null($value)) {
            if (!$this->required) {
                return is_null($this->default)? Null : $this->default;
            } else {
                if (is_null($this->default)) {
                    throw new ErrorMissingAttribute($this->name);
                }
            $value = $this->default;
            }
        }
        if (!is_null($this->match)) {
            if (!preg_match('/' . $this->match . '/i', $value)) {
                throw new ErrorInvalidValue($this->name);
            }
        }
        $cb = $this->callback;
        if (!is_null($cb)) {
            $value = $cb($value);
        }
//         $this->value = $value;
        $this->is_valid = True;
        return $value;
    }

    function make_error_message($e)
    {
        $msg = "";
        if ($e instanceof ErrorMissingAttribute) {
            $msg .= '<b>Missing attribute:</b>&nbsp;' . $this->name . '<br>';
            $msg .= '<b>Attribute information:</b>&nbsp;' . $this->info . '';
        } elseif ($e instanceof ErrorInvalidValue) {
            $msg .= '<b>Invalid value for '. $this->name . ':</b>&nbsp;' .
                $this->value . ' (regex: ' . $this->match . ')<br>';
            $msg .= '<b>Attribute information:</b>&nbsp;' . $this->info . '';
        } else {
            $msg .= "<b>Unknown error for attribute:</b>&nbsp;" . $this->name
                . '<br>';
            $msg .= '<b>Attribute information:</b>&nbsp;' . $this->info . '';
        }
        $out = '<div class="cfc-error" style="background-color: black; ' .
            'color:white; padding: 0.5em 1em; font-family: Arial; ' .
            'border-style: solid; border-width:2px; border-color: red">';
        $out .= '<h2>Wordpress Extension Error / Custom Field Chart</h2>';
        $out .= $msg;
        $out .= '</div>';
        return $out;
    }
}