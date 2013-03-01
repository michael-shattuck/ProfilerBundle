<?php

namespace Clamidity\ProfilerBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Clamidity\BaseBundle\Controller\BaseController;
use Clamidity\ProfilerBundle\Model\Xhprof\XHProfReport;
use Clamidity\ProfilerBundle\Model\Xhprof\XHProfCallGraph;

/**
 * Description of ProfilerController
 *
 * @author Michael Shattuck <ms2474@gmail.com>
 */
class ProfilerController extends BaseController
{
    /**
     *
     * @var XHProf
     */
    protected $xhprof;

    /**
     *
     * @var XHProfCallGraph
     */
    protected $callgraph;

    /**
     *
     * @param Request $request
     * @return Response 
     */
    public function indexAction(Request $request, $run)
    {
        $this->disableProfiler();

        $xhprof        = $this->getXhprof();
        $parameters    = $_GET;
        $query         = $this->getQuery($parameters);
        $squery        = $this->getSortedQuery($parameters);
        $params        = $this->getParameterArray();
        $params['run'] = $run;
        $all           = false;

        foreach ($parameters as $key => $value) {
            $params[$key] = $value;
        }

        if (isset($_GET['all']) && $_GET['all'] == 0) {
            $all = true;
        }

        $report = $xhprof->getReport($params);

        return $this->render('ClamidityProfilerBundle:Collector:index.html.twig', array(
            'url'    => $request->server->get('REQUEST_URI'),
            'params' => $params,
            'report' => $report,
            'run'    => $run,
            'query'  => $query,
            'squery' => $squery,
            'all'    => $all,
        ));
    }

    /**
     *
     * @param Request $request
     * @return Response 
     */
    public function functionAction(Request $request, $run, $function)
    {
        $this->disableProfiler();

        $xhprof           = $this->getXhprof();
        $parameters       = $_GET;
        $query            = $this->getQuery($parameters);
        $squery           = $this->getSortedQuery($parameters);
        $params           = $this->getParameterArray();
        $params['run']    = $run;
        $params['symbol'] = $function;

        foreach ($parameters as $key => $value) {
            $params[$key] = $value;
        }

        $report = $xhprof->getReport($params);

        return $this->render('ClamidityProfilerBundle:Collector:function.html.twig', array(
            'url'      => $request->server->get('REQUEST_URI'),
            'params'   => $params,
            'report'   => $report,
            'run'      => $run,
            'query'    => $query,
            'squery'   => $squery,
            'function' => $function,
        ));
    }

    public function callgraphAction()
    {
        ini_set('max_execution_time', 100);

        $xhprof     = $this->getCallGraph();
        $parameters = $_GET;
        $params     = $this->getCallGraphArray();

        foreach ($parameters as $key => $value) {
            $params[$key] = $value;
        }

        if ($params['threshold'] < 0) {
            $params['threshold'] = 0;
        }
        else if ($params['threshold'] > 1) {
            $params['threshold'] = 1;
        }

//        if (!array_key_exists($type, $xhprof_legal_image_types)) {
//            $type = $paramsRaw['type'][1]; // default image type.
//        }

        if (!empty($params['run'])) {
            $content = $xhprof->xhprof_render_image($params);
        }
        else {
            $content = $xhprof->xhprof_render_diff_image($params);
        }

        echo $content;
        die;
    }

    /**
     * Function for retrieving parameters
     *
     * @return array
     */
    protected function getParameterArray()
    {
        $params = array(
            'run'    => '',
            'wts'    => '',
            'symbol' => '',
            'sort'   => 'wt',
            'run1'   => '',
            'run2'   => '',
            'source' => $this->container->getParameter('clamidity_profiler.file_extension'),
            'all'    => 100,
        );

        return $params;
    }

    protected function getCallGraphArray()
    {
        return array(
            'run'       => '',
            'source'    => $this->container->getParameter('clamidity_profiler.file_extension'),
            'func'      => '',
            'type'      => 'png',
            'threshold' => 0.01,
            'critical'  => true,
            'run1'      => '',
            'run2'      => ''
        );
    }

    /**
     * Function for disabling profiler 
     */
    protected function disableProfiler()
    {
        $this->get('profiler')->disable();
    }

    /**
     *
     * @return XHProf
     */
    protected function getXhprof()
    {
        if (!isset($this->xhprof)) {
            $this->xhprof = new XHProfReport($this->getParameter('clamidity_profiler.location_reports'));
        }

        return $this->xhprof;
    }

    /**
     *
     * @return XHProfCallGraph
     */
    protected function getCallGraph()
    {
        if (!isset($this->callgraph)) {
            $this->callgraph = new XHProfCallGraph($this->getParameter('clamidity_profiler.location_reports'));
        }

        return $this->callgraph;
    }

    protected function getQuery($params)
    {
        $query = '?';

        $i = 1;
        foreach ($params as $key => $param) {
            
            if ($i === 1) {
                $query .= $key."=".$param;
            }
            else {
                $query .= "&".$key."=".$param;
            }

            $i++;
        }

        return $query;
    }

    protected function getSortedQuery($params)
    {
        $query = '';

        $i = 1;
        foreach ($params as $key => $param) {
            
            if ($key != 'sort') {
                $query .= "&".$key."=".$param;
            }

            $i++;
        }

        return $query;
    }
}
