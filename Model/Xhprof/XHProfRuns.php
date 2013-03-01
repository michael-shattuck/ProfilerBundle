<?php

namespace Clamidity\ProfilerBundle\Model\Xhprof;

use Clamidity\ProfilerBundle\Model\Xhprof\XHProfRunsInterface;

/**
 * XHProfRuns_Default is the default implementation of the
 * iXHProfRuns interface for saving/fetching XHProf runs.
 *
 * It stores/retrieves runs to/from a filesystem directory
 * specified by the "xhprof.output_dir" ini parameter.
 *
 * @author Kannan
 * 
 * Modifications made by:
 * @author Michael Shattuck <ms2474@gmail.com>
 */
class XHProfRuns implements XHProfRunsInterface
{
    /**
     *
     * @var string 
     */
    protected $dir;

    /**
     *
     * @param type $dir 
     */
    public function __construct($dir)
    {
        $this->vbar   = ' class="vbar"';
        $this->vwbar  = ' class="vwbar"';
        $this->vwlbar = ' class="vwlbar"';
        $this->vbbar  = ' class="vbbar"';
        $this->vrbar  = ' class="vrbar"';
        $this->vgbar  = ' class="vgbar"';

        /**
         * Our coding convention disallows relative paths in hrefs.
         * Get the base URL path from the SCRIPT_NAME.
         */
        $this->base_path = substr($_SERVER['PHP_SELF'], 0, -1);

        // default column to sort on -- wall time
        $this->sort_col = "wt";

        // default is "single run" report
        $this->diff_mode = false;

        // call count data present?
        $this->display_calls = true;

        // The following column headers are sortable
        $this->sortable_columns = array("fn" => 1,
            "ct" => 1,
            "wt" => 1,
            "excl_wt" => 1,
            "ut" => 1,
            "excl_ut" => 1,
            "st" => 1,
            "excl_st" => 1,
            "mu" => 1,
            "excl_mu" => 1,
            "pmu" => 1,
            "excl_pmu" => 1,
            "cpu" => 1,
            "excl_cpu" => 1,
            "samples" => 1,
            "excl_samples" => 1
        );

        // Textual descriptions for column headers in "single run" mode
        $this->descriptions = array(
            "fn" => "Function Name",
            "ct" => "Calls",
            "Calls%" => "Calls%",
            "wt" => "Incl. Wall Time<br>(microsec)",
            "IWall%" => "IWall%",
            "excl_wt" => "Excl. Wall Time<br>(microsec)",
            "EWall%" => "EWall%",
            "ut" => "Incl. User<br>(microsecs)",
            "IUser%" => "IUser%",
            "excl_ut" => "Excl. User<br>(microsec)",
            "EUser%" => "EUser%",
            "st" => "Incl. Sys <br>(microsec)",
            "ISys%" => "ISys%",
            "excl_st" => "Excl. Sys <br>(microsec)",
            "ESys%" => "ESys%",
            "cpu" => "Incl. CPU<br>(microsecs)",
            "ICpu%" => "ICpu%",
            "excl_cpu" => "Excl. CPU<br>(microsec)",
            "ECpu%" => "ECPU%",
            "mu" => "Incl.<br>MemUse<br>(bytes)",
            "IMUse%" => "IMemUse%",
            "excl_mu" => "Excl.<br>MemUse<br>(bytes)",
            "EMUse%" => "EMemUse%",
            "pmu" => "Incl.<br> PeakMemUse<br>(bytes)",
            "IPMUse%" => "IPeakMemUse%",
            "excl_pmu" => "Excl.<br>PeakMemUse<br>(bytes)",
            "EPMUse%" => "EPeakMemUse%",
            "samples" => "Incl. Samples",
            "ISamples%" => "ISamples%",
            "excl_samples" => "Excl. Samples",
            "ESamples%" => "ESamples%",
        );

        // Formatting Callback Functions...
        $this->format_cbk = array(
            "fn" => "",
            "ct" => "xhprof_count_format",
            "Calls%" => "xhprof_percent_format",
            "wt" => "number_format",
            "IWall%" => "xhprof_percent_format",
            "excl_wt" => "number_format",
            "EWall%" => "xhprof_percent_format",
            "ut" => "number_format",
            "IUser%" => "xhprof_percent_format",
            "excl_ut" => "number_format",
            "EUser%" => "xhprof_percent_format",
            "st" => "number_format",
            "ISys%" => "xhprof_percent_format",
            "excl_st" => "number_format",
            "ESys%" => "xhprof_percent_format",
            "cpu" => "number_format",
            "ICpu%" => "xhprof_percent_format",
            "excl_cpu" => "number_format",
            "ECpu%" => "xhprof_percent_format",
            "mu" => "number_format",
            "IMUse%" => "xhprof_percent_format",
            "excl_mu" => "number_format",
            "EMUse%" => "xhprof_percent_format",
            "pmu" => "number_format",
            "IPMUse%" => "xhprof_percent_format",
            "excl_pmu" => "number_format",
            "EPMUse%" => "xhprof_percent_format",
            "samples" => "number_format",
            "ISamples%" => "xhprof_percent_format",
            "excl_samples" => "number_format",
            "ESamples%" => "xhprof_percent_format",
        );


        // Textual descriptions for column headers in "diff" mode
        $this->diff_descriptions = array(
            "fn" => "Function Name",
            "ct" => "Calls Diff",
            "Calls%" => "Calls<br>Diff%",
            "wt" => "Incl. Wall<br>Diff<br>(microsec)",
            "IWall%" => "IWall<br> Diff%",
            "excl_wt" => "Excl. Wall<br>Diff<br>(microsec)",
            "EWall%" => "EWall<br>Diff%",
            "ut" => "Incl. User Diff<br>(microsec)",
            "IUser%" => "IUser<br>Diff%",
            "excl_ut" => "Excl. User<br>Diff<br>(microsec)",
            "EUser%" => "EUser<br>Diff%",
            "cpu" => "Incl. CPU Diff<br>(microsec)",
            "ICpu%" => "ICpu<br>Diff%",
            "excl_cpu" => "Excl. CPU<br>Diff<br>(microsec)",
            "ECpu%" => "ECpu<br>Diff%",
            "st" => "Incl. Sys Diff<br>(microsec)",
            "ISys%" => "ISys<br>Diff%",
            "excl_st" => "Excl. Sys Diff<br>(microsec)",
            "ESys%" => "ESys<br>Diff%",
            "mu" => "Incl.<br>MemUse<br>Diff<br>(bytes)",
            "IMUse%" => "IMemUse<br>Diff%",
            "excl_mu" => "Excl.<br>MemUse<br>Diff<br>(bytes)",
            "EMUse%" => "EMemUse<br>Diff%",
            "pmu" => "Incl.<br> PeakMemUse<br>Diff<br>(bytes)",
            "IPMUse%" => "IPeakMemUse<br>Diff%",
            "excl_pmu" => "Excl.<br>PeakMemUse<br>Diff<br>(bytes)",
            "EPMUse%" => "EPeakMemUse<br>Diff%",
            "samples" => "Incl. Samples Diff",
            "ISamples%" => "ISamples Diff%",
            "excl_samples" => "Excl. Samples Diff",
            "ESamples%" => "ESamples Diff%",
        );

        // columns that'll be displayed in a top-level report
        $this->stats = array();

        // columns that'll be displayed in a function's parent/child report
        $this->pc_stats = array();

        // Various total counts
        $this->totals = 0;
        $this->totals_1 = 0;
        $this->totals_2 = 0;

        /*
         * The subset of $this->possible_metrics that is present in the raw profile data.
         */
        $this->metrics = null;
        $this->dir     = $dir;
    }

