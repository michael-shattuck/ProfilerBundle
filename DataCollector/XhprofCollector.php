<?php

namespace Clamidity\ProfilerBundle\DataCollector;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Clamidity\ProfilerBundle\Model\Xhprof\XHProfLib;

/**
 * XhprofDataCollector.
 *
 * @author Jonas Wouters <hello@jonaswouters.be>
 */
class XhprofCollector extends DataCollector
{
    protected $container;
    protected $logger;
    protected $runId;
    protected $profiling = false;
    protected $xhprof;

    public function __construct(ContainerInterface $container, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if ($this->functionCheck()) {

            if (!$this->runId) {
                $this->stopProfiling();
            }

            $this->data = array(
                'xhprof' => $this->runId,
                'source' => $this->container->getParameter('clamidity_profiler.file_extension'),
            );
        }
    }

    public function startProfiling()
    {
        if ($this->functionCheck()) {

            if (PHP_SAPI == 'cli') {
                $_SERVER['REMOTE_ADDR'] = null;
                $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
            }

            $this->profiling = true;
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
            xhprof_enable(XHPROF_FLAGS_NO_BUILTINS);

            if ($this->logger) {
                $this->logger->debug('Enabled XHProf');
            }
        }
    }

    public function stopProfiling()
    {
        
        if ($this->functionCheck()) {

            global $_xhprof;

            if (!$this->profiling) {
                return;
            }

            $this->profiling = false;
            $xhprof_data     = xhprof_disable();

            if ($this->logger) {
                $this->logger->debug('Disabled XHProf');
            }

            $xhprof_runs = new XHProfLib($this->container->getParameter('clamidity_profiler.location_reports'));
            $extension   = $this->container->getParameter('clamidity_profiler.file_extension');
            $this->runId = $xhprof_runs->save_run($xhprof_data, $extension, $this->getFileName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        if ($this->functionCheck()) {
            return 'xhprof';
        }
    }

    /**
     * Gets the run id.
     *
     * @return integer The run id
     */
    public function getXhprof()
    {
        if ($this->functionCheck()) {
            return $this->data['xhprof'];
        }
    }

    /**
     * Gets the XHProf url.
     *
     * @return integer The XHProf url
     */
    public function getXhprofUrl()
    {
        if ($this->functionCheck()) {
            return $_SERVER['SCRIPT_NAME'] . '/_memory_profiler/' . $this->data['xhprof'] . '/';
        }
    }

    protected function functionCheck()
    {
        return function_exists('xhprof_enable');
    }

    protected function getFileName()
    {
        $uri  = 'url:_';
        $uri .= $this->container->get('request')->server->get('REQUEST_URI');
        $uri  = str_replace('/', '_', $uri);
        $uri  = str_replace('_app_dev.php', 'app_dev.php', $uri);

        if (!$this->container->getParameter('clamidity_profiler.overwrite')) {
            $uri .= '|date:_'.date('d-m-Y').'|time:_'.date('g:i:sa');
        }

        return $uri;
    }
}
