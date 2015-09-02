<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\HumanitarianResponse;

use Piwik\DataTable;
use Piwik\DataTable\Row;

/**
 * API for plugin HumanitarianResponse
 *
 * @method static \Piwik\Plugins\HumanitarianResponse\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
  /**
   * Another example method that returns a data table.
   * @param int    $idSite
   * @param string $period
   * @param string $date
   * @param bool|string $segment
   * @return DataTable
   */
  public function getSpaceSummary($idSite, $period, $date, $space_id, $space_type = 'operation')
  {

    $table = new DataTable();

    $params = array(
      'idSite' => $idSite,
      'period' => $period,
      'date'   => $date,
      'segment' => 'customVariablePageName1==spaces;customVariablePageValue1=@'.$space_id,
    );

    $data = \Piwik\API\Request::processRequest('API.get', $params);


    $tarray = $this->getTypes($params);

    $data->getRowFromId(0)->addColumns($tarray);

    $table->addRow($data->getRowFromId(0));

    // Get country ISO2 code
    $hr_url = 'https://www.humanitarianresponse.info/api/v1.0/'.$space_type.'s/'.$space_id;
    if ($space_raw = @file_get_contents($hr_url)) {
      $space = json_decode($space_raw);
      $table->getRowFromId(0)->addColumn('label', $space->data[0]->label);
      if (isset($space->data[0]->country)) {
        $iso2 = $space->data[0]->country->pcode;
        $cparams = $params;
        $cparams['segment'] = $params['segment'] . ';countryCode=='.$iso2;
        $cdata = \Piwik\API\Request::processRequest('API.get', $cparams);
        $cdata->getRowFromId(0)->addColumn('label', $space->data[0]->label.' - in country');
        $ctarray = $this->getTypes($cparams);
        $cdata->getRowFromId(0)->addColumns($ctarray);
        $table->addRow($cdata->getRowFromId(0));
      }
    }

    return $table;
  }

  /**
   * Another example method that returns a data table.
   * @param int    $idSite
   * @param string $period
   * @param string $date
   * @param bool|string $segment
   * @return DataTable
   */
  public function getClusterSummary($idSite, $period, $date, $cluster_id, $cluster_type = 'bundle')
  {

    $table = new DataTable();

    $params = array(
      'idSite' => $idSite,
      'period' => $period,
      'date'   => $date,
      'segment' => 'customVariablePageName2==' . $cluster_type . 's;customVariablePageValue2=@' . $cluster_id,
    );

    $data = \Piwik\API\Request::processRequest('API.get', $params);


    $tarray = $this->getTypes($params);

    $data->getRowFromId(0)->addColumns($tarray);

    $table->addRow($data->getRowFromId(0));

    // Get country ISO2 code
    $hr_url = 'https://www.humanitarianresponse.info/api/v1.0/' . $cluster_type . 's/' . $cluster_id;
    if ($space_raw = @file_get_contents($hr_url)) {
      $space = json_decode($space_raw);
      $table->getRowFromId(0)->addColumn('label', $space->data[0]->label);
      if (isset($space->data[0]->operation->country)) {
        $iso2 = $space->data[0]->operation->country->pcode;
        $cparams = $params;
        $cparams['segment'] = $params['segment'] . ';countryCode=='.$iso2;
        $cdata = \Piwik\API\Request::processRequest('API.get', $cparams);
        $cdata->getRowFromId(0)->addColumn('label', $space->data[0]->label.' - in country');
        $ctarray = $this->getTypes($cparams);
        $cdata->getRowFromId(0)->addColumns($ctarray);
        $table->addRow($cdata->getRowFromId(0));
      }
    }

    return $table;
  }

    private function getTypes($params) {
      $tarray = array();
      $types = array('hr_document',
        'hr_infographic',
        'hr_dataset',
        'hr_assessment');

      foreach ($types as $type) {
        $tparams = $params;
        $tparams['segment'] = $params['segment'] . ';customVariablePageName3==type;customVariablePageValue3=='.$type;
        $tdata = \Piwik\API\Request::processRequest('API.get', $tparams);
        $tarray['nb_downloads_'.$type] = $tdata[0]['nb_downloads'];
      }
      return $tarray;
    }


    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getSummaryStats($idSite, $period, $date, $segment = false)
    {
        $table = new DataTable();
        $operations_url = 'http://www.humanitarianresponse.info/api/v1.0/operations/?fields=id';
        if ($operations_raw = @file_get_contents($operations_url)) {
          $operations_json = json_decode($operations_raw);
          foreach ($operations_json->data as $op_id) {
            $temp = $this->getSpaceSummary($idSite, $period, $date, $op_id->id, 'operation');
            $table->addRow($temp->getRowFromId(0));
          }
        }

        $spaces_url = 'http://www.humanitarianresponse.info/api/v1.0/spaces/?fields=id';
        if ($spaces_raw = @file_get_contents($spaces_url)) {
          $spaces_json = json_decode($spaces_raw);
          foreach ($spaces_json->data as $space_id) {
            $temp = $this->getSpaceSummary($idSite, $period, $date, $space_id->id, 'space');
            $table->addRow($temp->getRowFromId(0));
          }
        }

        return $table;
    }

}
