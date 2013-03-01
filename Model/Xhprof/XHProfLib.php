<?php

namespace Clamidity\ProfilerBundle\Model\Xhprof;

use Clamidity\ProfilerBundle\Model\Xhprof\XHProfRuns;

//  Copyright (c) 2009 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

//
// This file contains various XHProf library (utility) functions.
// Do not add any display specific code here.
//

/**
 * Utilities class for XHPRof
 * 
 * @author Kannan
 * 
 * Modified by:
 * @author Michael Shattuck <ms2474@gmail.com> 
 */
class XHProfLib extends XHProfRuns
{
    const XHPROF_STRING_PARAM = 1;
    const XHPROF_UINT_PARAM   = 2;
    const XHPROF_FLOAT_PARAM  = 3;
    const XHPROF_BOOL_PARAM   = 4;

    public $display_calls;
    public $params;

    /*
    * The list of possible metrics collected as part of XHProf that
    * require inclusive/exclusive handling while reporting.
    *
    * @author Kannan
    */
    function xhprof_get_possible_metrics()
    {
        $this->possible_metrics = array(
            "wt"      => array("Wall", "microsecs", "walltime" ),
            "ut"      => array("User", "microsecs", "user cpu time" ),
            "st"      => array("Sys", "microsecs", "system cpu time"),
            "cpu"     => array("Cpu", "microsecs", "cpu time"),
            "mu"      => array("MUse", "bytes", "memory usage"),
            "pmu"     => array("PMUse", "bytes", "peak memory usage"),
            "samples" => array("Samples", "samples", "cpu time")
        );

        return $this->possible_metrics;
    }

    /*
    * Get the list of metrics present in $xhprof_data as an array.
    *
    * @author Kannan
    */
    function xhprof_get_metrics($xhprof_data)
    {
        $possible_metrics = $this->xhprof_get_possible_metrics();
        $metrics          = array();

        foreach ($possible_metrics as $metric => $desc) {
            if (isset($xhprof_data["main()"][$metric])) {
                $metrics[] = $metric;
            }
        }

        return $metrics;
    }

    /**
    * Takes a parent/child function name encoded as
    * "a==>b" and returns array("a", "b").
    *
    * @author Kannan
    */
    function xhprof_parse_parent_child($parent_child)
    {
        $ret = explode("==>", $parent_child);

        if (isset($ret[1])) {
            return $ret;
        }

        return array(null, $ret[0]);
    }

    /**
    * Given parent & child function name, composes the key
    * in the format present in the raw data.
    *
    * @author Kannan
    */
    function xhprof_build_parent_child_key($parent, $child)
    {
        if ($parent) {
            return $parent . "==>" . $child;
        }
        else {
            return $child;
        }
    }


    /**
    * Checks if XHProf raw data appears to be valid and not corrupted.
    *
    *  @param   int    $run_id        Run id of run to be pruned.
    *                                 [Used only for reporting errors.]
    *  @param   array  $raw_data      XHProf raw data to be pruned
    *                                 & validated.
    *
    *  @return  bool   true on success, false on failure
    *
    *  @author Kannan
    */
    function xhprof_valid_run($run_id, $raw_data)
    {
        $main_info = $raw_data["main()"];

        if (empty($main_info)) {
            $this->xhprof_error("XHProf: main() missing in raw data for Run ID: $run_id");

            return false;
        }

        if (isset($main_info["wt"])) {
            $metric = "wt";
        }
        else if (isset($main_info["samples"])) {
            $metric = "samples";
        }
        else {
            $this->xhprof_error("XHProf: Wall Time information missing from Run ID: $run_id");

            return false;
        }

        foreach ($raw_data as $info) {
            $val = $info[$metric];

            if ($val < 0) {
                $this->xhprof_error("XHProf: $metric should not be negative: Run ID $run_id".serialize($info));

                return false;
            }
            if ($val > (86400000000)) {
                $this->xhprof_error("XHProf: $metric > 1 day found in Run ID: $run_id ".serialize($info));

                return false;
            }
        }
        return true;
    }


