<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik
 * @package Piwik
 */

namespace Piwik\Archive;

use Exception;
use Piwik\Period;
use Piwik\Segment;

class Parameters
{
    /**
     * The list of site IDs to query archive data for.
     *
     * @var array
     */
    private $idSites = array();

    /**
     * The list of Period's to query archive data for.
     *
     * @var Period[]
     */
    private $periods = array();

    /**
     * Segment applied to the visits set.
     *
     * @var Segment
     */
    private $segment;

    public function getSegment()
    {
        return $this->segment;
    }

    public function __construct($idSites, $periods, Segment $segment)
    {
        $this->idSites = $this->getAsNonEmptyArray($idSites, 'idSites');
        $this->periods = $this->getAsNonEmptyArray($periods, 'periods');
        $this->segment = $segment;
    }

    public function getPeriods()
    {
        return $this->periods;
    }

    public function getIdSites()
    {
        return $this->idSites;
    }

    private function getAsNonEmptyArray($array, $paramName)
    {
        if (!is_array($array)) {
            $array = array($array);
        }

        if (empty($array)) {
            throw new Exception("Archive::__construct: \$$paramName is empty.");
        }

        return $array;
    }
}

