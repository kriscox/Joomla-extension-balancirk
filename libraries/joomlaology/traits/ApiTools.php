<?php

namespace Joomlaology\Traits;

use stdClass;

// Joomlaology common methods trait
trait ApiTools
{
  /**
   * emitJson
   *
   * @author	Joe Hacobian
   * @since	v0.0.1
   * @access	public
   * @param	mixed	$inputArr
   * @return	void Writes Response & closes connection
   */
  public function emitJson($inputArr)
  {
    /* Thanks go out to Nicholas K. Dionysopoulos from Akeeba
    for coming up with emitting JSON from Joomla this way.
    */
    header('Content-type:application/json;charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    @ob_end_clean();
    echo (json_encode($inputArr));
    flush();
    $this->app->close();
    return;
  }

  /**
   * prepErrMsgExmplPldFmt
   *
   * @author	Joe Hacobian
   * @since	v0.0.1
   * @access	public
   * @param	string	$pldString
   * @param string  $pldMode --> encB64AndUri OR onlyEncUri OR literal
   * @return string Returns payload string wrapped inside encoding functions according to the payload mode given in $pldMode
   */
  public function prepErrMsgExPldFmt($pldString, $pldMode)
  {
    if (gettype($pldString) == 'string' && gettype($pldMode) == 'string')
    {
      switch ($pldMode)
      {
        case 'literal':
          $formattedPayloadEncodingExample = $pldString;
          break;
        case 'onlyEncUri':
          $formattedPayloadEncodingExample = "encodeURIComponent( '$pldString' )";
          break;
        case 'encB64AndUri':
          $formattedPayloadEncodingExample = "encodeURIComponent( btoa( '$pldString' ) )";
          break;
        default:
          $formattedPayloadEncodingExample = $pldString;
          break;
      }
      return $formattedPayloadEncodingExample;
    }
    if (!isset($formattedPayloadEncodingExample))
    {
      if ($pldString !== null && $pldMode !== null)
      {
        return "Payload & Payload mode NOT supplied.";
      }
    }
  }

  /*
  ############################################################# checkIfJUserExists #############################################################
  */
  /**
   * checkIfJUserExists
   *
   * @author	Joe Hacobian
   * @since	v0.0.1
   * @access	public
   * @param	string	$userName --> username to check, must be email address
   * @return	 $existingJUser => [ userExists => true | false, userId, userDisplayName userName, userEmail, ]
   */
  protected function checkIfJUserExists($userName)
  {
    $db = $this->getDbo();
    // Run Joomla user existence check
    $query = $db->getQuery(true)
      ->select($db->quoteName(['U.id', 'U.name', 'U.username', 'U.email'], ['usrId', 'usrDisplayName', 'usrName', 'usrEmail']))
      ->from($db->quoteName('#__users', 'U'))
      ->where($db->quoteName('U.email') . ' = :suppliedUserEmail')
      ->setLimit('1')
      ->bind(':suppliedUserEmail', $userName);
    $db->setQuery($query);
    $existenceCheckDbResults = $db->loadAssocList('usrEmail');

    // Prepare response object.
    // Log::add(print_r($existenceCheckDbResults, true), Log::INFO);
    if ($existenceCheckDbResults["$userName"]['usrId'] && $existenceCheckDbResults["$userName"]['usrName'])
    {
      $existingJUser = new stdClass();
      $existingJUser->userId          = $existenceCheckDbResults["$userName"]['usrId'];
      $existingJUser->userDisplayName = $existenceCheckDbResults["$userName"]['usrDisplayName'];
      $existingJUser->userName        = $existenceCheckDbResults["$userName"]['usrName'];
      $existingJUser->userEmail       = $existenceCheckDbResults["$userName"]['usrEmail'];
      $existingJUser->userExists      = true;
    }
    else
    {
      $existingJUser = new stdClass();
      $existingJUser->userExists      = false;
    }
    return $existingJUser;
  }

  /*
  ############################################################# prepareSqlFieldsArray #############################################################
  */
  /**
   * prepareSqlFieldsArray
   *
   * @author	Joe Hacobian
   * @since	v0.0.1
   * @access	protected
   * @param	object 	$data - an object of key/value pairs corresponding to column/value pairs to be inserted/updated into/on the database record.
   * @param array   $data - an associative array of key/value pairs corresponding to column/value pairs to be inserted/updated into/on the database record.
   * @return	 array => SUCCESS: array('success' => true, $fields), FAILURE: array('success' => false)
   */
  protected function prepareSqlFieldsArray($data)
  {
    $db = $this->getDbo();
    $fields = array();

    if (gettype($data) == 'object')
    {
      // Using get_object_vars() to make $data (object) iterable so that count() can see its length.
      if (count(get_object_vars($data)) > 0)
      {
        foreach ($data as $key => $value)
        {
          if (gettype($value) == 'boolean')
          {
            $valAsBool = ((bool) $value == true) ? 1 : 0;
            array_push($fields, $db->quoteName($key) . " = '{$valAsBool}'");

            // Log::add("File: " . __FILE__ . "\n\n" . $db->quoteName($key) . " = '{$valAsBool}'" . " \n", Log::INFO);
          }
          else
          {
            array_push($fields, $db->quoteName($key) . " = '{$value}'");

            // Log::add("File: " . __FILE__ . "\n\n" . $db->quoteName($key) . " = '{$value}'" . " \n", Log::INFO);
          }
        }
        return array('success' => true, 'fields' => $fields);
      }
      else
      {
        return array('success' => false);
      }
    }
    elseif (gettype($data) == 'array')
    {
      if (count($data) > 0)
      {
        foreach ($data as $key => $value)
        {
          if (gettype($value) == 'boolean')
          {
            $valAsBool = ((bool) $value == true) ? 1 : 0;
            array_push($fields, $db->quoteName($key) . " = '{$valAsBool}'");

            // Log::add("File: " . __FILE__ . "\n\n" . $db->quoteName($key) . " = '{$valAsBool}'" . " \n", Log::INFO);
          }
          else
          {
            array_push($fields, $db->quoteName($key) . " = '{$value}'");

            // Log::add("File: " . __FILE__ . "\n\n" . $db->quoteName($key) . " = '{$value}'" . " \n", Log::INFO);
          }
        }
        return array('success' => true, 'fields' => $fields);
      }
      else
      {
        return array('success' => false);
      }
    }
  }
}