    /**
    * Return a trimmed version of the XHProf raw data. Note that the raw
    * data contains one entry for each unique parent/child function
    * combination.The trimmed version of raw data will only contain
    * entries where either the parent or child function is in the list
    * of $functions_to_keep.
    *
    * Note: Function main() is also always kept so that overall totals
    * can still be obtained from the trimmed version.
    *
    * @param  array  XHProf raw data
    * @param  array  array of function names
    *
    * @return array  Trimmed XHProf Report
    *
    * @author Kannan
    */
    function xhprof_trim_run($raw_data, $functions_to_keep) 
    {
        $function_map           = array_fill_keys($functions_to_keep, 1);
        $function_map['main()'] = 1;
        $new_raw_data           = array();

        foreach ($raw_data as $parent_child => $info) {
            list($parent, $child) = $this->xhprof_parse_parent_child($parent_child);

            if (isset($function_map[$parent]) || isset($function_map[$child])) {
                $new_raw_data[$parent_child] = $info;
            }
        }

        return $new_raw_data;
    }

    /**
    * Takes raw XHProf data that was aggregated over "$num_runs" number
    * of runs averages/nomalizes the data. Essentially the various metrics
    * collected are divided by $num_runs.
    *
    * @author Kannan
    */
    function xhprof_normalize_metrics($raw_data, $num_runs)
    {
        if (empty($raw_data) || ($num_runs == 0)) {
            return $raw_data;
        }

        $raw_data_total = array();

        if (isset($raw_data["==>main()"]) && isset($raw_data["main()"])) {
            $this->xhprof_error("XHProf Error: both ==>main() and main() set in raw data...");
        }

        foreach ($raw_data as $parent_child => $info) {
            foreach ($info as $metric => $value) {
                $raw_data_total[$parent_child][$metric] = ($value / $num_runs);
            }
        }

        return $raw_data_total;
    }


    /**
    * Get raw data corresponding to specified array of runs
    * aggregated by certain weightage.
    *
    * Suppose you have run:5 corresponding to page1.php,
    *                  run:6 corresponding to page2.php,
    *             and  run:7 corresponding to page3.php
    *
    * and you want to accumulate these runs in a 2:4:1 ratio. You
    * can do so by calling:
    *
    *     $this->xhprof_aggregate_runs(array(5, 6, 7), array(2, 4, 1));
    *
    * The above will return raw data for the runs aggregated
    * in 2:4:1 ratio.
    *
    *  @param object  $xhprof_runs_impl  An object that implements
    *                                    the iXHProfRuns interface
    *  @param  array  $runs            run ids of the XHProf runs..
    *  @param  array  $wts             integral (ideally) weights for $runs
    *  @param  string $source          source to fetch raw data for run from
    *  @param  bool   $use_script_name If true, a fake edge from main() to
    *                                  to __script::<scriptname> is introduced
    *                                  in the raw data so that after aggregations
    *                                  the script name is still preserved.
    *
    *  @return array  Return aggregated raw data
    *
    *  @author Kannan
    */
    function xhprof_aggregate_runs($xhprof_runs_impl, $runs, $wts, $source="phprof", $use_script_name=false)
    {
        $raw_data_total = null;
        $raw_data       = null;
        $metrics        = array();
        $run_count      = count($runs);
        $wts_count      = count($wts);

        if (($run_count == 0) || (($wts_count > 0) && ($run_count != $wts_count))) {
            return array('description' => 'Invalid input..', 'raw'  => null);
        }

        $bad_runs = array();

        foreach($runs as $idx => $run_id) {
            $raw_data = $xhprof_runs_impl->get_run($run_id, $source, $description);

            if ($idx == 0) {
                foreach ($raw_data["main()"] as $metric => $val) {
                    if ($metric != "pmu") {
                        if (isset($val)) {
                            $metrics[] = $metric;
                        }
                    }
                }
            }

            if (!$this->xhprof_valid_run($run_id, $raw_data)) {
                $bad_runs[] = $run_id;
                continue;
            }

            if ($use_script_name) {
                $page = $description;

                if ($page) {
                    foreach($raw_data["main()"] as $metric => $val) {
                        $fake_edge[$metric] = $val;
                        $new_main[$metric]  = $val + 0.00001;
                    }

                    $raw_data["main()"] = $new_main;
                    $raw_data[$this->xhprof_build_parent_child_key("main()", "__script::$page")] = $fake_edge;
                }
                else {
                    $use_script_name = false;
                }
            }

            $wt = ($wts_count == 0) ? 1 : $wts[$idx];

            foreach ($raw_data as $parent_child => $info) {
                if ($use_script_name) {
                    if (substr($parent_child, 0, 9) == "main()==>") {
                        $child = substr($parent_child, 9);

                        if (substr($child, 0, 10) != "__script::") {
                            $parent_child = $this->xhprof_build_parent_child_key("__script::$page", $child);
                        }
                    }
                }

                if (!isset($raw_data_total[$parent_child])) {
                    foreach ($metrics as $metric) {
                        $raw_data_total[$parent_child][$metric] = ($wt * $info[$metric]);
                    }
                }
                else {
                    foreach ($metrics as $metric) {
                        $raw_data_total[$parent_child][$metric] += ($wt * $info[$metric]);
                    }
                }
            }
        }

        $runs_string = implode(",", $runs);

        if (isset($wts)) {
            $wts_string  = "in the ratio (" . implode(":", $wts) . ")";
            $normalization_count = array_sum($wts);
        }
        else {
            $wts_string = "";
            $normalization_count = $run_count;
        }

        $run_count           = $run_count - count($bad_runs);
        $data['description'] = "Aggregated Report for $run_count runs: "."$runs_string $wts_string\n";
        $data['raw']         = $this->xhprof_normalize_metrics($raw_data_total, $normalization_count);
        $data['bad_runs']    = $bad_runs;

        return $data;
    }


