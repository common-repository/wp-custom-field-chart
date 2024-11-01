<?php
/*
Plugin Name: Wp Custom Field Chart
Plugin URI: http://wordpress.org/extend/plugins/wp-custom-field-chart
Description: Add Chart.js and graphs to your WordPress site based on tallies of any numeric custom field over time. Visualize progress toward any goal by day, week, month, or year.
Version: 0.0.5
Author: Joachim Basmaison
Note: Based on Dylan Kuhn Tally wordpress plugin (GPLv2)
Author URI:
Minimum WordPress Version Required: 2.5.1
*/

/*
 Copyright (c) 2005-2007 Dylan Kuhn, 2014 Joachim Basmaison

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

/// The default method for tallying
define('CFC_GRAPH_CUMULATIVE_METHOD', 'cumulative');
/// Tally changes to a running total
define('CFC_GRAPH_DELTA_METHOD', 'delta');
/// Don't tally, just track a changing number, like weight
define('CFC_GRAPH_TRACK_METHOD', 'track');

include "ChartJs.php";
include "Field.php";

use WpCustomFieldChart\Field as Field;
use WpCustomFieldChart\FieldArray;


$CFC_FIELDS = new FieldArray(array(
	new Field('width', true, 400, '^\d+$', 'Chart width'),
	new Field('height', true, 200, '^\d+$', 'Chart height'),
	new Field('kind', true, 'Line', '^(Line|Bar)$', 'Kind of chart: Line, Bar',
        function($e) { return ucfirst(strtolower($e)); }),
	new Field('js_data', false, Null, '^\w[\w\d_]+$',
	    'Javascript variable holding our chart datasets'),
	new Field('js_options', false, Null, '^\w[\w\d_]+$',
	    'Javascript variable holding our chart options'),
	new Field('fields', true, Null, '^\w[\w\d,_-]+$',
	    'Comma separated wordpress custom field that we want to plot', function($v) {
        return array_map(trim, explode(',', $v));
	}),
	new Field('method', true, 'track', '^(track|cumulative|delta)',
	    'Aggregation method: track, cumulative, delta'),
	new Field('interval', true, 'day', '^(year|month|day)$',
	    'Period: year, month, day'),
	new Field('interval_count', true, 31, '^\d+$', 'Interval count'),
	new Field('class', true, 'cfc-chart', '^[\w\d-_]+$',
	    'Class used for our HTML div element'),
    new Field('to_date', false),
    new Field('dump', false, false, null, 'Dumping attributes', function($e) {
        $e = strtolower($e);
        if ($e == 'true' || $e == '1') {
        	return True;
        }
        return False;
    }),
    new Field('round', false, null, '^\d+$',
        'Rounding data with specified precision', function($e) {
        return (int)$e;
    }),
    new Field('hook', false, null, '^\w[\w\d_-]+$',
        'Custom javascript function that can be called'),
));

/************************
* Extension entry point *
*************************/
function custom_field_chart($atts) {
	global $CFC_FIELDS;
	$chartJs = new WpCustomFieldChart\ChartJs();
	add_action('wp_enqueue_script', $chartJs->enqueue_script());
	try {
		cfc_validate_attributes(wp_parse_args($atts));
	} catch (WpCustomFieldChart\ErrorMissingAttribute $e) {
		return cfc_get_field_error($e);
	} catch (WpCustomFieldChart\ErrorInvalidValue $e) {
		return cfc_get_field_error($e);
	}
	$data =  cfc_collect_data($CFC_FIELDS);
	return $chartJs->gen_html($CFC_FIELDS, $data, $options);
}

function cfc_get_field_error($e) {
	global $CFC_FIELDS;
	$name = $e->getMessage();
	foreach($CFC_FIELDS->getPool() as $key => $field) {
		if ($field->name != $name) {
			continue;
		}
		return $field->make_error_message($e);
	}
	return "Unknow field $name";
}

function cfc_validate_attributes(&$atts)
{
    global $CFC_FIELDS;
    $dump = False;
    if (key_exists('dump', $atts)) {
        $dump = $CFC_FIELDS->setValue('dump', $atts['dump']);
    }
    foreach ($CFC_FIELDS->getPool() as $key => $field) {
        if ($key == 'dump') {
            continue;
        }
        $field->setValue($atts[$field->name]);
        if ($dump) {
            echo "$key: $value<br>\n";
        }
    }
    unset($atts);
}