    function xhprof_error($message)
    {
        error_log($message);
    }

    public function get_run($run_id, $type, &$run_desc) {
        $file_name = $this->file_name($run_id, $type);

        if (!file_exists($file_name)) {
            $this->xhprof_error("Could not find file $file_name");
            $run_desc = "Invalid Run Id = $run_id";

            return null;
        }

        $contents = file_get_contents($file_name);
        $run_desc = "XHProf Run (Namespace=$type)";

        return unserialize($contents);
    }

    public function save_run($xhprof_data, $type, $run_id = null)
    {
        $xhprof_data = serialize($xhprof_data);

        if ($run_id === null) {
            $run_id = $this->gen_run_id($type);
        }

        $run_name = $run_id;

        $file_name = $this->file_name($run_name, $type);
        $file      = fopen($file_name, 'w');

        if ($file) {
            fwrite($file, $xhprof_data);
            fclose($file);
        }
        else {
            $this->xhprof_error ("Could not open $file_name\n");
        }

        return $run_name;
    }

    protected function gen_run_id($type)
    {
        return uniqid();
    }

    protected function file_name($run_id, $type)
    {
        $file = "$run_id.$type";

        if (!empty($this->dir)) {
            $file = $this->dir . "/" . $file;
        }

        return $file;
    }

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

    /**
     * Initialize the metrics we'll display based on the information
     * in the raw data.
     *
     * @author Kannan
     */
    function init_metrics($xhprof_data, $rep_symbol, $sort, $diff_report = false)
    {
        $this->diff_mode = $diff_report;

        if (!empty($sort)) {
            if (array_key_exists($sort, $this->sortable_columns)) {
                $this->sort_col = $sort;
            }
            else {
                print("Invalid Sort Key $sort specified in URL");
            }
        }

        // For C++ profiler runs, walltime attribute isn't present.
        // In that case, use "samples" as the default sort column.
        if (!isset($xhprof_data["main()"]["wt"])) {

            if ($this->sort_col == "wt") {
                $this->sort_col = "samples";
            }

            // C++ profiler data doesn't have call counts.
            // ideally we should check to see if "ct" metric
            // is present for "main()". But currently "ct"
            // metric is artificially set to 1. So, relying
            // on absence of "wt" metric instead.
            $this->display_calls = false;
        }
        else {
            $this->display_calls = false;
//            $this->display_calls = true;
        }

        // parent/child report doesn't support exclusive times yet.
        // So, change sort hyperlinks to closest fit.
        if (!empty($rep_symbol)) {
            $this->sort_col = str_replace("excl_", "", $this->sort_col);
        }

        if ($this->display_calls) {
            $this->stats = array("fn", "ct", "Calls%");
        }
        else {
            $this->stats = array("fn");
        }

        $this->pc_stats = $this->stats;

        $possible_metrics = $this->xhprof_get_possible_metrics($xhprof_data);
        foreach ($possible_metrics as $metric => $desc) {
            if (isset($xhprof_data["main()"][$metric])) {
                $this->metrics[] = $metric;
                // flat (top-level reports): we can compute
                // exclusive metrics reports as well.
                $this->stats[] = $metric;
                $this->stats[] = "I" . $desc[0] . "%";
                $this->stats[] = "excl_" . $metric;
                $this->stats[] = "E" . $desc[0] . "%";

                // parent/child report for a function: we can
                // only breakdown inclusive times correctly.
                $this->pc_stats[] = $metric;
                $this->pc_stats[] = "I" . $desc[0] . "%";
            }
        }
    }
}
