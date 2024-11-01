<?php
/*
 Copyright (c) 2014 Joachim Basmaison

This program is free software; you can redistribute it
and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE. See the GNU General Public License for more
details.
*/

namespace WpCustomFieldChart;

class ChartJs
{

    public $sid;

    function __construct()
    {
        $this->sid = $this->genid();
    }

    function enqueue_script()
    {
        wp_enqueue_script('chartjs', plugins_url() .
            '/wp-custom-field-chart/js/Chart.min.js', array(), '1.0.1-beta.3',
            false);
    }

    function genid()
    {
        return uniqid('cfc') . '_';
    }

    function gen_html($fields, $data, $options)
    {
        return '<div class="' . $fields->getValue('class') . '">' .
            $this->gen_canvas($fields) .
            $this->gen_script($fields, $data, $options) . '</div>';
    }

    function gen_canvas($fields)
    {
        $out = '<canvas id="' . $this->sid . '" ';
        foreach (array(
            'width',
            'height'
        ) as $key) {
            $out .= $key . '="' . $fields->getValue($key) . '" ';
        }
        $out .= "/>\n";
        return $out;
    }

    function gen_jsdata($fields) {
        if (!$fields->isNull('js_data')) {
            return '';
        }
        $id = $this->sid . 'data';
        $out = "var $id = { datasets: [";
        foreach ($fields->getValue('fields') as $idx => $value) {
            $out .= '{},';
        }
        $out .= "]};\n";
        $fields->setValue('js_data', $id);
        return $out;
    }

    function gen_script($fields, $data, $options = Null)
    {
        $vadata = 'null';
        $varopt = 'null';
        $gendata .= $this->gen_data($fields, $data);
        if (!$fields->isNull('js_data')) {
            $vardata = $fields->getValue('js_data');
        }
        if (!$fields->isNull('js_options')) {
            $varopt = $fields->getValue('js_options');
        }
        $varobj = $this->sid . 'Object';
        $out = "<script>\n";
        $out .= 'jQuery(window).load(function() {' . "\n";
        $out .= $gendata;
        $out .= "var ctx = document.getElementById(\"" .
            $this->sid . "\").getContext(\"2d\");\n";
        $out .= "var $varobj = new Chart(ctx)." . $fields->getValue('kind') .
            "($vardata, $varopt);\n";
        if (!$fields->isNull('hook')) {
            $out .= $fields->getValue('hook') . "($varobj, '".$this->sid."');\n";
        }
        $out .= "});</script>\n";
        return $out;
    }

    function gen_data($fields, $data)
    {
        $keys = $fields->getValue('fields');
        $out = $this->gen_jsdata($fields);
        /* Note: Must be called after gen_jsdata */
        $vardata = $fields->getValue('js_data');
        $out .= $vardata . ".labels=[" .
            join(',',
                array_map(function($e) { return "'$e'"; },
                $data['labels'])) .
            "];\n";
        foreach ($keys as $idx => $name) {
            $out .= $vardata . ".datasets[$idx].data=[";
            foreach ($data['datasets'][$idx] as $key => $value) {
                $out .= "$value,";
            }
            $out .= "];\n";
        }
        return $out;
    }

    function gen_options($fields, $options = Null)
    {
        if ($fields->isNull('js_options')) {
            return '';
        }
        $vardata = $fields->getValue('js_options');
        return "var $vardata=$options;\n";
    }
}