function cfc_graph_week2date($year, $week, $weekday = 6)
{
    $time = mktime(0, 0, 0, 1, (4 + ($week - 1) * 7), $year);
    $this_weekday = date('w', $time);
    return mktime(0, 0, 0, 1,
        (4 + ($week - 1) * 7 + ($weekday - $this_weekday)), $year);
}

function cfc_collect_data($fields)
{
    global $wp_query;

    $end_time = time();
    if (!$fields->isNull('to_date')) {
        $to_date = $fields->getValue('to_date');
        if ($to_date == 'post') {
            $end_time = strtotime($wp_query->posts[0]->post_date);
        } else {
            $end_time = strtotime($atts['to_date']);
        }
        if (!$end_time) {
            return 'WpCustomFieldChart: couldn\'t read the to_date: $to_date';
        }
    }
    $cfc_interval = $fields->getValue('interval');
    $label_interval = $cfc_interval;
    $method = $fields->getValue('method');
    $interval_count = $fields->getValue('interval_count');
    $keys = $fields->getValue('fields');
    list ($index_gnu_format, $index_mysql_format, $first_day_suffix) =
        cfc_graph_interval_settings($cfc_interval);
    /*
     * Always start on the first day of the starting interval
     * Always end on the first day after the ending interval
     */
    $start_time = strtotime('-' . $interval_count . ' ' . $cfc_interval,
        $end_time);
    if ('week' == $cfc_interval) {
        // Wouldn't need this for PHP 5.1 and later
        $start_time = cfc_graph_week2date(date('Y', $start_time),
            date('W', $start_time) + 1, 1);
        $end_time = cfc_graph_week2date(date('Y', $end_time),
            date('W', $end_time), 1);
    } else {
        $next_date_prefix = date($index_gnu_format, strtotime('+1 ' .
            $cfc_interval, $start_time));
        $start_time = strtotime($next_date_prefix . $first_day_suffix);
        $next_date_prefix = date($index_gnu_format, strtotime('+1 ' .
            $cfc_interval, $end_time));
        $end_time = strtotime($next_date_prefix . $first_day_suffix);
    }
    $key_counts = array();
    $key_labels = array();
    foreach ($keys as $index => $key) {
        $key_counts[$index] = cfc_graph_get_counts($key, $start_time,
            $end_time, $cfc_interval, $method, $key_labels);
            if (!$fields->isNull('round')) {
                $round = $fields->getValue('round');
                foreach($key_counts[$index] as $idx => $value) {
                    $key_counts[$index][$idx] = round($value, $round);
                }
            }
    }
    $first_index = null;
    $label_array = Array();
    foreach ($key_counts as $index => $counts) {
        if (is_null($first_index))
            $first_index = $index;
        foreach ($counts as $date_index => $count) {
            if ($index == $first_index) {
                array_push($label_array,
                    $key_labels[$date_index][$cfc_interval]);
            }
        }
    }
    $data = array(
        'datasets' => $key_counts,
        'labels' => $label_array
    );
    return $data;
}