    /**
    * Analyze hierarchical raw data, and compute per-function (flat)
    * inclusive and exclusive metrics.
    *
    * Also, store overall totals in the 2nd argument.
    *
    * @param  array $raw_data          XHProf format raw profiler data.
    * @param  array &$overall_totals   OUT argument for returning
    *                                  overall totals for various
    *                                  metrics.
    * @return array Returns a map from function name to its
    *               call count and inclusive & exclusive metrics
    *               (such as wall time, etc.).
    *
    * @author Kannan Muthukkaruppan
    */
    function xhprof_compute_flat_info($raw_data, &$overall_totals)
    {

        $metrics        = $this->xhprof_get_metrics($raw_data);
        $symbol_tab     = $this->xhprof_compute_inclusive_times($raw_data);
        $overall_totals = array(
            "ct" => 0,
            "wt" => 0,
            "ut" => 0,
            "st" => 0,
            "cpu" => 0,
            "mu" => 0,
            "pmu" => 0,
            "samples" => 0
        );

        foreach ($metrics as $metric) {
            $overall_totals[$metric] = $symbol_tab["main()"][$metric];
        }

        foreach ($symbol_tab as $symbol => $info) {
            foreach ($metrics as $metric) {
                $symbol_tab[$symbol]["excl_" . $metric] = $symbol_tab[$symbol][$metric];
            }

            if ($this->display_calls) {
                $overall_totals["ct"] += $info["ct"];
            }
        }

        foreach ($raw_data as $parent_child => $info) {
            list($parent, $child) = $this->xhprof_parse_parent_child($parent_child);

            if ($parent) {
                foreach ($metrics as $metric) {
                    if (isset($symbol_tab[$parent])) {
                        $symbol_tab[$parent]["excl_" . $metric] -= $info[$metric];
                    }
                }
            }
        }

        return $symbol_tab;
    }

    /**
    * Hierarchical diff:
    * Compute and return difference of two call graphs: Run2 - Run1.
    *
    * @author Kannan
    */
    function xhprof_compute_diff($xhprof_data1, $xhprof_data2)
    {
        $metrics      = $this->xhprof_get_metrics($xhprof_data2);
        $xhprof_delta = $xhprof_data2;

        foreach ($xhprof_data1 as $parent_child => $info) {

            if (!isset($xhprof_delta[$parent_child])) {
                if ($this->display_calls) {
                    $xhprof_delta[$parent_child] = array("ct" => 0);
                }
                else {
                    $xhprof_delta[$parent_child] = array();
                }

                foreach ($metrics as $metric) {
                    $xhprof_delta[$parent_child][$metric] = 0;
                }
            }

            if ($this->display_calls) {
                $xhprof_delta[$parent_child]["ct"] -= $info["ct"];
            }

            foreach ($metrics as $metric) {
                $xhprof_delta[$parent_child][$metric] -= $info[$metric];
            }
        }

        return $xhprof_delta;
    }


