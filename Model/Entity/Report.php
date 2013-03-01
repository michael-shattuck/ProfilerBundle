<?php

namespace Clamidity\ProfilerBundle\Model\Entity;

use Clamidity\ProfilerBundle\Model\Entity\IndFunction;

class Report
{
    protected $name;

    protected $date;

    protected $url;

    protected $indFunctions;

    protected $wt;

    protected $cpu;
    
    protected $mu;

    protected $pmu;

    public function __construct()
    {
        $this->indFunctions = array();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getDate()
    {
        return $this->date;
    }
    
    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getIndFunctions()
    {
        return $this->indFunctions;
    }

    public function addIndFunction(IndFunction $indFunction)
    {
        $this->indFunctions[] = $indFunction;
    }

    public function getWT()
    {
        return $this->wt;
    }
    
    public function setWT($wt)
    {
        $this->wt = $wt;
    }

    public function getCPU()
    {
        return $this->cpu;
    }
    
    public function setCPU($cpu)
    {
        $this->cpu = $cpu;
    }

    public function getMU()
    {
        return $this->mu;
    }
    
    public function setMU($mu)
    {
        $this->mu = $mu;
    }

    public function getPMU()
    {
        return $this->pmu;
    }
    
    public function setPMU($pmu)
    {
        $this->pmu = $pmu;
    }
}