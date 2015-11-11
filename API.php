<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\HumanitarianResponse;

use Piwik\Archive;
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
   * @param $space_id
   * @param string $space_type
   * @return array
   */
  protected function prepareSettingsForSpaceSummary($space_id, $space_type = 'operation', $label = '')
  {
    return  array(
      'name' => 'customVariablePageName1',
      'type' => $space_type . 's',
      'context' => 'spaces',
      'value' => 'customVariablePageValue1',
      'id' => $space_id,
      'label' => $label,
    );
  }

  /**
   * Display space summary stats.
   *
   * @param int $idSite
   * @param string $period
   * @param string $date
   * @param $space_id
   * @param string $space_type
   * @return \Piwik\DataTable
   */
  public function getSpaceSummary($idSite, $period, $date, $space_id, $space_type = 'operation')
  {
    $settings = $this->prepareSettingsForSpaceSummary($space_id, $space_type);
    return $this->processSummary($idSite, $period, $date, $settings);
  }

  /**
   * Display cluster summary stats.
   *
   * @param int $idSite
   * @param string $period
   * @param string $date
   * @param $cluster_id
   * @param string $cluster_type
   * @return \Piwik\DataTable
   */
  public function getClusterSummary($idSite, $period, $date, $cluster_id, $cluster_type = 'bundle')
  {
    $settings = array(
      'name' => 'customVariablePageName2',
      'type' => $cluster_type . 's',
      'context' => 'bundles',
      'value' => 'customVariablePageValue2',
      'id' => $cluster_id,
    );
    return $this->processSummary($idSite, $period, $date, $settings);
  }

  /**
   * Helper function to display operation/cluster summary stats.
   *
   * @param $idSite
   * @param $period
   * @param $date
   * @param $settings
   *
   * @return \Piwik\DataTable
   */
  protected function processSummary($idSite, $period, $date, $settings, $addCountry = true)
  {
    $table = new DataTable();
    $segment = $settings['name'] . '==' . $settings['context'] . ';' . $settings['value'] . '=@' . $settings['id'];

    // Build an archive to retrieve the information from the records.
    $archive = Archive::build($idSite, $period, $date, $segment);
    /* @var \Piwik\DataTable $tdata */
    $data = $archive->getDataTableFromNumeric(array('nb_visits', 'Actions_nb_downloads'));

    // Add the downloads by type.
    $downloads = $this->attachDownloadByType($idSite, $period, $date, $segment);

    $data->getRowFromId(0)->addColumns($downloads);
    $table->addRow($data->getRowFromId(0));

    // Calculate the right label and load the content.
    if (empty($settings['label'])) {
      $base_url = 'https://www.humanitarianresponse.info/api/v1.0/';
      if ($content_raw = @file_get_contents($base_url . $settings['type'] . '/' . $settings['id'])) {
        $hrContent = json_decode($content_raw);
        $settings['label'] = $hrContent->data[0]->label;
      }
    }

    // Add the label to the row.
    $table->getRowFromId(0)->addColumn('label', $settings['label']);

    // Only add country stats on demand.
    if ($addCountry && !empty($hrContent)) {
      $dataByCountry = $this->attachStatsbyCountry($hrContent, $idSite, $period, $date, $segment);
      if ($row = $dataByCountry->getRowFromId(0)) {
        $table->addRow($row);
      }
    }

    return $table;
  }

  /**
   * Attach the downloads by type.
   *
   * @param $params
   *
   * @return array
   */
  private function attachDownloadByType($idSite, $period, $date, $segment)
  {
    $return = array();
    $types = array(
      'hr_document',
      'hr_infographic',
      'hr_dataset',
      'hr_assessment'
    );

    foreach ($types as $type) {
      $segmentByType = $segment . ';customVariablePageName3==type;customVariablePageValue3==' . $type;
      $archive = Archive::build($idSite, $period, $date, $segmentByType);
      /* @var \Piwik\DataTable $tdata */
      $data = $archive->getDataTableFromNumeric(array('Actions_nb_downloads'));
      $return['nb_downloads_'. $type] = $data[0]['Actions_nb_downloads'];
    }
    return $return;
  }

  /**
   * Attach the country information on demand.
   *
   * @param $content
   * @param $idSite
   * @param $period
   * @param $date
   * @param $segment
   * @return \Piwik\DataTable|\Piwik\DataTable\Map
   */
  private function attachStatsbyCountry($content, $idSite, $period, $date, $segment)
  {
    $data = new DataTable();

    if ($iso2 = $this->getIso2Country($content->data[0])) {
      $segmentByCountry = $segment . ';countryCode==' . $iso2;
      $archiveByCountry = Archive::build($idSite, $period, $date, $segmentByCountry);
      $data = $archiveByCountry->getDataTableFromNumeric(array('nb_visits', 'Actions_nb_downloads'));
      $data->getRowFromId(0)->addColumn('label', $content->data[0]->label.' - in country');
      $downloads = $this->attachDownloadByType($idSite, $period, $date, $segmentByCountry);
      $data->getRowFromId(0)->addColumns($downloads);
    }

    return $data;
  }

  /**
   * Retrieves the country iso2 from the space.
   *
   * @param $data
   * @return bool
   */
  protected function getIso2Country($data)
  {
    if (isset($data->country)) {
      return $data->country->pcode;
    }

    if (isset($data->operation[0]->country)) {
      return $data->operation[0]->country->pcode;
    }

    return FALSE;
  }

  /**
   * Another example method that returns a data table.
   *
   * @param int    $idSite
   * @param string $period
   * @param string $date
   * @param bool|string $segment
   * @return DataTable
   */
  public function getSummaryStats($idSite, $period, $date, $segment = false)
  {
    $table = new DataTable();

    $types = array(
      'operations',
      'spaces'
    );
    foreach ($types as $type) {
      $this->preProcessSummaryStats($table, $idSite, $period, $date, $type);
    }

    return $table;
  }

  /**
   * Accumulate items on the stats datatable.
   *
   * @param \Piwik\DataTable $table
   * @param $idSite
   * @param $period
   * @param $date
   * @param $type
   * @throws \Exception
   */
  protected function preProcessSummaryStats(\Piwik\DataTable &$table, $idSite, $period, $date, $type)
  {
    $base_url = "https://www.humanitarianresponse.info/api/v1.0/$type/?fields=id,label";
    if ($data_json = @file_get_contents($base_url)) {
      $data = json_decode($data_json);
      foreach ($data->data as $item) {
        $settings = $this->prepareSettingsForSpaceSummary($item->id, $type, $item->label);
        $datatable = $this->processSummary($idSite, $period, $date, $settings, false);
        $table->addDataTable($datatable);
      }
    }
  }

}