    /**
    * Compute inclusive metrics for function. This code was factored out
    * of $this->xhprof_compute_flat_info().
    *
    * The raw data contains inclusive metrics of a function for each
    * unique parent function it is called from. The total inclusive metrics
    * for a function is therefore the sum of inclusive metrics for the
    * function across all parents.
    *
    * @return array  Returns a map of function name to total (across all parents)
    *                inclusive metrics for the function.
    *
    * @author Kannan
    */
    function xhprof_compute_inclusive_times($raw_data)
    {
        $metrics    = $this->xhprof_get_metrics($raw_data);
        $symbol_tab = array();

        foreach ($raw_data as $parent_child => $info) {
            list($parent, $child) = $this->xhprof_parse_parent_child($parent_child);

            if ($parent == $child) {
                $this->xhprof_error("Error in Raw Data: parent & child are both: $parent");

                return;
            }

            if (!isset($symbol_tab[$child])) {

                if ($this->display_calls) {
                    $symbol_tab[$child] = array("ct" => $info["ct"]);
                }
                else {
                    $symbol_tab[$child] = array();
                }
                foreach ($metrics as $metric) {
                    $symbol_tab[$child][$metric] = $info[$metric];
                }
            } 
            else {
                if ($this->display_calls) {
                    $symbol_tab[$child]["ct"] += $info["ct"];
                }

                foreach ($metrics as $metric) {
                    $symbol_tab[$child][$metric] += $info[$metric];
                }
            }
        }

        return $symbol_tab;
    }


    /*
    * Prunes XHProf raw data:
    *
    * Any node whose inclusive walltime accounts for less than $prune_percent
    * of total walltime is pruned. [It is possible that a child function isn't
    * pruned, but one or more of its parents get pruned. In such cases, when
    * viewing the child function's hierarchical information, the cost due to
    * the pruned parent(s) will be attributed to a special function/symbol
    * "__pruned__()".]
    *
    *  @param   array  $raw_data      XHProf raw data to be pruned & validated.
    *  @param   double $prune_percent Any edges that account for less than
    *                                 $prune_percent of time will be pruned
    *                                 from the raw data.
    *
    *  @return  array  Returns the pruned raw data.
    *
    *  @author Kannan
    */
    function xhprof_prune_run($raw_data, $prune_percent)
    {
        $main_info = $raw_data["main()"];

        if (empty($main_info)) {
            $this->xhprof_error("XHProf: main() missing in raw data");

            return false;
        }

        // raw data should contain either wall time or samples information...
        if (isset($main_info["wt"])) {
            $prune_metric = "wt";
        }
        else if (isset($main_info["samples"])) {
            $prune_metric = "samples";
        }
        else {
            $this->xhprof_error("XHProf: for main() we must have either wt "."or samples attribute set");

            return false;
        }

        $metrics = array();

        foreach ($main_info as $metric => $val) {
            if (isset($val)) {
                $metrics[] = $metric;
            }
        }

        $prune_threshold = (($main_info[$prune_metric] * $prune_percent) / 100.0);

        $this->init_metrics($raw_data, null, null, false);

        $flat_info = $this->xhprof_compute_inclusive_times($raw_data);

        foreach ($raw_data as $parent_child => $info) {

            list($parent, $child) = $this->xhprof_parse_parent_child($parent_child);

            if ($flat_info[$child][$prune_metric] < $prune_threshold) {
                unset($raw_data[$parent_child]); // prune the edge
            }
            else if ($parent && ($parent != "__pruned__()") && ($flat_info[$parent][$prune_metric] < $prune_threshold)) {
                $pruned_edge = $this->xhprof_build_parent_child_key("__pruned__()", $child);

                if (isset($raw_data[$pruned_edge])) {
                    foreach ($metrics as $metric) {
                        $raw_data[$pruned_edge][$metric]+=$raw_data[$parent_child][$metric];
                    }
                }
                else {
                    $raw_data[$pruned_edge] = $raw_data[$parent_child];
                }

                unset($raw_data[$parent_child]);
            }
        }

        return $raw_data;
    }


    /**
    * Set one key in an array and return the array
    *
    * @author Kannan
    */
    function xhprof_array_set($arr, $k, $v)
    {
        $arr[$k] = $v;

        return $arr;
    }

    /**
    * Removes/unsets one key in an array and return the array
    *
    * @author Kannan
    */
    function xhprof_array_unset($arr, $k)
    {
        unset($arr[$k]);

        return $arr;
    }

    /**
    * Internal helper function used by various
    * xhprof_get_param* flavors for various
    * types of parameters.
    *
    * @param string   name of the URL query string param
    *
    * @author Kannan
    */
    function xhprof_get_param_helper($param)
    {
        $val = null;

        if (isset($_GET[$param])) {
            $val = $_GET[$param];
        }
        else if (isset($_POST[$param])) {
            $val = $_POST[$param];
        }

        return $val;
    }

