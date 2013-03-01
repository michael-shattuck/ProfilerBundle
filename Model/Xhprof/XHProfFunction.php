<?php

namespace Clamidity\ProfilerBundle\Model\Xhprof;

use Clamidity\ProfilerBundle\Model\Xhprof\XHProfLib;
use Clamidity\ProfilerBundle\Model\Entity\Report;
use Clamidity\ProfilerBundle\Model\Entity\IndFunction;

/**
 *  Copyright (c) 2009 Facebook
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *
 * XHProf: A Hierarchical Profiler for PHP
 *
 *  @author Kannan Muthukkaruppan
 * 
 *  Modifications made by:
 *  @author Michael Shattuck <ms2474@gmail.com>
 */

class XHProfReport extends XHProfLib
{

    /**
     * Generate a Report entity based on the
     * parameters given.
     * 
     * @param array $url_params
     */
    function getReport($url_params)
    {
        $xhprof_data = $this->get_run($url_params['run'], $url_params['source'], $description);

        $this->init_metrics($xhprof_data, $url_params['symbol'], $url_params['sort'], false);

        $symbol_tab = $this->xhprof_compute_flat_info($xhprof_data, $this->totals);

        return $this->full_report(new Report(), $symbol_tab);
    }

    /*
     * Formats call counts for XHProf reports.
     *
     * Description:
     * Call counts in single-run reports are integer values.
     * However, call counts for aggregated reports can be
     * fractional. This function will print integer values
     * without decimal point, but with commas etc.
     *
     *   4000 ==> 4,000
     *
     * It'll round fractional values to decimal precision of 3
     *   4000.1212 ==> 4,000.121
     *   4000.0001 ==> 4,000
     *
     */
    function xhprof_count_format($num)
    {
        $num = round($num, 3);
        if (round($num) == $num) {
            return number_format($num);
        }
        else {
            return number_format($num, 3);
        }
    }

    function xhprof_percent_format($s, $precision = 1)
    {
        return sprintf('%.' . $precision . 'f%%', 100 * $s);
    }

    /**
     * Callback comparison operator (passed to usort() for sorting array of
     * tuples) that compares array elements based on the sort column
     * specified in $this->sort_col (global parameter).
     *
     * @author Kannan
     */
    function sort_cbk($a, $b)
    {
        if ($this->sort_col == "fn") {

            // case insensitive ascending sort for function names
            $left = strtoupper($a["fn"]);
            $right = strtoupper($b["fn"]);

            if ($left == $right)
                return 0;
            return ($left < $right) ? -1 : 1;
        } else {

            // descending sort for all others
            $left = $a[$this->sort_col];
            $right = $b[$this->sort_col];

            // if diff mode, sort by absolute value of regression/improvement
            if ($this->diff_mode) {
                $left = abs($left);
                $right = abs($right);
            }

            if ($left == $right)
                return 0;
            return ($left > $right) ? -1 : 1;
        }
    }

    /**
     * Computes percentage for a pair of values, and returns it
     * in string format.
     */
    function pct($a, $b)
    {
        if ($b == 0) {
            return "N/A";
        }
        else {
            $res = (round(($a * 1000 / $b)) / 10);
            return $res;
        }
    }

    /**
     * Prints a <td> element with a pecentage.
     */
    function return_pct($numer, $denom)
    {
        if ($denom == 0) {
            $pct = "N/A%";
        }
        else {
            $pct = $this->xhprof_percent_format($numer / abs($denom));
        }

        return $pct;
    }

    /**
     * Generates a tabular report for all functions. This is the top-level report.
     */
    function full_report(Report $report, $symbol_tab)
    {
        foreach ($this->metrics as $metric) {
            $function = 'set'.strtoupper($metric);
            $report->$function(number_format($this->totals[$metric]));
        }

        $flat_data = array();

        foreach ($symbol_tab as $symbol => $info) {
            $tmp = $info;
            $tmp["fn"] = $symbol;
            $flat_data[] = $tmp;
        }
        usort($flat_data, array($this, 'sort_cbk'));

        foreach ($flat_data as $func) {

            $indFunction = new IndFunction();
            $indFunction->setReport($report);

            foreach ($func as $key => $item) {
                $f = 'set'.ucfirst($key);
                $indFunction->$f($item);
            }

            foreach ($this->metrics as $metric) {
                $f = 'set'.ucfirst($metric).'p';
                $indFunction->$f($this->return_pct($func[$metric], $this->totals[$metric]));

                $f = 'setExcl_'.$metric.'p';
                $indFunction->$f($this->return_pct($func["excl_" . $metric], $this->totals[$metric]));
            }
        }

        return $report;
    }
}