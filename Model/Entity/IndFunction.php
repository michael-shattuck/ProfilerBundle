<?php

namespace Clamidity\ProfilerBundle\Model\Entity;

use Clamidity\ProfilerBundle\Model\Entity\Report;

/**
 * Description of Function
 *
 * @author Michael Shattuck <ms2474@gmail.com>
 */
class IndFunction
{
    protected $fn;

    protected $report;

    protected $wt;

    protected $wtp;

    protected $excl_wt;

    protected $excl_wtp;

    protected $cpu;

    protected $cpup;

    protected $excl_cpu;

    protected $excl_cpup;

    protected $mu;

    protected $mup;

    protected $excl_mu;

    protected $excl_mup;
    
    protected $pmu;

    protected $pmup;
    
    protected $excl_pmu;

    protected $excl_pmup;

    public function __construct()
    {
        
    }

    public function getFn()
    {
        return $this->fn;
    }
    
    public function setFn($fn)
    {
        $this->fn = $fn;
    }

    public function getReport()
    {
        return $this->report;
    }

    public function setReport(Report $report)
    {
        $this->report = $report;
        $report->addIndFunction($this);
    }

    public function getWt()
    {
        return $this->wt;
    }
    
    public function setWt($wt)
    {
        $this->wt = $wt;
    }

    public function getWtp()
    {
        return $this->wtp;
    }
    
    public function setWtp($wtp)
    {
        $this->wtp = $wtp;
    }

    public function getExcl_wt()
    {
        return $this->excl_wt;
    }
    
    public function setExcl_wt($excl_wt)
    {
        $this->excl_wt = $excl_wt;
    }

    public function getExcl_wtp()
    {
        return $this->excl_wtp;
    }
    
    public function setExcl_wtp($excl_wtp)
    {
        $this->excl_wtp = $excl_wtp;
    }

    public function getCpu()
    {
        return $this->cpu;
    }
    
    public function setCpu($cpu)
    {
        $this->cpu = $cpu;
    }

    public function getCpup()
    {
        return $this->cpup;
    }
    
    public function setCpup($cpup)
    {
        $this->cpup = $cpup;
    }

    public function getExcl_cpu()
    {
        return $this->excl_cpu;
    }
    
    public function setExcl_cpu($excl_cpu)
    {
        $this->excl_cpu = $excl_cpu;
    }

    public function getExcl_cpup()
    {
        return $this->excl_cpup;
    }
    
    public function setExcl_cpup($excl_cpup)
    {
        $this->excl_cpup = $excl_cpup;
    }

    public function getMu()
    {
        return $this->mu;
    }
    
    public function setMu($mu)
    {
        $this->mu = $mu;
    }

    public function getMup()
    {
        return $this->mup;
    }
    
    public function setMup($mup)
    {
        $this->mup = $mup;
    }

    public function getExcl_mu()
    {
        return $this->excl_mu;
    }
    
    public function setExcl_mu($excl_mu)
    {
        $this->excl_mu = $excl_mu;
    }

    public function getExcl_mup()
    {
        return $this->excl_mup;
    }
    
    public function setExcl_mup($excl_mup)
    {
        $this->excl_mup = $excl_mup;
    }

    public function getPmu()
    {
        return $this->pmu;
    }
    
    public function setPmu($pmu)
    {
        $this->pmu = $pmu;
    }

    public function getPmup()
    {
        return $this->pmup;
    }
    
    public function setPmup($pmup)
    {
        $this->pmup = $pmup;
    }

    public function getExcl_pmu()
    {
        return $this->excl_pmu;
    }
    
    public function setExcl_pmu($excl_pmu)
    {
        $this->excl_pmu = $excl_pmu;
    }

    public function getExcl_pmup()
    {
        return $this->excl_pmup;
    }
    
    public function setExcl_pmup($excl_pmup)
    {
        $this->excl_pmup = $excl_pmup;
    }
}