    /**
    * Extracts value for string param $param from query
    * string. If param is not specified, return the
    * $default value.
    *
    * @author Kannan
    */
    function xhprof_get_string_param($param, $default = '')
    {
        $val = $this->xhprof_get_param_helper($param);

        if ($val === null) {
            return $default;
        }

        return $val;
    }

    /**
    * Extracts value for unsigned integer param $param from
    * query string. If param is not specified, return the
    * $default value.
    *
    * If value is not a valid unsigned integer, logs error
    * and returns null.
    *
    * @author Kannan
    */
    function xhprof_get_uint_param($param, $default = 0)
    {
        $val = $this->xhprof_get_param_helper($param);

        if ($val === null) {
            $val = $default;
        }

        $val = trim($val);

        if (ctype_digit($val)) {
            return $val;
        }

        $this->xhprof_error("$param is $val. It must be an unsigned integer.");

        return null;
    }


    /**
    * Extracts value for a float param $param from
    * query string. If param is not specified, return
    * the $default value.
    *
    * If value is not a valid unsigned integer, logs error
    * and returns null.
    *
    * @author Kannan
    */
    function xhprof_get_float_param($param, $default = 0)
    {
        $val = $this->xhprof_get_param_helper($param);

        if ($val === null) {
            $val = $default;
        }

        $val = trim($val);

        if (true) {
            return (float)$val;
        }

        $this->xhprof_error("$param is $val. It must be a float.");

        return null;
    }

    /**
    * Extracts value for a boolean param $param from
    * query string. If param is not specified, return
    * the $default value.
    *
    * If value is not a valid unsigned integer, logs error
    * and returns null.
    *
    * @author Kannan
    */
    function xhprof_get_bool_param($param, $default = false)
    {
        $val = $this->xhprof_get_param_helper($param);

        if ($val === null) {
            $val = $default;
        }

        $val = trim($val);

        switch (strtolower($val)) {
            case '0':
            case '1':
                $val = (bool)$val;
                break;
            case 'true':
            case 'on':
            case 'yes':
                $val = true;
                break;
            case 'false':
            case 'off':
            case 'no':
                $val = false;
                break;
            default:
                $this->xhprof_error("$param is $val. It must be a valid boolean string.");
                return null;
        }

        return $val;
    }

    /**
    * Initialize params from URL query string. The function
    * creates globals variables for each of the params
    * and if the URL query string doesn't specify a particular
    * param initializes them with the corresponding default
    * value specified in the input.
    *
    * @params array $params An array whose keys are the names
    *                       of URL params who value needs to
    *                       be retrieved from the URL query
    *                       string. PHP globals are created
    *                       with these names. The value is
    *                       itself an array with 2-elems (the
    *                       param type, and its default value).
    *                       If a param is not specified in the
    *                       query string the default value is
    *                       used.
    * @author Kannan
    */
    function xhprof_param_init($params) {
        foreach ($params as $k => $v) {
            switch ($v[0]) {
                case XHPROF_STRING_PARAM:
                    $p = $this->xhprof_get_param_helper($k, $v[1]);
                    break;
                case XHPROF_UINT_PARAM:
                    $p = $this->xhprof_get_uint_param($k, $v[1]);
                    break;
                case XHPROF_FLOAT_PARAM:
                    $p = $this->xhprof_get_float_param($k, $v[1]);
                    break;
                case XHPROF_BOOL_PARAM:
                    $p = $this->xhprof_get_bool_param($k, $v[1]);
                    break;
                default:
                    $this->xhprof_error("Invalid param type passed to $this->xhprof_param_init: ".$v[0]);
                    exit();
            }

            $this->params[$k] = $p;
        }

        return $this->params;
    }


    /**
    * Given a partial query string $q return matching function names in
    * specified XHProf run. This is used for the type ahead function
    * selector.
    *
    * @author Kannan
    */
    function xhprof_get_matching_functions($q, $xhprof_data)
    {
        $matches = array();

        foreach ($xhprof_data as $parent_child => $info) {
            list($parent, $child) = $this->xhprof_parse_parent_child($parent_child);

            if (stripos($parent, $q) !== false) {
                $matches[$parent] = 1;
            }

            if (stripos($child, $q) !== false) {
                $matches[$child] = 1;
            }
        }

        $res = array_keys($matches);

        asort($res);

        return ($res);
    }
}