function cfc_graph_get_counts($key, $start_time, $end_time, $interval,
    $method = CFC_GRAPH_CUMULATIVE_METHOD, &$labels = null)
{
    global $wpdb;

    list ($gnu_index_format, $mysql_index_format) =
        cfc_graph_interval_settings($interval);
    $counts = array();
    for ($the_time = $start_time; $the_time < $end_time;
            $the_time = strtotime('+1 ' . $interval, $the_time)) {
        $index = date($gnu_index_format, $the_time);
        $counts[$index] = 0;
        if (is_array($labels)) {
            $labels[$index] = array(
                'day' => date('j', $the_time),
                'week' => date('\\WW', $the_time),
                'month' => date('M', $the_time),
                'year' => date('Y', $the_time)
            );
        }
    }

    $aggregator = (CFC_GRAPH_TRACK_METHOD == $method) ? 'AVG' : 'SUM';

    $query_sql = 'SELECT DATE_FORMAT(p.post_date,\'' . $mysql_index_format .
        '\') AS xdata, ' . $aggregator . '(pm.meta_value) AS ydata ' .
        'FROM ' . $wpdb->posts . ' p ' .
        'JOIN ' . $wpdb->postmeta . ' pm ON pm.post_id = p.ID ' .
        'WHERE pm.meta_key = \'' . esc_sql($key) . '\' ' .
        'AND p.post_date >= \'' . date('Y-m-d', $start_time) . '\' ' .
        'AND p.post_date < \'' . date('Y-m-d', $end_time) . '\' ' .
        'GROUP BY DATE_FORMAT(p.post_date,\'' . $mysql_index_format . '\') ' .
        'ORDER BY p.post_date';
    $wpdb->query($query_sql);
    if ($wpdb->last_result) {
        $chd_min = $chd_max = $wpdb->last_result[0]->ydata;
        foreach ($wpdb->last_result as $result) {
            if ($result->ydata < $chd_min)
                $chd_min = $result->ydata;
            else
                if ($result->ydata > $chd_max)
                    $chd_max = $result->ydata;
            $counts[$result->xdata] = $result->ydata;
        }
    }

    if (CFC_GRAPH_DELTA_METHOD == $method) {
        $delta_count = cfc_graph_get_count_as_of($key, $start_time);
        for ($the_time = $start_time; $the_time < $end_time;
                $the_time = strtotime('+1 ' . $interval, $the_time)) {
            $index = date($gnu_index_format, $the_time);
            $delta_count += $counts[$index];
            $counts[$index] = $delta_count;
        }
    } else
        if (CFC_GRAPH_TRACK_METHOD == $method) {
            $the_time = $start_time;
            $track_value = 0;
            $index = date($gnu_index_format, $the_time);
            if (! $counts[$index]) {
                $counts[$index] = cfc_graph_get_last_value($key, $start_time);
            }
            do {
                if ($counts[$index]) {
                    $track_value = $counts[$index];
                } else {
                    $counts[$index] = $track_value;
                }
                $the_time = strtotime('+1 ' . $interval, $the_time);
                $index = date($gnu_index_format, $the_time);
            } while ($the_time < $end_time);
        }

    return $counts;
}

function cfc_graph_get_count_as_of($key, $start_time)
{
    global $wpdb;

	$query_sql = 'SELECT SUM(pm.meta_value) AS count '.
        'FROM ' . $wpdb->posts . ' p ' .
        'JOIN ' . $wpdb->postmeta . ' pm ON pm.post_id = p.ID ' .
        'WHERE pm.meta_key = \'' . esc_sql($key) . '\' ' .
        'AND p.post_date < \''.date('Y-m-d',$start_time).'\'';
    return $wpdb->get_var($query_sql);
}

function cfc_graph_get_last_value($key, $start_time)
{
    global $wpdb;

    $query_sql = 'SELECT pm.meta_value '.
            'FROM ' . $wpdb->posts . ' p ' .
            'JOIN ' . $wpdb->postmeta . ' pm ON pm.post_id = p.ID ' .
            'WHERE pm.meta_key = \'' . esc_sql($key) . '\' ' .
            'AND p.post_date = (SELECT MAX(p.post_date) ' .
            'FROM ' . $wpdb->posts . ' ip ' .
            'JOIN ' . $wpdb->postmeta . ' ipm ON ipm.post_id = ip.ID ' .
            'WHERE pm.meta_key = \'' . esc_sql($key) . '\' ' .
            'AND p.post_date < \''.date('Y-m-d',$start_time).'\')';
    return $wpdb->get_var($query_sql);
}

function cfc_graph_interval_settings($interval)
{
    if ($interval == 'year') {
        return array(
            'Y',
            '%Y',
            '-01-01'
        );
    }
    if ($interval == 'week') {
        return array(
            'Y-\\WW',
            '%Y-W%v',
            '-1'
        );
    }
    if ($interval == 'day') {
        return array(
            'Y-m-d',
            '%Y-%m-%d',
            ''
        );
    }
    return array(
        'Y-m',
        '%Y-%m',
        '-01'
    );
}

add_shortcode('custom_field_chart','custom_field_chart');
